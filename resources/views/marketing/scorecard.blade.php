<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Free AI Readiness Score — Is your brand ready for ChatGPT, Gemini & Perplexity?</title>
    <meta name="description" content="Get your free AI Readiness Scorecard: 30+ checks on crawlability, schema, content and brand signals. See exactly what your Shopify store needs to be recommended by AI.">
    @vite(['resources/css/app.css'])
    <style>.hero-bg { background: radial-gradient(900px 400px at 50% -10%, #0a84ff22 0%, transparent 60%), linear-gradient(160deg, #0f172a 0%, #111c34 60%, #0f172a 100%); }</style>
</head>
<body class="bg-slate-950 text-slate-100">
    <header class="border-b border-white/10 bg-slate-950/80 backdrop-blur sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-brand-500 flex items-center justify-center font-extrabold text-white">AI</div>
                <div class="font-bold">AI Visibility</div>
            </a>
            <a href="{{ route('app', ['demo' => 1]) }}" class="text-xs font-semibold text-slate-300 hover:text-white">Live demo →</a>
        </div>
    </header>

    <section class="hero-bg">
        <div class="max-w-3xl mx-auto px-4 pt-14 pb-10 text-center">
            <h1 class="text-3xl md:text-4xl font-extrabold">Your free <span class="grad-text" style="background:linear-gradient(90deg,#60a5fa,#38bdf8);-webkit-background-clip:text;background-clip:text;color:transparent;">AI Readiness Score</span></h1>
            <p class="text-slate-400 mt-3">30+ evidence-based checks — crawlability, schema, content, brand &amp; speed — weighted to a 0–100 score. Enter your email and we'll run your storefront and send the scorecard with a step-by-step fix plan.</p>
        </div>
    </section>

    <section class="pb-14">
        <div class="max-w-3xl mx-auto px-4">
            <div class="rounded-3xl bg-slate-900 border border-white/10 p-6 md:p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <div class="text-xs text-slate-500 uppercase tracking-wider">Sample scorecard</div>
                        <div class="font-bold mt-1">Aurelia Naturals (demo)</div>
                    </div>
                    <div class="text-right">
                        <div class="text-4xl font-extrabold grad-text" style="background:linear-gradient(90deg,#60a5fa,#38bdf8);-webkit-background-clip:text;background-clip:text;color:transparent;">
                            {{ $demoScore['total'] ?? 74 }}<span class="text-base text-slate-500">/100</span>
                        </div>
                        <div class="text-xs text-slate-500">Grade {{ $demoScore['grade'] ?? 'B' }}</div>
                    </div>
                </div>
                @php($cats = $demoScore['categories'] ?? ['crawlability' => 92, 'schema' => 55, 'content' => 78, 'brand' => 85, 'speed' => 100])
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                    @foreach ($cats as $k => $v)
                    <div class="text-center">
                        <div class="text-[11px] text-slate-500 capitalize">{{ $k }}</div>
                        <div class="text-xl font-bold {{ $v >= 80 ? 'text-emerald-400' : ($v >= 60 ? 'text-amber-400' : 'text-red-400') }}">{{ $v }}</div>
                    </div>
                    @endforeach
                </div>
                <div class="space-y-2 text-xs text-slate-400">
                    <div class="flex gap-2 items-start"><span class="badge-amber shrink-0 mt-0.5">Fix</span> No Product schema on product pages — enable Schema Builder (one click)</div>
                    <div class="flex gap-2 items-start"><span class="badge-green shrink-0 mt-0.5">Good</span> AI crawlers allowed in robots.txt</div>
                    <div class="flex gap-2 items-start"><span class="badge-green shrink-0 mt-0.5">Good</span> Sitemap healthy (142 URLs)</div>
                </div>
            </div>

            <div class="rounded-3xl bg-slate-900 border border-brand-500/40 p-6 md:p-8 mt-6">
                <h2 class="font-extrabold text-lg">Get your real score — it takes 30 seconds</h2>
                <p class="text-xs text-slate-400 mt-1 mb-5">We'll crawl your storefront (Shopify or any ecommerce site) and email the scorecard + fix plan.</p>
                @if (session('status'))
                    <div class="rounded-xl bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-sm p-4 mb-4">{{ session('status') }}</div>
                @endif
                <form method="POST" action="{{ route('lead') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="source" value="scorecard">
                    <input type="email" name="email" required placeholder="you@brand.com" class="input !bg-slate-950 !border-white/15 !text-white" value="{{ old('email') }}">
                    <input type="text" name="brand" placeholder="Brand name (optional)" class="input !bg-slate-950 !border-white/15 !text-white" value="{{ old('brand') }}">
                    <input type="text" name="shop_url" placeholder="your-brand.myshopify.com or yourbrand.in (optional)" class="input !bg-slate-950 !border-white/15 !text-white" value="{{ old('shop_url') }}">
                    <button type="submit" class="btn-primary !bg-brand-500 w-full py-3">Send my free AI Readiness Score</button>
                </form>
                <p class="text-[11px] text-slate-500 mt-3">No spam. One scorecard email, then an occasional 2-line update. Unsubscribe anytime.</p>
            </div>
        </div>
    </section>

    <footer class="border-t border-white/10 py-8 text-center text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-slate-300">← Back to home</a> · <a href="{{ route('pricing') }}" class="hover:text-slate-300">Pricing</a>
    </footer>
</body>
</html>
