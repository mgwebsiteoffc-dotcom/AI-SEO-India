<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Shopify\ShopifyService;
use Closure;
use Illuminate\Http\Request;

class VerifyProxyRequest
{
    public function handle(Request $request, Closure $next)
    {
        $query = $request->query();

        // Demo mode bypass for local preview
        if (($query['demo'] ?? null) === '1') {
            $store = Store::where('is_demo', true)->first();
            $request->attributes->set('store', $store);
            return $next($request);
        }

        if (! ShopifyService::verifyProxySignature($query)) {
            return response('Forbidden', 403);
        }

        $shop = strtolower((string) ($query['shop'] ?? ''));
        $store = Store::where('shop', $shop)->first();
        if (! $store) {
            return response('Store not found', 404);
        }

        $request->attributes->set('store', $store);
        return $next($request);
    }
}
