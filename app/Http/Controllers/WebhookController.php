<?php

namespace App\Http\Controllers;

use App\Models\WebhookCall;
use App\Webhooks\AppUninstalledHandler;
use App\Webhooks\OrdersPaidHandler;
use App\Webhooks\ProductsUpdateHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Shopify\Webhooks\Registry;
use Shopify\Webhooks\Topics;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            \App\Shopify\ShopifyService::init();

            Registry::addHandler(Topics::APP_UNINSTALLED, new AppUninstalledHandler());
            Registry::addHandler(Topics::PRODUCTS_UPDATE, new ProductsUpdateHandler());
            Registry::addHandler(Topics::ORDERS_PAID, new OrdersPaidHandler());

            $rawHeaders = collect($request->headers->all())
                ->map(fn ($v) => is_array($v) ? (string) end($v) : (string) $v)
                ->all();

            $status = Registry::process($rawHeaders, $request->getContent());

            if ($status->isSuccess()) {
                $topic = str_replace('.', '/', (string) $request->header('X-Shopify-Topic', 'unknown'));
                WebhookCall::create([
                    'topic' => $topic,
                    'shop' => $request->header('X-Shopify-Shop-Domain'),
                    'status' => 'processed',
                ]);
                return response('', 200);
            }
            Log::warning('Webhook processing failed: '.$status->getErrorMessage());
            return response('', 500);
        } catch (\Throwable $e) {
            Log::warning('Webhook exception: '.get_class($e).': '.$e->getMessage());
            WebhookCall::create(['topic' => 'unknown', 'status' => 'failed']);
            return response('', 500);
        }
    }
}
