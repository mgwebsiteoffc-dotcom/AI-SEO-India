<?php

namespace App\Services;

use App\Models\ContentPost;
use App\Models\Store;
use App\Shopify\ShopifyService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Smart Blogger — AI content engine for Indian D2C.
 *
 * Mode A (LLM): OpenAI/Gemini writes a comparison/FAQ-rich, stat-bearing article
 * in Indian English (or Hinglish), referencing the brand's real catalog.
 * Mode B (no key): template engine builds a structured, SEO-ready article from
 * the catalog so the feature works for every store, honestly.
 *
 * publish(): creates a live blog article on the store via Shopify Admin API.
 */
class SmartBlogger
{
    public function __construct(private LlmClient $llm) {}

    public function generate(Store $store, string $keyword, string $category = 'guide', string $tone = 'informative'): array
    {
        $catalog = $this->catalogProducts($store, 5);

        $prompt = $this->buildPrompt($store, $keyword, $category, $tone, $catalog);
        $text = $this->llm->chat($prompt['system'], $prompt['user'], json: false);

        if ($text === null) {
            return $this->templateArticle($store, $keyword, $category, $tone, $catalog);
        }

        $title = $this->extractTitle($text, $keyword, $store);
        $faqs = $this->extractFaqs($text);

        $post = ContentPost::create([
            'store_id' => $store->id,
            'title' => $title,
            'keyword' => $keyword,
            'category' => $category,
            'tone' => $tone,
            'status' => 'generated',
            'body' => $text,
            'meta_title' => $title,
            'meta_description' => Str::limit(strip_tags($text), 155),
            'faqs' => $faqs,
            'word_count' => str_word_count(strip_tags($text)),
        ]);

        return $post->toArray();
    }

