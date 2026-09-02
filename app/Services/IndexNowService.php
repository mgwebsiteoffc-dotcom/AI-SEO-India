<?php

namespace App\Services;

use App\Models\IndexnowSubmission;
use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Instant Indexing via IndexNow (https://www.indexnow.org).
 *
 * IndexNow is the protocol Google/Bing/Seznam/Yandex support for telling
 * search engines a URL changed *right now*, instead of waiting for a recrawl.
 * Because AI engines build their knowledge bases from search indexes, faster
 * discovery of product/page changes also shortens the time before AI answers
 * can cite the new content.
 *
 * Key rules respected here:
 *  - Only one batch request per host per flush; every URL in a batch shares
 *    the same host + key.
 *  - Retry failures up to 3 attempts, then drop (with a log line).
 *  - Honoured only when the SaaS master switch AND the store's own toggle are
 *    on (store.settings['indexnow_enabled']).
 */
class IndexNowService
{
    public const ENDPOINT = 'https://api.indexnow.org/indexnow';

    public const MAX_ATTEMPTS = 3;

    // ------------------------------------------------------------- settings

    /** SaaS-level switch + shared key (owner sets these in /admin/settings). */
    public function config(): array
    {
        $defaults = ['enabled' => false, 'key' => ''];
        return array_merge($defaults, \App\Models\SaasSetting::getValue('indexnow') ?? []);
    }

    public function enabled(): bool
    {
        return (bool) ($this->config()['enabled'] ?? false) && $this->key() !== '';
    }

    public function key(): string
    {
        return trim((string) ($this->config()['key'] ?? ''));
    }

    public function saveConfig(bool $enabled, string $key): void
    {
        $key = trim($key);
        // Auto-generate a key when enabled without one (hex string, spec allows any).
        if ($enabled && $key === '') {
            $key = bin2hex(random_bytes(16));
        }
        \App\Models\SaasSetting::setValue('indexnow', [
            'enabled' => $enabled,
            'key' => $key,
        ]);
    }

    /** Per-store opt-out (defaults ON when the owner enables the service). */
    public function storeEnabled(Store $store): bool
    {
        $settings = $store->settings ?? [];
        return (bool) ($settings['indexnow_enabled'] ?? true);
    }

    // -------------------------------------------------------------- queueing

    /** Queue one absolute URL for the store (dedupes pending + submitted). */
    public function queueUrl(Store $store, string $url): void
    {
        $url = rtrim(trim($url), '/');
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return;
        }
        $exists = IndexnowSubmission::where('store_id', $store->id)
            ->where('url', $url)->exists();
        if ($exists) {
            return;
        }
        IndexnowSubmission::create(['store_id' => $store->id, 'url' => $url]);
    }

    /**
     * Queue the storefront URLs that matter when the catalog changes:
     * every page listed in the llms.txt reading list + the homepage.
     */
    public function queueStoreUrls(Store $store, bool $includeHome = true): int
    {
        $domain = $store->hostname();
        $base = 'https://'.$domain;
        $count = 0;

        if ($includeHome) {
            $this->queueUrl($store, $base.'/');
            $count++;
        }
        foreach ($store->llmsEntries()->orderBy('position')->get() as $e) {
            $this->queueUrl($store, $base.$e->path);
            $count++;
        }
        return $count;
    }

    // ---------------------------------------------------------------- flush

    /**
     * Send all queued URLs for a store in a single IndexNow batch.
     * Returns ['sent' => int, 'failed' => int].
     */
    public function flushStore(Store $store): array
    {
        if (! $this->enabled() || ! $this->storeEnabled($store)) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 1];
        }

        $pending = IndexnowSubmission::where('store_id', $store->id)
            ->whereNull('submitted_at')->orderBy('id')->limit(100)->get();
        if ($pending->isEmpty()) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        }

        $host = parse_url('https://'.$store->hostname(), PHP_URL_HOST);
        $urls = $pending->pluck('url')->unique()->values()->all();
        $sent = 0;
        $failed = 0;

        try {
            $response = Http::timeout(12)
                ->asJson()
                ->post(self::ENDPOINT, [
                    'host' => $host,
                    'key' => $this->key(),
                    'urlList' => $urls,
                ]);

            if ($response->successful() || in_array($response->status(), [200, 202], true)) {
                IndexnowSubmission::whereIn('id', $pending->pluck('id'))
                    ->update(['submitted_at' => now(), 'attempts' => 0]);
                $sent = count($urls);
            } else {
                // 400 = bad request (e.g. key mismatch) — don't hammer; drop.
                // 429 = rate limited — keep for next flush.
                if ($response->status() === 400 || $response->status() === 403) {
                    IndexnowSubmission::whereIn('id', $pending->pluck('id'))->delete();
                    Log::warning('IndexNow rejected URLs ('.$response->status().') for '.$store->shop.': '.$response->body());
                    $failed = count($urls);
                } else {
                    $failed = $this->retryOrDrop($pending);
                }
            }
        } catch (\Throwable $e) {
            Log::debug('IndexNow ping failed: '.$e->getMessage());
            $failed = $this->retryOrDrop($pending);
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /** Increment attempts; drop rows past MAX_ATTEMPTS. */
    private function retryOrDrop($pending): int
    {
        foreach ($pending as $row) {
            $attempts = (int) $row->attempts + 1;
            if ($attempts >= self::MAX_ATTEMPTS) {
                $row->delete();
            } else {
                $row->update(['attempts' => $attempts]);
            }
        }
        return count($pending); // rows remain for the next flush attempt
    }
}
