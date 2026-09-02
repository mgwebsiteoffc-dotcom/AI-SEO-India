<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', [
        'title' => 'How it works — Score → Fix → Track → Publish, every week',
        'description' => 'The AI Visibility loop in four steps: measure your AI Readiness Score, fix the signals, track mentions daily, and publish content AI engines love.',
    ])
</head>
<body class="marketing">
    @include('marketing.partials.header')

    <section class="hero-bg relative overflow-hidden">
        <div class="grid-pattern absolute inset-0"></div>
        <div class="relative max-w-4xl mx-auto px-4 pt-16 pb-12 text-center">
            <div class="pill animate-fade-up">One loop, every week</div>
            <h1 class="font-display mt-5 text-4xl md:text-5xl font-extrabold leading-tight animate-fade-up delay-100">
                Score → Fix → Track → <span class="grad-text">Publish</span>
            </h1>
            <p class="text-slate-400 mt-5 max-w-2xl mx-auto animate-fade-up delay-200 leading-relaxed">
                Indian shoppers now ask AI what to buy. We make sure the answer mentions your brand — with measurable, honest progress.
            </p>
        </div>
    </section>

    <section class="py-14">
        <div class="max-w-5xl mx-auto px-4 space-y-6">
            @php
                $steps = [
                    ['01', 'Score', 'AI Readiness Score', 'A 30+ point audit of crawlability, schema, content and brand — weighted to a shareable 0–100 scorecard. Run it in under a minute, free.', 'score'],
                    ['02', 'Fix', 'Fix the signals', 'One-click robots.txt AI-bot rules, JSON-LD schema, llms.txt and sitemap — no developer needed. Each fix is tied to points on your score.', 'llms'],
                    ['03', 'Track', 'Track mentions', 'Daily snapshots of how often ChatGPT, Gemini and Perplexity mention you for real shopping queries. Watch your mention rate (and competitors\') climb.', 'tracker'],
                    ['04', 'Publish', 'Publish content', 'Smart Blogger writes India-flavoured comparison & FAQ articles and publishes them to your Shopify blog — the content AI answers cite.', 'content'],
                ];
            @endphp
            @foreach ($steps as [$num, $tag, $title, $desc, $tab])
                <div class="card-dark rounded-2xl p-6 md:p-7 flex flex-col md:flex-row gap-5 items-start">
                    <div class="font-display text-4xl font-extrabold grad-text leading-none shrink-0">{{ $num }}</div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <span class="badge badge-green">{{ $tag }}</span>
                            <h2 class="font-display text-xl font-bold text-white">{{ $title }}</h2>
                        </div>
                        <p class="text-sm text-slate-400 mt-2 leading-relaxed">{{ $desc }}</p>
                        <a href="{{ route('app', ['demo' => 1]) }}" class="inline-block mt-3 text-xs font-semibold text-brand-400 hover:text-brand-300">See it on demo data →</a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="pb-16">
        <div class="max-w-3xl mx-auto px-4 text-center">
            <h2 class="font-display text-2xl font-extrabold text-white">Weekly, not one-time</h2>
            <p class="text-sm text-slate-400 mt-3">AI answers change every week. The loop runs daily in the background (6 AM IST snapshot) and shows up on your dashboard — so you act on fresh data, not last year’s audit.</p>
            <div class="flex flex-wrap items-center justify-center gap-3 mt-7">
                <a href="{{ route('install') }}" class="btn-primary">Install on Shopify</a>
                <a href="{{ route('app', ['demo' => 1]) }}" class="btn-secondary">Try the live demo →</a>
            </div>
        </div>
    </section>

    @include('marketing.partials.footer')
</body>
</html>
