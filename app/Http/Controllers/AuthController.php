<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Services\BillingService;
use App\Shopify\OAuthService;
use App\Shopify\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /** GET /auth/install?shop=... → redirect to Shopify OAuth */
    public function install(Request $request)
    {
        $shop = strtolower(trim((string) $request->query('shop', '')));
        if (! preg_match('/^[a-z0-9\-]+\.myshopify\.com$/', $shop)) {
            return response('Invalid shop domain. Use format: yourstore.myshopify.com', 400);
        }

        // No real Shopify credentials configured (local preview) → explain
        // instead of letting the SDK throw a 500.
        if (! ShopifyService::init()) {
            return response(
                'AI Visibility is not connected to Shopify yet. Set SHOPIFY_API_KEY and '
                .'SHOPIFY_API_SECRET in .env to install on a live store. '
                .'For a preview without credentials open /app?demo=1.',
                503
            );
        }

        try {
            $oauthUrl = OAuthService::begin($shop);
            return redirect()->away($oauthUrl);
        } catch (\Throwable $e) {
            Log::error('OAuth begin failed for ' . $shop . ': ' . $e->getMessage());
            return response(
                'Could not start the install process. Error: ' . $e->getMessage() . '<br><br>'
                .'<b>Possible causes:</b><br>'
                .'1. SHOPIFY_API_KEY or SHOPIFY_API_SECRET in .env are incorrect<br>'
                .'2. The app URL in Shopify Partner Dashboard doesn\'t match your server URL<br>'
                .'3. The server URL is not accessible from the internet<br><br>'
                .'<a href="/install">← Back to install page</a>',
                500
            );
        }
    }

    /** GET /auth/callback → validate, save token, register webhooks */
    public function callback(Request $request)
    {
        $query = $request->query();
        if (! ShopifyService::verifyHmac($query)) {
            return response('Invalid signature', 403);
        }
        try {
            $store = OAuthService::complete($query);
        } catch (\Throwable $e) {
            return response('OAuth failed: '.$e->getMessage(), 500);
        }

        // Redirect into the embedded app. Shopify will load the app URL
        // (configured in Partner Dashboard) inside the admin iframe with
        // ?shop= and ?host= params — our MarketingController detects those
        // and forwards to /app so the onboarding flow shows immediately.
        $apiKey = config('shopify.api_key');
        if ($apiKey) {
            return redirect()->away(
                "https://{$store->shop}/admin/apps/{$apiKey}"
            );
        }

        return redirect()->away(
            "https://{$store->shop}/admin/apps/"
        );
    }

    /** GET /auth/demo → demo store for local preview */
    public function demo()
    {
        $store = Store::where('is_demo', true)->first();
        if (! $store) {
            return response('Demo store not seeded. Run: php artisan demo:seed', 500);
        }
        return redirect()->route('app', ['demo' => 1]);
    }

    /** GET /auth/check → diagnostic: check Shopify config and scopes */
    public function check()
    {
        $apiKey = config('shopify.api_key', '');
        $secret = config('shopify.api_secret', '');
        $scopes = config('shopify.scopes', []);
        $host = config('shopify.host', '');
        $appUrl = config('app.url', '');
        $apiVersion = config('shopify.api_version', '2025-04');

        $isPlaceholder = fn (string $v): bool => $v === '' || str_contains($v, 'your_');

        $issues = [];
        if ($isPlaceholder($apiKey)) $issues[] = 'SHOPIFY_API_KEY is not set or is a placeholder';
        if ($isPlaceholder($secret)) $issues[] = 'SHOPIFY_API_SECRET is not set or is a placeholder';
        if (empty($host) || $host === '127.0.0.1:8123') $issues[] = 'SHOPIFY_APP_HOST_NAME is not set (should be your server domain)';

        // Check API version
        if (!preg_match('/^\d{4}-\d{2}$/', $apiVersion) || $apiVersion > '2025-10') {
            $issues[] = "SHOPIFY_API_VERSION '{$apiVersion}' is invalid. Use '2025-04' (latest stable).";
        }

        $requiredScopes = ['read_content', 'write_content'];
        $missingScopes = array_diff($requiredScopes, $scopes);
        if (!empty($missingScopes)) {
            $issues[] = 'Missing scopes: ' . implode(', ', $missingScopes) . '. Update SHOPIFY_APP_SCOPES in .env';
        }

        $store = Store::where('shop', '!=', '')->first();
        $storeScopes = $store ? $store->scopes : 'no store found';

        return response()->json([
            'api_key_set' => !$isPlaceholder($apiKey),
            'api_secret_set' => !$isPlaceholder($secret),
            'host' => $host,
            'app_url' => $appUrl,
            'api_version' => $apiVersion,
            'configured_scopes' => $scopes,
            'store_scopes' => $storeScopes,
            'issues' => $issues,
            'ok' => empty($issues),
            'install_url' => $appUrl . '/auth/install?shop=YOUR-STORE.myshopify.com',
            'callback_url' => $appUrl . '/auth/callback',
        ]);
    }

    /** GET /auth/test-shopify?shop=... → test Shopify API connection for a store */
    public function testShopify(Request $request)
    {
        $shop = strtolower(trim((string) $request->query('shop', '')));
        if (empty($shop)) {
            // Try to find any store
            $store = \App\Models\Store::whereNotNull('shopify_token')->first();
        } else {
            $store = \App\Models\Store::where('shop', $shop)->first();
        }

        if (!$store) {
            return response()->json(['error' => 'No store found. Provide ?shop=yourstore.myshopify.com'], 404);
        }

        $result = [
            'shop' => $store->shop,
            'has_token' => !empty($store->shopify_token),
            'token_prefix' => $store->shopify_token ? substr($store->shopify_token, 0, 10) . '...' : 'none',
            'scopes' => $store->scopes,
            'has_write_content' => str_contains((string) $store->scopes, 'write_content'),
            'has_read_content' => str_contains((string) $store->scopes, 'read_content'),
        ];

        // Try to list blogs
        try {
            $client = \App\Shopify\ShopifyService::client($store);
            $res = $client->query(['query' => '{ blogs(first: 5) { edges { node { id title handle } } } }']);
            $body = $res->getDecodedBody();
            $result['blogs_response'] = $body;
            $result['blogs_success'] = empty($body['errors']);
            $result['blogs'] = $body['data']['blogs']['edges'] ?? [];
        } catch (\Throwable $e) {
            $result['blogs_error'] = $e->getMessage();
        }

        // Try to list products (to test read_products scope)
        try {
            $client = \App\Shopify\ShopifyService::client($store);
            $res = $client->query(['query' => '{ products(first: 3) { edges { node { title handle } } } }']);
            $body = $res->getDecodedBody();
            $result['products_response'] = $body;
            $result['products_success'] = empty($body['errors']);
            $result['products'] = $body['data']['products']['edges'] ?? [];
        } catch (\Throwable $e) {
            $result['products_error'] = $e->getMessage();
        }

        return response()->json($result);
    }
    public function testLlm()
    {
        $llm = app(\App\Services\LlmClient::class);

        $result = [
            'available' => $llm->available(),
            'provider' => $llm->available() ? $llm->provider() : 'none',
            'openrouter_key_set' => !empty(config('services.openrouter.key')),
            'openrouter_key_prefix' => config('services.openrouter.key') ? substr(config('services.openrouter.key'), 0, 15) . '...' : 'not set',
            'openrouter_model' => config('services.openrouter.model', 'nvidia/nemotron-3.5-lightning:free'),
            'openai_key_set' => !empty(config('services.openai.key')),
            'gemini_key_set' => !empty(config('services.gemini.key')),
        ];

        if ($llm->available()) {
            // Try a simple test call
            $response = $llm->chat('Reply with exactly: OK', 'Test');
            $result['test_response'] = $response;
            $result['test_success'] = $response !== null;
            if ($response === null) {
                $result['test_error'] = $llm->lastError ?: 'Unknown error — check Laravel logs';
            }
        }

        return response()->json($result);
    }
}
