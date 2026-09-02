<?php

namespace App\Services;

use App\Models\BrandSignalRun;
use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Brand Signals — the third-party trust layer AI engines weigh most when they
 * decide whether to cite you (reviews/ratings on trusted platforms are the
 * strongest documented correlate of AI citations; see the research notes in
 * README / docs).
 *
 * Each check is a live, honest probe:
 *   rating_schema        — AggregateRating JSON-LD (ratingValue + reviewCount)
 *   review_content       — visible reviews/testimonials with sentiment text
 *   platform_presence    — brand appears in web results near known review
 *                          platforms (Trustpilot, Google, MouthShut, JustDial,
 *                          Amazon.in, Flipkart, …)
 *   third_party_mentions — other domains talking about the brand (off-site)
 *   social_profiles      — Instagram / Facebook / YouTube / X links on site
 *
 * Failures are honest (site unreachable = checks fail with a note), mirroring
 * the AuditService philosophy.
 */
class BrandSignalsService
{
    /** Per-check weights (sum 100) + labels + fixes. */
    public const CHECKS = [
        'rating_schema' => [
            'weight' => 30,
            'label' => 'Ratings in structured data',
            'fix' => 'Add AggregateRating JSON-LD (ratingValue + reviewCount) to your Product schema — AI engines parse it directly.',
        ],
        'review_content' => [
            'weight' => 15,
            'label' => 'Visible reviews / testimonials',
            'fix' => 'Publish real customer reviews/testimonials on product pages (with star ratings) — citation-ready content.',
        ],
        'platform_presence' => [
            'weight' => 25,
            'label' => 'Review platforms',
            'fix' => 'Claim profiles on review platforms (Trustpilot, Google Business Profile, MouthShut, JustDial) and Amazon/Flipkart listings.',
        ],
        'third_party_mentions' => [
            'weight' => 20,
            'label' => 'Off-site mentions',
            'fix' => 'Earn mentions from blogs, press and communities — off-site mentions correlate most strongly with AI citations.',
        ],
        'social_profiles' => [
            'weight' => 10,
            'label' => 'Social profiles linked',
            'fix' => 'Link Instagram / Facebook / YouTube / X profiles from your site so they are easy to verify.',
        ],
    ];

    private const REVIEW_DOMAINS = [
        'trustpilot.com', 'google.com', 'google.co.in', 'mouthshut.com',
        'justdial.com', 'amazon.in', 'amazon.com', 'flipkart.com',
        'g2.com', 'producthunt.com',
    ];

    public function latestFor(Store $store): ?BrandSignalRun
    {
        return $store->brandSignalRuns()->latest()->first();
    }

