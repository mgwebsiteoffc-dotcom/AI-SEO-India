<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pricing — AI Visibility for Shopify | Free, ₹999, ₹1,999, ₹4,999/month</title>
    <meta name="description" content="AI SEO for Indian D2C brands. Free AI Readiness Score. Grow ₹999/mo, Scale ₹1,999/mo, Agency ₹4,999/mo. Annual plans save 2 months. 3-day free trial.">
    @vite(['resources/css/app.css'])
    <style>.hero-bg { background: linear-gradient(160deg, #0f172a 0%, #111c34 60%, #0f172a 100%); }</style>
</head>
<body class="bg-slate-950 text-slate-100">
    <header class="border-b border-white/10 bg-slate-950/80 backdrop-blur sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-brand-500 flex items-center justify-center font-extrabold text-white">AI</div>
                <div class="font-bold">AI Visibility</div>
            </a>
            <a href="{{ route('scorecard') }}" class="btn-primary !bg-brand-500 !py-2 text-xs">Free AI Score</a>
        </div>
    </header>

    <section class="hero-bg">
        <div class="max-w-6xl mx-auto px-4 pt-16 pb-12 text-center">
            <h1 class="text-3xl md:text-5xl font-extrabold">Simple pricing, in ₹, for Indian D2C</h1>
            <p class="text-slate-400 mt-4 max-w-xl mx-auto">Pays for itself with 2–3 AI-referred orders a month. 3-day free trial on paid plans. Cancel anytime.</p>
        </div>
    </section>

    <section class="pb-20">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-4">
                @foreach ([
                    ['Free', '₹0', '/month', ['AI Readiness Score', '25 tracked queries/month', '1 store', 'AI SEO guides', 'Community support'], false],
                    ['Grow', '₹999', '/month', ['Everything in Free', '150 tracked queries/month', 'llms.txt + robots.txt automation', 'Schema builder', 'AI traffic attribution', 'Standard WhatsApp support'], true],
                    ['Scale', '₹1,999', '/month', ['Everything in Grow', '500 tracked queries/month', 'Smart Blogger + publish to blog', 'AI sentiment analysis', 'Competitor tracking (2)', 'Priority WhatsApp support'], false],
                    ['Agency', '₹4,999', '/month', ['Everything in Scale', '2000 tracked queries/month', 'Multi-store dashboard', 'Competitor tracking (10)', 'White-label client reports', 'Dedicated manager'], false],
                ] as [$name, $price, $per, $features, $popular])
                <div class="rounded-2xl bg-slate-900 border p-6 flex flex-col {{ $popular ? 'border-brand-500 ring-2 ring-brand-500/30' : 'border-white/10' }}">
                    <div class="flex items-center justify-between">
                        <span class="font-bold">{{ $name }}</span>
                        @if ($popular)<span class="badge-green">Most popular</span>@endif
                    </div>
                    <div class="mt-3"><span class="text-4xl font-extrabold">{{ $price }}</span><span class="text-xs text-slate-500">{{ $per }}</span></div>
                    <div class="text-[11px] text-slate-500 mt-1">or {{ $name === 'Free' ? '—' : ($price === '₹999' ? '₹9,999' : ($price === '₹1,999' ? '₹19,999' : '₹49,999')) }}/year (save ~17%)</div>
                    <ul class="mt-5 space-y-2.5 text-xs text-slate-400 flex-1">
                        @foreach ($features as $f)<li class="flex gap-2"><span class="text-emerald-400 font-bold">✓</span>{{ $f }}</li>@endforeach
                    </ul>
                    <a href="{{ route('scorecard') }}" class="mt-6 btn-primary w-full text-xs {{ $popular ? '!bg-brand-500' : '!bg-white/10 !border-white/20 hover:!bg-white/20' }}">Start free trial</a>
                </div>
                @endforeach
            </div>
            <p class="text-center text-xs text-slate-500 mt-8">All plans billed by Shopify in INR · 18% GST applies · 3-day free trial · Shopify App Store review required for listing</p>
        </div>
    </section>

    <footer class="border-t border-white/10 py-8 text-center text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-slate-300">← Back to home</a> · <a href="{{ route('blog') }}" class="hover:text-slate-300">Blog</a>
    </footer>
</body>
</html>
