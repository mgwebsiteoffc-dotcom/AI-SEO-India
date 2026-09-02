<?php

namespace App\Webhooks;

use App\Models\Store;
use Illuminate\Support\Facades\Log;
use Shopify\Webhooks\Handler;

/**
 * Handles products/create, products/update and products/delete:
 *  1. marks the store's llms.txt dirty so the AI reading list auto-refreshes,
 *  2. queues the storefront URLs for an IndexNow (instant indexing) ping.
 */
class ProductsUpdateHandler implements Handler
{
    public function handle(string $topic, string $shop, array $body): void
    {
        Log::info("Product event [{$topic}]: {$shop}");
        $store = Store::where('shop', $shop)->first();
        if (! $store) {
            return;
        }

        // 1) llms.txt auto-refresh
        $settings = $store->settings ?? [];
        $settings['llms_dirty'] = true;
        $store->update(['settings' => $settings]);

        // 2) instant indexing for the changed storefront
        try {
            $service = app(\App\Services\IndexNowService::class);
            if ($service->enabled() && $service->storeEnabled($store)) {
                $service->queueStoreUrls($store);
            }
        } catch (\Throwable $e) {
            Log::debug('IndexNow queue on product change failed: '.$e->getMessage());
        }
    }
}
