<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', [
        'title' => $post->exists ? 'Edit article — Blog Manager' : 'New article — Blog Manager',
        'description' => 'SEO/AEO-optimised blog editor with categories, FAQs and automatic JSON-LD.',
    ])
    <style>
        .serp-card { background:#fff; border-radius:12px; padding:14px 16px; }
        .serp-url { color:#1a0dab; font-size:12px; }
        .serp-title { color:#1a0dab; font-size:16px; line-height:1.3; font-weight:400; }
        .serp-title b { font-weight:700; }
        .serp-desc { color:#4d5156; font-size:12px; line-height:1.5; margin-top:4px; }
        .tbar-btn { @apply inline-flex items-center justify-center w-8 h-8 rounded-lg text-[13px] font-bold
                    text-slate-300 hover:bg-white/10 hover:text-white transition-colors border-0 bg-transparent cursor-pointer; }
        .tbar-sep { width:1px; height:20px; background:rgba(255,255,255,.12); margin:0 4px; align-self:center; }
        .faq-row { @apply rounded-2xl border border-white/10 bg-white/[0.03] p-4; }
        .field-hint { @apply text-[11px] text-slate-500 mt-1; }
        .counter { @apply text-[11px] tabular-nums; }
    </style>
</head>
<body class="marketing min-h-screen">
    @include('admin.partials.topbar', ['active' => 'blog'])
    <main class="max-w-7xl mx-auto px-4 py-8">
        <form method="POST" action="{{ $post->exists ? route('admin.blog.update', $post) : route('admin.blog.store') }}"
              class="space-y-5" onsubmit="return confirmPostSave()">
            @csrf
            <div class="flex items-end justify-between gap-4 flex-wrap">
                <div>
                    <a href="{{ route('admin.blog') }}" class="text-[11px] text-slate-500 hover:text-white font-semibold">← Blog manager</a>
                    <h1 class="font-display text-2xl font-extrabold text-white mt-1">
                        {{ $post->exists ? 'Edit article' : 'New article' }}
                    </h1>
                </div>
                <div class="flex items-center gap-2">
                    @if ($post->exists && $post->published_at)
                        <a href="{{ route('blog.show', $post->slug) }}" target="_blank" class="btn !py-2 text-xs">View live ↗</a>
                    @endif
                    <button class="btn-primary !py-2 text-xs">Save article</button>
                </div>
            </div>

            @if ($errors->any())
                <div class="rounded-xl bg-red-500/15 border border-red-500/40 text-red-300 text-sm px-4 py-3">
                    <b>Please fix the following:</b>
                    <ul class="list-disc ml-5 mt-1">
                        @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="grid lg:grid-cols-3 gap-5 items-start">
                {{-- Left: content --}}
                <div class="lg:col-span-2 space-y-5">
                    <section class="glass rounded-2xl p-5">
                        <h2 class="font-display font-bold text-white text-sm mb-4">1 · Article</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-semibold text-slate-300">Title</label>
                                <input id="f-title" name="title" value="{{ old('title', $post->title) }}" required maxlength="160"
                                       class="input mt-1" placeholder="e.g. Why ChatGPT Is the New Google for Indian D2C Brands">
                                <div class="field-hint"><span class="counter" id="c-title">0</span>/160 characters — keep it under ~60 for search, longer is fine for humans.</div>
                            </div>
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-semibold text-slate-300">Slug <span class="text-slate-600">(URL)</span></label>
                                    <div class="flex gap-2">
                                        <input id="f-slug" name="slug" value="{{ old('slug', $post->slug) }}"
                                               class="input mt-1 font-mono" placeholder="why-chatgpt-is-the-new-google-for-d2c">
                                        <button type="button" onclick="slugFromTitle()" title="Generate from title"
                                                class="btn !py-2 text-xs mt-1 shrink-0">Auto</button>
                                    </div>
                                    <div class="field-hint">Lowercase, hyphens. Empty → generated from the title.</div>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-300">Category</label>
                                    <select name="category_id" class="input mt-1">
                                        <option value="">— Uncategorised —</option>
                                        @foreach ($categories as $c)
                                            <option value="{{ $c->id }}" @selected((int) old('category_id', $post->category_id) === $c->id)>{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="field-hint">Category pages get their own CollectionPage + ItemList JSON-LD.</div>
                                </div>
                            </div>
                            <div class="grid sm:grid-cols-2 gap-4 items-end">
                                <div>
                                    <label class="text-xs font-semibold text-slate-300">Author</label>
                                    <input name="author" value="{{ old('author', $post->author ?: 'AI Visibility Team') }}" maxlength="120" class="input mt-1">
                                </div>
                                <div class="flex items-end gap-3">
                                    <div class="flex-1">
                                        <label class="text-xs font-semibold text-slate-300">Publish date</label>
                                        <input type="date" id="f-pubdate" name="published_date"
                                               value="{{ old('published_date', $post->published_at?->format('Y-m-d') ?: now()->format('Y-m-d')) }}"
                                               class="input mt-1">
                                    </div>
                                    <label class="flex items-center gap-2 cursor-pointer pb-2">
                                        <input type="checkbox" name="publish" value="1" id="f-publish" class="w-4 h-4 accent-brand-500"
                                               @checked($post->published_at !== null || ! $post->exists)>
                                        <span class="text-xs font-semibold text-slate-300">Published</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="glass rounded-2xl p-5">
                        <h2 class="font-display font-bold text-white text-sm mb-1">2 · Content editor</h2>
                        <p class="text-[11px] text-slate-500 mb-4">Write in HTML — headings (&lt;h2&gt;/&lt;h3&gt;), lists, quotes and tables make your article citation-ready for AI answers.</p>
                        <div class="rounded-xl border border-white/10 overflow-hidden">
                            <div class="flex items-center flex-wrap gap-0.5 px-2 py-1.5 bg-white/[0.03] border-b border-white/10">
                                <button type="button" class="tbar-btn" data-cmd="h2" title="Heading 2">H2</button>
                                <button type="button" class="tbar-btn" data-cmd="h3" title="Heading 3">H3</button>
                                <span class="tbar-sep"></span>
                                <button type="button" class="tbar-btn" data-cmd="bold" title="Bold"><b>B</b></button>
                                <button type="button" class="tbar-btn" data-cmd="italic" title="Italic"><i>I</i></button>
                                <button type="button" class="tbar-btn" data-cmd="underline" title="Underline"><u>U</u></button>
                                <span class="tbar-sep"></span>
                                <button type="button" class="tbar-btn" data-cmd="ul" title="Bullet list">•≡</button>
                                <button type="button" class="tbar-btn" data-cmd="ol" title="Numbered list">1≡</button>
                                <button type="button" class="tbar-btn" data-cmd="quote" title="Blockquote">❝</button>
                                <button type="button" class="tbar-btn" data-cmd="code" title="Code">&lt;/&gt;</button>
                                <span class="tbar-sep"></span>
                                <button type="button" class="tbar-btn" data-cmd="link" title="Link">🔗</button>
                                <span class="tbar-sep"></span>
                                <button type="button" class="tbar-btn" data-cmd="table" title="Comparison table">⊞</button>
                                <button type="button" class="tbar-btn" data-cmd="faq" title="Insert FAQ heading">FAQ</button>
                            </div>
                            <textarea id="f-body" name="body" rows="18" required
                                      class="w-full bg-transparent text-white text-sm leading-relaxed p-4 focus:outline-none font-mono"
                                      placeholder="<h2>Discovery has moved into chat</h2>&#10;<p>Write your article…</p>">{{ old('body', $post->body) }}</textarea>
                        </div>
                        <div class="field-hint flex items-center justify-between mt-2">
                            <span>Tip: the first <b>h2</b> should contain your primary keyword — engines answer from headings + paragraphs.</span>
                            <span class="counter" id="c-body">0</span>
                        </div>
                    </section>

                    <section class="glass rounded-2xl p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="font-display font-bold text-white text-sm">3 · FAQs <span class="text-slate-500">(FAQPage JSON-LD — the strongest AEO signal)</span></h2>
                                <p class="text-[11px] text-slate-500 mt-0.5">Question/answer pairs become visible Q&amp;A blocks <i>and</i> structured data. Max 12.</p>
                            </div>
                            <button type="button" onclick="addFaq()" class="btn-primary !py-2 text-xs">+ Add FAQ</button>
                        </div>
                        <div id="faq-list" class="space-y-3">
                            @foreach (old('faqs', $post->faqs ?? []) as $i => $faq)
                                <div class="faq-row">
                                    <div class="flex items-start justify-between gap-2">
                                        <input name="faqs[{{ $i }}][q]" value="{{ $faq['q'] ?? '' }}" maxlength="300"
                                               class="input text-sm" placeholder="Question shoppers (and AIs) actually ask…">
                                        <button type="button" onclick="removeFaq(this)" class="btn !py-1.5 text-[11px] !text-red-400 shrink-0">Remove</button>
                                    </div>
                                    <textarea name="faqs[{{ $i }}][a]" rows="2" maxlength="2000"
                                              class="input text-sm mt-2" placeholder="Concise, factual answer (2–3 sentences, cite specifics)…">{{ $faq['a'] ?? '' }}</textarea>
                                </div>
                            @endforeach
                        </div>
                        <div id="faq-empty" class="text-center text-xs text-slate-500 py-6 border border-dashed border-white/10 rounded-xl {{ count(old('faqs', $post->faqs ?? [])) ? 'hidden' : '' }}">
                            No FAQs yet — add 2–4 to earn the FAQ rich result.
                        </div>
                    </section>
                </div>

                {{-- Right: SEO/AEO + JSON-LD preview --}}
                <div class="space-y-5 lg:sticky lg:top-20">
                    <section class="glass rounded-2xl p-5">
                        <h2 class="font-display font-bold text-white text-sm mb-4">4 · SEO metadata</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-semibold text-slate-300">Meta title <span class="text-slate-600">(optional)</span></label>
                                <input id="f-metatitle" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" maxlength="200"
                                       class="input mt-1" placeholder="Falls back to the article title">
                                <div class="field-hint"><span class="counter" id="c-metatitle">0</span>/200 — 50–60 chars display best in search.</div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-300">Meta description</label>
                                <textarea id="f-metadesc" name="meta_description" rows="3" maxlength="320"
                                          class="input mt-1 text-sm" placeholder="The snippet under the title — summarise the value + include the keyword.">{{ old('meta_description', $post->meta_description) }}</textarea>
                                <div class="field-hint"><span class="counter" id="c-metadesc">0</span>/320 — keep the key sentence inside ~155 chars.</div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-300">Meta keywords <span class="text-slate-600">(comma separated)</span></label>
                                <input id="f-metakeys" name="meta_keywords" value="{{ old('meta_keywords', $post->meta_keywords) }}" maxlength="500"
                                       class="input mt-1" placeholder="ai seo india, d2c brand visibility, chatgpt recommendations">
                                <div class="field-hint"><span class="counter" id="c-metakeys">0</span>/500 — also rendered as topic tags + AEO entity hints.</div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-300">Excerpt <span class="text-slate-600">(card text)</span></label>
                                <textarea name="excerpt" rows="3" maxlength="800"
                                          class="input mt-1 text-sm" placeholder="One-two lines shown on the blog index and in JSON-LD description.">{{ old('excerpt', $post->excerpt) }}</textarea>
                            </div>
                        </div>
                    </section>

                    <section class="glass rounded-2xl p-5">
                        <div class="text-xs font-bold text-white mb-2">Live search preview</div>
                        <div class="serp-card">
                            <div class="serp-url" id="p-url">aivisibility.app/blog/your-slug</div>
                            <div class="serp-title" id="p-title"><b>Article title</b></div>
                            <div class="serp-desc" id="p-desc">Meta description appears here.</div>
                        </div>
                    </section>

                    <section class="glass rounded-2xl p-5">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-xs font-bold text-white">Structured data (JSON-LD)</div>
                            <button type="button" onclick="copyLd()" class="text-[11px] text-brand-400 hover:text-brand-300 font-semibold">Copy</button>
                        </div>
                        <p class="text-[11px] text-slate-500 mb-3">Auto-emitted on the live page: BlogPosting + FAQPage (when FAQs exist) + BreadcrumbList.</p>
                        <pre id="p-ld" class="rounded-xl bg-black/40 p-3 text-[10px] leading-relaxed text-emerald-300 overflow-auto max-h-72 font-mono whitespace-pre"></pre>
                    </section>

                    <section class="rounded-2xl border border-emerald-500/30 bg-emerald-500/[0.06] p-4 text-[11px] text-slate-300 leading-relaxed">
                        <b class="text-emerald-300">AEO checklist</b>
                        <ul class="mt-2 space-y-1 list-none">
                            <li id="a-keyword-title">○ Primary keyword present in the title</li>
                            <li id="a-keyword-desc">○ Keyword echoed in the meta description</li>
                            <li id="a-keyword-h2">○ First H2 contains the keyword</li>
                            <li id="a-faq">○ At least 2 FAQs → FAQPage rich result</li>
                            <li id="a-keywords">○ Meta keywords set (topic hints)</li>
                        </ul>
                    </section>
                </div>
            </div>
        </form>
    </main>

    <script>
    // ---------- slug helper ----------
    function slugify(s) { return (s||'').toString().toLowerCase().trim().replace(/[^a-z0-9\s-]/g,'').replace(/[\s_]+/g,'-').replace(/-+/g,'-').replace(/^-|-$/g,''); }
    function slugFromTitle() {
        const t = document.getElementById('f-title').value;
        if (!t) return alert('Type a title first.');
        document.getElementById('f-slug').value = slugify(t);
    }

    // ---------- FAQ repeater ----------
    const FAQ_TMPL = (i) => `
        <div class="faq-row">
            <div class="flex items-start justify-between gap-2">
                <input name="faqs[${i}][q]" maxlength="300" class="input text-sm" placeholder="Question shoppers (and AIs) actually ask…">
                <button type="button" onclick="removeFaq(this)" class="btn !py-1.5 text-[11px] !text-red-400 shrink-0">Remove</button>
            </div>
            <textarea name="faqs[${i}][a]" rows="2" maxlength="2000" class="input text-sm mt-2" placeholder="Concise, factual answer (2–3 sentences, cite specifics)…"></textarea>
        </div>`;
    function addFaq() {
        const list = document.getElementById('faq-list');
        if (list.children.length >= 12) return alert('Max 12 FAQs.');
        list.insertAdjacentHTML('beforeend', FAQ_TMPL(list.children.length));
        document.getElementById('faq-empty')?.classList.add('hidden');
    }
    function removeFaq(btn) {
        btn.closest('.faq-row').remove();
        const list = document.getElementById('faq-list');
        // re-index names
        [...list.children].forEach((row, i) => {
            row.querySelector('input').name = `faqs[${i}][q]`;
            row.querySelector('textarea').name = `faqs[${i}][a]`;
        });
        document.getElementById('faq-empty')?.classList.toggle('hidden', list.children.length > 0);
    }

    // ---------- HTML editor toolbar ----------
    const bodyTa = document.getElementById('f-body');
    function wrap(sel, before, after = '') { const v = bodyTa.value; bodyTa.value = v.slice(0, sel.start) + before + sel.text + after + v.slice(sel.end); }
    function wrapLines(before) { const sel = bodyTa.value.substring(bodyTa.selectionStart, bodyTa.selectionEnd); wrap(sel, before, before.replace('<', '</')); }
    document.querySelectorAll('[data-cmd]').forEach(btn => btn.addEventListener('click', () => {
        const cmd = btn.dataset.cmd, s = bodyTa.selectionStart, e = bodyTa.selectionEnd, v = bodyTa.value;
        const lineStart = v.lastIndexOf('\n', s - 1) + 1;
        const lineEnd = v.indexOf('\n', e) === -1 ? v.length : v.indexOf('\n', e);
        const line = v.slice(lineStart, lineEnd).trim();
        const insert = (t) => { bodyTa.setRangeText(t, s, e, 'end'); };
        switch (cmd) {
            case 'bold':    wrap('', '**', '**'); break;
            case 'italic':  wrap('', '_', '_'); break;
            case 'underline': wrap('', '<u>', '</u>'); break;
            case 'h2': case 'h3': {
                const tag = cmd.toUpperCase();
                const inner = line ? v.slice(lineStart, lineEnd) : '';
                bodyTa.value = v.slice(0, lineStart) + (inner ? `<${tag}>${inner}</${tag}>` : `<${tag}></${tag}>`) + v.slice(lineEnd);
                break;
            }
            case 'ul': case 'ol': {
                const lines = (line ? [line] : ['List item']).map(x => (cmd === 'ul' ? '  - ' : '  1. ') + x).join('\n');
                bodyTa.value = v.slice(0, lineStart) + lines + v.slice(lineEnd);
                break;
            }
            case 'quote':   wrapLines('<blockquote>'); break;
            case 'code':    wrap('', '<code>', '</code>'); break;
            case 'table':   insert('\n<table><thead><tr><th>Brand</th><th>Why it wins</th></tr></thead><tbody><tr><td></td><td></td></tr></tbody></table>\n'); break;
            case 'faq':     insert('\n<h2>Frequently asked questions</h2>\n'); break;
            case 'link': {
                const url = prompt('URL (https://…):');
                if (url) wrap('', '[anchor text](', url + ')');
                break;
            }
        }
        bodyTa.focus();
        refresh();
    }));

    // ---------- counters / live preview / AEO checklist ----------
    const $ = (id) => document.getElementById(id);
    function setCounter(input, el) { el.textContent = (input.value || '').length; }
    function serpTitle() {
        const meta = $('f-metatitle').value.trim(), t = $('f-title').value.trim();
        let out = meta || t || 'Article title';
        if (out.length > 60) out = out.slice(0, 57) + '…';
        return out;
    }
    function serpDesc() {
        const meta = $('f-metadesc').value.trim(), ex = document.querySelector('[name=excerpt]').value.trim();
        let out = meta || ex || 'Meta description appears here.';
        if (out.length > 155) out = out.slice(0, 152) + '…';
        return out;
    }
    function buildLd() {
        const t = $('f-title').value.trim() || 'Untitled article';
        const slug = slugify($('f-slug').value || t);
        const date = $('f-pubdate').value || new Date().toISOString().slice(0,10);
        const bodyText = (bodyTa.value || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
        const excerpt = document.querySelector('[name=excerpt]').value.trim();
        const cat = document.querySelector('[name=category_id] option:checked');
        const faqs = [...document.querySelectorAll('#faq-list .faq-row')]
            .map(r => ({ q: r.querySelector('input').value.trim(), a: r.querySelector('textarea').value.trim() }))
            .filter(f => f.q && f.a);
        const graph = [{ ['@'+'context']: 'https://schema.org', '@type': 'BlogPosting',
            '@id': location.origin + '/blog/' + slug + '#article',
            'headline': t,
            'description': excerpt || serpDesc(),
            'image': location.origin + '/og-image.svg',
            'datePublished': date, 'dateModified': date,
            'author': { '@type': 'Organization', 'name': document.querySelector('[name=author]').value || 'AI Visibility' },
            'publisher': { '@type': 'Organization', 'name': 'AI Visibility', 'logo': { '@type': 'ImageObject', 'url': location.origin + '/favicon.svg' } },
            'mainEntityOfPage': location.origin + '/blog/' + slug,
            'keywords': $('f-metakeys').value.split(',').map(k => k.trim()).filter(Boolean).join(', '),
        }];
        if (cat && cat.value) {
            const cName = cat.textContent.trim();
            graph.push({ '@type': 'BreadcrumbList', '@id': location.origin + '/blog/' + slug + '#breadcrumb',
                'itemListElement': [
                    { '@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': location.origin + '/' },
                    { '@type': 'ListItem', 'position': 2, 'name': 'Blog', 'item': location.origin + '/blog' },
                    { '@type': 'ListItem', 'position': 3, 'name': cName, 'item': location.origin + '/blog/category/' + cat.value } ] });
            graph[0].articleSection = cName;
        }
        if (faqs.length) graph.push({ '@type': 'FAQPage', '@id': location.origin + '/blog/' + slug + '#faq',
            'mainEntity': faqs.map(f => ({ '@type': 'Question', 'name': f.q, 'acceptedAnswer': { '@type': 'Answer', 'text': f.a } })) });
        return { ['@'+'context']: 'https://schema.org', '@graph': graph };
    }
    function refresh() {
        const tl = $('f-title');
        setCounter(tl, $('c-title')); setCounter($('f-metatitle'), $('c-metatitle'));
        setCounter($('f-metadesc'), $('c-metadesc')); setCounter($('f-metakeys'), $('c-metakeys'));
        $('c-body').textContent = (bodyTa.value || '').length.toLocaleString('en-IN') + ' chars';
        $('p-title').innerHTML = serpTitle().replace(/([a-z0-9]+(?:[- ][a-z0-9]+)+)/gi, '<b>$1</b>');
        const slug = slugify($('f-slug').value || tl.value) || 'your-slug';
        $('p-url').textContent = 'aivisibility.app/blog/' + slug;
        $('p-desc').textContent = serpDesc();
        const kw = tl.value.toLowerCase();
        const bodyFirst = (bodyTa.value || '').slice(0, 600).toLowerCase();
        const set = (id, ok) => { const li = $(id); li.className = ok ? 'text-emerald-300' : ''; li.textContent = (ok ? '● ' : '○ ') + li.textContent.replace(/^[●○]\s*/, ''); };
        const re = kw.split(/\s+/).filter(w => w.length > 3);
        set('a-keyword-title', re.every(w => tl.value.toLowerCase().includes(w)));
        set('a-keyword-desc', re.some(w => serpDesc().toLowerCase().includes(w)));
        set('a-keyword-h2', re.every(w => bodyFirst.includes(w)));
        const faqCount = document.querySelectorAll('#faq-list .faq-row').length;
        set('a-faq', faqCount >= 2);
        set('a-keywords', ($('f-metakeys').value || '').trim().length > 0);
        const ld = buildLd();
        $('p-ld').textContent = JSON.stringify(ld, null, 2);
        window.__ld = ld;
    }
    function copyLd() {
        if (!window.__ld) refresh();
        navigator.clipboard?.writeText(JSON.stringify(window.__ld, null, 2));
    }
    function confirmPostSave() {
        const checked = $('f-publish').checked;
        return checked || confirm('Save as a draft? It will NOT be visible on /blog until published.');
    }
    // wire live refresh
    ['f-title','f-slug','f-metatitle','f-metadesc','f-metakeys'].forEach(id => $(id).addEventListener('input', refresh));
    document.querySelector('[name=excerpt]').addEventListener('input', refresh);
    document.querySelector('[name=author]').addEventListener('input', refresh);
    document.querySelector('[name=category_id]').addEventListener('change', refresh);
    bodyTa.addEventListener('input', refresh);
    const obs = new MutationObserver(() => refresh());
    obs.observe(document.getElementById('faq-list'), { childList: true, subtree: true });
    document.addEventListener('DOMContentLoaded', () => { refresh(); setCounter($('f-title'), $('c-title')); });
    refresh();
    </script>
</body>
</html>
