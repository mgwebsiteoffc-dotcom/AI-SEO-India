<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI SEO Blog for Indian D2C — AI Visibility</title>
    <meta name="description" content="Guides on AI SEO, generative engine optimization and getting your Indian D2C brand recommended by ChatGPT, Gemini and Perplexity.">
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
        <div class="max-w-4xl mx-auto px-4 pt-14 pb-8">
            <h1 class="text-3xl md:text-4xl font-extrabold">The AI SEO blog for Indian D2C</h1>
            <p class="text-slate-400 mt-3">Practical guides on getting recommended by ChatGPT, Gemini &amp; Perplexity — with data, not hype.</p>
        </div>
    </section>

    <section class="pb-20">
        <div class="max-w-4xl mx-auto px-4 space-y-4">
            @forelse ($posts as $post)
            <a href="{{ route('blog.show', $post->slug) }}" class="block rounded-2xl bg-slate-900 border border-white/10 p-6 hover:border-brand-500/50 transition-colors">
                <div class="text-[11px] text-slate-500">{{ $post->published_at?->format('d M Y') }} · {{ $post->author }}</div>
                <h2 class="font-bold text-lg mt-1.5">{{ $post->title }}</h2>
                <p class="text-sm text-slate-400 mt-2">{{ $post->excerpt }}</p>
            </a>
            @empty
            <p class="text-slate-500 text-sm">Articles coming soon.</p>
            @endforelse
        </div>
    </section>

    <footer class="border-t border-white/10 py-8 text-center text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-slate-300">← Back to home</a> · <a href="{{ route('pricing') }}" class="hover:text-slate-300">Pricing</a>
    </footer>
</body>
</html>
