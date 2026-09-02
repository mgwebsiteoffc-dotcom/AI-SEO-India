@php
    $tabs = [
        'overview' => ['route' => route('admin.overview'), 'label' => 'Overview'],
        'stores' => ['route' => route('admin.stores'), 'label' => 'Stores'],
        'leads' => ['route' => route('admin.leads'), 'label' => 'Leads'],
        'activity' => ['route' => route('admin.activity'), 'label' => 'Activity'],
        'settings' => ['route' => route('admin.settings'), 'label' => 'Settings'],
    ];
@endphp
<div class="border-b border-white/10 bg-surface-950/90 backdrop-blur-xl sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 min-w-0">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-sky-400 flex items-center justify-center text-white text-sm font-extrabold shrink-0">AI</div>
            <div class="leading-tight min-w-0">
                <div class="font-display font-bold text-white text-sm truncate">AI Visibility — Owner</div>
                <div class="text-[10px] text-slate-500">SaaS admin · {{ app()->environment() }}</div>
            </div>
        </div>
        <nav class="hidden md:flex items-center gap-1 text-xs font-semibold">
            @foreach ($tabs as $key => $t)
                <a href="{{ $t['route'] }}"
                   class="px-3 py-1.5 rounded-lg transition-colors {{ ($active ?? 'overview') === $key ? 'bg-brand-500/20 text-brand-300' : 'text-slate-400 hover:text-white' }}">{{ $t['label'] }}</a>
            @endforeach
        </nav>
        <a href="{{ route('home') }}" target="_blank"
           class="text-[11px] font-semibold text-slate-400 hover:text-white transition-colors border border-white/10 rounded-lg px-2.5 py-1.5 whitespace-nowrap">← View site</a>
    </div>
</div>

@if (session('status'))
    <div class="max-w-7xl mx-auto px-4 mt-4">
        <div class="rounded-xl bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-sm px-4 py-3">{{ session('status') }}</div>
    </div>
@endif
@if (session('error'))
    <div class="max-w-7xl mx-auto px-4 mt-4">
        <div class="rounded-xl bg-red-500/15 border border-red-500/40 text-red-300 text-sm px-4 py-3">{{ session('error') }}</div>
    </div>
@endif
