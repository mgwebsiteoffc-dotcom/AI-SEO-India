<!DOCTYPE html>
<html lang="en">
<head>
    @if ($category)
        @php
            $catDesc = $category->meta_description ?: 'Articles about '.strtolower($category->name).' — AI-visibility advice for Indian D2C brands.';
        @endphp
        @include('marketing.partials.head', [
            'title' => $category->name.' — AI SEO Blog',
            'description' => $catDesc,
            'ogImage' => url('/og-image.svg'),
        ])
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'CollectionPage',
                    '@id' => route('blog.category', $category->slug).'#page',
                    'name' => $category->name.' articles',
                    'description' => $catDesc,
                    'url' => route('blog.category', $category->slug),
                    'isPartOf' => ['@type' => 'Blog', 'name' => 'AI Visibility blog', 'url' => route('blog')],
                    'about' => $category->name,
                ],
                [
                    '@type' => 'ItemList',
                    '@id' => route('blog.category', $category->slug).'#list',
                    'itemListElement' => $posts->values()->map(fn ($p, $i) => [
                        '@type' => 'ListItem',
                        'position' => $i + 1,
                        'name' => $p->title,
                        'url' => route('blog.show', $p->slug),
                    ])->values()->all(),
                ],
                ['@type' => 'BreadcrumbList', 'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blog')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $category->name, 'item' => route('blog.category', $category->slug)],
                ]],
            ],
        ], JSON_UNESCAPED_SLASHES) !!}</script>
    @else
        @include('marketing.partials.head', [
            'title' => 'AI SEO Blog for Indian D2C',
            'description' => 'Guides on AI SEO, generative engine optimization and getting your Indian D2C brand recommended by ChatGPT, Gemini and Perplexity.',
        ])
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Blog',
            'name' => 'AI Visibility blog',
            'url' => route('blog'),
            'description' => 'Guides on AI SEO, generative engine optimization and getting your Indian D2C brand recommended by ChatGPT, Gemini and Perplexity.',
            'blogPost' => $posts->take(10)->values()->map(fn ($p) => [
                '@type' => 'BlogPosting',
                'headline' => $p->title,
                'url' => route('blog.show', $p->slug),
                'datePublished' => $p->published_at?->toIso8601String(),
            ])->all(),
        ], JSON_UNESCAPED_SLASHES) !!}</script>
    @endif
</head>
<body class="marketing">
    @include('marketing.partials.header')

    <section class="hero-bg relative overflow-hidden">
        <div class="grid-pattern absolute inset-0"></div>
        <div class="relative max-w-4xl mx-auto px-4 pt-16 pb-10">
            @if ($category)
                <div class="pill"><a href="{{ route('blog') }}" class="hover:text-white">← The AI SEO blog</a></div>
                <h1 class="font-display mt-5 text-4xl md:text-5xl font-extrabold leading-tight">
                    {{ $category->name }}
                </h1>
                <p class="text-slate-400 mt-4 max-w-xl">{{ $category->meta_description ?: 'Articles in this category — practical AI-visibility advice for Indian D2C brands.' }}</p>
            @else
                <div class="pill">The AI SEO blog</div>
                <h1 class="font-display mt-5 text-4xl md:text-5xl font-extrabold leading-tight">The <span class="grad-text">AI SEO blog</span> for Indian D2C</h1>
                <p class="text-slate-400 mt-4 max-w-xl">Practical guides on getting recommended by ChatGPT, Gemini &amp; Perplexity — with data, not hype.</p>
            @endif
        </div>
    </section>

    <section class="pb-16">
        <div class="max-w-4xl mx-auto px-4 space-y-4">
            {{-- Category chips --}}
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('blog') }}" class="rounded-full px-3.5 py-1.5 text-xs font-semibold border transition-colors {{ ! $category ? 'bg-brand-500/20 border-brand-500/50 text-brand-300' : 'border-white/10 text-slate-400 hover:text-white' }}">All</a>
                @foreach ($categories as $c)
                    <a href="{{ route('blog.category', $c->slug) }}" class="rounded-full px-3.5 py-1.5 text-xs font-semibold border transition-colors {{ $category?->id === $c->id ? 'bg-brand-500/20 border-brand-500/50 text-brand-300' : 'border-white/10 text-slate-400 hover:text-white' }}">
                        {{ $c->name }}
                    </a>
                @endforeach
            </div>

            @forelse ($posts as $post)
            <a href="{{ route('blog.show', $post->slug) }}" class="card-dark card-dark-hover block p-6">
                <div class="flex items-center gap-3 text-[11px] text-slate-500 flex-wrap">
                    <span>{{ $post->published_at?->format('d M Y') }}</span>
                    <span class="w-1 h-1 rounded-full bg-slate-600"></span>
                    <span>{{ $post->author }}</span>
                    <span class="w-1 h-1 rounded-full bg-slate-600"></span>
                    <span>{{ $post->readingMinutes() }} min read</span>
                    @if ($post->category)
                        <span class="ml-auto rounded-full bg-brand-500/15 border border-brand-500/40 text-brand-300 px-2.5 py-0.5 text-[10px] font-semibold">{{ $post->category->name }}</span>
                    @endif
                </div>
                <h2 class="font-display font-bold text-lg text-white mt-2 hover:text-brand-400 transition-colors">{{ $post->title }}</h2>
                <p class="text-sm text-slate-400 mt-2 leading-relaxed">{{ $post->excerpt }}</p>
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-400 mt-4">
                    Read article
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </span>
            </a>
            @empty
            <p class="text-slate-500 text-sm py-10 text-center">
                @if ($category) No published articles in “{{ $category->name }}” yet. @else Articles coming soon. @endif
            </p>
            @endforelse
        </div>
    </section>

    @include('marketing.partials.footer')
</body>
</html>
