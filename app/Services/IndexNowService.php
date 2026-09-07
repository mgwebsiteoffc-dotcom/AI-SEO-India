<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * IndexNow — notify search engines about new/updated URLs instantly.
 *
 * IndexNow is supported by Bing, Yandex, Seznam, Naver, and others.
 * Google doesn't support IndexNow but uses its own Indexing API.
 *
 * We send URLs to the IndexNow API endpoint whenever content changes.
 */
class IndexNowService
{
    private const ENDPOINT = 'https://api.indexnow.org/IndexNow';

    /**
     * Submit a single URL to IndexNow.
     */
    public function submitUrl(Store $store, string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST);
        $key = $this->getOrCreateKey($store);

        try {
            $res = Http::timeout(10)->post(self::ENDPOINT, [
                'host' => $host,
                'key' => $key,
                'keyLocation' => "https://{$host}/{$key}.txt",
                'urlList' => [$url],
            ]);

            $success = $res->successful();
            if (!$success) {
                Log::warning('IndexNow submit failed', ['status' => $res->status(), 'url' => $url]);
            }

            return ['ok' => $success, 'status' => $res->status()];
        } catch (\Throwable $e) {
            Log::warning('IndexNow error: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Submit multiple URLs to IndexNow (batch).
     */
    public function submitUrls(Store $store, array $urls): array
    {
        if (empty($urls)) {
            return ['ok' => true, 'submitted' => 0];
        }

        $host = parse_url($urls[0], PHP_URL_HOST);
        $key = $this->getOrCreateKey($store);

        try {
            $res = Http::timeout(15)->post(self::ENDPOINT, [
                'host' => $host,
                'key' => $key,
                'keyLocation' => "https://{$host}/{$key}.txt",
                'urlList' => $urls,
            ]);

            $success = $res->successful();
            return ['ok' => $success, 'submitted' => count($urls), 'status' => $res->status()];
        } catch (\Throwable $e) {
            Log::warning('IndexNow batch error: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Submit all store pages (products, collections, pages, blog posts) to IndexNow.
     */
    public function submitAll(Store $store): array
    {
        $domain = $store->hostname();
        $urls = [];

        // Always include homepage
        $urls[] = "https://{$domain}/";

        // Get llms entries
        $entries = $store->llmsEntries()->orderBy('position')->get();

        // If no entries, generate them
        if ($entries->isEmpty()) {
            try {
                app(LlmsGenerator::class)->generate($store, persist: true);
                $entries = $store->llmsEntries()->orderBy('position')->get();
            } catch (\Throwable $e) {
                \Log::warning('IndexNow: LlmsGenerator failed: ' . $e->getMessage());
            }
        }

        // Build URLs from entries
        foreach ($entries as $entry) {
            if (!empty($entry->path)) {
                $urls[] = "https://{$domain}{$entry->path}";
            }
        }

        // Also try to get products/collections directly from Shopify
        if (\App\Shopify\ShopifyService::hasValidToken($store)) {
            try {
                $client = \App\Shopify\ShopifyService::client($store);
                $res = $client->query([
                    'query' => '{ products(first: 50) { edges { node { handle } } } collections(first: 20) { edges { node { handle } } } }',
                ]);
                $body = $res->getDecodedBody();
                foreach (($body['data']['products']['edges'] ?? []) as $e) {
                    $urls[] = "https://{$domain}/products/{$e['node']['handle']}";
                }
                foreach (($body['data']['collections']['edges'] ?? []) as $e) {
                    $urls[] = "https://{$domain}/collections/{$e['node']['handle']}";
                }
            } catch (\Throwable $e) {
                // Shopify API unavailable — use llms entries only
            }
        }

        // Remove duplicates
        $urls = array_unique(array_filter($urls));

        if (empty($urls)) {
            return [
                'ok' => true,
                'submitted' => 0,
                'message' => 'No pages found. Make sure the app has read_products scope, or generate llms.txt first.',
            ];
        }

        // IndexNow accepts up to 10,000 URLs per request
        $chunks = array_chunk($urls, 10000);
        $totalSubmitted = 0;

        foreach ($chunks as $chunk) {
            $result = $this->submitUrls($store, $chunk);
            if ($result['ok']) {
                $totalSubmitted += count($chunk);
            }
        }

        return [
            'ok' => true,
            'submitted' => $totalSubmitted,
            'urls' => array_values($urls),
        ];
    }

    /**
     * Get or create an IndexNow key for the store.
     * The key is a UUID stored in the store's settings.
     */
    private function getOrCreateKey(Store $store): string
    {
        $settings = $store->settings ?? [];
        $key = $settings['indexnow_key'] ?? null;

        if (!$key) {
            $key = str_replace('-', '', \Illuminate\Support\Str::uuid()->toString());
            $settings['indexnow_key'] = $key;
            $store->update(['settings' => $settings]);
        }

        return $key;
    }

    /**
     * Get the IndexNow key file content (for serving at /{key}.txt).
     */
    public function getKeyFileContent(Store $store): string
    {
        return $this->getOrCreateKey($store);
    }
}
