<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', [
        'title' => 'Settings — Owner',
        'description' => 'SaaS owner controls — AI engines monitored by the visibility tracker, the daily snapshot scheduler, and plan prices.',
    ])
</head>
<body class="marketing min-h-screen">
    @include('admin.partials.topbar', ['active' => 'settings'])
    <main class="max-w-5xl mx-auto px-4 py-8 space-y-6">
        <div>
            <h1 class="font-display text-2xl font-extrabold text-white">Platform settings</h1>
            <p class="text-sm text-slate-400 mt-1">Every switch here takes effect immediately — no code deploy. Changes apply across all tenant stores.</p>
        </div>

        {{-- ── AI engines ─────────────────────────────────────────────────── --}}
        <section class="glass rounded-2xl p-5 md:p-6">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div>
                    <h2 class="font-display font-bold text-white text-lg">AI engines monitored</h2>
                    <p class="text-xs text-slate-400 mt-1 max-w-2xl leading-relaxed">
                        The visibility tracker snapshots each enabled engine daily. ChatGPT &amp; Gemini run <em>real
                        LLM answer checks</em> when their API key is configured below; every engine can also fall back to
                        honest retrieval-proxy checks against the live web results that feed AI answers.
                    </p>
                </div>
                <span class="badge badge-green shrink-0">{{ count(array_filter($engines, fn ($e) => $e['enabled'])) }}/{{ count($engines) }} on</span>
            </div>

            <form method="POST" action="{{ route('admin.settings.engines') }}" class="mt-5">
                @csrf
                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach ($engines as $key => $engine)
                        <label class="rounded-xl border p-4 flex items-start gap-3 cursor-pointer transition-colors {{ $engine['enabled'] ? 'border-brand-500/40 bg-brand-500/[0.06]' : 'border-white/10 bg-white/[0.02] hover:bg-white/[0.05]' }}">
                            <input type="checkbox" name="engines[{{ $key }}]" value="1"
                                   {{ $engine['enabled'] ? 'checked' : '' }}
                                   class="mt-1 w-4 h-4 rounded accent-brand-500">
                            <span class="min-w-0">
                                <span class="flex items-center gap-2 flex-wrap">
                                    <span class="w-2.5 h-2.5 rounded-full inline-block shrink-0" style="background: {{ $engine['color'] }}"></span>
                                    <span class="text-sm font-bold text-white">{{ $engine['label'] }}</span>
                                    @if ($engine['method'] === 'llm')
                                        <span class="badge badge-green !py-0.5 text-[10px]">LLM answer checks</span>
                                    @else
                                        <span class="badge badge-slate !py-0.5 text-[10px]">Retrieval proxy</span>
                                    @endif
                                </span>
                                <span class="block text-[11px] text-slate-500 mt-1 leading-relaxed">{{ $engine['hint'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <div class="flex items-center justify-between gap-3 mt-5 flex-wrap">
                    <p class="text-[11px] text-slate-500">When every engine is off, the daily snapshot is paused for all stores.</p>
                    <button class="btn-primary !py-2 text-xs">Save engine toggles</button>
                </div>
            </form>
        </section>

        {{-- ── LLM provider keys ─────────────────────────────────────────── --}}
        <section class="glass rounded-2xl p-5 md:p-6">
            <h2 class="font-display font-bold text-white text-lg">LLM answer-check keys</h2>
            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                Set in <code class="text-brand-300 text-xs">.env</code> on the server. When a key is present, ChatGPT / Gemini
                results come from the real model; otherwise those engines fall back to retrieval-proxy checks.
            </p>
            <div class="grid sm:grid-cols-2 gap-3 mt-4">
                @foreach ($llm as $provider => $info)
                    <div class="rounded-xl border border-white/10 bg-white/[0.02] p-4 flex items-center justify-between gap-3">
                        <span class="text-sm font-semibold text-white">{{ $info['label'] }}</span>
                        @if ($info['configured'])
                            <span class="badge badge-green">Key configured ✓</span>
                        @else
                            <span class="badge badge-slate">Not configured — proxy mode</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ── Daily snapshot scheduler ──────────────────────────────────── --}}
        <section class="glass rounded-2xl p-5 md:p-6">
            <h2 class="font-display font-bold text-white text-lg">Daily snapshot scheduler</h2>
            <p class="text-xs text-slate-400 mt-1 max-w-2xl leading-relaxed">
                Runs the visibility tracker for every store with tracking enabled ({{ $storeCounts['tracking'] }} of {{ $storeCounts['all'] }} stores).
                Scheduled via <code class="text-brand-300 text-xs">schedule:run</code> — the scheduler picks up the time below on every tick.
            </p>
            <form method="POST" action="{{ route('admin.settings.tracking') }}" class="mt-4 flex flex-col sm:flex-row sm:items-end gap-4">
                @csrf
                <label class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-white/[0.02] px-4 py-3 cursor-pointer">
                    <input type="checkbox" name="tracking_enabled" value="1" {{ $tracking['enabled'] ? 'checked' : '' }} class="w-4 h-4 accent-brand-500">
                    <span class="text-sm font-semibold text-white">Scheduler enabled (Asia/Kolkata)</span>
                </label>
                <label class="flex flex-col gap-1">
                    <span class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Run time (IST)</span>
                    <input type="time" name="tracking_time" value="{{ $tracking['time'] }}" class="input !py-2 !text-sm">
                </label>
                <button class="btn-primary !py-2 text-xs">Save schedule</button>
            </form>
            <form method="POST" action="{{ route('admin.settings.run') }}" class="mt-3 flex items-center gap-3 flex-wrap">
                @csrf
                <button class="btn !py-2 text-xs !bg-emerald-500/15 !text-emerald-300 border-emerald-500/30 hover:!bg-emerald-500/25">▶ Run snapshot now</button>
                <span class="text-[11px] text-slate-500">Can take a minute for larger stores — the page waits for the run to finish.</span>
            </form>
        </section>

        {{-- ── Instant indexing (IndexNow) ──────────────────────────────── --}}
        <section class="glass rounded-2xl p-5 md:p-6">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div>
                    <h2 class="font-display font-bold text-white text-lg">Instant indexing — IndexNow</h2>
                    <p class="text-xs text-slate-400 mt-1 max-w-2xl leading-relaxed">
                        When a merchant adds/updates/deletes a product or publishes a blog article, we ping
                        <code class="text-brand-300 text-xs">api.indexnow.org</code> so search engines (and the indexes AI
                        engines read) notice immediately — no waiting for recrawls. Pending URLs flush automatically every 15 minutes.
                    </p>
                </div>
                <span class="badge {{ $indexnow['enabled'] ? 'badge-green' : 'badge-slate' }} shrink-0">
                    {{ $indexnow['enabled'] ? 'Enabled' : 'Disabled' }} · {{ $indexnow['pending'] }} pending · {{ $indexnow['sent'] }} sent
                </span>
            </div>
            <form method="POST" action="{{ route('admin.settings.indexnow') }}" class="mt-4 flex flex-col sm:flex-row sm:items-end gap-4">
                @csrf
                <label class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-white/[0.02] px-4 py-3 cursor-pointer shrink-0">
                    <input type="checkbox" name="indexnow_enabled" value="1" {{ $indexnow['enabled'] ? 'checked' : '' }} class="w-4 h-4 accent-brand-500">
                    <span class="text-sm font-semibold text-white">Service enabled</span>
                </label>
                <label class="flex flex-col gap-1 flex-1">
                    <span class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">IndexNow key <span class="normal-case">(shared across stores — leave blank to auto-generate)</span></span>
                    <input type="text" name="indexnow_key" value="{{ $indexnow['key'] }}" placeholder="auto-generated hex key" class="input !py-2 !text-sm font-mono" />
                </label>
                <button class="btn-primary !py-2 text-xs">Save IndexNow</button>
            </form>
            <form method="POST" action="{{ route('admin.settings.indexnow-run') }}" class="mt-3 flex items-center gap-3 flex-wrap">
                @csrf
                <button class="btn !py-2 text-xs !bg-sky-500/15 !text-sky-300 border-sky-500/30 hover:!bg-sky-500/25">▶ Flush pending now</button>
                <span class="text-[11px] text-slate-500">Runs <code class="text-brand-300 text-xs">indexnow:flush --all</code>.</span>
            </form>
            <p class="text-[11px] text-slate-600 mt-3 leading-relaxed">
                Per-store opt-out is on <a href="{{ route('admin.stores') }}" class="text-brand-400 hover:text-brand-300 font-semibold">Stores → tracking/IndexNow</a>.
                Note: IndexNow helps freshness — it is not an llms.txt-style promise; AI engines still decide what they cite.
            </p>
        </section>

        {{-- ── Plan pricing ──────────────────────────────────────────────── --}}
        <section class="glass rounded-2xl p-5 md:p-6">
            <h2 class="font-display font-bold text-white text-lg">Plan pricing (INR / month)</h2>
            <p class="text-xs text-slate-400 mt-1 max-w-2xl leading-relaxed">
                Monthly amounts charged through Shopify billing. The annual option is automatically 10 × the monthly price.
                Changing a price only affects <strong>new</strong> subscriptions — existing charges keep the amount the merchant approved.
            </p>
            <form method="POST" action="{{ route('admin.settings.billing') }}" class="mt-4 grid sm:grid-cols-3 gap-4 items-end">
                @csrf
                @foreach (['grow' => 'Grow', 'scale' => 'Scale', 'agency' => 'Agency'] as $planKey => $planName)
                    <label class="flex flex-col gap-1">
                        <span class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">{{ $planName }}</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-slate-500 text-sm">₹</span>
                            <input type="number" min="0" step="1" name="price_{{ $planKey }}" value="{{ $billing[$planKey] }}"
                                   class="input !py-2 !text-sm" required>
                        </div>
                        <span class="text-[10px] text-slate-600">Annual ₹{{ number_format($billing[$planKey] * 10) }}</span>
                    </label>
                @endforeach
                <button class="btn-primary !py-2 text-xs">Save prices</button>
            </form>
        </section>

        {{-- ── Per-store pause ───────────────────────────────────────────── --}}
        <section class="glass rounded-2xl p-5 md:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-display font-bold text-white">Pause tracking per store</h2>
                <p class="text-xs text-slate-400 mt-1">Suspend one tenant’s daily snapshots without touching their plan.</p>
            </div>
            <a href="{{ route('admin.stores') }}" class="btn !py-2 text-xs shrink-0">Manage stores →</a>
        </section>
    </main>
    <footer class="border-t border-white/10 py-6 text-center text-xs text-slate-600">
        AI Visibility · SaaS owner area · <a href="{{ route('privacy') }}" class="hover:text-slate-400">Privacy</a> · <a href="{{ route('terms') }}" class="hover:text-slate-400">Terms</a>
    </footer>
</body>
</html>
