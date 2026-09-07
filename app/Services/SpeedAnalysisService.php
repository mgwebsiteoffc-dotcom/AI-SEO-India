<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Speed Analysis — check page speed and Core Web Vitals.
 *
 * Uses Google PageSpeed Insights API (free, no key required for basic usage).
 * Checks the store's homepage and key pages for performance issues.
 */
class SpeedAnalysisService
{
    private const PSI_ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    /**
     * Run speed analysis on the store's homepage.
     */
    public function analyze(Store $store, string $path = '/'): array
    {
        $domain = $store->hostname();
        $url = "https://{$domain}{$path}";

        try {
            $params = [
                'url' => $url,
                'strategy' => 'mobile',
                'category' => ['performance', 'accessibility', 'best-practices', 'seo'],
            ];

            // Use API key if available (higher rate limits)
            $apiKey = config('services.pagespeed.key') ?: env('PAGESPEED_API_KEY');
            if ($apiKey) {
                $params['key'] = $apiKey;
            }

            $res = Http::timeout(45)->get(self::PSI_ENDPOINT, $params);

            if ($res->status() === 429) {
                // Rate limited — return a helpful message instead of an error
                return [
                    'ok' => false,
                    'error' => 'rate_limit',
                    'error_message' => 'Google PageSpeed API rate limit reached. This is normal for high-traffic apps.',
                    'tip' => 'Add a free Google PageSpeed API key in .env (PAGESPEED_API_KEY=your_key) for higher limits. Get one at https://developers.google.com/speed/docs/insights/v5/get-started',
                    'url' => $url,
                ];
            }

            if (!$res->successful()) {
                return [
                    'ok' => false,
                    'error' => 'PageSpeed API returned status ' . $res->status(),
                    'url' => $url,
                ];
            }

            $data = $res->json();
            $lighthouse = $data['lighthouseResult'] ?? [];
            $categories = $lighthouse['categories'] ?? [];
            $audits = $lighthouse['audits'] ?? [];

            return [
                'ok' => true,
                'url' => $url,
                'scores' => [
                    'performance' => (int) round(($categories['performance']['score'] ?? 0) * 100),
                    'accessibility' => (int) round(($categories['accessibility']['score'] ?? 0) * 100),
                    'best_practices' => (int) round(($categories['best-practices']['score'] ?? 0) * 100),
                    'seo' => (int) round(($categories['seo']['score'] ?? 0) * 100),
                ],
                'metrics' => $this->extractMetrics($audits),
                'opportunities' => $this->extractOpportunities($audits),
            ];
        } catch (\Throwable $e) {
            Log::warning('Speed analysis failed: ' . $e->getMessage());
            return [
                'ok' => false,
                'error' => $e->getMessage(),
                'url' => $url,
            ];
        }
    }

    /**
     * Extract Core Web Vitals and key metrics.
     */
    private function extractMetrics(array $audits): array
    {
        $metrics = [];

        $metricKeys = [
            'first-contentful-paint' => 'First Contentful Paint',
            'largest-contentful-paint' => 'Largest Contentful Paint',
            'total-blocking-time' => 'Total Blocking Time',
            'cumulative-layout-shift' => 'Cumulative Layout Shift',
            'speed-index' => 'Speed Index',
            'interactive' => 'Time to Interactive',
        ];

        foreach ($metricKeys as $key => $label) {
            if (isset($audits[$key])) {
                $audit = $audits[$key];
                $metrics[] = [
                    'id' => $key,
                    'label' => $label,
                    'value' => $audit['displayValue'] ?? 'N/A',
                    'score' => $audit['score'] ?? null,
                    'description' => $audit['description'] ?? '',
                ];
            }
        }

        return $metrics;
    }

    /**
     * Extract top opportunities for improvement.
     */
    private function extractOpportunities(array $audits): array
    {
        $opportunities = [];

        $oppKeys = [
            'render-blocking-resources',
            'unused-css-rules',
            'unused-javascript',
            'uses-optimized-images',
            'uses-webp-images',
            'uses-responsive-images',
            'efficient-animated-content',
            'unminified-css',
            'unminified-javascript',
            'uses-text-compression',
            'uses-long-cache-ttl',
            'dom-size',
            'redirects',
            'font-display',
            'server-response-time',
        ];

        foreach ($oppKeys as $key) {
            if (isset($audits[$key]) && ($audits[$key]['score'] ?? 1) < 0.9) {
                $audit = $audits[$key];
                $opportunities[] = [
                    'id' => $key,
                    'title' => $audit['title'] ?? $key,
                    'description' => $audit['description'] ?? '',
                    'score' => $audit['score'] ?? null,
                    'savings' => $audit['details']['overallSavingsMs'] ?? null,
                ];
            }
        }

        // Sort by potential savings (highest first)
        usort($opportunities, fn($a, $b) => ($b['savings'] ?? 0) - ($a['savings'] ?? 0));

        return array_slice($opportunities, 0, 5);
    }
}
