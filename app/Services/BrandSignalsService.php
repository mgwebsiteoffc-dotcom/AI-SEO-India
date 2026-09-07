<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Brand Signals — build and verify brand presence across the web.
 *
 * Brand signals help AI engines recognize and trust your brand:
 * - Google Business Profile verification
 * - Social media profile links
 * - Consistent NAP (Name, Address, Phone) across directories
 * - Brand mentions in structured data
 * - Wikipedia/Wikidata presence check
 */
class BrandSignalsService
{
    /**
     * Analyze brand signals for a store.
     * Returns a score (0-100) and list of signals found/missing.
     */
    public function analyze(Store $store): array
    {
        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));
        $domain = $store->hostname();
        $signals = [];
        $score = 0;

        // 1. Check if brand has structured data (Organization schema)
        $signals[] = $this->checkOrganizationSchema($domain, $store);

        // 2. Check social media presence (search for brand)
        $signals[] = $this->checkSocialPresence($brand, $domain);

        // 3. Check if brand appears in search results
        $signals[] = $this->checkSearchPresence($brand, $domain);

        // 4. Check Google Business Profile
        $signals[] = $this->checkGoogleBusiness($brand, $domain);

        // 5. Check consistent branding in store
        $signals[] = $this->checkStoreBranding($store);

        // Calculate score
        $found = collect($signals)->where('status', 'found')->count();
        $partial = collect($signals)->where('status', 'partial')->count();
        $total = count($signals);
        $score = (int) round((($found * 100 + $partial * 50) / max(1, $total)));

        return [
            'score' => $score,
            'brand' => $brand,
            'domain' => $domain,
            'signals' => $signals,
            'found' => $found,
            'total' => $total,
        ];
    }

    private function checkOrganizationSchema(string $domain, Store $store): array
    {
        $settings = $store->settings ?? [];
        $hasSchema = $settings['schema_org'] ?? false;

        return [
            'name' => 'Organization Schema',
            'description' => 'Structured data (JSON-LD) on your homepage that tells AI engines about your brand.',
            'status' => $hasSchema ? 'found' : 'missing',
            'action' => $hasSchema ? null : 'Install Organization schema from the Schema Builder tab.',
            'impact' => 'high',
        ];
    }

    private function checkSocialPresence(string $brand, string $domain): array
    {
        // Check if the store has social links in settings
        // For now, we check if the brand name is unique enough to have social presence
        $socialPlatforms = ['instagram.com', 'facebook.com', 'twitter.com', 'x.com', 'youtube.com', 'linkedin.com'];
        $found = false;

        try {
            // Search for brand on DuckDuckGo
            $res = Http::timeout(8)->get('https://html.duckduckgo.com/html/', [
                'q' => $brand . ' site:instagram.com OR site:facebook.com OR site:twitter.com',
            ]);

            if ($res->successful()) {
                $html = $res->body();
                $found = stripos($html, strtolower($brand)) !== false;
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return [
            'name' => 'Social Media Presence',
            'description' => 'Active social media profiles that reference your brand and domain.',
            'status' => $found ? 'found' : 'partial',
            'action' => $found ? null : 'Create social media profiles and link them from your store.',
            'impact' => 'medium',
        ];
    }

    private function checkSearchPresence(string $brand, string $domain): array
    {
        $found = false;

        try {
            $res = Http::timeout(8)->get('https://html.duckduckgo.com/html/', [
                'q' => $brand . ' ' . $domain,
            ]);

            if ($res->successful()) {
                $html = $res->body();
                $found = stripos($html, $domain) !== false;
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return [
            'name' => 'Search Engine Presence',
            'description' => 'Your brand appears in search results when searched by name.',
            'status' => $found ? 'found' : 'missing',
            'action' => $found ? null : 'Submit your sitemap to Google Search Console and Bing Webmaster Tools.',
            'impact' => 'high',
        ];
    }

    private function checkGoogleBusiness(string $brand, string $domain): array
    {
        // We can't verify Google Business Profile programmatically without API access
        // But we can check if the brand has a physical presence indicator
        $store = Store::where('domain', $domain)->first();

        return [
            'name' => 'Google Business Profile',
            'description' => 'A verified Google Business Profile helps AI engines trust your brand.',
            'status' => 'unknown',
            'action' => 'Create and verify a Google Business Profile at business.google.com',
            'impact' => 'medium',
        ];
    }

    private function checkStoreBranding(Store $store): array
    {
        $hasBrandName = !empty($store->brand_name);
        $hasDomain = !empty($store->domain) && !str_contains($store->domain ?? '', 'myshopify');
        $settings = $store->settings ?? [];
        $hasDescription = !empty($settings['brand_description'] ?? null);

        $checks = [$hasBrandName, $hasDomain, $hasDescription];
        $passed = collect($checks)->filter()->count();

        return [
            'name' => 'Store Branding',
            'description' => 'Brand name, custom domain, and description configured in your store.',
            'status' => $passed >= 2 ? 'found' : ($passed >= 1 ? 'partial' : 'missing'),
            'action' => $passed >= 2 ? null : 'Set your brand name, custom domain, and brand description in Settings.',
            'impact' => 'high',
        ];
    }
}