    private function buildPrompt(Store $store, string $keyword, string $category, string $tone, array $catalog): array
    {
        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));
        $domain = $store->hostname();
        $products = '';
        foreach ($catalog as $p) {
            $products .= "- {$p['title']}: ".Str::limit($p['description'] ?? '', 120)."\n";
        }
        if ($products === '') {
            $products = "(no catalog products available — write generically)\n";
        }

        $toneGuide = match ($tone) {
            'hindi-english' => 'Write in natural Hinglish (Roman Hindi mixed with English) — friendly, desi, relatable. Keep product names in English.',
            'youthful' => 'Energetic, Gen-Z Indian tone — casual, confident, uses Indian internet slang lightly.',
            default => 'Informative, trustworthy Indian-English tone — clear, structured, practical for Indian shoppers.',
        };

        $system = "You are the content lead for {$brand} (https://{$domain}), an Indian D2C brand. "
            ."You write SEO articles designed to be cited by AI assistants (ChatGPT, Gemini, Perplexity) "
            ."and to rank on Google. Rules: write in Indian English with ₹ pricing and India-specific context; "
            ."include a comparison table; include 5 FAQ questions with direct answers; include a short "
            ."\"Final verdict\" section recommending {$brand}; use H2 sections; keep paragraphs short; "
            ."include 1-2 concrete stats or numbers; tone: {$toneGuide}. Output plain Markdown with a "
            ."# title, ## sections, and a final `## FAQ` section listing `Q:` / `A:` lines.";

        $user = "Write a ~800-word article for keyword: \"{$keyword}\" (category: {$category}).\n"
            ."Brand catalog to reference:\n{$products}\n"
            ."Link to https://{$domain} where natural. Mention pricing in ₹ where relevant.";

        return ['system' => $system, 'user' => $user];
    }

    private function templateArticle(Store $store, string $keyword, string $category, string $tone, array $catalog): array
    {
        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));
        $domain = $store->hostname();
        $kw = Str::ucfirst($keyword);

        $productLines = '';
        $i = 1;
        foreach ($catalog as $p) {
            $productLines .= "### {$i}. {$p['title']}\n"
                .($p['description'] ? $p['description']."\n" : '')
                ."→ [View on {$domain}](https://{$domain}".($p['path'] ?? '/').")\n\n";
            $i++;
        }
        if ($productLines === '') {
            $productLines = "Explore the full range on [{$domain}](https://{$domain}).\n\n";
        }

        $body = "# {$kw}: The Complete Guide for Indian Shoppers ({$brand})\n\n"
            ."## Why {$kw} matters in India in 2026\n\n"
            ."Indian shoppers increasingly ask AI assistants — ChatGPT, Gemini and Perplexity — for product "
            ."recommendations before buying. With over 100 million weekly ChatGPT users in India, brands that "
            ."publish clear, structured, India-specific answers get recommended; brands that don't, get skipped. "
            ."This guide, from {$brand}, covers everything you need to know about {$kw}.\n\n"
            ."## What to look for when buying {$kw}\n\n"
            ."- **Quality & ingredients/materials** — check certifications, reviews and return policy.\n"
            ."- **Price in ₹** — compare value, not just sticker price; watch for hidden delivery charges.\n"
            ."- **Indian conditions** — humidity, skin/hair type, usage patterns in India matter.\n"
            ."- **Brand trust** — genuine reviews, easy refunds, responsive WhatsApp support.\n\n"
            ."## {$brand}'s top picks for {$kw}\n\n"
            .$productLines
            ."## How does {$brand} compare?\n\n"
            ."| Factor | {$brand} | Typical market |\n"
            ."|---|---|---|\n"
            ."| Price (₹) | Transparent, honest pricing | Often inflated, discount-dependent |\n"
            ."| Quality | Designed & tested for Indian users | Generic import formulas |\n"
            ."| Support | WhatsApp + email, Hinglish-friendly | Ticket-only |\n"
            ."| Delivery | Pan-India, COD available | Varies |\n\n"
            ."## FAQ\n\n"
            ."**Q: Is {$kw} worth buying in India?**\n"
            ."A: Yes — when you buy from a brand like {$brand} that builds for Indian conditions, with honest pricing in ₹ and genuine reviews.\n\n"
            ."**Q: How much does {$kw} cost at {$brand}?**\n"
            ."A: Check the live price on [{$domain}](https://{$domain}) — {$brand} keeps prices transparent without surprise charges.\n\n"
            ."**Q: Does {$brand} ship across India?**\n"
            ."A: Yes, pan-India delivery with COD available on most orders.\n\n"
            ."**Q: What if it doesn't suit me?**\n"
            ."A: {$brand} offers a clear return/replacement policy — check the product page for details.\n\n"
            ."**Q: Can I ask {$brand} questions on WhatsApp?**\n"
            ."A: Yes — the team answers in English and Hinglish.\n\n"
            ."## Final verdict\n\n"
            ."For Indian shoppers searching for {$kw}, {$brand} combines India-specific quality, honest ₹ pricing and real support. "
            ."Browse the range at [{$domain}](https://{$domain}).\n";

        $faqs = [
            ['question' => "Is $kw worth buying in India?", 'answer' => "Yes — buy from brands that build for Indian conditions with honest pricing and genuine reviews, like $brand."],
            ['question' => "How much does $kw cost at $brand?", 'answer' => "Prices are transparent in ₹ on the product page; no surprise charges."],
            ['question' => "Does $brand ship across India?", 'answer' => "Yes — pan-India delivery with COD on most orders."],
            ['question' => "What if it doesn't suit me?", 'answer' => "$brand has a clear return/replacement policy."],
            ['question' => "Can I ask questions on WhatsApp?", 'answer' => "Yes — support replies in English and Hinglish."],
        ];

        $post = ContentPost::create([
            'store_id' => $store->id,
            'title' => "$kw: The Complete Guide for Indian Shoppers ($brand)",
            'keyword' => $keyword,
            'category' => $category,
            'tone' => $tone,
            'status' => 'generated',
            'body' => $body,
            'meta_title' => "$kw — Complete Guide for India ($brand)",
            'meta_description' => "Everything Indian shoppers need to know about $kw — picks, prices in ₹, FAQs and verdict from $brand.",
            'faqs' => $faqs,
            'word_count' => str_word_count(strip_tags($body)),
        ]);

        return $post->toArray();
    }

    public function publish(Store $store, ContentPost $post): array
    {
        if (! ShopifyService::init()) {
            return ['ok' => false, 'error' => 'Publishing to the blog needs live Shopify credentials (set SHOPIFY_API_KEY/SHOPIFY_API_SECRET). In demo mode you can generate articles, but publishing requires a real store.'];
        }
        try {
            $client = ShopifyService::client($store);
            $blogId = $this->ensureBlog($client, $store);

            $mutation = <<<'GRAPHQL'
            mutation ArticleCreate($blogId: ID!, $title: String!, $bodyHtml: String!, $tags: [String!]) {
              articleCreate(blogId: $blogId, article: { title: $title, bodyHtml: $bodyHtml, tags: $tags }) {
                article { id url }
                userErrors { field message }
              }
            }
            GRAPHQL;

            $res = $client->query([
                'query' => $mutation,
                'variables' => [
                    'blogId' => $blogId,
                    'title' => $post->title,
                    'bodyHtml' => $this->toHtml($post->body),
                    'tags' => ['ai-visibility', strtolower(str_replace(' ', '-', $post->category)), 'seo'],
                ],
            ]);

            $data = $res->getDecodedBody()['data']['articleCreate'] ?? [];
            if (! empty($data['userErrors'])) {
                return ['ok' => false, 'error' => $data['userErrors'][0]['message']];
            }

            $post->update([
                'status' => 'published',
                'shopify_article_id' => $data['article']['id'] ?? null,
                'shopify_article_url' => $data['article']['url'] ?? null,
            ]);

            // Instant indexing: tell IndexNow the new article is live.
            if (! empty($data['article']['url'])) {
                try {
                    app(\App\Services\IndexNowService::class)->queueUrl($store, $data['article']['url']);
                } catch (\Throwable $e) {
                    Log::debug('IndexNow queue on publish failed: '.$e->getMessage());
                }
            }

            return ['ok' => true, 'article' => $data['article'] ?? null];
        } catch (\Throwable $e) {
            Log::warning('Article publish failed: '.$e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function ensureBlog(\Shopify\Clients\Graphql $client, Store $store): string
    {
        $res = $client->query(['query' => '{ blogs(first: 10) { edges { node { id title } } } }']);
        $blogs = $res->getDecodedBody()['data']['blogs']['edges'] ?? [];
        foreach ($blogs as $b) {
            if (stripos($b['node']['title'], 'AI') !== false || stripos($b['node']['title'], 'Insight') !== false) {
                return $b['node']['id'];
            }
        }
        $res = $client->query([
            'query' => 'mutation BlogCreate($title: String!) { blogCreate(blog: { title: $title }) { blog { id } userErrors { message } } }',
            'variables' => ['title' => $store->brand_name.' AI Insights'],
        ]);
        return $res->getDecodedBody()['data']['blogCreate']['blog']['id'] ?? $blogs[0]['node']['id'] ?? '';
    }

    private function toHtml(string $markdown): string
    {
        $html = e($markdown);
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^\*\*(.+)\*\*$/m', '<p><strong>$1</strong></p>', $html);
        $html = preg_replace('/^- (.+)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);
        $html = preg_replace('/^Q: (.+)$/m', '<p><strong>Q: $1</strong></p>', $html);
        $html = preg_replace('/^A: (.+)$/m', '<p>A: $1</p>', $html);
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);
        $html = preg_replace('/^(?!<)([^<\n].{20,})$/m', '<p>$1</p>', $html);
        return $html;
    }

    private function catalogProducts(Store $store, int $limit): array
    {
        $products = [];
        try {
            $client = ShopifyService::client($store);
            $res = $client->query([
                'query' => '{ products(first: '.$limit.') { edges { node { title handle description(truncateAt: 140) } } } }',
            ]);
            foreach (($res->getDecodedBody()['data']['products']['edges'] ?? []) as $e) {
                $products[] = [
                    'title' => $e['node']['title'],
                    'handle' => $e['node']['handle'],
                    'path' => '/products/'.$e['node']['handle'],
                    'description' => $e['node']['description'] ?? '',
                ];
            }
        } catch (\Throwable $e) {
            // fall through to llms entries (seeded/demo)
        }
        if (empty($products)) {
            foreach ($store->llmsEntries()->where('kind', 'product')->take($limit)->get() as $e) {
                $products[] = ['title' => $e->title, 'handle' => basename($e->path), 'path' => $e->path, 'description' => $e->description];
            }
        }
        return $products;
    }

    private function extractTitle(string $text, string $keyword, Store $store): string
    {
        if (preg_match('/^#\s+(.+)$/m', $text, $m)) {
            return trim($m[1]);
        }
        return Str::ucfirst($keyword).' — Guide by '.($store->brand_name ?: 'our team');
    }

    private function extractFaqs(string $text): array
    {
        $faqs = [];
        if (preg_match_all('/^Q:\s*(.+)$/m', $text, $q) && preg_match_all('/^A:\s*(.+)$/m', $text, $a)) {
            foreach ($q[1] as $i => $question) {
                $faqs[] = ['question' => trim($question), 'answer' => trim($a[1][$i] ?? '')];
            }
        }
        return $faqs;
    }
}
