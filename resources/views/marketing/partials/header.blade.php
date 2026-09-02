<header class="sticky top-0 z-50 border-b border-white/10 bg-surface-950/80 backdrop-blur-xl">
    <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between gap-4">
        <div class="flex items-center gap-7 min-w-0">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 group shrink-0">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500 to-sky-400 flex items-center justify-center font-extrabold text-white shadow-lg shadow-brand-500/30 group-hover:scale-105 transition-transform">AI</div>
                <div class="leading-tight hidden xs:block">
                    <div class="font-display font-bold text-white leading-none">AI Visibility</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">AI SEO for Shopify · India</div>
                </div>
            </a>
            <nav class="hidden lg:flex items-center gap-5 text-sm font-medium text-slate-300">
                <a href="{{ route('features') }}" class="hover:text-white transition-colors">Features</a>
                <a href="{{ route('how-it-works') }}" class="hover:text-white transition-colors">How it works</a>
                <a href="{{ route('pricing') }}" class="hover:text-white transition-colors">Pricing</a>
                <a href="{{ route('faq') }}" class="hover:text-white transition-colors">FAQ</a>
                <a href="{{ route('blog') }}" class="hover:text-white transition-colors">Blog</a>
            </nav>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('demo-store') }}" class="hidden md:inline-flex text-xs font-semibold text-slate-300 hover:text-white transition-colors">Storefront preview</a>
            <a href="{{ route('app', ['demo' => 1]) }}" class="hidden sm:inline-flex text-xs font-semibold text-slate-300 hover:text-white transition-colors">Live demo →</a>
            <a href="{{ route('install') }}" class="btn-primary !py-2 text-xs !bg-emerald-500 hover:!bg-emerald-400 !shadow-emerald-500/20">Install app</a>
            <a href="{{ route('scorecard') }}" class="hidden md:inline-flex btn-primary !py-2 text-xs !bg-brand-500 hover:!bg-brand-400">Free AI Score</a>
        </div>
    </div>
    {{-- Mobile row: essential links --}}
    <div class="lg:hidden border-t border-white/5">
        <div class="max-w-6xl mx-auto px-4 h-10 flex items-center justify-center gap-6 text-xs font-medium text-slate-300 overflow-x-auto">
            <a href="{{ route('features') }}" class="hover:text-white whitespace-nowrap">Features</a>
            <a href="{{ route('how-it-works') }}" class="hover:text-white whitespace-nowrap">How it works</a>
            <a href="{{ route('pricing') }}" class="hover:text-white whitespace-nowrap">Pricing</a>
            <a href="{{ route('faq') }}" class="hover:text-white whitespace-nowrap">FAQ</a>
            <a href="{{ route('blog') }}" class="hover:text-white whitespace-nowrap">Blog</a>
            <a href="{{ route('scorecard') }}" class="hover:text-brand-300 whitespace-nowrap font-semibold">Free AI Score</a>
        </div>
    </div>
</header>
