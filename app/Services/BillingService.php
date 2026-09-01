<?php

namespace App\Services;

use App\Models\Store;
use App\Shopify\ShopifyService;
use Illuminate\Support\Facades\Log;

class BillingService
{
    public const PLANS = [
        'grow'  => ['name' => 'Grow',  'price' => 999,  'interval' => 'EVERY_30_DAYS'],
        'scale' => ['name' => 'Scale', 'price' => 1999, 'interval' => 'EVERY_30_DAYS'],
        'agency'=> ['name' => 'Agency','price' => 4999, 'interval' => 'EVERY_30_DAYS'],
    ];

    /**
     * Create a recurring app subscription; returns confirmation URL.
     * $interval: 'monthly' (EVERY_30_DAYS) or 'annual' (EVERY_12_MONTHS).
     */
    public function subscribe(Store $store, string $plan, string $interval = 'monthly'): array
    {
        $planDef = self::PLANS[$plan] ?? null;
        if (! $planDef) {
            return ['ok' => false, 'error' => 'Unknown plan'];
        }

        // Demo store without Shopify credentials → simulate the charge so the UI flow can be previewed
        if ($store->is_demo && ! ShopifyService::init()) {
            return [
                'ok' => true,
                'demo' => true,
                'confirmation_url' => '/billing/callback?plan='.$plan.'&interval='.$interval.'&shop='.$store->shop.'&charge_id=demo-charge',
                'message' => 'Demo mode: Shopify billing skipped (no API credentials configured).',
            ];
        }

        try {
            $client = ShopifyService::client($store);
            $intervalCode = $interval === 'annual' ? 'EVERY_12_MONTHS' : 'EVERY_30_DAYS';
            $price = $interval === 'annual' ? round($planDef['price'] * 10) : $planDef['price'];
            $returnUrl = rtrim(config('app.url'), '/')."/billing/callback?plan={$plan}&interval={$interval}&shop={$store->shop}";
            $query = <<<'GRAPHQL'
            mutation AppSubscriptionCreate($name: String!, $returnUrl: URL!, $lineItems: [AppSubscriptionLineItemInput!]!) {
              appSubscriptionCreate(name: $name, returnUrl: $returnUrl, lineItems: $lineItems) {
                appSubscription { id }
                confirmationUrl
                userErrors { field message }
              }
            }
            GRAPHQL;
            $res = $client->query([
                'query' => $query,
                'variables' => [
                    'name' => 'AI Visibility '.$planDef['name'].' ('.($interval === 'annual' ? 'Annual' : 'Monthly').')',
                    'returnUrl' => $returnUrl,
                    'lineItems' => [[
                        'plan' => [
                            'appRecurringPricingDetails' => [
                                'price' => ['amount' => $price, 'currencyCode' => 'INR'],
                                'interval' => $intervalCode,
                            ],
                        ],
                    ]],
                ],
            ]);
            $data = $res->getDecodedBody()['data']['appSubscriptionCreate'] ?? [];
            $errors = $data['userErrors'] ?? [];
            if (! empty($errors) || empty($data['confirmationUrl'])) {
                return ['ok' => false, 'error' => $errors[0]['message'] ?? 'Billing creation failed'];
            }
            $store->update(['billing_id' => $data['appSubscription']['id'] ?? null]);
            return ['ok' => true, 'confirmationUrl' => $data['confirmationUrl']];
        } catch (\Throwable $e) {
            Log::error('Billing subscribe failed: '.$e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** Activate the plan after the merchant returns from Shopify checkout. */
    public function activate(Store $store, string $plan, string $interval = 'monthly'): void
    {
        $planDef = self::PLANS[$plan] ?? null;
        $days = $interval === 'annual' ? 365 : 30;
        $price = $planDef && $interval === 'annual' ? round($planDef['price'] * 10) : ($planDef['price'] ?? 0);
        $store->update([
            'plan' => $plan,
            'billing_status' => 'active',
            'billing_ends_at' => now()->addDays($days),
        ]);
        if ($planDef) {
            \App\Models\AppBilling::create([
                'store_id' => $store->id,
                'plan' => $plan,
                'amount' => $price,
                'charge_type' => 'recurring',
                'charge_status' => 'active',
                'activated_at' => now(),
                'expires_at' => now()->addDays($days),
            ]);
        }
    }

    public function cancel(Store $store): array
    {
        try {
            if ($store->billing_id) {
                $client = ShopifyService::client($store);
                $query = <<<'GRAPHQL'
                mutation AppSubscriptionCancel($id: ID!) {
                  appSubscriptionCancel(id: $id) {
                    appSubscription { id status }
                    userErrors { field message }
                  }
                }
                GRAPHQL;
                $client->query(['query' => $query, 'variables' => ['id' => $store->billing_id]]);
            }
            $store->update(['plan' => 'free', 'billing_status' => 'cancelled', 'billing_id' => null]);
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
