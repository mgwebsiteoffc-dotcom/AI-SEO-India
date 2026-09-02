<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $share = $sharePayload; // ['brand','domain','score','grade','total'??0,'issues'=>[],'generated_at']
        $shareTitle = ($share['brand'] ?: $share['domain']).' — AI Readiness Score '.($share['score'] ?? '—').'/100';
        $shareDesc = 'Free AI visibility scan of '.$share['domain'].' by AI Visibility — get recommended by ChatGPT, Gemini & Perplexity.';
    @endphp
    @include('marketing.partials.head', [
        'title' => $shareTitle,
        'description' => $shareDesc,
        'ogImage' => url('/og-image.svg'),
    ])
</head>
<body class="marketing min-h-screen">
    @include('marketing.partials.header')

    <section class="hero-bg relative overflow-hidden">
        <div class="grid-pattern absolute inset-0"></div>
        <div class="relative max-w-3xl mx-auto px-4 pt-16 pb-10 text-center">
            <div class="pill animate-fade-up">Free AI Readiness Score</div>
            <h1 class="font-display mt-4 text-3xl md:text-5xl font-extrabold leading-tight animate-fade-up delay-100">
                {{ $share['brand'] ?: $share['domain'] }}
            </h1>
            <p class="text-slate-400 mt-3 animate-fade-up delay-200 text-sm md:text-base">
                How ready is <span class="text-white font-semibold">{{ $share['domain'] }}</span> to be recommended
                by ChatGPT, Gemini &amp; Perplexity when shoppers ask?
            </p>
        </div>
    </section>

    <section class="pb-16">
        <div class="max-w-3xl mx-auto px-4 space-y-5">
            <div class="glass-strong rounded-3xl p-6 md:p-8">
                <div class="flex items-center justify-center gap-4 flex-wrap">
                    <div class="text-center">
                        <div class="font-display text-6xl font-extrabold grad-text">{{ $share['score'] }}<span class="text-xl text-slate-500">/100</span></div>
                        <div class="text-xs text-slate-400 mt-1">Grade {{ $share['grade'] ?? '—' }}</div>
                    </div>
                    <div class="w-px h-16 bg-white/10 hidden sm:block"></div>
                    <div class="text-left text-xs text-slate-400 leading-relaxed max-w-xs">
                        <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-brand-500"></span> Crawlability {{ $share['categories']['crawlability'] ?? '—' }}/100</div>
                        <div class="flex items-center gap-2 mt-1.5"><span class="w-2.5 h-2.5 rounded-full bg-sky-400"></span> Schema {{ $share['categories']['schema'] ?? '—' }}/100</div>
                        <div class="flex items-center gap-2 mt-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span> Content {{ $share['categories']['content'] ?? '—' }}/100</div>
                        <div class="flex items-center gap-2 mt-1.5"><span class="w-2.5 h-2.5 rounded-full bg-violet-400"></span> Brand {{ $share['categories']['brand'] ?? '—' }}/100</div>
                    </div>
                </div>

                @if (! empty($share['issues']))
                    <div class="mt-6 border-t border-white/10 pt-4">
                        <div class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold mb-3">Top fixes from the scan ({{ count($share['issues']) }})</div>
                        <div class="grid gap-2">
                            @foreach (array_slice($share['issues'], 0, 5) as $issue)
                                <div class="rounded-xl bg-white/[0.04] border border-white/10 px-3.5 py-2.5 text-xs flex items-start gap-2.5">
                                    <span class="mt-0.5 {{ ($issue['severity'] ?? '') === 'critical' ? 'text-red-400' : (($issue['severity'] ?? '') === 'warning' ? 'text-amber-400' : 'text-slate-500') }}">●</span>
                                    <div class="min-w-0">
                                        <div class="text-slate-200 font-semibold">{{ $issue['title'] }}</div>
                                        <div class="text-slate-500 text-[11px] mt-0.5">{{ $issue['recommendation'] ?? '' }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <p class="text-[11px] text-slate-500 mt-4">Scan run {{ $share['generated_at'] ?? '' }} by AI Visibility — the AI SEO app for Indian D2C brands.</p>
            </div>

            <div class="text-center">
                <a href="{{ route('scorecard') }}" class="btn-primary">Run your own free score →</a>
            </div>
        </div>
    </section>

    @include('marketing.partials.footer')
</body>
</html>
