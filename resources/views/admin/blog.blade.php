<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', ['title' => 'Blog Manager', 'description' => 'Manage the AI Visibility marketing blog — SEO/AEO-optimised articles with categories, FAQs and JSON-LD.'])
</head>
<body class="marketing min-h-screen">
    @include('admin.partials.topbar', ['active' => 'blog'])
    <main class="max-w-7xl mx-auto px-4 py-8 space-y-5">
        <div class="flex items-end justify-between gap-4 flex-wrap">
            <div>
                <h1 class="font-display text-2xl font-extrabold text-white">Blog manager</h1>
                <p class="text-sm text-slate-400 mt-1">
                    {{ $posts->total() }} articles · {{ \App\Models\BlogCategory::count() }} categories.
                    Every article ships BlogPosting + FAQPage + Breadcrumb JSON-LD and a full SEO/AEO field set.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.blog.categories') }}" class="btn !py-2 text-xs">Categories</a>
                <a href="{{ route('admin.blog.create') }}" class="btn-primary !py-2 text-xs">+ New article</a>
            </div>
        </div>

        {{-- Filters --}}
        <form method="get" class="glass rounded-2xl p-4 flex flex-wrap items-center gap-3">
            <input type="search" name="q" value="{{ $q }}" placeholder="Search title / body / keywords…" class="input !py-2 !text-xs !w-72">
            <select name="category" class="input !py-2 !text-xs !w-52" onchange="this.form.submit()">
                <option value="">All categories</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected($filterCategory === $c->id)>{{ $c->name }} ({{ $c->posts_count ?? $c->posts()->count() }})</option>
                @endforeach
            </select>
            <select name="status" class="input !py-2 !text-xs !w-44" onchange="this.form.submit()">
                <option value="">Any status</option>
                <option value="published" @selected($filterStatus === 'published')>Published</option>
                <option value="draft" @selected($filterStatus === 'draft')>Drafts</option>
            </select>
            <button class="btn-primary !py-2 text-xs">Filter</button>
            @if ($q || $filterCategory || $filterStatus)
                <a href="{{ route('admin.blog') }}" class="btn !py-2 text-xs">Reset</a>
            @endif
        </form>

        <div class="glass rounded-2xl overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500 border-b border-white/10">
                        <th class="px-4 py-3">Article</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">SEO fields</th>
                        <th class="px-4 py-3">FAQs</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                        <tr class="border-b border-white/5 hover:bg-white/[0.03] align-top">
                            <td class="px-4 py-3">
                                <div class="text-white font-semibold max-w-md">{{ $post->title }}</div>
                                <a href="{{ route('blog.show', $post->slug) }}" target="_blank"
                                   class="text-[11px] text-brand-400 hover:text-brand-300">/blog/{{ $post->slug }} ↗</a>
                            </td>
                            <td class="px-4 py-3">
                                @if ($post->category)
                                    <span class="badge">{{ $post->category->name }}</span>
                                @else
                                    <span class="text-[11px] text-slate-600">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[11px] text-slate-500">
                                @if ($post->meta_title)<div class="text-slate-300">Title: {{ \Illuminate\Support\Str::limit($post->meta_title, 34) }}</div>@endif
                                @if ($post->meta_description)<div class="text-slate-500 mt-0.5">Desc: {{ \Illuminate\Support\Str::limit($post->meta_description, 42) }}</div>@endif
                                @if ($post->meta_keywords)<div class="text-slate-600 mt-0.5">Keywords: {{ \Illuminate\Support\Str::limit($post->meta_keywords, 42) }}</div>@endif
                                @if (! $post->meta_title && ! $post->meta_description && ! $post->meta_keywords)<span class="text-slate-600 italic">none set</span>@endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="badge {{ count($post->faqs ?? []) ? 'badge-green' : 'badge-slate' }}">
                                    {{ count($post->faqs ?? []) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($post->published_at)
                                    <span class="badge badge-green block w-fit">Published</span>
                                    <span class="text-[10px] text-slate-500 mt-1 block">{{ $post->published_at->format('d M Y') }}</span>
                                @else
                                    <span class="badge badge-amber block w-fit">Draft</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.blog.edit', $post) }}" class="btn !py-1.5 text-[11px]">Edit</a>
                                    <form method="POST" action="{{ route('admin.blog.delete', $post) }}" onsubmit="return confirm('Delete this article?')">
                                        @csrf
                                        <button class="btn !py-1.5 text-[11px] !text-red-400">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-xs text-slate-500">No articles match — write your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($posts->hasPages())
                <div class="px-4 py-3 border-t border-white/10">{{ $posts->links() }}</div>
            @endif
        </div>
    </main>
</body>
</html>
