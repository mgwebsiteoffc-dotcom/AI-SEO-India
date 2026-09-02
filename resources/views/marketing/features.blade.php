<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', [
        'title' => 'Features — Everything an Indian D2C brand needs to get recommended by AI',
        'description' => 'AI Readiness Score, visibility tracker, Smart Blogger, schema builder, llms.txt, attribution and INR billing — every AI-visibility feature for Shopify stores.',
    ])
</head>
<body class="marketing">
    @include('marketing.partials.header')

    <section class="hero-bg relative overflow-hidden">
        <div class="grid-pattern absolute inset-0"></div>
        <div class="relative max-w-4xl mx-auto px-4 pt-16 pb-12 text-center">
            <div class="pill animate-fade-up">Everything from the plan — built in</div>
            <h1 class="font-display mt-5 text-4xl md:text-5xl font-extrabold leading-tight animate-fade-up delay-100">
                A full <span class="grad-text">AI visibility stack</span> for Indian D2C
            </h1>
            <p class="text-slate-400 mt-5 max-w-2xl mx-auto animate-fade-up delay-200 leading-relaxed">
                Not a black box. Every feature measures or fixes a real signal — so you always know what to do next.
            </p>
        </div>
    </section>

    <section class="py-14">
        <div class="max-w-6xl mx-auto px-4 grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            @php
                $features = [
                    ['AI Readiness Score', 'score', '30+ evidence-based checks weighted to a shareable 0–100 score with a prioritised fix list. Re-run any time to watch your grade climb.'],
                    ['AI Visibility Tracker', 'tracker', 'Daily 6 AM IST snapshots of how often ChatGPT, Gemini and Perplexity mention you for real shopping queries — with competitor comparison built in.'],
                    ['Competitor Watch', 'tracker', 'Add rival stores (Minimalist, Plum, Mamaearth…) and see their mention rate next to yours. Know exactly whose content is winning the AI answer.'],
                    ['Smart Blogger', 'content', 'AI-written comparison & FAQ articles in Indian English or Hinglish, built from your real catalog, published to your Shopify blog with one click.'],
                    ['AI Sentiment Analysis', 'content', 'How do AI answers currently feel about your brand? Score, themes and one concrete recommendation.'],
                    ['Schema Builder', 'schema', 'Organization, Product and FAQ JSON-LD with live ₹ pricing — injected natively via the theme app extension. No developer needed.'],
                    ['llms.txt + robots.txt', 'llms', 'AI reading list and crawler rules for GPTBot, ClaudeBot, PerplexityBot, Google-Extended & more — served straight from your store\'s app proxy.'],
                    ['AI Traffic Attribution', 'attribution', 'See orders that came from ChatGPT, Gemini and Perplexity — revenue, AOV and channel breakdown from your orders/paid webhook.'],
                    ['GA4 Data API', 'attribution', 'AI-sourced sessions, transactions and purchase revenue straight from Google Analytics 4 — service-account setup, no OAuth popup.'],
                    ['INR Billing', 'billing', 'Free, Grow ₹999, Scale ₹1,999 and Agency ₹4,999. Monthly or annual (save ~17%). GST-ready, India-first pricing.'],
                    ['WhatsApp + language', 'settings', 'Set your WhatsApp number and language (English / Hinglish / हिंदी) — support and Smart Blogger content follow your preference.'],
                    ['Actionable weekly loop', 'dashboard', 'Score → Fix → Track → Publish. One loop, every week, measured on your dashboard.'],
                ];
            @endphp
            @foreach ($features as [$name, $tab, $desc])
                <a href="{{ route('app', ['demo' => 1]) }}" class="card-dark group rounded-2xl p-6 flex flex-col gap-3">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-brand-500/15 border border-brand-500/25 flex items-center justify-center text-brand-300 font-bold text-sm group-hover:bg-brand-500/25 transition-colors">AI</span>
                        <h3 class="font-display font-bold text-white leading-tight">{{ $name }}</h3>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed flex-1">{{ $desc }}</p>
                    <span class="text-[11px] font-semibold text-brand-400 group-hover:text-brand-300 transition-colors">See it in the demo →</span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="pb-16">
        <div class="max-w-3xl mx-auto px-4 text-center">
            <h2 class="font-display text-2xl font-extrabold text-white">See the whole stack on live demo data</h2>
            <p class="text-sm text-slate-400 mt-3">The demo store (Aurelia Naturals) has real scores, snapshots, generated posts and orders — so every feature has something to show.</p>
            <div class="flex flex-wrap items-center justify-center gap-3 mt-7">
                <a href="{{ route('app', ['demo' => 1]) }}" class="btn-primary">Open live demo →</a>
                <a href="{{ route('install') }}" class="btn-secondary">Install on Shopify</a>
                <a href="{{ route('scorecard') }}" class="btn-secondary">Free AI Score</a>
            </div>
        </div>
    </section>

    @include('marketing.partials.footer')
</body>
</html>
