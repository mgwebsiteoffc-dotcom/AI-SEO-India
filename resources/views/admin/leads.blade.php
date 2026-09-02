<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', ['title' => 'Leads', 'description' => 'Every scorecard / marketing lead.'])
</head>
<body class="marketing min-h-screen">
    @include('admin.partials.topbar', ['active' => 'leads'])
    <main class="max-w-7xl mx-auto px-4 py-8 space-y-5">
        <div class="flex items-end justify-between gap-4 flex-wrap">
            <div>
                <h1 class="font-display text-2xl font-extrabold text-white">Leads</h1>
                <p class="text-sm text-slate-400 mt-1">{{ $leads->total() }} signups from the free AI Scorecard and marketing pages.</p>
            </div>
            <form method="get" class="flex gap-2">
                <input type="search" name="q" value="{{ $q }}" placeholder="Search email / brand / store…" class="input !py-2 !text-xs !w-64">
                <button class="btn-primary !py-2 text-xs">Search</button>
                @if ($q)<a href="{{ route('admin.leads') }}" class="btn !py-2 text-xs">Reset</a>@endif
            </form>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach (['scorecard' => 'Scorecard', 'pricing' => 'Pricing', 'blog' => 'Blog', 'footer' => 'Footer/other'] as $k => $label)
                <div class="glass rounded-xl px-4 py-3">
                    <div class="text-[11px] uppercase tracking-wider text-slate-500">{{ $label }}</div>
                    <div class="font-display text-xl font-extrabold text-white">{{ $sources->contains($k) ? number_format(\App\Models\Lead::where('source', $k)->count()) : 0 }}</div>
                </div>
            @endforeach
        </div>

        <div class="glass rounded-2xl overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500 border-b border-white/10">
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Brand</th>
                        <th class="px-4 py-3">Store URL</th>
                        <th class="px-4 py-3">Source</th>
                        <th class="px-4 py-3">When</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leads as $l)
                        <tr class="border-b border-white/5 hover:bg-white/[0.03]">
                            <td class="px-4 py-3 text-white">{{ $l->email }}</td>
                            <td class="px-4 py-3">{{ $l->brand ?: '—' }}</td>
                            <td class="px-4 py-3 text-[11px] text-slate-400">{{ $l->shop_url ?: '—' }}</td>
                            <td class="px-4 py-3"><span class="badge">{{ $l->source }}</span></td>
                            <td class="px-4 py-3 text-[11px] text-slate-500">{{ $l->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.lead.delete', $l) }}" onsubmit="return confirm('Delete this lead?')">
                                    @csrf
                                    <button class="btn !py-1.5 text-[11px] !text-red-400">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-xs text-slate-500">No leads yet — submit the free scorecard on the site to see them here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="text-xs text-slate-500">{{ $leads->links('admin.pagination') }}</div>
    </main>
    <footer class="border-t border-white/10 py-6 text-center text-xs text-slate-600">
        AI Visibility · SaaS owner area · <a href="{{ route('privacy') }}" class="hover:text-slate-400">Privacy</a> · <a href="{{ route('terms') }}" class="hover:text-slate-400">Terms</a>
    </footer>
</body>
</html>
