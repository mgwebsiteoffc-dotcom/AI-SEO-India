<?php

return [
    'api_key' => env('SHOPIFY_API_KEY', ''),
    'api_secret' => env('SHOPIFY_API_SECRET', ''),
    'scopes' => array_values(array_filter(explode(',', env(
        'SHOPIFY_APP_SCOPES',
        'read_products,write_products,read_orders,read_themes,write_themes'
    )))),
    'api_version' => env('SHOPIFY_API_VERSION', '2025-04'),
    'host' => env('SHOPIFY_APP_HOST_NAME', '127.0.0.1:8123'),
    'proxy_prefix' => env('SHOPIFY_APP_PROXY_PREFIX', 'apps/ai-visibility'),
];
