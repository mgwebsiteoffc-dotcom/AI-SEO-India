@php
    $liveScore = $liveScore ?? null;
    $status = $status ?? null;
    $scoreSummary = $liveScore['summary'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', [
        'title' => 'Free AI Readiness Score — Is your brand ready for ChatGPT, Gemini & Perplexity?',
        'description' => 'Get your free AI Readiness Scorecard: 30+ checks on crawlability, schema, content and brand signals. See exactly what your Shopify store needs to be recommended by AI.',
    ])
</head>
<body class="marketing">
    @include('marketing.partials.header')

    <section class="hero-bg relative overflow-hidden">
        <div class="grid-pattern absolute inset-0"></div>
        <div class="relative max-w-3xl mx-auto px-4 pt-16 pb-10 text-center">
            <div class="pill animate-fade-up">Free · instant scan · no credit card</div>
            <h1 class="font-display mt-5 text-4xl md:text-5xl font-extrabold leading-tight animate-fade-up delay-100">
                Your free <span class="grad-text">AI Readiness Score</span>
            </h1>
            <p class="text-slate-400 mt-5 animate-fade-up delay-200 leading-relaxed">
                30+ evidence-based checks — crawlability, schema, content, brand &amp; speed — weighted to a 0–100 score.
                Enter your store URL and we scan it <strong class="text-white">right now</strong>, live.
            </p>
        </div>
    </section>

    <section class="pb-16">
        <div class="max-w-3xl mx-auto px-4">
            @if ($status)
                <div class="rounded-2xl bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-sm px-5 py-4 mb-5">{{ $status }}</div>
            @endif

            @if ($liveScore && isset($liveScore['failed']) && $liveScore['failed'] && ! $scoreSummary)
                <div class="rounded-2xl bg-amber-500/15 border border-amber-500/40 text-amber-300 text-sm px-5 py-4 mb-5">
                    We could not complete a live scan of <strong>{{ $liveScore['domain'] }}</strong> (the site may be down or blocking us). Your email is saved — we will retry and email the scorecard.
                </div>
            @elseif ($scoreSummary)
                {{-- Live result --}}
                <div class="glass-strong rounded-3xl p-6 md:p-8 mb-6 animate-fade-up">
                    <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
                        <div>
                            <div class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold">Live scan · just now</div>
                            <div class="font-display font-bold text-white mt-1 break-all">{{ $liveScore['domain'] }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-display text-5xl font-extrabold {{ ($scoreSummary['total'] ?? 0) >= 70 ? 'text-emerald-400' : (($scoreSummary['total'] ?? 0) >= 45 ? 'text-amber-400' : 'text-red-400') }}">
                                {{ $scoreSummary['total'] ?? '—' }}<span class="text-base text-slate-500">/100</span>
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5">Grade {{ $scoreSummary['grade'] ?? '—' }}</div>
                        </div>
                    </div>

                    @if (! empty($scoreSummary['categories']) && is_array($scoreSummary['categories']))
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
                            @foreach ($scoreSummary['categories'] as $k => $v)
                                <div class="rounded-xl bg-white/5 border border-white/10 p-3 text-center">
                                    <div class="text-[10px] text-slate-500 uppercase tracking-wide">{{ $k }}</div>
                                    <div class="text-xl font-extrabold mt-1 {{ $v >= 80 ? 'text-emerald-400' : ($v >= 60 ? 'text-amber-400' : 'text-red-400') }}">{{ $v }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (! empty($liveScore['issues']))
                        <div class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold mb-2">What we found ({{ count($liveScore['issues']) }})</div>
                        <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                            @foreach ($liveScore['issues'] as $issue)
                                <div class="flex gap-2.5 items-start rounded-xl border border-white/10 bg-white/[0.03] p-3 text-xs">
                                    <span class="badge shrink-0 mt-0.5
                                        @if ($issue['severity'] === 'critical') badge-red
                                        @elseif ($issue['severity'] === 'warning') badge-amber
                                        @else badge-slate @endif">{{ $issue['severity'] }}</span>
                                    <div class="min-w-0">
                                        <div class="text-slate-200 font-medium">{{ $issue['title'] }}</div>
                                        @if (! empty($issue['recommendation']))
                                            <div class="text-slate-500 mt-0.5 leading-relaxed">{{ $issue['recommendation'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-slate-400">No issues recorded — great job. (A real storefront scan checks robots.txt, sitemap, schema, content and speed.)</p>
                    @endif

                    @if (! empty($liveScore['share_url']))
                        <div class="mt-6 rounded-2xl border border-brand-500/40 bg-brand-500/10 p-4">
                            <div class="text-xs font-bold text-white">🎉 Share your score — it is live at</div>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 mt-2">
                                <code class="text-[11px] text-brand-300 break-all flex-1">{{ $liveScore['share_url'] }}</code>
                                <button onclick="navigator.clipboard.writeText(this.previousElementSibling.textContent).then(()=>{const b=this;b.textContent='Copied ✓';setTimeout(()=>b.textContent='Copy link',1500)})"
                                        class="btn !py-1.5 !text-[11px] shrink-0">Copy link</button>
                                <a href="{{ $liveScore['share_url'] }}" target="_blank" class="btn-primary !py-1.5 !text-[11px] shrink-0">Open →</a>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-2">Post it on LinkedIn/Instagram — founders love comparing AI scores. Your email stays private; the page shows only the score + fixes.</p>
                        </div>
                    @endif

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('install') }}" class="btn-primary !py-2.5 text-xs">Fix these with AI Visibility →</a>
                        <a href="{{ route('app', ['demo' => 1]) }}" class="btn-secondary !py-2.5 text-xs">See the fix loop on demo data</a>
                    </div>
                </div>
            @endif

            {{-- Lead + scan form --}}
            <div class="relative rounded-3xl border border-brand-500/40 bg-gradient-to-br from-brand-600/15 to-surface-800 p-6 md:p-8">
                <h2 class="font-display font-extrabold text-xl text-white">Scan your store now — 30 seconds</h2>
                <p class="text-xs text-slate-400 mt-1.5 mb-6">Works for Shopify or any ecommerce site. We crawl robots.txt, sitemap, homepage + product pages and score you instantly.</p>

                <form method="POST" action="{{ route('scorecard.run') }}" class="space-y-3">
                    @csrf
                    <input type="email" name="email" required placeholder="you@brand.com" class="input !bg-surface-950/60 !border-white/15 !text-white !placeholder-slate-500" value="{{ old('email') }}">
                    <input type="url" name="shop_url" placeholder="https://your-brand.in or your-brand.myshopify.com" class="input !bg-surface-950/60 !border-white/15 !text-white !placeholder-slate-500" value="{{ old('shop_url') }}">
                    <div class="grid sm:grid-cols-2 gap-3">
                        <input type="text" name="brand" placeholder="Brand name (optional)" class="input !bg-surface-950/60 !border-white/15 !text-white !placeholder-slate-500" value="{{ old('brand') }}">
                    </div>
                    <button type="submit" class="btn-primary !bg-brand-500 hover:!bg-brand-400 w-full py-3.5 text-sm shadow-xl shadow-brand-500/20">Scan my store → show my AI Readiness Score</button>
                </form>
                @error('email')<p class="text-xs text-red-400 mt-2">{{ $message }}</p>@enderror
                @error('shop_url')<p class="text-xs text-red-400 mt-2">{{ $message }}</p>@enderror
                <p class="text-[11px] text-slate-500 mt-3.5">No spam. One scorecard email if the live scan can't complete, then an occasional 2-line update. Unsubscribe anytime.</p>
            </div>

            {{-- Sample scorecard (from the seeded demo audit) --}}
            <div class="mt-6">
                <div class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold mb-3 text-center">Example — Aurelia Naturals (demo store, real scan)</div>
                <div class="glass-strong rounded-3xl p-6 md:p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <div class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold">Sample scorecard</div>
                            <div class="font-display font-bold text-white mt-1">Aurelia Naturals (demo)</div>
                        </div>
                        <div class="text-right">
                            <div class="font-display text-5xl font-extrabold grad-text">{{ $demoScore['total'] ?? 74 }}<span class="text-base text-slate-500">/100</span></div>
                            <div class="text-xs text-slate-500 mt-0.5">Grade {{ $demoScore['grade'] ?? 'B' }}</div>
                        </div>
                    </div>
                    @php($cats = $demoScore['categories'] ?? ['crawlability' => 92, 'schema' => 55, 'content' => 78, 'brand' => 85, 'speed' => 100])
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
                        @foreach ($cats as $k => $v)
                            <div class="rounded-xl bg-white/5 border border-white/10 p-3 text-center">
                                <div class="text-[10px] text-slate-500 uppercase tracking-wide">{{ $k }}</div>
                                <div class="text-xl font-extrabold mt-1 {{ $v >= 80 ? 'text-emerald-400' : ($v >= 60 ? 'text-amber-400' : 'text-red-400') }}">{{ $v }}</div>
                                <div class="relative h-1 mt-2 rounded-full bg-white/10 overflow-hidden">
                                    <div class="absolute inset-y-0 left-0 rounded-full {{ $v >= 80 ? 'bg-emerald-400' : ($v >= 60 ? 'bg-amber-400' : 'bg-red-400') }}" style="width: {{ $v }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="space-y-2 text-xs text-slate-400">
                        <div class="flex gap-2.5 items-start rounded-xl border border-amber-400/30 bg-amber-400/10 p-3">
                            <span class="badge-amber shrink-0 mt-0.5">Fix</span> No Product schema on product pages — enable Schema Builder (one click)
                        </div>
                        <div class="flex gap-2.5 items-start rounded-xl border border-emerald-400/30 bg-emerald-400/10 p-3">
                            <span class="badge-green shrink-0 mt-0.5">Good</span> AI crawlers allowed in robots.txt
                        </div>
                        <div class="flex gap-2.5 items-start rounded-xl border border-emerald-400/30 bg-emerald-400/10 p-3">
                            <span class="badge-green shrink-0 mt-0.5">Good</span> Sitemap healthy (142 URLs)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('marketing.partials.footer')
</body>
</html>
