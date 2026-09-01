<?php

namespace App\Webhooks;

use App\Models\Store;
use Illuminate\Support\Facades\Log;
use Shopify\Webhooks\Handler;

class ProductsUpdateHandler implements Handler
{
    public function handle(string $topic, string $shop, array $body): void
    {
        // Keep llms.txt fresh: flag the store so the next generate() rebuilds from catalog.
        Log::info("Product updated: {$shop}");
        $store = Store::where('shop', $shop)->first();
        if ($store) {
            $settings = $store->settings ?? [];
            $settings['llms_dirty'] = true;
            $store->update(['settings' => $settings]);
        }
    }
}
