<header class="sticky top-0 z-50 border-b border-white/10 bg-surface-950/80 backdrop-blur-xl">
    <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500 to-sky-400 flex items-center justify-center font-extrabold text-white shadow-lg shadow-brand-500/30 group-hover:scale-105 transition-transform">AI</div>
            <div class="leading-tight">
                <div class="font-display font-bold text-white">AI Visibility</div>
                <div class="text-[11px] text-slate-400">AI SEO for Shopify · India</div>
            </div>
        </a>
        <nav class="hidden md:flex items-center gap-7 text-sm font-medium text-slate-300">
            <a href="{{ route('home') }}#how" class="hover:text-white transition-colors">How it works</a>
            <a href="{{ route('home') }}#features" class="hover:text-white transition-colors">Features</a>
            <a href="{{ route('pricing') }}" class="hover:text-white transition-colors">Pricing</a>
            <a href="{{ route('blog') }}" class="hover:text-white transition-colors">Blog</a>
        </nav>
        <div class="flex items-center gap-3">
            <a href="{{ route('app', ['demo' => 1]) }}" class="hidden sm:inline-flex text-xs font-semibold text-slate-300 hover:text-white transition-colors">Live demo →</a>
            <a href="{{ route('scorecard') }}" class="btn-primary !py-2 text-xs !bg-brand-500 hover:!bg-brand-400">Free AI Score</a>
        </div>
    </div>
</header>
