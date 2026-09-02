<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $cat = $post->category;
        $catDesc = $post->meta_description ?: $post->excerpt ?: $post->title;
    @endphp
    @include('marketing.partials.head', [
        'title' => $post->seoTitle(),
        'description' => $catDesc,
    ])
    @if ($post->meta_keywords)
        <meta name="keywords" content="{{ $post->meta_keywords }}">
    @endif
    <link rel="canonical" href="{{ route('blog.show', $post->slug) }}">
    <meta property="article:published_time" content="{{ $post->published_at?->toIso8601String() }}">
    @if ($post->updated_at)<meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">@endif
    @if ($cat)<meta property="article:section" content="{{ $cat->name }}">@endif
    @php
        $graph = [
            [
                '@type' => 'BlogPosting',
                '@id' => route('blog.show', $post->slug).'#article',
                'headline' => $post->title,
                'description' => $catDesc,
                'image' => url('/og-image.svg'),
                'datePublished' => $post->published_at?->toIso8601String(),
                'dateModified' => ($post->updated_at ?: $post->published_at)?->toIso8601String(),
                'author' => ['@type' => 'Organization', 'name' => $post->author],
                'publisher' => ['@type' => 'Organization', 'name' => 'AI Visibility', 'logo' => ['@type' => 'ImageObject', 'url' => url('/favicon.svg')]],
                'mainEntityOfPage' => route('blog.show', $post->slug),
                'keywords' => $post->meta_keywords ?: null,
                'articleSection' => $cat?->name,
                'wordCount' => str_word_count(strip_tags((string) $post->body)),
            ],
            [
                '@type' => 'BreadcrumbList',
                '@id' => route('blog.show', $post->slug).'#breadcrumb',
                'itemListElement' => array_values(array_filter([
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blog')],
                    $cat ? ['@type' => 'ListItem', 'position' => 3, 'name' => $cat->name, 'item' => route('blog.category', $cat->slug)] : null,
                ])),
            ],
        ];
        if ($post->faqs) {
            $graph[] = [
                '@type' => 'FAQPage',
                '@id' => route('blog.show', $post->slug).'#faq',
                'mainEntity' => collect($post->faqs)->map(fn ($f) => [
                    '@type' => 'Question',
                    'name' => $f['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
                ])->values()->all(),
            ];
        }
    @endphp
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES) !!}</script>
</head>
<body class="marketing">
    @include('marketing.partials.header')

    <article class="max-w-3xl mx-auto px-4 py-14">
        <a href="{{ $cat ? route('blog.category', $cat->slug) : route('blog') }}"
           class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white transition-colors">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m6 6-6-6 6-6"/></svg>
            @if ($cat) {{ $cat->name }} articles @else All articles @endif
        </a>
        <div class="mt-6 flex items-center gap-3 text-[11px] text-slate-500 flex-wrap">
            <span>{{ $post->published_at?->format('d M Y') }}</span>
            <span class="w-1 h-1 rounded-full bg-slate-600"></span>
            <span>{{ $post->author }}</span>
            <span class="w-1 h-1 rounded-full bg-slate-600"></span>
            <span>{{ $post->readingMinutes() }} min read</span>
        </div>
        <h1 class="font-display text-3xl md:text-4xl font-extrabold mt-4 leading-tight text-white">{{ $post->title }}</h1>

        @if ($post->keywords())
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($post->keywords() as $kw)
                    <span class="rounded-full border border-white/10 bg-white/[0.03] text-slate-400 text-[11px] px-3 py-1">{{ $kw }}</span>
                @endforeach
            </div>
        @endif

        <div class="prose-dark mt-8">{!! $post->body !!}</div>

        @if ($post->faqs)
            <div class="mt-10">
                <h2 class="font-display font-bold text-white text-xl">Frequently asked questions</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($post->faqs as $i => $f)
                        <details class="rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-4 group" {{ $i === 0 ? 'open' : '' }}>
                            <summary class="text-sm font-semibold text-white cursor-pointer list-none flex items-center justify-between gap-4">
                                {{ $f['q'] }}
                                <span class="text-slate-500 group-open:rotate-45 transition-transform text-lg leading-none shrink-0">+</span>
                            </summary>
                            <p class="text-sm text-slate-400 mt-3 leading-relaxed">{!! nl2br(e($f['a'])) !!}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-14 rounded-3xl border border-brand-500/40 bg-gradient-to-br from-brand-600/15 to-surface-800 p-8 text-center">
            <div class="font-display font-bold text-xl text-white">Is your store ready for AI shopping?</div>
            <p class="text-sm text-slate-400 mt-2">Get your free AI Readiness Score — 30+ checks, one scorecard, one fix plan.</p>
            <a href="{{ route('scorecard') }}" class="btn-primary !bg-brand-500 hover:!bg-brand-400 mt-5">Get my free score</a>
        </div>
    </article>

    @include('marketing.partials.footer')
</body>
</html>
