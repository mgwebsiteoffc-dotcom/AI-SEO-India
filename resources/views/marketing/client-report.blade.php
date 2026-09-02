<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $rTitle = ($store->brand_name ?: ucfirst(strtok($store->shop, '.'))).' — AI Visibility Report';
    @endphp
    @include('marketing.partials.head', [
        'title' => $rTitle,
        'description' => 'AI visibility performance report for '.$store->hostname().' — prepared by '.$agencyName.'.',
    ])
</head>
<body class="marketing min-h-screen">
    @include('marketing.partials.header')

    <section class="hero-bg relative overflow-hidden">
        <div class="grid-pattern absolute inset-0"></div>
        <div class="relative max-w-3xl mx-auto px-4 pt-14 pb-8 text-center">
            <div class="pill animate-fade-up">Client AI Visibility Report</div>
            <h1 class="font-display mt-4 text-3xl md:text-4xl font-extrabold leading-tight animate-fade-up delay-100">
                {{ $store->brand_name ?: ucfirst(strtok($store->shop, '.')) }}
            </h1>
            <p class="text-slate-400 mt-2 text-sm animate-fade-up delay-200">{{ $store->hostname() }} · as of {{ $asOf }}</p>
        </div>
    </section>

    <section class="pb-16">
        <div class="max-w-3xl mx-auto px-4 space-y-5">
            {{-- Score cards --}}
            <div class="grid sm:grid-cols-3 gap-4">
                <div class="glass rounded-2xl p-5 text-center">
                    <div class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">AI Readiness</div>
                    <div class="font-display text-3xl font-extrabold text-white mt-1">
                        {{ $audit?->score ?? '—' }}<span class="text-sm text-slate-500">/100</span>
                    </div>
                    <div class="text-[11px] text-slate-500">Grade {{ $audit?->summary['grade'] ?? 'n/a' }}</div>
                </div>
                <div class="glass rounded-2xl p-5 text-center">
                    <div class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Brand Signals</div>
                    <div class="font-display text-3xl font-extrabold text-white mt-1">
                        {{ $signal?->score ?? '—' }}<span class="text-sm text-slate-500">/100</span>
                    </div>
                    <div class="text-[11px] text-slate-500">Grade {{ $signal?->summary['grade'] ?? 'n/a' }}</div>
                </div>
                <div class="glass rounded-2xl p-5 text-center">
                    <div class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">AI mention rate</div>
                    <div class="font-display text-3xl font-extrabold grad-text mt-1">
                        {{ ($engines->avg('rate')) === null ? '—' : round($engines->avg('rate'), 1).'%' }}
                    </div>
                    <div class="text-[11px] text-slate-500">latest per-engine check</div>
                </div>
            </div>

            {{-- Trend --}}
            @if (count($trend))
                <div class="glass rounded-2xl p-5">
                    <div class="text-sm font-bold text-white mb-3">AI mention rate — last 7 days</div>
                    <div class="flex items-end gap-2 h-28">
                        @foreach ($trend as $t)
                            <div class="flex-1 flex flex-col items-center gap-1 justify-end h-full">
                                <span class="text-[10px] text-slate-400">{{ $t['rate'] === null ? '·' : round($t['rate']).'%' }}</span>
                                <div class="w-full rounded-t-md bg-gradient-to-t from-brand-600/40 to-brand-400"
                                     style="height: {{ max(3, min(100, $t['rate'] ?? 0)) }}%"></div>
                                <span class="text-[9px] text-slate-600">{{ $t['date'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Engine table --}}
            <div class="glass rounded-2xl p-5">
                <div class="text-sm font-bold text-white mb-3">Latest mentions per AI engine</div>
                @if (count($engines))
                    <div class="grid gap-2">
                        @foreach ($engines as $e)
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-300 w-28 capitalize">{{ $e['engine'] }}</span>
                                <div class="flex-1 h-2 rounded-full bg-white/10 overflow-hidden">
                                    <div class="h-full rounded-full bg-brand-500" style="width: {{ $e['rate'] }}%"></div>
                                </div>
                                <span class="text-xs font-bold text-white w-12 text-right">{{ $e['rate'] }}%</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-500">Tracking not started yet — first checks will appear here.</p>
                @endif
            </div>

            {{-- Top issues --}}
            @if (count($issues))
                <div class="glass rounded-2xl p-5">
                    <div class="text-sm font-bold text-white mb-3">Priority fixes</div>
                    <div class="space-y-2">
                        @foreach ($issues as $issue)
                            <div class="rounded-xl bg-white/[0.04] border border-white/10 px-4 py-3 text-xs flex gap-3 items-start">
                                <span class="badge @if ($issue['severity'] === 'critical') badge-red @elseif ($issue['severity'] === 'warning') badge-amber @else badge-slate @endif shrink-0">{{ $issue['severity'] }}</span>
                                <div>
                                    <div class="text-slate-200 font-semibold">{{ $issue['title'] }}</div>
                                    <div class="text-slate-500 text-[11px] mt-1">{{ $issue['recommendation'] ?? '' }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Attribution --}}
            <div class="text-center pt-4">
                <div class="text-xs text-slate-500">
                    Prepared for <strong class="text-slate-300">{{ $store->brand_name ?: $store->shop }}</strong> by
                    <strong class="text-slate-300">{{ $agencyName }}</strong>
                    @if ($agencyWebsite)<a href="{{ $agencyWebsite }}" class="text-brand-400 hover:text-brand-300"> · {{ $agencyWebsite }}</a>@endif
                </div>
                @if (! $whiteLabel)
                    <div class="text-[10px] text-slate-600 mt-2">
                        Generated with <a href="{{ route('home') }}" class="text-brand-400 hover:text-brand-300">AI Visibility</a> — honest AI-visibility tracking for Shopify.
                    </div>
                @endif
            </div>
        </div>
    </section>

    @include('marketing.partials.footer')
</body>
</html>
