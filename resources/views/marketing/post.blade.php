<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', [
        'title' => $post->title,
        'description' => $post->meta_description,
    ])
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post->title,
        'datePublished' => $post->published_at?->toIso8601String(),
        'author' => ['@type' => 'Organization', 'name' => $post->author],
        'publisher' => ['@type' => 'Organization', 'name' => 'AI Visibility'],
    ], JSON_UNESCAPED_SLASHES) !!}</script>
</head>
<body class="marketing">
    @include('marketing.partials.header')

    <article class="max-w-3xl mx-auto px-4 py-14">
        <a href="{{ route('blog') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white transition-colors">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m6 6-6-6 6-6"/></svg>
            All articles
        </a>
        <div class="mt-6 flex items-center gap-3 text-[11px] text-slate-500">
            <span>{{ $post->published_at?->format('d M Y') }}</span>
            <span class="w-1 h-1 rounded-full bg-slate-600"></span>
            <span>{{ $post->author }}</span>
            <span class="w-1 h-1 rounded-full bg-slate-600"></span>
            <span>4 min read</span>
        </div>
        <h1 class="font-display text-3xl md:text-4xl font-extrabold mt-4 leading-tight text-white">{{ $post->title }}</h1>
        @if ($post->excerpt)
            <p class="text-lg text-slate-400 mt-4 leading-relaxed">{{ $post->excerpt }}</p>
        @endif
        <div class="prose-dark mt-8">{!! $post->body !!}</div>

        <div class="mt-14 rounded-3xl border border-brand-500/40 bg-gradient-to-br from-brand-600/15 to-surface-800 p-8 text-center">
            <div class="font-display font-bold text-xl text-white">Is your store ready for AI shopping?</div>
            <p class="text-sm text-slate-400 mt-2">Get your free AI Readiness Score — 30+ checks, one scorecard, one fix plan.</p>
            <a href="{{ route('scorecard') }}" class="btn-primary !bg-brand-500 hover:!bg-brand-400 mt-5">Get my free score</a>
        </div>
    </article>

    @include('marketing.partials.footer')
</body>
</html>
