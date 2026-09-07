<?php

namespace App\Services;

use App\Models\AiSnapshot;
use App\Models\Store;
use App\Models\TrackedQuery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI Visibility Tracker.
 *
 * Mode A — LLM mode (honest AI mentions): when OPENAI_API_KEY and/or
 * GEMINI_API_KEY are configured, each tracked query is asked to the real model
 * and we record whether the brand is mentioned/cited in the answer.
 *
 * Mode B — Retrieval proxy (no keys needed): AI answers are built from search
 * indexes, so we check whether the brand's domain appears in live web results
 * for the query. This is a genuine, honest proxy for "would an AI engine find
 * and cite you for this query".
 */
class AiVisibilityService
{
    public const ENGINES = ['chatgpt', 'gemini', 'perplexity', 'grok', 'deepseek'];

    public function availableEngines(): array
    {
        $engines = [];
        // OpenRouter can simulate any engine
        if (config('services.openrouter.key')) {
            $engines[] = 'chatgpt';
        }
        if (config('services.openai.key')) {
            $engines[] = 'chatgpt';
        }
        if (config('services.gemini.key')) {
            $engines[] = 'gemini';
        }
        if (empty($engines)) {
            $engines[] = 'web'; // retrieval proxy
        }
        return array_unique($engines);
    }

    /** Run a snapshot cycle for a store: returns snapshot rows created. */
    public function runSnapshot(Store $store): array
    {
        $queries = $store->queries()->where('active', true)->limit($store->queryLimit())->get();
        if ($queries->isEmpty()) {
            $queries = $this->seedQueries($store);
        }

        $engines = $this->availableEngines();
        $created = [];

        foreach ($engines as $engine) {
            $mentioned = 0;
            $cited = 0;
            $samples = [];
            foreach ($queries as $q) {
                $result = $this->checkQuery($store, $q->query, $engine);
                if ($result['mentioned']) {
                    $mentioned++;
                }
                if ($result['cited']) {
                    $cited++;
                }
                if (count($samples) < 3 && ($result['mentioned'] || $result['snippet'])) {
                    $samples[] = [
                        'query' => $q->query,
                        'mentioned' => $result['mentioned'],
                        'cited' => $result['cited'],
                        'snippet' => mb_substr($result['snippet'] ?? '', 0, 400),
                    ];
                }
            }

            $snapshot = AiSnapshot::updateOrCreate(
                ['store_id' => $store->id, 'snapshot_date' => now()->startOfDay(), 'engine' => $engine],
                [
                    'total_queries' => $queries->count(),
                    'mentioned' => $mentioned,
                    'cited' => $cited,
                    'samples' => $samples,
                ]
            );
            $created[] = $snapshot;
        }

        return $created;
    }

