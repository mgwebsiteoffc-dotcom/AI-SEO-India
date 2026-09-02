<?php

namespace App\Shopify;

use App\Models\Store;
use Illuminate\Support\Facades\Log;
use Shopify\Auth\FileSessionStorage;
use Shopify\Auth\OAuth;
use Shopify\Clients\Graphql;
use Shopify\Context;
use Shopify\Utils;

class ShopifyService
{
    private static bool $initialized = false;

    public static function init(): bool
    {
        if (self::$initialized) {
            return true;
        }
        $host = (string) config('shopify.host', '');
        $apiKey = (string) config('shopify.api_key', '');
        $secret = (string) config('shopify.api_secret', '');
        $scopes = (array) config('shopify.scopes', []);

        // No credentials configured (local preview / unconfigured install) → SDK stays off.
        // .env.example ships placeholders — treat any "your_…" value the same as empty
        // so demo stores get graceful/demo responses instead of real Shopify calls.
        $isPlaceholder = fn (string $v): bool => $v === '' || str_contains($v, 'your_');
        if ($isPlaceholder($apiKey) || $isPlaceholder($secret)) {
            return false;
        }

        Context::initialize(
            apiKey: $apiKey,
            apiSecretKey: $secret,
            scopes: $scopes,
            hostName: $host,
            sessionStorage: new FileSessionStorage(storage_path('framework/sessions/shopify')),
            apiVersion: config('shopify.api_version', '2025-04'),
            isEmbeddedApp: true,
            isPrivateApp: false,
        );
        self::$initialized = true;
        return true;
    }

    /** Verify Shopify request HMAC (query `hmac` param) — used on install/callback/proxy. */
    public static function verifyHmac(array $query): bool
    {
        $hmac = $query['hmac'] ?? null;
        if (! $hmac) {
            return false;
        }
        unset($query['hmac'], $query['signature']);
        ksort($query);
        $message = urldecode(http_build_query($query));
        $calc = hash_hmac('sha256', $message, (string) config('shopify.api_secret'));
        return hash_equals($calc, (string) $hmac);
    }

    /** Verify App Proxy signature (query `signature` param). */
    public static function verifyProxySignature(array $query): bool
    {
        $signature = $query['signature'] ?? null;
        if (! $signature) {
            return false;
        }
        unset($query['signature']);
        ksort($query);
        $message = urldecode(http_build_query($query));
        $calc = hash_hmac('sha256', $message, (string) config('shopify.api_secret'));
        return hash_equals($calc, (string) $signature);
    }

    public static function client(Store $store): Graphql
    {
        self::init();
        return new Graphql($store->shop, $store->shopify_token);
    }

    /** Load the current embedded-app session (JWT from Authorization header). Returns store or null. */
    public static function sessionStore(): ?Store
    {
        // Demo bypass — no Shopify credentials needed for local preview
        if (request()->query('demo') === '1') {
            return Store::where('is_demo', true)->first();
        }

        if (! self::init()) {
            return null;
        }

        $headers = function_exists('getallheaders') ? getallheaders() : self::headersFromServer();
        $cookies = $_COOKIE ?? [];
        try {
            $sessionId = OAuth::getCurrentSessionId($headers ?: [], $cookies, true);
            if (! $sessionId) {
                return null;
            }
            // Extract shop from the JWT session id: "{userId}_{shop}"
            $shop = null;
            $pos = strpos((string) $sessionId, '_');
            if ($pos !== false) {
                $shop = substr((string) $sessionId, $pos + 1);
            }
            return $shop ? Store::where('shop', $shop)->first() : null;
        } catch (\Throwable $e) {
            Log::debug('Shopify session load failed: '.$e->getMessage());
            return null;
        }
    }

    public static function appUrl(): string
    {
        return config('app.url');
    }

    /** Build headers from $_SERVER when getallheaders() is unavailable (php-fpm). */
    public static function headersFromServer(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    public static function proxyPrefix(): string
    {
        return config('shopify.proxy_prefix', 'apps/ai-visibility');
    }

    /** Full proxy URL for a path, e.g. https://shop.example.com/apps/ai-visibility/llms.txt */
    public static function proxyUrl(Store $store, string $path): string
    {
        $prefix = self::proxyPrefix();
        return "https://{$store->shop}/{$prefix}/".ltrim($path, '/');
    }
}
