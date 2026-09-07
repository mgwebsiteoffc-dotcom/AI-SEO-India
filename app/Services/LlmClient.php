<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared LLM client — OpenRouter, OpenAI, or Gemini (whichever key is configured).
 * Priority: OpenRouter > OpenAI > Gemini.
 * Returns raw text or null when no key / failure (callers fall back to templates).
 */
class LlmClient
{
    public string $lastError = '';

    public function available(): bool
    {
        return (bool) (
            config('services.openrouter.key')
            || config('services.openai.key')
            || config('services.gemini.key')
        );
    }

    public function provider(): string
    {
        if (config('services.openrouter.key')) return 'openrouter';
        if (config('services.openai.key')) return 'openai';
        return 'gemini';
    }

    public function chat(string $system, string $user, bool $json = false): ?string
    {
        if (! $this->available()) {
            return null;
        }
        try {
            // Priority: OpenRouter > OpenAI > Gemini
            if (config('services.openrouter.key')) {
                return $this->openrouter($system, $user, $json);
            }
            if (config('services.openai.key')) {
                return $this->openai($system, $user, $json);
            }
            return $this->gemini($system, $user, $json);
        } catch (\Throwable $e) {
            Log::warning('LLM call failed ('.$this->provider().'): '.$e->getMessage());
            return null;
        }
    }

    /**
     * OpenRouter — unified API for 200+ models (OpenAI-compatible endpoint).
     * Supports free models like nvidia/nemotron-3.5-lightning:free.
     */
    private function openrouter(string $system, string $user, bool $json): ?string
    {
        $key = config('services.openrouter.key');
        $model = config('services.openrouter.model', 'nvidia/nemotron-3.5-lightning:free');

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0.7,
            'max_tokens' => 1600,
        ];
        if ($json) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $res = Http::timeout(60)
                ->withToken($key)
                ->withHeaders([
                    'HTTP-Referer' => config('app.url'),
                    'X-Title' => 'AI Visibility for Shopify',
                ])
                ->post('https://openrouter.ai/api/v1/chat/completions', $payload);
        } catch (\Throwable $e) {
            Log::error('OpenRouter network error: ' . $e->getMessage());
            $this->lastError = 'Network error: ' . $e->getMessage();
            return null;
        }

        if (! $res->successful()) {
            $body = $res->body();
            $error = $res->json('error.message', $body);
            Log::error('OpenRouter API error', [
                'status' => $res->status(),
                'model' => $model,
                'key_prefix' => substr($key, 0, 15) . '...',
                'error' => substr($body, 0, 500),
            ]);
            $this->lastError = "OpenRouter error ({$res->status()}): {$error}";
            return null;
        }

        $content = $res->json('choices.0.message.content');
        if (empty($content)) {
            Log::warning('OpenRouter returned empty content', ['response' => substr($res->body(), 0, 500)]);
            $this->lastError = 'OpenRouter returned empty response';
            return null;
        }

        return $content;
    }

    private function openai(string $system, string $user, bool $json): ?string
    {
        $payload = [
            'model' => config('services.openai.model', 'gpt-4o-mini'),
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0.7,
            'max_tokens' => 1600,
        ];
        if ($json) {
            $payload['response_format'] = ['type' => 'json_object'];
        }
        $res = Http::timeout(45)->withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/chat/completions', $payload);
        if (! $res->successful()) {
            return null;
        }
        return $res->json('choices.0.message.content');
    }

    private function gemini(string $system, string $user, bool $json): ?string
    {
        $res = Http::timeout(45)
            ->post('https://generativelanguage.googleapis.com/v1beta/models/'.config('services.gemini.model', 'gemini-1.5-flash').':generateContent?key='.config('services.gemini.key'), [
                'contents' => [['parts' => [['text' => $system."\n\n".$user]]]],
                'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 1600],
            ]);
        if (! $res->successful()) {
            return null;
        }
        return $res->json('candidates.0.content.parts.0.text');
    }
}