    /** Run all probes and persist a run. */
    public function run(Store $store): BrandSignalRun
    {
        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));
        $domain = $store->hostname();

        $homeHtml = $this->fetch("https://{$domain}/");
        $productHtml = $this->fetchProductPage($store);

        $rating = $this->checkRatingSchema($homeHtml, $productHtml);
        $reviews = $this->checkReviewContent($productHtml ?: $homeHtml);
        $platforms = $this->checkPlatformPresence($brand, $domain);
        $mentions = $this->checkThirdPartyMentions($brand, $domain);
        $social = $this->checkSocialProfiles($homeHtml);

        $checkResults = [
            'rating_schema' => $rating,
            'review_content' => $reviews,
            'platform_presence' => $platforms,
            'third_party_mentions' => $mentions,
            'social_profiles' => $social,
        ];
        $score = 0;
        foreach ($checkResults as $key => $r) {
            if ($r['found']) {
                $score += self::CHECKS[$key]['weight'];
            }
        }

        $payload = [];
        foreach (self::CHECKS as $key => $meta) {
            $r = $checkResults[$key];
            $payload[] = [
                'key' => $key,
                'label' => $meta['label'],
                'found' => $r['found'],
                'detail' => $r['detail'],
                'fix' => $meta['fix'],
                'score' => $r['found'] ? $meta['weight'] : 0,
                'max' => $meta['weight'],
            ];
        }

        $grade = $score >= 70 ? 'A' : ($score >= 45 ? 'B' : ($score >= 25 ? 'C' : 'D'));

        $run = BrandSignalRun::create([
            'store_id' => $store->id,
            'score' => $score,
            'summary' => [
                'total' => $score,
                'grade' => $grade,
                'checked_at' => now()->toIso8601String(),
                'domain' => $domain,
            ],
            'checks' => $payload,
        ]);

        Log::info("Brand signals run for {$store->shop}: {$score}/100 ({$grade})");
        return $run;
    }

    // ------------------------------------------------------------------ probes

    private function checkRatingSchema(?string $home, ?string $product): array
    {
        $found = false;
        $detail = 'No AggregateRating JSON-LD found on the homepage or a product page.';
        foreach ([$product, $home] as $html) {
            if (! $html || ! str_contains($html, 'AggregateRating')) {
                continue;
            }
            if (preg_match('/"@type"\s*:\s*"AggregateRating"[\s\S]{0,400}?"reviewCount"\s*:\s*"?\d+/', $html)
                || (str_contains($html, 'ratingValue') && str_contains($html, 'reviewCount'))) {
                $found = true;
                $detail = 'Product/site exposes ratingValue + reviewCount in JSON-LD — engines can parse your ratings directly.';
                break;
            }
        }
        return ['found' => $found, 'detail' => $detail];
    }

    private function checkReviewContent(?string $html): array
    {
        if (! $html) {
            return ['found' => false, 'detail' => 'Storefront unreachable — could not look for review content.'];
        }
        $clean = strip_tags($html);
        $found = preg_match('/\b(reviews?|testimonials?)\b/i', $clean)
            && preg_match('/(\d(\.\d)?\s*[\/★]|\b\d+\s+out of\s+\d+\b|\bstars?\b)/i', $clean);
        return [
            'found' => (bool) $found,
            'detail' => $found
                ? 'Found review/testimonial content with ratings on the storefront.'
                : 'No visible reviews or testimonials with ratings detected on the storefront.',
        ];
    }

    private function checkPlatformPresence(string $brand, string $domain): array
    {
        $results = $this->ddg("\"{$brand}\" reviews India");
        if ($results === null) {
            return ['found' => false, 'detail' => 'Could not run the web probe for review platforms (network).'];
        }
        $foundDomains = [];
        foreach ($results as $url) {
            $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
            foreach (self::REVIEW_DOMAINS as $known) {
                if (str_contains($host, $known) && ! in_array($known, $foundDomains, true)) {
                    $foundDomains[] = $known;
                }
            }
        }
        return [
            'found' => count($foundDomains) > 0,
            'detail' => count($foundDomains)
                ? 'Brand appears in web results near: '.implode(', ', array_slice($foundDomains, 0, 4)).'.'
                : 'No review-platform presence found in web results for “'.$brand.' reviews”.',
        ];
    }

    private function checkThirdPartyMentions(string $brand, string $domain): array
    {
        $results = $this->ddg("\"{$brand}\"");
        if ($results === null) {
            return ['found' => false, 'detail' => 'Could not run the web probe for off-site mentions (network).'];
        }
        $external = [];
        foreach ($results as $url) {
            $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
            if ($host === '' || str_contains($host, str_replace('www.', '', $domain))) {
                continue;
            }
            $key = preg_replace('/^www\./', '', $host);
            if (! isset($external[$key])) {
                $external[$key] = true;
            }
        }
        $count = count($external);
        return [
            'found' => $count > 0,
            'detail' => $count
                ? $count.' different site(s) mention the brand in web results (incl. '.array_key_first($external).').'
                : 'No off-site mentions found for the brand — third-party sites aren’t talking about you yet.',
        ];
    }

    private function checkSocialProfiles(?string $home): array
    {
        if (! $home) {
            return ['found' => false, 'detail' => 'Storefront unreachable — could not scan for social links.'];
        }
        $found = [];
        foreach (['instagram.com', 'facebook.com', 'youtube.com', 'x.com', 'twitter.com'] as $net) {
            if (preg_match('#https?://(www\.)?'.$net.'/[^\s"\'<>]+#i', $home)) {
                $found[] = $net;
            }
        }
        return [
            'found' => count($found) > 0,
            'detail' => count($found) ? 'Linked profiles: '.implode(', ', $found).'.' : 'No Instagram/Facebook/YouTube/X links found on the homepage.',
        ];
    }

    // ---------------------------------------------------------------- helpers

    private function fetchProductPage(Store $store): ?string
    {
        $entry = $store->llmsEntries()->where('kind', 'product')->orderBy('position')->first();
        return $entry ? $this->fetch('https://'.$store->hostname().$entry->path) : null;
    }

    private function fetch(string $url): ?string
    {
        try {
            $res = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; AIVisibilityBot/1.0; +https://aivisibility.app)'])
                ->get($url);
            return $res->successful() ? (string) $res->body() : null;
        } catch (\Throwable $e) {
            Log::debug('Brand signal fetch failed: '.$e->getMessage());
            return null;
        }
    }

    /** Honest retrieval-proxy: live web results via DuckDuckGo HTML. null = failed. */
    private function ddg(string $query): ?array
    {
        try {
            $res = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; AIVisibilityBot/1.0; +https://aivisibility.app)'])
                ->get('https://html.duckduckgo.com/html/', ['q' => $query]);
            if (! $res->successful()) {
                return null;
            }
            preg_match_all('#href="([^"]*uddg=[^"]*)"#', (string) $res->body(), $m);
            $urls = array_map(fn ($u) => urldecode(html_entity_decode($u)), $m[1] ?? []);
            return array_values(array_unique(array_filter($urls))) ?: null;
        } catch (\Throwable $e) {
            Log::debug('Brand signal DDG failed: '.$e->getMessage());
            return null;
        }
    }
}
