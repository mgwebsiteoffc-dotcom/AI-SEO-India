<?php

namespace App\Webhooks;

use App\Models\CompetitorMention;
use App\Models\Store;
use Illuminate\Support\Facades\Log;
use Shopify\Webhooks\Handler;

/**
 * shop/redact (GDPR) — the merchant asked Shopify to redact the shop's data.
 * Wipe every tenant row and all derived analytics so nothing about the store
 * remains. The audit/derived tables hold no personal data, but deleting them
 * is the safe, reviewable behaviour for a public app.
 */
class ShopRedactHandler implements Handler
{
    public function handle(string $topic, string $shop, array $body): void
    {
        $store = Store::where('shop', $shop)->first();
        if (! $store) {
            Log::info("GDPR shop/redact for {$shop} — no store row found.");
            return;
        }

        foreach ($store->competitors as $c) {
            CompetitorMention::where('competitor_domain', $c->domain)
                ->where('store_id', $store->id)->delete();
        }
        $store->competitors()->delete();
        $store->attributedOrders()->delete();
        $store->contentPosts()->delete();
        $store->audits()->each(fn ($a) => $a->issues()->delete());
        $store->audits()->delete();
        $store->snapshots()->delete();
        $store->queries()->delete();
        $store->llmsEntries()->delete();
        $store->billing()->delete();
        $store->delete();

        Log::info("GDPR shop/redact for {$shop} — store and all derived data erased.");
    }
}
