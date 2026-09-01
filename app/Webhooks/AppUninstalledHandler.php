<?php

namespace App\Webhooks;

use App\Models\Store;
use Illuminate\Support\Facades\Log;
use Shopify\Webhooks\Handler;

class AppUninstalledHandler implements Handler
{
    public function handle(string $topic, string $shop, array $body): void
    {
        Log::info("App uninstalled: {$shop}");
        Store::where('shop', $shop)->update([
            'shopify_token' => null,
            'plan' => 'free',
            'billing_status' => 'cancelled',
        ]);
    }
}
