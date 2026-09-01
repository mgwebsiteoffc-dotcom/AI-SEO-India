<?php

namespace App\Http\Middleware;

use App\Shopify\ShopifyService;
use Closure;
use Illuminate\Http\Request;

class VerifyShopifySession
{
    public function handle(Request $request, Closure $next)
    {
        $store = ShopifyService::sessionStore();
        if (! $store) {
            return response()->json(['error' => 'Unauthenticated', 'code' => 'AUTH_REQUIRED'], 401);
        }
        $request->attributes->set('store', $store);
        return $next($request);
    }
}
