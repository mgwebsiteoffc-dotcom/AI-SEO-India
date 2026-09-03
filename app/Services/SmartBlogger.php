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

            if (empty($blogId)) {
                return ['ok' => false, 'error' => 'Could not find or create a blog on your Shopify store. Check that the app has write_content scope.'];
            }

            $bodyHtml = $this->toHtml($post->body);

            // Validate HTML is not empty
            if (strlen(strip_tags($bodyHtml)) < 50) {
                return ['ok' => false, 'error' => 'Article body is too short or empty after conversion. Try regenerating the article.'];
            }

            // Shopify GraphQL articleCreate mutation (2025-04+ syntax)
            // blogId goes INSIDE the article input, not as a separate argument
            $mutation = <<<'GRAPHQL'
            mutation CreateArticle($article: ArticleCreateInput!) {
              articleCreate(article: $article) {
                article { id title handle }
                userErrors { code field message }
              }
            }
            GRAPHQL;

            $res = $client->query([
                'query' => $mutation,
                'variables' => [
                    'article' => [
                        'blogId' => $blogId,
                        'title' => $post->title,
                        'body' => $bodyHtml,
                        'author' => ['name' => $store->brand_name ?: 'AI Visibility'],
                        'tags' => ['ai-visibility', strtolower(str_replace(' ', '-', $post->category)), 'seo'],
                        'isPublished' => true,
                    ],
                ],
            ]);

            $data = $res->getDecodedBody()['data']['articleCreate'] ?? [];
            if (! empty($data['userErrors'])) {
                $errMsg = collect($data['userErrors'])->pluck('message')->implode('; ');
                Log::warning('Shopify articleCreate errors', ['errors' => $data['userErrors'], 'shop' => $store->shop]);

                // Detect scope/permission errors
                if (stripos($errMsg, 'access') !== false || stripos($errMsg, 'permission') !== false || stripos($errMsg, 'scope') !== false) {
                    return ['ok' => false, 'error' => 'Permission denied. The app needs write_content scope. Please reinstall the app from the Shopify admin to grant the new permissions.'];
                }

                return ['ok' => false, 'error' => 'Shopify error: ' . $errMsg];
            }

            $article = $data['article'] ?? null;
            if (! $article || empty($article['id'])) {
                // Get the full response for debugging
                $fullResponse = $res->getDecodedBody();
                Log::error('Shopify articleCreate returned no article', [
                    'response' => json_encode($fullResponse),
                    'shop' => $store->shop,
                    'blog_id' => $blogId,
                    'store_scopes' => $store->scopes,
                ]);
                $errorDetail = !empty($data['userErrors']) ? collect($data['userErrors'])->pluck('message')->implode('; ') : 'No article returned';
                return ['ok' => false, 'error' => "Shopify publish failed: {$errorDetail}. Store scopes: {$store->scopes}. Try reinstalling the app from Shopify admin."];
            }

            // Build the article URL from blog handle + article handle
            $articleHandle = $article['handle'] ?? null;
            $blogHandle = $this->blogHandle($client, $blogId);
            $articleUrl = 'https://' . $store->shop . '/blogs/' . $blogHandle . '/' . $articleHandle;

            $post->update([
                'status' => 'published',
                'shopify_article_id' => $article['id'],
                'shopify_article_url' => $articleUrl,
            ]);

            return ['ok' => true, 'article' => $article, 'url' => $articleUrl];
        } catch (\Throwable $e) {
            Log::error('Article publish failed: ' . $e->getMessage(), [
                'shop' => $store->shop,
                'post_id' => $post->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return ['ok' => false, 'error' => 'Publish failed: ' . $e->getMessage()];
        }
    }

    private function ensureBlog(\Shopify\Clients\Graphql $client, Store $store): string
    {
        try {
            $res = $client->query(['query' => '{ blogs(first: 10) { edges { node { id title } } } }']);
            $body = $res->getDecodedBody();

            // Check for errors (e.g., missing scopes)
            if (! empty($body['errors'])) {
                $errMsg = collect($body['errors'])->pluck('message')->implode('; ');
                Log::warning('Blog list errors (likely missing read_content scope)', ['errors' => $body['errors'], 'shop' => $store->shop]);
                return '';
            }

            $blogs = $body['data']['blogs']['edges'] ?? [];
        } catch (\Throwable $e) {
            Log::warning('Failed to list blogs: ' . $e->getMessage());
            return '';
        }

        // Look for an existing AI/Insights blog
        foreach ($blogs as $b) {
            if (stripos($b['node']['title'], 'AI') !== false || stripos($b['node']['title'], 'Insight') !== false) {
                return $b['node']['id'];
            }
        }

        // Create a new blog
        try {
            $blogTitle = ($store->brand_name ?: 'AI') . ' Insights';
            $res = $client->query([
                'query' => 'mutation BlogCreate($title: String!) { blogCreate(blog: { title: $title }) { blog { id } userErrors { message } } }',
                'variables' => ['title' => $blogTitle],
            ]);
            $data = $res->getDecodedBody()['data']['blogCreate'] ?? [];
            if (! empty($data['blog']['id'])) {
                return $data['blog']['id'];
            }
            if (! empty($data['userErrors'])) {
                Log::warning('Blog creation errors', ['errors' => $data['userErrors']]);
            }
        } catch (\Throwable $e) {
            Log::warning('Blog creation failed: ' . $e->getMessage());
        }

        // Fallback to first existing blog
        return $blogs[0]['node']['id'] ?? '';
    }

    /** Get the blog handle (slug) for building article URLs. */
    private function blogHandle(\Shopify\Clients\Graphql $client, string $blogId): string
    {
        try {
            $res = $client->query(['query' => "{ blog(id: \"{$blogId}\") { handle } }"]);
            return $res->getDecodedBody()['data']['blog']['handle'] ?? 'news';
        } catch (\Throwable $e) {
            return 'news';
        }
    }

    /**
     * Convert Markdown to clean HTML for Shopify.
     * Does NOT use e() (HTML escape) — that would mangle the output.
     * Instead, converts markdown syntax directly to HTML tags.
     */
    private function toHtml(string $markdown): string
    {
        $lines = explode("\n", $markdown);
        $html = [];
        $inList = false;
        $inTable = false;
        $tableRows = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Blank line — close list/table if open
            if ($trimmed === '') {
                if ($inList) {
                    $html[] = '</ul>';
                    $inList = false;
                }
                if ($inTable) {
                    $html[] = $this->buildTable($tableRows);
                    $tableRows = [];
                    $inTable = false;
                }
                continue;
            }

            // Table separator line (|---|---|) — skip
            if (preg_match('/^\|[\s\-:]+\|$/', $trimmed) || preg_match('/^\|(\s*[-:]+[-| :]*\s*)+\|?$/', $trimmed)) {
                continue;
            }

            // Table row
            if (str_starts_with($trimmed, '|') && str_ends_with($trimmed, '|')) {
                $inTable = true;
                $cells = array_map('trim', explode('|', trim($trimmed, '|')));
                $tableRows[] = $cells;
                continue;
            }

            // Close table if we hit non-table content
            if ($inTable) {
                $html[] = $this->buildTable($tableRows);
                $tableRows = [];
                $inTable = false;
            }

            // Headings
            if (preg_match('/^# (.+)$/', $trimmed, $m)) {
                if ($inList) { $html[] = '</ul>'; $inList = false; }
                $html[] = '<h1>' . htmlspecialchars($m[1]) . '</h1>';
                continue;
            }
            if (preg_match('/^## (.+)$/', $trimmed, $m)) {
                if ($inList) { $html[] = '</ul>'; $inList = false; }
                $html[] = '<h2>' . htmlspecialchars($m[1]) . '</h2>';
                continue;
            }
            if (preg_match('/^### (.+)$/', $trimmed, $m)) {
                if ($inList) { $html[] = '</ul>'; $inList = false; }
                $html[] = '<h3>' . htmlspecialchars($m[1]) . '</h3>';
                continue;
            }

            // List items
            if (preg_match('/^- (.+)$/', $trimmed, $m)) {
                if (!$inList) {
                    $html[] = '<ul>';
                    $inList = true;
                }
                $html[] = '<li>' . $this->inlineMarkdown($m[1]) . '</li>';
                continue;
            }

            // Close list if we hit non-list content
            if ($inList) {
                $html[] = '</ul>';
                $inList = false;
            }

            // Q: / A: lines (FAQ)
            if (preg_match('/^Q:\s*(.+)$/i', $trimmed, $m)) {
                $html[] = '<p><strong>Q: ' . htmlspecialchars($m[1]) . '</strong></p>';
                continue;
            }
            if (preg_match('/^A:\s*(.+)$/i', $trimmed, $m)) {
                $html[] = '<p>A: ' . $this->inlineMarkdown($m[1]) . '</p>';
                continue;
            }

            // Regular paragraph
            $html[] = '<p>' . $this->inlineMarkdown($trimmed) . '</p>';
        }

        if ($inList) {
            $html[] = '</ul>';
        }
        if ($inTable) {
            $html[] = $this->buildTable($tableRows);
        }

        return implode("\n", $html);
    }

    /** Build an HTML table from parsed rows. */
    private function buildTable(array $rows): string
    {
        if (empty($rows)) return '';
        $html = '<table style="border-collapse:collapse;width:100%;margin:1em 0;">';
        foreach ($rows as $i => $row) {
            $tag = ($i === 0) ? 'th' : 'td';
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= "<{$tag} style=\"border:1px solid #ddd;padding:8px;text-align:left;\">" . htmlspecialchars($cell) . "</{$tag}>";
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    /** Convert inline markdown (bold, links) to HTML. */
    private function inlineMarkdown(string $text): string
    {
        // Convert markdown links first: [text](url)
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function ($m) {
            $linkText = htmlspecialchars($m[1]);
            $url = $m[2]; // Don't escape URLs — they're trusted (generated by our app)
            return '<a href="' . $url . '">' . $linkText . '</a>';
        }, $text);

        // Escape HTML entities in the remaining text (outside of HTML tags we just created)
        // Split by HTML tags, escape non-tag parts, rejoin
        $parts = preg_split('/(<[^>]+>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = '';
        foreach ($parts as $part) {
            if (str_starts_with($part, '<') && str_ends_with($part, '>')) {
                $result .= $part; // Keep HTML tags as-is
            } else {
                $result .= htmlspecialchars($part);
            }
        }
        $text = $result;

        // Bold: **text**
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

        return $text;
    }

    private function catalogProducts(Store $store, int $limit): array
    {
        $products = [];
        try {
            $client = ShopifyService::client($store);
            $res = $client->query([
                'query' => '{ products(first: '.$limit.') { edges { node { title handle description(truncateAt: 140) } } } }',
            ]);
            $body = $res->getDecodedBody();
            if (! empty($body['errors'])) {
                Log::warning('Catalog fetch errors', ['errors' => $body['errors'], 'shop' => $store->shop]);
            }
            foreach (($body['data']['products']['edges'] ?? []) as $e) {
                $products[] = [
                    'title' => $e['node']['title'],
                    'handle' => $e['node']['handle'],
                    'path' => '/products/'.$e['node']['handle'],
                    'description' => $e['node']['description'] ?? '',
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Catalog fetch failed for '.$store->shop.': '.$e->getMessage());
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
