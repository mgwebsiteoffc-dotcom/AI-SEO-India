<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Visibility for Shopify — Get found by ChatGPT, Gemini &amp; Perplexity</title>
    <meta name="description" content="Install AI Visibility — the AI SEO app for Indian D2C brands on Shopify. AI Readiness Score, visibility tracking, schema and Smart Blogger. From ₹999/month.">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    @php($viteCss = \App\Support\Vite::css())
    @if ($viteCss)<link rel="stylesheet" href="{{ $viteCss }}">@endif
    <style>
        body { background: radial-gradient(900px 500px at 20% -10%, rgba(10,132,255,.25) 0%, transparent 60%), linear-gradient(160deg, #070d1a 0%, #0b1220 55%, #101a2e 100%); min-height: 100vh; }
        .card { background: rgba(255,255,255,0.98); border-radius: 22px; box-shadow: 0 30px 80px rgba(2,6,23,.5); }
    </style>
</head>
<body class="flex items-center justify-center p-6">
    <div class="card w-full max-w-md p-8">
        <div class="mb-6">
            <div class="flex items-center gap-2 mb-3">
                <svg width="34" height="34" viewBox="0 0 48 48" fill="none"><rect width="48" height="48" rx="12" fill="#0a84ff"/><path d="M24 10c-7.7 0-14 5.8-14 13 0 4 1.9 7.5 4.9 9.9-.3 2.9-1.4 5.3-3.2 6.7 2.7.4 5.1-.2 7.2-1.6 1.6.5 3.3.8 5.1.8 7.7 0 14-5.8 14-13S31.7 10 24 10z" fill="#fff"/><circle cx="19" cy="22.5" r="1.8" fill="#0a84ff"/><circle cx="24" cy="22.5" r="1.8" fill="#0a84ff"/><circle cx="29" cy="22.5" r="1.8" fill="#0a84ff"/></svg>
                <div>
                    <div class="text-lg font-extrabold text-slate-900 leading-tight">AI Visibility</div>
                    <div class="text-xs font-medium text-slate-500">AI SEO for Shopify · India</div>
                </div>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900">Get recommended by ChatGPT, Gemini &amp; Perplexity</h1>
            <p class="mt-2 text-sm text-slate-600">Indians ask AI what to buy — make sure your brand is the answer. Free AI Readiness Score. Plans from ₹999/mo.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-xl bg-red-50 border border-red-200 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <form method="GET" action="{{ url('/auth/install') }}" class="space-y-3">
            <input type="text" name="shop" required placeholder="your-brand.myshopify.com"
                   class="input" value="{{ old('shop', $shop) }}"
                   pattern="^[a-z0-9\-]+\.myshopify\.com$" title="e.g. mybrand.myshopify.com">
            <button type="submit" class="btn-primary w-full py-3">Install free — 3-day trial</button>
        </form>

        <a href="{{ url('/auth/demo') }}" class="mt-3 block text-center text-sm font-semibold text-brand-600 hover:underline">Preview the demo dashboard →</a>

        <div class="mt-6 grid grid-cols-3 gap-2 text-center">
            <div class="rounded-xl bg-slate-50 p-2"><div class="text-lg font-bold text-slate-900">100M+</div><div class="text-[11px] text-slate-500">Indians on ChatGPT weekly</div></div>
            <div class="rounded-xl bg-slate-50 p-2"><div class="text-lg font-bold text-slate-900">9×</div><div class="text-[11px] text-slate-500">AI traffic converts vs Google</div></div>
            <div class="rounded-xl bg-slate-50 p-2"><div class="text-lg font-bold text-slate-900">₹999</div><div class="text-[11px] text-slate-500">from / month</div></div>
        </div>
    </div>
</body>
</html>
