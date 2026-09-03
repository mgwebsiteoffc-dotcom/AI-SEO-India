<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', ['title' => ($post ? 'Edit' : 'New') . ' Post', 'description' => 'Blog post editor with SEO and FAQ.'])
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body class="marketing min-h-screen">
    @include('admin.partials.topbar', ['active' => 'blogs'])
    <main class="max-w-4xl mx-auto px-4 py-8">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('admin.blogs') }}" class="text-xs text-slate-400 hover:text-white">← Back to posts</a>
            <h1 class="font-display text-xl font-extrabold text-white">{{ $post ? 'Edit Post' : 'New Post' }}</h1>
        </div>

        <form method="POST" action="{{ $post ? route('admin.blogs.update', $post) : route('admin.blogs.store') }}" class="space-y-6">
            @csrf
            @if ($post) @method('PUT') @endif

            @if ($errors->any())
                <div class="rounded-xl bg-red-500/10 border border-red-500/30 p-4 text-sm text-red-300">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <!-- Title & Slug -->
            <div class="glass rounded-2xl p-5 space-y-4">
                <div>
                    <label class="text-xs font-semibold text-slate-300">Title *</label>
                    <input type="text" name="title" value="{{ old('title', $post?->title) }}" required
                           class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                           placeholder="e.g. Best Skincare Brands in India 2026" id="post-title" oninput="autoSlug()" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Slug</label>
                        <input type="text" name="slug" value="{{ old('slug', $post?->slug) }}"
                               class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2.5 text-sm text-white font-mono placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                               placeholder="auto-generated-from-title" id="post-slug" />
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Author</label>
                        <input type="text" name="author" value="{{ old('author', $post?->author ?? 'AI Visibility Team') }}"
                               class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40" />
                    </div>
                </div>
            </div>

            <!-- SEO Meta -->
            <div class="glass rounded-2xl p-5 space-y-4">
                <div class="text-sm font-bold text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    SEO / AEO Meta
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-300">Meta Description <span class="text-slate-500">(max 160 chars for Google, 300 for AI)</span></label>
                    <textarea name="meta_description" rows="2" maxlength="300"
                              class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                              placeholder="Concise summary for search engines and AI assistants...">{{ old('meta_description', $post?->meta_description) }}</textarea>
                    <div class="text-[11px] text-slate-500 mt-1">Used in &lt;meta name="description"&gt; and by AI engines for snippet generation.</div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-300">Excerpt / Summary</label>
                    <textarea name="excerpt" rows="2"
                              class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                              placeholder="Short preview for blog listing pages...">{{ old('excerpt', $post?->excerpt) }}</textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-300">SEO / AEO Guidelines Notes <span class="text-slate-500">(internal, not published)</span></label>
                    <textarea name="seo_notes" rows="3"
                              class="mt-1 w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                              placeholder="Target keywords, internal links to add, AI citation strategy...">{{ old('seo_notes') }}</textarea>
                    <div class="text-[11px] text-slate-500 mt-1">Notes for your team — not visible to readers. Use for tracking SEO/AEO optimization strategy.</div>
                </div>
            </div>

            <!-- FAQ Schema Builder -->
            <div class="glass rounded-2xl p-5 space-y-4" id="faq-section">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-bold text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        FAQ Schema <span class="text-[10px] text-emerald-400 font-normal">(AI citation-ready)</span>
                    </div>
                    <button type="button" onclick="addFaq()" class="text-xs text-brand-400 hover:text-brand-300">+ Add FAQ</button>
                </div>
                <p class="text-xs text-slate-500">FAQs are injected as JSON-LD schema. AI engines quote them verbatim in shopping answers.</p>
                <div id="faq-list" class="space-y-3">
                    <!-- FAQs populated by JS -->
                </div>
                <input type="hidden" name="faq_json" id="faq_json" value="{{ old('faq_json') }}" />
            </div>

            <!-- Body Editor -->
            <div class="glass rounded-2xl p-5 space-y-3">
                <div class="text-sm font-bold text-white">Content *</div>
                <div class="text-xs text-slate-500 mb-2">Write in Indian English with ₹ pricing. Include H2 sections, comparison tables, and a FAQ section for AI citation.</div>
                <textarea name="body" id="post-body" rows="20"
                          class="w-full rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/40 font-mono leading-relaxed"
                          >{{ old('body', $post?->body) }}</textarea>
            </div>

            <!-- Publish -->
            <div class="flex items-center justify-between gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="publish" value="1" class="rounded border-white/20 bg-white/5 text-brand-500 focus:ring-brand-500/40"
                           {{ ($post?->published_at) ? 'checked' : '' }} />
                    <span class="text-sm text-slate-300">Published</span>
                </label>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.blogs') }}" class="btn-secondary text-xs">Cancel</a>
                    <button type="submit" class="btn-primary text-xs">{{ $post ? 'Update Post' : 'Create Post' }}</button>
                </div>
            </div>
        </form>
    </main>

    <script>
        // Auto-generate slug from title
        function autoSlug() {
            const slug = document.getElementById('post-slug');
            if (slug.value === '' || slug.dataset.auto === '1') {
                slug.value = document.getElementById('post-title').value
                    .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
                slug.dataset.auto = '1';
            }
        }
        document.getElementById('post-slug')?.addEventListener('input', function() {
            this.dataset.auto = '0';
        });

        // FAQ builder
        let faqs = [];
        try {
            const existing = document.getElementById('faq_json')?.value;
            if (existing) faqs = JSON.parse(existing);
        } catch(e) {}

        function renderFaqs() {
            const list = document.getElementById('faq-list');
            list.innerHTML = faqs.map((f, i) => `
                <div class="rounded-xl bg-white/[0.03] border border-white/10 p-3 space-y-2">
                    <div class="flex items-start gap-2">
                        <span class="text-[10px] text-slate-500 font-bold mt-2">Q${i+1}</span>
                        <input type="text" value="${f.question.replace(/"/g, '&quot;')}" onchange="faqs[${i}].question=this.value; saveFaqs()"
                               class="flex-1 rounded-lg bg-white/5 border border-white/10 px-2.5 py-1.5 text-xs text-white focus:outline-none focus:ring-1 focus:ring-brand-500/40"
                               placeholder="Question..." />
                        <button type="button" onclick="faqs.splice(${i},1); renderFaqs()" class="text-red-400 hover:text-red-300 text-xs mt-1">✕</button>
                    </div>
                    <div class="flex items-start gap-2 ml-7">
                        <span class="text-[10px] text-slate-500 font-bold mt-2">A</span>
                        <textarea onchange="faqs[${i}].answer=this.value; saveFaqs()" rows="2"
                                  class="flex-1 rounded-lg bg-white/5 border border-white/10 px-2.5 py-1.5 text-xs text-white focus:outline-none focus:ring-1 focus:ring-brand-500/40"
                                  placeholder="Answer...">${f.answer}</textarea>
                    </div>
                </div>
            `).join('');
            saveFaqs();
        }

        function addFaq() {
            faqs.push({ question: '', answer: '' });
            renderFaqs();
        }

        function saveFaqs() {
            document.getElementById('faq_json').value = JSON.stringify(faqs);
        }

        renderFaqs();

        // Initialize TinyMCE if available, otherwise use plain textarea
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: '#post-body',
                height: 500,
                menubar: 'file edit view insert format tools',
                plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount',
                toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table | removeformat code | help',
                content_style: 'body { font-family: Inter, sans-serif; font-size: 14px; color: #e2e8f0; background: #0f172a; } a { color: #0a84ff; } h1,h2,h3 { color: #fff; }',
                skin: 'oxide-dark',
                content_css: 'dark',
            });
        }
    </script>
</body>
</html>
