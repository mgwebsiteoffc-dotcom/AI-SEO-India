<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', ['title' => 'Activity', 'description' => 'Webhook + audit activity across the platform.'])
</head>
<body class="marketing min-h-screen">
    @include('admin.partials.topbar', ['active' => 'activity'])
    <main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
        <h1 class="font-display text-2xl font-extrabold text-white">Platform activity</h1>

        <div class="grid lg:grid-cols-2 gap-6">
            <section class="glass rounded-2xl p-5">
                <h2 class="font-display font-bold text-white mb-4">Latest webhook events <span class="text-slate-500 font-normal text-xs">(orders/paid, products/update, app/uninstalled)</span></h2>
                <div class="space-y-2">
                    @forelse ($webhooks as $w)
                        <div class="flex items-center justify-between gap-3 text-sm border-b border-white/5 pb-2">
                            <div class="min-w-0">
                                <div class="text-white truncate">{{ $w->topic }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ $w->shop ?: '—' }} · {{ $w->created_at->format('d M Y H:i') }}</div>
                            </div>
                            <span class="badge {{ $w->status === 'processed' ? 'badge-green' : 'badge-red' }} shrink-0">{{ $w->status }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">No webhook events recorded. They arrive when a real Shopify store is connected.</p>
                    @endforelse
                </div>
            </section>

            <section class="glass rounded-2xl p-5">
                <h2 class="font-display font-bold text-white mb-4">Latest AI Readiness audits</h2>
                <div class="space-y-2">
                    @forelse ($audits as $a)
                        <div class="flex items-center justify-between gap-3 text-sm border-b border-white/5 pb-2">
                            <div class="min-w-0">
                                <div class="text-white truncate">{{ $a->store?->shop ?: 'store #'.$a->store_id }}</div>
                                <div class="text-[11px] text-slate-500 truncate">
                                    {{ $a->status }} · {{ $a->started_at ? $a->started_at->format('d M Y H:i') : '—' }}
                                    @if ($a->status === 'completed') · grade {{ $a->summary['grade'] ?? '—' }} @endif
                                </div>
                            </div>
                            <span class="font-display font-extrabold {{ ($a->summary['total'] ?? 0) >= 70 ? 'text-emerald-400' : 'text-amber-400' }} shrink-0">
                                {{ $a->status === 'completed' ? ($a->summary['total'] ?? '—').'/100' : '—' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">No audits yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </main>
    <footer class="border-t border-white/10 py-6 text-center text-xs text-slate-600">
        AI Visibility · SaaS owner area · <a href="{{ route('privacy') }}" class="hover:text-slate-400">Privacy</a> · <a href="{{ route('terms') }}" class="hover:text-slate-400">Terms</a>
    </footer>
</body>
</html>
