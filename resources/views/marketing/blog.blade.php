<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', [
        'title' => 'AI SEO Blog for Indian D2C',
        'description' => 'Guides on AI SEO, generative engine optimization and getting your Indian D2C brand recommended by ChatGPT, Gemini and Perplexity.',
    ])
</head>
<body class="marketing">
    @include('marketing.partials.header')

    <section class="hero-bg relative overflow-hidden">
        <div class="grid-pattern absolute inset-0"></div>
        <div class="relative max-w-4xl mx-auto px-4 pt-16 pb-10">
            <div class="pill">The AI SEO blog</div>
            <h1 class="font-display mt-5 text-4xl md:text-5xl font-extrabold leading-tight">The <span class="grad-text">AI SEO blog</span> for Indian D2C</h1>
            <p class="text-slate-400 mt-4 max-w-xl">Practical guides on getting recommended by ChatGPT, Gemini &amp; Perplexity — with data, not hype.</p>
        </div>
    </section>

    <section class="pb-16">
        <div class="max-w-4xl mx-auto px-4 space-y-4">
            @forelse ($posts as $post)
            <a href="{{ route('blog.show', $post->slug) }}" class="card-dark card-dark-hover block p-6">
                <div class="flex items-center gap-3 text-[11px] text-slate-500">
                    <span>{{ $post->published_at?->format('d M Y') }}</span>
                    <span class="w-1 h-1 rounded-full bg-slate-600"></span>
                    <span>{{ $post->author }}</span>
                </div>
                <h2 class="font-display font-bold text-lg text-white mt-2 hover:text-brand-400 transition-colors">{{ $post->title }}</h2>
                <p class="text-sm text-slate-400 mt-2 leading-relaxed">{{ $post->excerpt }}</p>
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-400 mt-4">
                    Read article
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </span>
            </a>
            @empty
            <p class="text-slate-500 text-sm py-10 text-center">Articles coming soon.</p>
            @endforelse
        </div>
    </section>

    @include('marketing.partials.footer')
</body>
</html>
