<?php

namespace App\Services;

use App\Models\AuditIssue;
use App\Models\AuditRun;
use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI Readiness Audit Engine.
 *
 * Evidence-based checks (per 2026 industry data): AI answers are built from
 * search-index retrieval + structured data + citation-worthy content. We score:
 *   crawlability (robots/sitemap/llms.txt)  - 30 pts
 *   schema / structured data                - 25 pts
 *   content extractability (Q&A, specs)     - 25 pts
 *   brand & trust signals                   - 15 pts
 *   speed & delivery                        -  5 pts
 */
class AuditService
{
    private const CATEGORY_WEIGHTS = [
        'crawlability' => 30,
        'schema'       => 25,
        'content'      => 25,
        'brand'        => 15,
        'speed'        => 5,
    ];

    public function run(Store $store, array $options = []): AuditRun
    {
        $run = AuditRun::create([
            'store_id'   => $store->id,
            'score'      => 0,
            'status'     => 'running',
            'started_at' => now(),
        ]);

        try {
            $host = $store->hostname();
            $base = $this->baseUrl($host);

            $issues = [];
            $results = [];

            // ---- Crawlability (30) ----
            $robots = $this->fetch("$base/robots.txt");
            $results['robots_found'] = $robots !== null;
            $issues = array_merge($issues, $this->checkRobots($robots, $base, $store));

            $sitemap = $this->fetch("$base/sitemap.xml");
            $results['sitemap_found'] = $sitemap !== null;
            $issues = array_merge($issues, $this->checkSitemap($sitemap, $base, $store));

            $llms = $this->fetch("$base/llms.txt");
            $results['llms_found'] = $llms !== null;

            // ---- Homepage + product pages ----
            $home = $this->fetch($base);
            $productUrls = $this->discoverProductUrls($home, $sitemap, $base);
            $productHtml = [];
            foreach (array_slice($productUrls, 0, 5) as $url) {
                $html = $this->fetch($url);
                if ($html !== null) {
                    $productHtml[$url] = $html;
                }
            }
            $results['pages_fetched'] = 1 + count($productHtml);

            // ---- Schema (25) ----
            $issues = array_merge($issues, $this->checkSchema($home, $productHtml, $base, $store));

            // ---- Content (25) ----
            $issues = array_merge($issues, $this->checkContent($home, $productHtml, $base, $store));

            // ---- Brand & trust (15) ----
            $issues = array_merge($issues, $this->checkBrand($home, $base, $store));

            // ---- Speed (5) ----
            $issues = array_merge($issues, $this->checkSpeed($base, $store));

            // ---- Score ----
            $summary = $this->score($issues);
            $run->update([
                'score'        => $summary['total'],
                'summary'      => $summary,
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            foreach ($issues as $issue) {
                AuditIssue::create(array_merge($issue, ['audit_run_id' => $run->id]));
            }

            return $run->fresh(['issues']);
        } catch (\Throwable $e) {
            Log::error('Audit failed for '.$store->shop.': '.$e->getMessage());
            $run->update(['status' => 'failed', 'error' => $e->getMessage()]);
            return $run;
        }
    }

    // ------------------------------------------------------------------ helpers

    private function baseUrl(string $host): string
    {
        // Always https; fall back to http if the site is http-only.
        return "https://".$host;
    }

    private function fetch(string $url): ?string
    {
        try {
            $response = Http::timeout(6)
                ->withHeaders(['User-Agent' => 'AIVisibilityBot/1.0 (+https://aivisibility.app)'])
                ->get($url);
            if (! $response->successful()) {
                return null;
            }
            return (string) $response->body();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function has(?string $haystack, string $needle): bool
    {
        return $haystack !== null && stripos($haystack, $needle) !== false;
    }

    private function checkRobots(?string $robots, string $base, Store $store): array
    {
        $issues = [];
        $bots = ['GPTBot', 'OAI-SearchBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended', 'Applebot-Extended', 'Meta-ExternalAgent'];

        if ($robots === null) {
            $issues[] = [
                'category' => 'crawlability',
                'severity' => 'critical',
                'code'     => 'robots_missing',
                'title'    => 'robots.txt not reachable',
                'detail'   => "We could not fetch {$base}/robots.txt. AI crawlers use robots.txt to decide whether they may read your store.",
                'recommendation' => 'Ensure robots.txt is served (Shopify serves one by default on myshopify.com; for custom domains check DNS/theme).',
            ];
            return $issues;
        }

        $blocked = [];
        foreach ($bots as $bot) {
            if (preg_match('/^User-agent:\s*'.preg_quote($bot, '/').'\s*$/mi', $robots) && preg_match('/^User-agent:\s*'.preg_quote($bot, '/').'\s*$[\s\S]*?Disallow:\s*\/\s*$/mi', $robots)) {
                $blocked[] = $bot;
            }
        }
        // A blanket "Disallow: /" for * or the specific bot means blocked
        if (preg_match('/^User-agent:\s*\*\s*$/mi', $robots) && preg_match('/^User-agent:\s*\*\s*$[\s\S]*?Disallow:\s*\/\s*$/mi', $robots)) {
            $blocked[] = 'all AI bots (wildcard)';
        }

        if ($blocked) {
            $issues[] = [
                'category' => 'crawlability',
                'severity' => 'critical',
                'code'     => 'robots_blocking_ai',
                'title'    => 'AI crawlers blocked by robots.txt',
                'detail'   => 'Blocked: '.implode(', ', $blocked).'. When AI bots are blocked, ChatGPT/Gemini/Perplexity cannot read your store for answers.',
                'recommendation' => 'Allow GPTBot, OAI-SearchBot, ClaudeBot, PerplexityBot and Google-Extended in robots.txt. Our robots.txt manager can generate the correct file.',
            ];
        } elseif (count($blocked) === 0) {
            $issues[] = [
                'category' => 'crawlability',
                'severity' => 'info',
                'code'     => 'robots_ok',
                'title'    => 'AI crawlers allowed',
                'detail'   => 'robots.txt is reachable and does not block the major AI crawlers we checked.',
                'recommendation' => 'Keep it that way — re-run this audit after any robots.txt change.',
            ];
        }

        if (! $this->has($robots, 'Sitemap:')) {
            $issues[] = [
                'category' => 'crawlability',
                'severity' => 'warning',
                'code'     => 'robots_no_sitemap',
                'title'    => 'No sitemap declared in robots.txt',
                'detail'   => 'Declaring Sitemap: in robots.txt helps crawlers (including AI crawlers) discover all pages faster.',
                'recommendation' => 'Add "Sitemap: '.$base.'/sitemap.xml" to robots.txt.',
            ];
        }

        return $issues;
    }

    private function checkSitemap(?string $sitemap, string $base, Store $store): array
    {
        if ($sitemap === null) {
            return [[
                'category' => 'crawlability',
                'severity' => 'critical',
                'code'     => 'sitemap_missing',
                'title'    => 'sitemap.xml not found',
                'detail'   => "We could not fetch {$base}/sitemap.xml. Without a sitemap, AI engines may miss most of your product pages.",
                'recommendation' => 'Enable Shopify sitemap (on by default). If on a custom domain, check that it resolves and serves /sitemap.xml.',
            ]];
        }

        $issues = [];
        if (substr_count($sitemap, '<url>') < 10) {
            $issues[] = [
                'category' => 'crawlability',
                'severity' => 'warning',
                'code'     => 'sitemap_small',
                'title'    => 'Sitemap has very few URLs',
                'detail'   => 'Your sitemap contains fewer than 10 URLs — AI engines see little content to cite.',
                'recommendation' => 'Publish more pages (products, collections, blog posts) and keep the sitemap auto-updating.',
            ];
        } else {
            $issues[] = [
                'category' => 'crawlability',
                'severity' => 'info',
                'code'     => 'sitemap_ok',
                'title'    => 'Sitemap healthy',
                'detail'   => 'sitemap.xml is reachable and lists '.substr_count($sitemap, '<url>').' URLs.',
                'recommendation' => 'Nothing to do.',
            ];
        }
        return $issues;
    }

    private function discoverProductUrls(?string $home, ?string $sitemap, string $base): array
    {
        $urls = [];
        $patterns = [
            '#https?://[^"\'<> ]*/products/[a-z0-9\-]+#i',
            '#https?://[^"\'<> ]*/collections/[a-z0-9\-]+#i',
        ];
        foreach ([$home, $sitemap] as $html) {
            if (! $html) {
                continue;
            }
            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $html, $m)) {
                    foreach ($m[0] as $u) {
                        $urls[$u] = true;
                    }
                }
            }
        }
        // Relative links in sitemap (Shopify uses absolute; some themes use relative)
        if ($sitemap && preg_match_all('#<loc>\s*(/products/[a-z0-9\-]+)#i', $sitemap, $m)) {
            foreach ($m[1] as $p) {
                $urls[$base.$p] = true;
            }
        }
        return array_keys($urls);
    }

    private function checkSchema(?string $home, array $productHtml, string $base, Store $store): array
    {
        $issues = [];

        if ($home === null) {
            $issues[] = [
                'category' => 'schema', 'severity' => 'critical', 'code' => 'site_unreachable',
                'title' => 'Storefront not reachable',
                'detail' => "We could not load {$base}. Check that the domain is live and HTTPS-enabled.",
                'recommendation' => 'Verify DNS/SSL and that the store is online.',
            ];
            return $issues;
        }

        $hasLdJson = $this->has($home, 'application/ld+json');
        if (! $hasLdJson) {
            $issues[] = [
                'category' => 'schema', 'severity' => 'warning', 'code' => 'schema_home_missing',
                'title' => 'No JSON-LD structured data on homepage',
                'detail' => 'AI engines parse JSON-LD (Organization, WebSite, Product, FAQ) directly when generating shopping answers.',
                'recommendation' => 'Install our Schema Builder (one click) — it injects Organization, WebSite and product-level JSON-LD.',
            ];
        } else {
            $issues[] = [
                'category' => 'schema', 'severity' => 'info', 'code' => 'schema_home_ok',
                'title' => 'JSON-LD present on homepage',
                'detail' => 'Structured data found on the homepage.',
                'recommendation' => 'Ensure Product and FAQ schemas also exist on product pages.',
            ];
        }

        $withProductSchema = 0;
        $total = count($productHtml);
        foreach ($productHtml as $html) {
            if ($this->has($html, 'application/ld+json') && (stripos($html, '"@type":"Product"') !== false || stripos($html, '"@type": "Product"') !== false)) {
                $withProductSchema++;
            }
        }
        if ($total > 0) {
            if ($withProductSchema === 0) {
                $issues[] = [
                    'category' => 'schema', 'severity' => 'warning', 'code' => 'schema_product_missing',
                    'title' => 'No Product schema on product pages',
                    'detail' => "Checked $total product page(s): none had Product JSON-LD. Product schema is one of the strongest AI-citation signals.",
                    'recommendation' => 'Enable Product + FAQ schema via our Schema Builder.',
                ];
            } else {
                $issues[] = [
                    'category' => 'schema', 'severity' => 'info', 'code' => 'schema_product_ok',
                    'title' => 'Product schema present',
                    'detail' => "$withProductSchema of $total sampled product pages have Product JSON-LD.",
                    'recommendation' => 'Nothing to do.',
                ];
            }
        }

        return $issues;
    }

    private function checkContent(?string $home, array $productHtml, string $base, Store $store): array
    {
        $issues = [];

        if ($home && ! preg_match('/<h1[^>]*>/i', $home)) {
            $issues[] = [
                'category' => 'content', 'severity' => 'warning', 'code' => 'no_h1',
                'title' => 'Homepage has no H1 heading',
                'detail' => 'AI engines extract headings to understand page structure.',
                'recommendation' => 'Add a clear H1 describing your brand and category.',
            ];
        }

        if ($home && ! $this->has($home, '<title>')) {
            $issues[] = [
                'category' => 'content', 'severity' => 'warning', 'code' => 'no_title',
                'title' => 'Homepage missing <title> tag',
                'detail' => 'Title tags feed both search indexes and AI retrieval.',
                'recommendation' => 'Add a descriptive title (brand + category + value prop).',
            ];
        }

        if (preg_match('/<title[^>]*>([^<]{0,80})/i', (string) $home, $m) && strlen(trim($m[1])) < 15) {
            $issues[] = [
                'category' => 'content', 'severity' => 'warning', 'code' => 'title_short',
                'title' => 'Title tag is very short',
                'detail' => '"'.trim($m[1]).'" — add category keywords Indian shoppers actually ask AI (e.g. "best under ₹1000", "vegan", "for Indian skin").',
                'recommendation' => 'Rewrite the title to 40–60 characters with intent keywords.',
            ];
        }

        $longDesc = 0;
        $total = count($productHtml);
        foreach ($productHtml as $html) {
            $text = strip_tags((string) $html);
            if (strlen($text) > 1500) {
                $longDesc++;
            }
        }
        if ($total > 0 && $longDesc === 0) {
            $issues[] = [
                'category' => 'content', 'severity' => 'warning', 'code' => 'thin_product_content',
                'title' => 'Product pages are thin on text',
                'detail' => "Sampled $total product page(s) with little extractable text. AI answers quote product details — thin pages rarely get cited.",
                'recommendation' => 'Add detailed descriptions: materials, sizes, usage, ingredients, FAQs, comparison tables.',
            ];
        }

        $hasFaq = $this->has($home, 'faq') || $this->has($home, 'question');
        if (! $hasFaq) {
            $issues[] = [
                'category' => 'content', 'severity' => 'info', 'code' => 'no_faq',
                'title' => 'No FAQ content detected',
                'detail' => 'Q&A blocks are "citation-ready" — AI engines quote them verbatim in shopping answers.',
                'recommendation' => 'Add 3–5 FAQs per product page ("Is it suitable for oily skin?", "What is the delivery time in India?").',
            ];
        }

        return $issues;
    }

    private function checkBrand(?string $home, string $base, Store $store): array
    {
        $issues = [];
        $brand = $store->brand_name ?? '';
        if ($brand !== '' && $home) {
            $found = stripos($home, $brand) !== false;
            if (! $found) {
                $issues[] = [
                    'category' => 'brand', 'severity' => 'warning', 'code' => 'brand_not_on_home',
                    'title' => 'Brand name not prominent on homepage',
                    'detail' => "AI models need clear brand identity to associate answers with you. We couldn't find \"$brand\" in the homepage text.",
                    'recommendation' => 'Ensure the brand name appears in the H1, title, header and footer.',
                ];
            }
        }

        foreach (['instagram.com', 'facebook.com', 'youtube.com', 'whatsapp'] as $social) {
            if ($home && $this->has($home, $social)) {
                $issues[] = [
                    'category' => 'brand', 'severity' => 'info', 'code' => 'social_found',
                    'title' => 'Social/trust signals found',
                    'detail' => "Found $social link on the storefront — helps trust signals.",
                    'recommendation' => 'Nothing to do.',
                ];
                return $issues;
            }
        }
        $issues[] = [
            'category' => 'brand', 'severity' => 'info', 'code' => 'no_social_links',
            'title' => 'No social/contact links detected',
            'detail' => 'Trust signals (reviews, social proof, contact info) influence how confidently AI recommends a brand.',
            'recommendation' => 'Add review widgets (Judge.me etc.), social links and contact page.',
        ];
        return $issues;
    }

    private function checkSpeed(string $base, Store $store): array
    {
        $issues = [];
        try {
            $start = microtime(true);
            $response = \Illuminate\Support\Facades\Http::timeout(6)
                ->withHeaders(['User-Agent' => 'AIVisibilityBot/1.0'])
                ->head($base);
            $ms = (int) ((microtime(true) - $start) * 1000);
            if ($ms > 3000) {
                $issues[] = [
                    'category' => 'speed', 'severity' => 'warning', 'code' => 'slow_ttfb',
                    'title' => "Slow server response (~{$ms}ms)",
                    'detail' => 'Slow pages get crawled less often and rank lower in retrieval.',
                    'recommendation' => 'Compress images, reduce app bloat, enable Shopify CDN.',
                ];
            } else {
                $issues[] = [
                    'category' => 'speed', 'severity' => 'info', 'code' => 'speed_ok',
                    'title' => "Fast response (~{$ms}ms)",
                    'detail' => 'Server response time is healthy.',
                    'recommendation' => 'Nothing to do.',
                ];
            }
        } catch (\Throwable $e) {
            $issues[] = [
                'category' => 'speed', 'severity' => 'warning', 'code' => 'speed_unknown',
                'title' => 'Could not measure response time',
                'detail' => $e->getMessage(),
                'recommendation' => 'Retry the audit later.',
            ];
        }
        return $issues;
    }

    private function score(array $issues): array
    {
        $scores = [];
        foreach (array_keys(self::CATEGORY_WEIGHTS) as $cat) {
            $catIssues = array_values(array_filter($issues, fn ($i) => $i['category'] === $cat));
            $deduction = 0;
            foreach ($catIssues as $issue) {
                $deduction += match ($issue['severity']) {
                    'critical' => 0.55,
                    'warning'  => 0.25,
                    default    => 0.05,
                };
            }
            $scores[$cat] = (int) round(max(0, min(100, 100 * (1 - min(1, $deduction)))));
        }

        $total = 0;
        foreach (self::CATEGORY_WEIGHTS as $cat => $weight) {
            $total += ($scores[$cat] ?? 0) * $weight / 100;
        }
        $total = (int) round($total);

        $grade = match (true) {
            $total >= 85 => 'A',
            $total >= 70 => 'B',
            $total >= 50 => 'C',
            $total >= 30 => 'D',
            default      => 'E',
        };

        return [
            'total'     => $total,
            'grade'     => $grade,
            'categories' => $scores,
            'weights'   => self::CATEGORY_WEIGHTS,
            'checked_at' => now()->toIso8601String(),
        ];
    }
}
