<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Services\BillingService;
use App\Shopify\OAuthService;
use App\Shopify\ShopifyService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /** GET /auth/install?shop=... → redirect to Shopify OAuth */
    public function install(Request $request)
    {
        $shop = strtolower(trim((string) $request->query('shop', '')));
        if (! preg_match('/^[a-z0-9\-]+\.myshopify\.com$/', $shop)) {
            return response('Invalid shop domain', 400);
        }
        return redirect()->away(OAuthService::begin($shop));
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
}
