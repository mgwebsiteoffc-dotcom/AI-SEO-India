<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', ['title' => 'Blog Categories', 'description' => 'Category taxonomy for the marketing blog.'])
</head>
<body class="marketing min-h-screen">
    @include('admin.partials.topbar', ['active' => 'blog'])
    <main class="max-w-5xl mx-auto px-4 py-8 space-y-5">
        <div class="flex items-end justify-between gap-4 flex-wrap">
            <div>
                <a href="{{ route('admin.blog') }}" class="text-[11px] text-slate-500 hover:text-white font-semibold">← Blog manager</a>
                <h1 class="font-display text-2xl font-extrabold text-white mt-1">Categories</h1>
                <p class="text-sm text-slate-400 mt-1">Each category gets a public landing page (<code class="text-brand-400">/blog/category/{slug}</code>) with CollectionPage + ItemList JSON-LD.</p>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-5 items-start">
            <div class="glass rounded-2xl overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500 border-b border-white/10">
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Slug</th>
                            <th class="px-4 py-3 text-center">Posts</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $c)
                            <tr class="border-b border-white/5 hover:bg-white/[0.03]">
                                <td class="px-4 py-3">
                                    <div class="text-white font-semibold">{{ $c->name }}</div>
                                    @if ($c->meta_description)
                                        <div class="text-[11px] text-slate-500 mt-0.5 max-w-56">{{ \Illuminate\Support\Str::limit($c->meta_description, 80) }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-[11px] text-slate-400">{{ $c->slug }}</td>
                                <td class="px-4 py-3 text-center"><span class="badge">{{ $c->posts_count }}</span></td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('admin.blog.categories.delete', $c) }}" onsubmit="return confirm('Delete “{{ $c->name }}”? Its posts stay, uncategorised.')">
                                        @csrf
                                        <button class="btn !py-1.5 text-[11px] !text-red-400">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-xs text-slate-500">No categories yet — add your first below.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="glass rounded-2xl p-5">
                <div class="text-sm font-bold text-white mb-4">Add a category</div>
                <form method="POST" action="{{ route('admin.blog.categories.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Name</label>
                        <input name="name" required maxlength="80" class="input mt-1" placeholder="e.g. AI Search">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Slug</label>
                        <input name="slug" class="input mt-1 font-mono" placeholder="ai-search (blank → auto from name)">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Meta description <span class="text-slate-600">(for the category page)</span></label>
                        <textarea name="meta_description" rows="3" maxlength="300" class="input mt-1 text-sm" placeholder="What this category covers — used in the H1-adjacent intro, meta tag and JSON-LD."></textarea>
                    </div>
                    <button class="btn-primary text-xs">Save category</button>
                </form>
                @if ($errors->any())
                    <div class="rounded-xl bg-red-500/15 border border-red-500/40 text-red-300 text-sm px-4 py-3 mt-4">
                        <ul class="list-disc ml-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif
            </div>
        </div>
    </main>
</body>
</html>
