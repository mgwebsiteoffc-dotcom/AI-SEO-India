<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Shopify\ShopifyService;
use Closure;
use Illuminate\Http\Request;

class VerifyShopifySession
{
    public function handle(Request $request, Closure $next)
    {
        $store = ShopifyService::sessionStore();

        // Fallback: find store by ?shop= query param. Covers the first API
        // calls right after OAuth where the JWT may not be established yet.
        if (! $store) {
            $shop = strtolower(trim((string) $request->query('shop', '')));
            if ($shop && preg_match('/\.myshopify\.com$/', $shop)) {
                $store = Store::where('shop', $shop)->first();
            }
        }

        if (! $store) {
            return response()->json(['error' => 'Unauthenticated', 'code' => 'AUTH_REQUIRED'], 401);
        }
        $request->attributes->set('store', $store);
        return $next($request);
    }
}