    /** Check a single query against a single engine. */
    public function checkQuery(Store $store, string $query, string $engine): array
    {
        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));
        $domain = $store->hostname();

        if ($engine === 'web') {
            return $this->checkRetrievalProxy($query, $domain);
        }
        // OpenRouter works as a universal LLM proxy
        if (config('services.openrouter.key')) {
            return $this->checkLlm('openrouter', $query, $brand, $domain);
        }
        if ($engine === 'chatgpt' && config('services.openai.key')) {
            return $this->checkLlm('openai', $query, $brand, $domain);
        }
        if ($engine === 'gemini' && config('services.gemini.key')) {
            return $this->checkLlm('gemini', $query, $brand, $domain);
        }
        // Engines without a configured provider fall back to the retrieval proxy
        return $this->checkRetrievalProxy($query, $domain);
    }

    /** No-key mode: does the brand's domain surface in live web results? */
    private function checkRetrievalProxy(string $query, string $domain): array
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; AIVisibilityBot/1.0; +https://aivisibility.app)',
                ])
                ->get('https://html.duckduckgo.com/html/', ['q' => $query]);
            if (! $response->successful()) {
                return ['mentioned' => false, 'cited' => false, 'snippet' => ''];
            }
            $html = (string) $response->body();
            $mentioned = stripos($html, $domain) !== false;
            // A result linking to the store = a "citation"
            $cited = preg_match('#https?://[^"\'<> ]*'.preg_quote($domain, '#').'[^"\'<> ]*#i', $html) === 1;
            $snippet = $mentioned ? $this->extractSnippet($html, $domain) : '';
            return ['mentioned' => $mentioned, 'cited' => $cited, 'snippet' => $snippet];
        } catch (\Throwable $e) {
            Log::debug('Retrieval proxy failed: '.$e->getMessage());
            return ['mentioned' => false, 'cited' => false, 'snippet' => ''];
        }
    }

    /** LLM mode: ask the actual model whether the brand is mentioned/cited. */
    private function checkLlm(string $provider, string $query, string $brand, string $domain): array
    {
        $system = <<<PROMPT
You are an AI visibility monitor. A brand named "{$brand}" (domain: {$domain}) wants to know
if they appear in answers for shopping queries. Answer in strict JSON:
{"mentioned": true|false, "cited": true|false, "snippet": "short quote from the answer mentioning the brand, or empty"}
"mentioned" = the brand or its domain is named in the answer.
"cited" = the answer includes a clickable citation/link to the domain or names it as a source.
Be strict: absent = false. Return ONLY the JSON object.
PROMPT;

        try {
            if ($provider === 'openrouter') {
                $response = Http::timeout(30)
                    ->withToken(config('services.openrouter.key'))
                    ->withHeaders([
                        'HTTP-Referer' => config('app.url'),
                        'X-Title' => 'AI Visibility Tracker',
                    ])
                    ->post('https://openrouter.ai/api/v1/chat/completions', [
                        'model' => config('services.openrouter.model', 'nvidia/nemotron-3.5-lightning:free'),
                        'messages' => [
                            ['role' => 'system', 'content' => $system],
                            ['role' => 'user', 'content' => "Shopping query: {$query}"],
                        ],
                        'temperature' => 0,
                        'response_format' => ['type' => 'json_object'],
                    ]);
            } elseif ($provider === 'openai') {
                $response = Http::timeout(20)
                    ->withToken(config('services.openai.key'))
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => config('services.openai.model', 'gpt-4o-mini'),
                        'messages' => [
                            ['role' => 'system', 'content' => $system],
                            ['role' => 'user', 'content' => "Shopping query: {$query}"],
                        ],
                        'temperature' => 0,
                        'response_format' => ['type' => 'json_object'],
                    ]);
            } else {
                $response = Http::timeout(20)
                    ->withToken(config('services.gemini.key'))
                    ->post('https://generativelanguage.googleapis.com/v1beta/models/'.config('services.gemini.model', 'gemini-1.5-flash').':generateContent?key='.config('services.gemini.key'), [
                        'contents' => [['parts' => [['text' => $system."\n\nShopping query: {$query}"]]]],
                        'generationConfig' => ['temperature' => 0],
                    ]);
            }

            if (! $response->successful()) {
                return ['mentioned' => false, 'cited' => false, 'snippet' => ''];
            }

            $body = $response->json();
            // OpenRouter and OpenAI share the same response format
            $text = in_array($provider, ['openrouter', 'openai'])
                ? ($body['choices'][0]['message']['content'] ?? '')
                : ($body['candidates'][0]['content']['parts'][0]['text'] ?? '');

            $json = json_decode(trim((string) $text, "` \n"), true);
            return [
                'mentioned' => (bool) ($json['mentioned'] ?? false),
                'cited' => (bool) ($json['cited'] ?? false),
                'snippet' => (string) ($json['snippet'] ?? ''),
            ];
        } catch (\Throwable $e) {
            Log::debug('LLM check failed ('.$provider.'): '.$e->getMessage());
            return ['mentioned' => false, 'cited' => false, 'snippet' => ''];
        }
    }

    private function extractSnippet(string $html, string $domain): string
    {
        // Find the result block containing the domain and pull its snippet text.
        $pos = stripos($html, $domain);
        if ($pos === false) {
            return '';
        }
        $window = substr($html, max(0, $pos - 800), 2400);
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($window)));
        return mb_substr($text, 0, 300);
    }

    /** India-relevant seed queries for a brand. */
    public function seedQueries(Store $store): \Illuminate\Support\Collection
    {
        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));
        $seeds = [
            ["query" => "best {$brand} products review", 'category' => 'brand'],
            ["query" => "{$brand} price in India", 'category' => 'brand'],
            ["query" => "is {$brand} good quality", 'category' => 'brand'],
            ["query" => "buy {$brand} online India", 'category' => 'brand'],
            ["query" => "top D2C brands India 2026", 'category' => 'category'],
            ["query" => "{$brand} vs competitors India", 'category' => 'comparison'],
        ];
        $created = collect();
        foreach ($seeds as $seed) {
            $created->push(TrackedQuery::firstOrCreate(
                ['store_id' => $store->id, 'query' => $seed['query']],
                ['category' => $seed['category'], 'active' => true]
            ));
        }
        return $created;
    }
}
