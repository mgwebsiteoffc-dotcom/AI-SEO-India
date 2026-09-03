<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', ['title' => 'Blog Management', 'description' => 'Manage blog posts with SEO/AEO optimization.'])
</head>
<body class="marketing min-h-screen">
    @include('admin.partials.topbar', ['active' => 'blogs'])
    <main class="max-w-5xl mx-auto px-4 py-8 space-y-6">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h1 class="font-display text-2xl font-extrabold text-white">Blog / SEO Content</h1>
                <p class="text-sm text-slate-400 mt-1">Manage blog posts with SEO meta, FAQ schema, and AEO guidelines.</p>
                <p class="text-[11px] text-slate-500 mt-1">Published posts appear on the <a href="{{ route('blog') }}" target="_blank" class="text-brand-400 hover:underline">marketing blog page</a> — not on the Shopify store's blog.</p>
            </div>
            <a href="{{ route('admin.blogs.create') }}" class="btn-primary !py-2 text-xs">+ New Post</a>
        </div>

        @if (session('status'))
            <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 p-4 text-sm text-emerald-300">{{ session('status') }}</div>
        @endif

        <div class="glass rounded-2xl overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/10 text-left">
                        <th class="px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Title</th>
                        <th class="px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Slug</th>
                        <th class="px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Date</th>
                        <th class="px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $p)
                        <tr class="border-b border-white/5 hover:bg-white/[0.02]">
                            <td class="px-5 py-3">
                                <div class="text-white font-medium truncate max-w-xs">{{ $p->title }}</div>
                                @if ($p->meta_description)
                                    <div class="text-[11px] text-slate-500 truncate max-w-xs mt-0.5">{{ Str::limit($p->meta_description, 80) }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-400 font-mono">/{{ $p->slug }}</td>
                            <td class="px-5 py-3">
                                @if ($p->published_at)
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold bg-emerald-500/20 text-emerald-300">Published</span>
                                @else
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold bg-amber-500/20 text-amber-300">Draft</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">{{ $p->published_at?->format('d M Y') ?? '—' }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.blogs.edit', $p) }}" class="text-xs text-brand-400 hover:text-brand-300">Edit</a>
                                    @if ($p->published_at)
                                        <a href="{{ route('blog.show', $p->slug) }}" target="_blank" class="text-xs text-slate-400 hover:text-white">View</a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.blogs.delete', $p) }}" onsubmit="return confirm('Delete this post?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-400 hover:text-red-300">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">
                                No blog posts yet. <a href="{{ route('admin.blogs.create') }}" class="text-brand-400 hover:underline">Create your first post →</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="text-center">
            {{ $posts->links() }}
        </div>
    </main>
</body>
</html>
