<?php

namespace App\Webhooks;

use App\Models\Store;
use App\Services\AttributionService;
use Illuminate\Support\Facades\Log;
use Shopify\Webhooks\Handler;

class OrdersPaidHandler implements Handler
{
    public function handle(string $topic, string $shop, array $body): void
    {
        // Attribute the order to an AI channel (chatgpt/gemini/perplexity/…)
        // when landing/referring signals indicate it — powers the AI Traffic report.
        $store = Store::where('shop', $shop)->first();
        if ($store) {
            app(AttributionService::class)->processOrder($store, $body);
        }
        Log::info("Order paid on {$shop}: #".($body['name'] ?? $body['id'] ?? 'n/a'));
    }
}
