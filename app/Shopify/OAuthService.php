<?php

namespace App\Shopify;

use App\Models\Store;
use Illuminate\Support\Facades\Log;
use Shopify\Auth\OAuth;
use Shopify\Webhooks\Registry;
use Shopify\Webhooks\Topics;

class OAuthService
{
    /** Step 1: build the OAuth install URL. */
    public static function begin(string $shop): string
    {
        ShopifyService::init();
        return OAuth::begin(
            shop: $shop,
            redirectPath: '/auth/callback',
            isOnline: false,
            setCookieFunction: null,
        );
    }

    /** Step 2: handle the callback, persist the offline token, register webhooks, create billing. */
    public static function complete(array $query): Store
    {
        ShopifyService::init();
        $session = OAuth::callback(
            cookies: $_COOKIE ?? [],
            query: $query,
            setCookieFunction: null,
        );

        $shop = strtolower(trim($query['shop'] ?? $session->getShop() ?? ''));

        $store = Store::updateOrCreate(
            ['shop' => $shop],
            [
                'shopify_token' => $session->getAccessToken(),
                'scopes' => implode(',', $session->getScope() ?? []),
                'trial_ends_at' => now()->addDays(3),
            ]
        );

        // Pre-fill the weekly report recipient from the shop owner email
        // (only when the merchant hasn't set one already).
        $settings = $store->settings ?? [];
        if (empty($settings['report_email'])) {
            $email = ShopifyService::shopEmail($store);
            if ($email) {
                $settings['report_email'] = $email;
                $store->update(['settings' => $settings]);
            }
        }

        // Agency install: ?agency=<agency-shop> links this store under that
        // Agency-plan store so it can manage + report on it.
        if (! empty($query['agency'])) {
            $agency = Store::where('shop', strtolower(trim((string) $query['agency'])))->where('plan', 'agency')->first();
            if ($agency && $agency->isNot($store)) {
                $store->update(['parent_store_id' => $agency->id]);
            }
        }

        self::registerWebhooks($store);

        return $store;
    }

    public static function registerWebhooks(Store $store): void
    {
        ShopifyService::init();
        $base = rtrim(config('app.url'), '/');
        $topics = [
            Topics::APP_UNINSTALLED => "/webhooks/app/uninstalled",
            Topics::PRODUCTS_CREATE => "/webhooks/products/create",
            Topics::PRODUCTS_UPDATE => "/webhooks/products/update",
            Topics::PRODUCTS_DELETE => "/webhooks/products/delete",
            Topics::ORDERS_PAID => "/webhooks/orders/paid",
            // Mandatory GDPR webhooks for every public app (approval checklist).
            Topics::CUSTOMERS_DATA_REQUEST => "/webhooks/customers/data_request",
            Topics::CUSTOMERS_REDACT => "/webhooks/customers/redact",
            Topics::SHOP_REDACT => "/webhooks/shop/redact",
        ];
        foreach ($topics as $topic => $path) {
            try {
                Registry::register($path, $topic, $store->shop, $store->shopify_token);
            } catch (\Throwable $e) {
                Log::warning("Webhook registration failed [$topic] for {$store->shop}: ".$e->getMessage());
            }
        }
    }
}
