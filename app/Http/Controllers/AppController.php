<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Shopify\ShopifyService;
use Illuminate\Http\Request;

class AppController extends Controller
{
    /** GET /app → embedded admin app shell (SPA). */
    public function index(Request $request)
    {
        $demo = $request->query('demo') === '1';
        $store = null;

        if ($demo) {
            $store = Store::where('is_demo', true)->first();
        } else {
            // Try JWT session first (normal embedded-app flow)
            $store = ShopifyService::sessionStore();

            // Fallback: find store by ?shop= query param. This covers the
            // first load right after OAuth where the JWT may not be ready yet.
            if (! $store) {
                $shop = strtolower(trim((string) $request->query('shop', '')));
                if ($shop && preg_match('/\.myshopify\.com$/', $shop)) {
                    $store = Store::where('shop', $shop)->first();
                }
            }
        }

        if (! $store) {
            // Local/dev preview without Shopify credentials → show the demo dashboard
            if (app()->environment('local') && empty(config('shopify.api_key'))) {
                return redirect()->route('app', ['demo' => 1]);
            }
            return view('auth.login', ['shop' => $request->query('shop', '')]);
        }

        return view('app', [
            'store' => $store,
            'demo' => $demo,
            'apiKey' => config('shopify.api_key'),
            'host' => $request->query('host', ''),
            'shop' => $store->shop,
            'onboardingCompleted' => (bool) $store->onboarding_completed,
        ]);
    }
}
