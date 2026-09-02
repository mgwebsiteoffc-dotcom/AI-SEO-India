<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', ['title' => 'Owner overview', 'description' => 'SaaS owner panel — every store, subscription and lead at a glance.'])
</head>
<body class="marketing min-h-screen">
    @include('admin.partials.topbar', ['active' => 'overview'])
    <main class="max-w-7xl mx-auto px-4 py-8 space-y-8">
        <div class="flex items-end justify-between gap-4 flex-wrap">
            <div>
                <h1 class="font-display text-2xl font-extrabold text-white">Business at a glance</h1>
                <p class="text-sm text-slate-400 mt-1">MRR below = active recurring subscriptions × plan price (INR).</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.blog.create') }}" class="btn !py-2 text-xs">✍️ New blog article</a>
                <a href="{{ route('admin.stores') }}" class="btn-primary !py-2 text-xs">Manage stores →</a>
            </div>
        </div>

        @php
            $cards = [
                ['label' => 'Stores', 'value' => number_format($kpis['stores']), 'hint' => 'all tenants', 'icon' => '🏪'],
                ['label' => 'Monthly revenue', 'value' => '₹'.number_format($kpis['mrr']), 'hint' => 'ARR ₹'.number_format($kpis['arr']), 'icon' => '₹'],
                ['label' => 'Leads', 'value' => number_format($kpis['leads']), 'hint' => 'scorecard signups', 'icon' => '✉️', 'link' => 'admin.leads'],
                ['label' => 'Blog posts', 'value' => number_format($kpis['posts']), 'hint' => 'founder content', 'icon' => '📝', 'link' => 'admin.blog'],
                ['label' => 'Content generated', 'value' => number_format($kpis['content_posts']), 'hint' => 'Smart Blogger articles', 'icon' => '🤖'],
                ['label' => 'Audits run', 'value' => number_format($kpis['audits']), 'hint' => 'AI Readiness scans', 'icon' => '📊'],
                ['label' => 'Tracked queries', 'value' => number_format($kpis['tracked_queries']), 'hint' => 'visibility watch', 'icon' => '👁️'],
                ['label' => 'Webhook events', 'value' => number_format($kpis['webhooks']), 'hint' => 'Shopify → app', 'icon' => '⚡'],
            ];
        @endphp
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach ($cards as $c)
                @if (isset($c['link']))
                    <a href="{{ route($c['link']) }}" class="glass rounded-2xl p-4 transition-colors hover:border-brand-500/40 hover:bg-brand-500/[0.06] block">
                        <div class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold flex items-center gap-1.5">{{ $c['icon'] }} {{ $c['label'] }}</div>
                        <div class="font-display text-2xl font-extrabold text-white mt-1.5">{{ $c['value'] }}</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">{{ $c['hint'] }}</div>
                    </a>
                @else
                    <div class="glass rounded-2xl p-4">
                        <div class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold flex items-center gap-1.5">{{ $c['icon'] }} {{ $c['label'] }}</div>
                        <div class="font-display text-2xl font-extrabold text-white mt-1.5">{{ $c['value'] }}</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">{{ $c['hint'] }}</div>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <section class="glass rounded-2xl p-5">
                <h2 class="font-display font-bold text-white mb-4">Stores by plan</h2>
                @php
                    $planMeta = ['free' => ['Free', '#64748b'], 'grow' => ['Grow ₹999', '#10b981'], 'scale' => ['Scale ₹1,999', '#0a84ff'], 'agency' => ['Agency ₹4,999', '#a78bfa']];
                    $total = max(1, $planCounts->sum());
                @endphp
                <div class="space-y-3">
                    @foreach ($planMeta as $key => [$label, $color])
                        @php($n = $planCounts->get($key, 0))
                        <div>
                            <div class="flex justify-between text-xs mb-1"><span class="text-slate-300">{{ $label }}</span><span class="text-slate-500">{{ $n }} store{{ $n === 1 ? '' : 's' }}</span></div>
                            <div class="h-2 rounded-full bg-white/10 overflow-hidden"><div class="h-full rounded-full" style="width: {{ $n / $total * 100 }}%; background: {{ $color }}"></div></div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="glass rounded-2xl p-5">
                <h2 class="font-display font-bold text-white mb-4">Recent stores</h2>
                <div class="space-y-2.5">
                    @forelse ($recentStores as $s)
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <div class="text-white truncate">{{ $s->brand_name ?: $s->shop }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ $s->shop }} · {{ $s->billing_status }}</div>
                            </div>
                            <span class="badge shrink-0">{{ $s->plan }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">No stores yet.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <section class="glass rounded-2xl p-5">
                <h2 class="font-display font-bold text-white mb-4">Latest leads</h2>
                <div class="space-y-2.5">
                    @forelse ($recentLeads as $l)
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <div class="text-white truncate">{{ $l->email }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ $l->brand ?: '—' }} · {{ $l->created_at->diffForHumans() }}</div>
                            </div>
                            <span class="text-[10px] uppercase tracking-wide text-slate-500 shrink-0">{{ $l->source }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">No leads yet.</p>
                    @endforelse
                </div>
                <a href="{{ route('admin.leads') }}" class="text-xs text-brand-400 hover:text-brand-300 mt-3 inline-block">View all leads →</a>
            </section>

            <section class="glass rounded-2xl p-5">
                <h2 class="font-display font-bold text-white mb-4">Latest webhook events</h2>
                <div class="space-y-2.5">
                    @forelse ($recentActivity as $w)
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <div class="text-white truncate">{{ $w->topic }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ $w->shop ?: '—' }} · {{ $w->created_at->diffForHumans() }}</div>
                            </div>
                            <span class="badge {{ $w->status === 'processed' ? 'badge-green' : 'badge-red' }} shrink-0">{{ $w->status }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">No webhook events yet.</p>
                    @endforelse
                </div>
                <a href="{{ route('admin.activity') }}" class="text-xs text-brand-400 hover:text-brand-300 mt-3 inline-block">View all activity →</a>
            </section>
        </div>
    </main>
    <footer class="border-t border-white/10 py-6 text-center text-xs text-slate-600">
        AI Visibility · SaaS owner area · <a href="{{ route('privacy') }}" class="hover:text-slate-400">Privacy</a> · <a href="{{ route('terms') }}" class="hover:text-slate-400">Terms</a>
    </footer>
</body>
</html>
