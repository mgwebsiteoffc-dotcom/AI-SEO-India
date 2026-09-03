<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', [
        'title' => 'Install AI Visibility on your Shopify store',
        'description' => 'Install AI Visibility from the Shopify App Store (or your partner dashboard) and get found by ChatGPT, Gemini and Perplexity. Demo store install available without a Shopify account.',
    ])
</head>
<body class="marketing">
    @include('marketing.partials.header')

    <section class="hero-bg relative overflow-hidden">
        <div class="grid-pattern absolute inset-0"></div>
        <div class="relative max-w-3xl mx-auto px-4 pt-16 pb-12 text-center">
            <div class="pill animate-fade-up">2 minutes · no developer needed · ₹0 to start</div>
            <h1 class="font-display mt-5 text-4xl md:text-5xl font-extrabold leading-tight animate-fade-up delay-100">
                Install <span class="grad-text">AI Visibility</span> on your Shopify store
            </h1>
            <p class="text-slate-400 mt-5 animate-fade-up delay-200 leading-relaxed">
                Schema, robots.txt and llms.txt deploy automatically. The panel starts scoring and tracking the moment you approve.
            </p>
        </div>
    </section>

    <section class="pb-14">
        <div class="max-w-3xl mx-auto px-4">
            @if ($configured)
                {{-- Real Shopify credentials are configured → direct OAuth install --}}
                <div class="glass-strong rounded-3xl p-6 md:p-8">
                    <h2 class="font-display font-bold text-white text-lg">Install on your store</h2>
                    <p class="text-xs text-slate-400 mt-1 mb-5">Enter your myshopify.com store name — we will take you to Shopify’s approval screen.</p>
                    <form method="GET" action="{{ route('auth.install') }}" class="flex flex-col sm:flex-row gap-3">
                        <input type="text" name="shop" required placeholder="your-brand.myshopify.com"
                               pattern="^[a-z0-9\-]+\.myshopify\.com$" title="e.g. your-brand.myshopify.com"
                               class="input !bg-surface-950/60 flex-1">
                        <button class="btn-primary !bg-brand-500 hover:!bg-brand-400 whitespace-nowrap">Install app →</button>
                    </form>
                    <p class="text-[11px] text-slate-500 mt-4">After approving, you land back in your store admin with AI Visibility installed.</p>

                    <div class="mt-5 pt-5 border-t border-white/10">
                        <div class="text-xs font-semibold text-slate-300 mb-2">Using a custom distribution app?</div>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Install from <b>Shopify admin → Settings → Apps and sales channels → Develop apps → Install custom app</b>.
                            The app will redirect here automatically after OAuth.
                        </p>
                        <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
                            Make sure in your <b>Shopify Partner Dashboard → App setup</b>:<br>
                            • <b>App URL</b> = <code class="bg-slate-800 px-1 rounded text-brand-300">https://aivisibility.akestech.in.net/auth/install</code><br>
                            • <b>Allowed redirection URL(s)</b> = <code class="bg-slate-800 px-1 rounded text-brand-300">https://aivisibility.akestech.in.net/auth/callback</code>
                        </p>
                    </div>
                </div>
            @else
                {{-- No credentials → demo install + guidance (this sandbox state) --}}
                <div class="rounded-3xl border border-emerald-500/30 bg-gradient-to-br from-emerald-500/10 to-surface-800 p-6 md:p-8 text-center">
                    <div class="badge badge-green mb-4">No Shopify account needed</div>
                    <h2 class="font-display font-bold text-white text-xl">Try the demo install first</h2>
                    <p class="text-sm text-slate-400 mt-2 max-w-xl mx-auto leading-relaxed">
                        We are not connected to a live Shopify partner account in this preview, so a real OAuth install isn’t available here.
                        One click installs the <strong class="text-white">Aurelia Naturals</strong> demo store and opens the full panel with seeded data.
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-3 mt-6">
                        <a href="{{ route('auth.demo') }}" class="btn-primary !bg-emerald-500 hover:!bg-emerald-400 shadow-xl shadow-emerald-500/20">Install demo store →</a>
                        <a href="{{ route('app', ['demo' => 1]) }}" class="btn-secondary">Open live demo panel</a>
                    </div>
                </div>

                <div class="mt-6 rounded-3xl border border-brand-500/30 bg-gradient-to-br from-brand-600/10 to-surface-800 p-6 md:p-8">
                    <h2 class="font-display font-bold text-white text-lg">Install on your real Shopify store</h2>
                    <p class="text-sm text-slate-400 mt-1.5 leading-relaxed">
                        Live installation uses the standard Shopify OAuth flow (install URL →
                        approval in your store admin → webhooks registered → app opens).
                        The code path is ready and tested for graceful behaviour —
                        it just needs <code class="text-brand-300 text-xs">SHOPIFY_API_KEY</code> and
                        <code class="text-brand-300 text-xs">SHOPIFY_API_SECRET</code> from a Shopify Partner account.
                    </p>
                    <div class="grid sm:grid-cols-3 gap-3 mt-5 text-left">
                        <div class="rounded-xl bg-white/5 border border-white/10 p-4"><div class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">1. Add app</div><p class="text-xs text-slate-300 mt-1.5">Create an app in the Shopify Partner dashboard and set the app URL to this site.</p></div>
                        <div class="rounded-xl bg-white/5 border border-white/10 p-4"><div class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">2. Approve</div><p class="text-xs text-slate-300 mt-1.5">Merchant clicks install and approves scopes (products, orders, content, themes).</p></div>
                        <div class="rounded-xl bg-white/5 border border-white/10 p-4"><div class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">3. Automate</div><p class="text-xs text-slate-300 mt-1.5">Webhooks + theme extension deploy. Schema and llms.txt go live on the storefront.</p></div>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-4">Need a guided install? <a href="https://wa.me/919876543210" target="_blank" class="text-brand-400 hover:text-brand-300 font-semibold">Message us on WhatsApp</a> — we are a Shopify Partner and can walk you through it.</p>
                </div>
            @endif

            <div class="mt-6 grid sm:grid-cols-3 gap-3 text-center">
                <div class="glass rounded-2xl p-4"><div class="font-display text-lg font-extrabold text-white">2 min</div><div class="text-[11px] text-slate-500 mt-0.5">to install &amp; start</div></div>
                <div class="glass rounded-2xl p-4"><div class="font-display text-lg font-extrabold text-white">₹0</div><div class="text-[11px] text-slate-500 mt-0.5">free plan included</div></div>
                <div class="glass rounded-2xl p-4"><div class="font-display text-lg font-extrabold text-white">1 click</div><div class="text-[11px] text-slate-500 mt-0.5">schema + llms.txt deploy</div></div>
            </div>
        </div>
    </section>

    @include('marketing.partials.footer')
</body>
</html>
