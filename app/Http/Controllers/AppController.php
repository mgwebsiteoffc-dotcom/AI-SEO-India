<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Shopify\ShopifyService;
use Illuminate\Http\Request;

class AppController extends Controller
{
    /** GET / → embedded admin app shell (SPA). */
    public function index(Request $request)
    {
        $demoVal = (string) $request->query('demo', '');
        $demo = $demoVal !== '';
        $store = null;

        if ($demo) {
            // ?demo=1 → the standard demo store; ?demo=agency → the agency-tier demo
            $store = $demoVal === 'agency'
                ? Store::where('shop', 'demo-agency.myshopify.com')->first()
                : Store::where('is_demo', true)->first();
        } else {
            $store = ShopifyService::sessionStore();
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
            'demoVal' => $demoVal,
            'apiKey' => config('shopify.api_key'),
            'host' => $request->query('host', ''),
            'shop' => $store->shop,
        ]);
    }
}
