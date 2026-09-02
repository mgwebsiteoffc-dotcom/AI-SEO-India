<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', [
        'title' => 'Free AI Readiness Score — Is your brand ready for ChatGPT, Gemini & Perplexity?',
        'description' => 'Get your free AI Readiness Scorecard: 30+ checks on crawlability, schema, content and brand signals. See exactly what your Shopify store needs to be recommended by AI.',
    ])
</head>
<body class="marketing">
    @include('marketing.partials.header')

    <section class="hero-bg relative overflow-hidden">
        <div class="grid-pattern absolute inset-0"></div>
        <div class="relative max-w-3xl mx-auto px-4 pt-16 pb-10 text-center">
            <div class="pill animate-fade-up">Free · takes 30 seconds · no credit card</div>
            <h1 class="font-display mt-5 text-4xl md:text-5xl font-extrabold leading-tight animate-fade-up delay-100">
                Your free <span class="grad-text">AI Readiness Score</span>
            </h1>
            <p class="text-slate-400 mt-5 animate-fade-up delay-200 leading-relaxed">
                30+ evidence-based checks — crawlability, schema, content, brand &amp; speed — weighted to a 0–100 score.
                Enter your email and we'll run your storefront and send the scorecard with a step-by-step fix plan.
            </p>
        </div>
    </section>

    <section class="pb-16">
        <div class="max-w-3xl mx-auto px-4">
            <!-- Sample scorecard -->
            <div class="glass-strong rounded-3xl p-6 md:p-8 animate-fade-up">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <div class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold">Sample scorecard</div>
                        <div class="font-display font-bold text-white mt-1">Aurelia Naturals (demo)</div>
                    </div>
                    <div class="text-right">
                        <div class="font-display text-5xl font-extrabold grad-text">{{ $demoScore['total'] ?? 74 }}<span class="text-base text-slate-500">/100</span></div>
                        <div class="text-xs text-slate-500 mt-0.5">Grade {{ $demoScore['grade'] ?? 'B' }}</div>
                    </div>
                </div>
                @php($cats = $demoScore['categories'] ?? ['crawlability' => 92, 'schema' => 55, 'content' => 78, 'brand' => 85, 'speed' => 100])
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
                    @foreach ($cats as $k => $v)
                    <div class="rounded-xl bg-white/5 border border-white/10 p-3 text-center">
                        <div class="text-[10px] text-slate-500 uppercase tracking-wide">{{ $k }}</div>
                        <div class="text-xl font-extrabold mt-1 {{ $v >= 80 ? 'text-emerald-400' : ($v >= 60 ? 'text-amber-400' : 'text-red-400') }}">{{ $v }}</div>
                        <div class="relative h-1 mt-2 rounded-full bg-white/10 overflow-hidden">
                            <div class="absolute inset-y-0 left-0 rounded-full {{ $v >= 80 ? 'bg-emerald-400' : ($v >= 60 ? 'bg-amber-400' : 'bg-red-400') }}" style="width: {{ $v }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="space-y-2 text-xs text-slate-400">
                    <div class="flex gap-2.5 items-start rounded-xl border border-amber-400/30 bg-amber-400/10 p-3">
                        <span class="badge-amber shrink-0 mt-0.5">Fix</span> No Product schema on product pages — enable Schema Builder (one click)
                    </div>
                    <div class="flex gap-2.5 items-start rounded-xl border border-emerald-400/30 bg-emerald-400/10 p-3">
                        <span class="badge-green shrink-0 mt-0.5">Good</span> AI crawlers allowed in robots.txt
                    </div>
                    <div class="flex gap-2.5 items-start rounded-xl border border-emerald-400/30 bg-emerald-400/10 p-3">
                        <span class="badge-green shrink-0 mt-0.5">Good</span> Sitemap healthy (142 URLs)
                    </div>
                </div>
            </div>

            <!-- Lead capture -->
            <div class="relative mt-6 rounded-3xl border border-brand-500/40 bg-gradient-to-br from-brand-600/15 to-surface-800 p-6 md:p-8">
                <h2 class="font-display font-extrabold text-xl text-white">Get your real score — it takes 30 seconds</h2>
                <p class="text-xs text-slate-400 mt-1.5 mb-6">We'll crawl your storefront (Shopify or any ecommerce site) and email the scorecard + fix plan.</p>
                @if (session('status'))
                    <div class="rounded-xl bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-sm p-4 mb-5">{{ session('status') }}</div>
                @endif
                <form method="POST" action="{{ route('lead') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="source" value="scorecard">
                    <input type="email" name="email" required placeholder="you@brand.com" class="input !bg-surface-950/60 !border-white/15 !text-white !placeholder-slate-500" value="{{ old('email') }}">
                    <div class="grid sm:grid-cols-2 gap-3">
                        <input type="text" name="brand" placeholder="Brand name (optional)" class="input !bg-surface-950/60 !border-white/15 !text-white !placeholder-slate-500" value="{{ old('brand') }}">
                        <input type="text" name="shop_url" placeholder="your-brand.myshopify.com (optional)" class="input !bg-surface-950/60 !border-white/15 !text-white !placeholder-slate-500" value="{{ old('shop_url') }}">
                    </div>
                    <button type="submit" class="btn-primary !bg-brand-500 hover:!bg-brand-400 w-full py-3.5 text-sm shadow-xl shadow-brand-500/20">Send my free AI Readiness Score →</button>
                </form>
                <p class="text-[11px] text-slate-500 mt-3.5">No spam. One scorecard email, then an occasional 2-line update. Unsubscribe anytime.</p>
            </div>
        </div>
    </section>

    @include('marketing.partials.footer')
</body>
</html>
