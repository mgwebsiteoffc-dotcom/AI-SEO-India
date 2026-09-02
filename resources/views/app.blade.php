<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Visibility — AI SEO for Shopify</title>
    <meta name="shopify-api-key" content="{{ $apiKey }}">
    <meta name="shopify-shop-origin" content="{{ $shop }}">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app"
         data-demo="{{ $demo ? '1' : '0' }}"
         data-api-key="{{ $apiKey }}"
         data-host="{{ $host }}"
         data-shop="{{ $shop }}"
         data-brand="{{ $store->brand_name ?? '' }}"
         data-domain="{{ $store->hostname() }}"
         data-plan="{{ $store->plan }}"></div>
    @if ($demo)
        <div style="position:fixed;bottom:0;left:0;right:0;z-index:50;background:#0f172a;color:#fbbf24;font:600 12px/1.4 system-ui;padding:6px 14px;text-align:center">
            DEMO MODE — this preview runs on seeded data. Install from the Shopify App Store for live data.
        </div>
    @endif
</body>
</html>
