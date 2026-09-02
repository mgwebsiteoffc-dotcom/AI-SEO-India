<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', ['title' => 'Stores', 'description' => 'All tenant stores on AI Visibility.'])
</head>
<body class="marketing min-h-screen">
    @include('admin.partials.topbar', ['active' => 'stores'])
    <main class="max-w-7xl mx-auto px-4 py-8 space-y-5">
        <div class="flex items-end justify-between gap-4 flex-wrap">
            <div>
                <h1 class="font-display text-2xl font-extrabold text-white">Stores</h1>
                <p class="text-sm text-slate-400 mt-1">{{ $stores->total() }} tenants · change a plan below and it takes effect for that store immediately.</p>
            </div>
            <form method="get" class="flex gap-2 flex-wrap">
                <input type="search" name="filter" value="{{ $filter }}" placeholder="Search shop / domain / brand…" class="input !py-2 !text-xs !w-56">
                <select name="plan" class="input !py-2 !text-xs !w-36">
                    <option value="">All plans</option>
                    @foreach (['free', 'grow', 'scale', 'agency'] as $p)
                        <option value="{{ $p }}" @selected($plan === $p)>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
                <button class="btn-primary !py-2 text-xs">Filter</button>
                @if ($filter || $plan)<a href="{{ route('admin.stores') }}" class="btn !py-2 text-xs">Reset</a>@endif
            </form>
        </div>

        <div class="glass rounded-2xl overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500 border-b border-white/10">
                        <th class="px-4 py-3">Store</th>
                        <th class="px-4 py-3">Plan / billing</th>
                        <th class="px-4 py-3">Signals</th>
                        <th class="px-4 py-3">Joined</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stores as $s)
                        <tr class="border-b border-white/5 hover:bg-white/[0.03]">
                            <td class="px-4 py-3">
                                <div class="text-white font-medium">{{ $s->brand_name ?: $s->shop }}</div>
                                <div class="text-[11px] text-slate-500">{{ $s->shop }}@if ($s->domain) · {{ $s->domain }}@endif</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge @if ($s->plan === 'free') badge-slate @elseif ($s->plan === 'grow') badge-green @elseif ($s->plan === 'scale') @endif">{{ $s->plan }}</span>
                                <div class="text-[11px] text-slate-500 mt-1">{{ $s->billing_status }}@if ($s->billing_ends_at) · till {{ $s->billing_ends_at->format('d M Y') }}@endif</div>
                            </td>
                            <td class="px-4 py-3 text-[11px] text-slate-400">
                                {{ $s->audits_count }} audits · {{ $s->content_posts_count }} posts · {{ $s->queries_count }} queries
                            </td>
                            <td class="px-4 py-3 text-[11px] text-slate-500">{{ $s->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($s->is_demo)
                                        <a href="{{ route('app', ['demo' => 1]) }}" target="_blank" class="btn !py-1.5 text-[11px]">Open demo →</a>
                                    @else
                                        <form method="POST" action="{{ route('admin.store.plan', $s) }}" class="flex gap-1.5 items-center">
                                            @csrf
                                            <select name="plan" class="input !py-1.5 !text-[11px] !w-28">
                                                @foreach (['free', 'grow', 'scale', 'agency'] as $p)
                                                    <option value="{{ $p }}" @selected($s->plan === $p)>{{ ucfirst($p) }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn-primary !py-1.5 text-[11px]">Save</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.store.delete', $s) }}" onsubmit="return confirm('Delete {{ $s->shop }}?')">
                                            @csrf
                                            <button class="btn !py-1.5 text-[11px] !text-red-400">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-xs text-slate-500">No stores match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="text-xs text-slate-500">{{ $stores->links("admin.pagination") }}</div>
    </main>
    <footer class="border-t border-white/10 py-6 text-center text-xs text-slate-600">
        AI Visibility · SaaS owner area · <a href="{{ route('privacy') }}" class="hover:text-slate-400">Privacy</a> · <a href="{{ route('terms') }}" class="hover:text-slate-400">Terms</a>
    </footer>
</body>
</html>
