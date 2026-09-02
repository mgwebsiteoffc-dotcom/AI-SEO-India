<footer class="border-t border-white/10 mt-20">
    <div class="max-w-6xl mx-auto px-4 py-14 grid gap-10 md:grid-cols-4">
        <div class="md:col-span-2 max-w-sm">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-sky-400 flex items-center justify-center font-extrabold text-white">AI</div>
                <div class="font-bold text-white">AI Visibility</div>
            </div>
            <p class="mt-4 text-xs leading-relaxed text-slate-500">
                The honest AI SEO platform for Indian D2C brands on Shopify — AI Readiness Score, visibility tracking,
                schema, and content that gets your brand recommended by ChatGPT, Gemini &amp; Perplexity.
            </p>
            <a href="https://wa.me/919876543210" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 mt-5 text-xs font-semibold text-emerald-400 hover:text-emerald-300 transition-colors">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2zm0 18.15a8.2 8.2 0 0 1-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.2 8.2 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24 2.2 0 4.27.86 5.82 2.42a8.18 8.18 0 0 1 2.41 5.83c0 4.54-3.7 8.23-8.23 8.23zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.12-.17.25-.64.81-.78.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.12-.14.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.22.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.14-1.18-.06-.1-.22-.16-.47-.28z"/></svg>
                WhatsApp support · 91 98765 43210
            </a>
        </div>
        <div class="text-xs">
            <div class="font-semibold text-slate-300 mb-3 uppercase tracking-wider text-[10px] text-slate-500">Product</div>
            <div class="space-y-2.5 text-slate-500">
                <a href="{{ route('features') }}" class="block hover:text-slate-300 transition-colors">Features</a>
                <a href="{{ route('how-it-works') }}" class="block hover:text-slate-300 transition-colors">How it works</a>
                <a href="{{ route('pricing') }}" class="block hover:text-slate-300 transition-colors">Pricing</a>
                <a href="{{ route('install') }}" class="block hover:text-slate-300 transition-colors">Install on Shopify</a>
                <a href="{{ route('app', ['demo' => 1]) }}" class="block hover:text-slate-300 transition-colors">Live demo</a>
                <a href="{{ route('scorecard') }}" class="block hover:text-slate-300 transition-colors">Free AI Score</a>
            </div>
        </div>
        <div class="text-xs">
            <div class="font-semibold text-slate-300 mb-3 uppercase tracking-wider text-[10px] text-slate-500">Learn</div>
            <div class="space-y-2.5 text-slate-500">
                <a href="{{ route('blog') }}" class="block hover:text-slate-300 transition-colors">Blog</a>
                <a href="{{ route('faq') }}" class="block hover:text-slate-300 transition-colors">FAQ</a>
                <a href="{{ route('demo-store') }}" class="block hover:text-slate-300 transition-colors">Demo storefront</a>
                <a href="{{ route('privacy') }}" class="block hover:text-slate-300 transition-colors">Privacy Policy</a>
                <a href="{{ route('terms') }}" class="block hover:text-slate-300 transition-colors">Terms of Service</a>
            </div>
        </div>
    </div>
    <div class="border-t border-white/5">
        <div class="max-w-6xl mx-auto px-4 py-5 flex flex-col md:flex-row items-center justify-between gap-2 text-[11px] text-slate-600">
            <div>© {{ date('Y') }} AI Visibility · Built for Indian D2C brands · GST invoicing · Made in India 🇮🇳</div>
            <div class="flex items-center gap-4">
                <span>Honest AI SEO — nobody can guarantee AI rankings</span>
                <span class="hidden md:inline text-slate-700">·</span>
                <a href="{{ route('privacy') }}" class="hover:text-slate-400">Privacy</a>
                <a href="{{ route('terms') }}" class="hover:text-slate-400">Terms</a>
                <span class="hidden md:inline text-slate-700">·</span>
                <a href="{{ route('admin.overview') }}" class="hover:text-slate-400">Owner login</a>
            </div>
        </div>
    </div>
</footer>
