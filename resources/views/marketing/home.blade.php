<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', [
        'title' => 'Get found by ChatGPT, Gemini & Perplexity',
        'description' => 'The AI SEO platform for Indian D2C brands. Run your AI Readiness Score, get an action plan, track mentions across every major AI — and turn AI answers into orders. From ₹999/month.',
    ])
    <style>
        .hero-orb { position: absolute; border-radius: 9999px; filter: blur(70px); opacity: .5; pointer-events: none; }
    </style>
</head>
<body class="marketing">
    @include('marketing.partials.header')

    <!-- ============ HERO ============ -->
    <section class="hero-bg relative overflow-hidden">
        <div class="hero-orb w-[520px] h-[520px] bg-brand-500/30 -top-40 -left-32"></div>
        <div class="hero-orb w-[420px] h-[420px] bg-sky-400/20 top-10 right-[-120px]"></div>
        <div class="grid-pattern absolute inset-0"></div>

        <div class="relative max-w-6xl mx-auto px-4 pt-20 pb-24 text-center">
            <div class="pill animate-fade-up">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse-dot"></span>
                100M+ Indians ask ChatGPT every week — is your brand the answer?
            </div>

            <h1 class="font-display mt-6 text-4xl md:text-6xl font-extrabold leading-[1.08] max-w-4xl mx-auto animate-fade-up delay-100">
                Get recommended by<br>
                <span class="grad-text">ChatGPT, Gemini &amp; Perplexity</span>
            </h1>

            <p class="mt-6 text-lg text-slate-400 max-w-2xl mx-auto animate-fade-up delay-200 leading-relaxed">
                The AI SEO platform for Indian D2C brands. Run your AI Readiness Score, fix the signals AI
                actually reads, track your mentions — and turn AI answers into orders.
            </p>

            <div class="mt-9 flex flex-col sm:flex-row items-center justify-center gap-3 animate-fade-up delay-300">
                <a href="{{ route('scorecard') }}" class="btn-primary px-7 py-3.5 text-sm !bg-brand-500 hover:!bg-brand-400 shadow-xl shadow-brand-500/25">
                    Run your free AI Score →
                </a>
                <a href="{{ route('app', ['demo' => 1]) }}" class="btn-secondary px-7 py-3.5 text-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    Watch the live demo
                </a>
            </div>

            <p class="mt-5 text-xs text-slate-500">Free plan forever · 3-day trial on paid · Cancel anytime · Made for Indian stores</p>

            <!-- Floating product preview -->
            <div class="relative mt-16 max-w-4xl mx-auto animate-fade-up delay-300">
                <div class="absolute -inset-x-8 -top-10 h-40 bg-gradient-to-r from-brand-500/20 via-sky-400/10 to-emerald-400/20 blur-3xl rounded-full"></div>
                <div class="glass-strong rounded-3xl p-6 md:p-8 text-left shadow-2xl shadow-black/40">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <div class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold">AI Readiness Score</div>
                            <div class="font-display font-bold text-white mt-0.5">Aurelia Naturals · aurelianaturals.in</div>
                        </div>
                        <div class="flex items-end gap-1">
                            <span class="font-display text-5xl font-extrabold grad-text">74</span>
                            <span class="text-sm text-slate-500 mb-1.5">/100 · Grade B</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
                        @foreach ([['Crawlability', 92, 'text-emerald-400'], ['Schema', 55, 'text-red-400'], ['Content', 78, 'text-amber-400'], ['Brand', 85, 'text-emerald-400'], ['Speed', 100, 'text-emerald-400']] as [$label, $val, $color])
                        <div class="rounded-xl bg-white/5 border border-white/10 p-3 text-center">
                            <div class="text-[10px] text-slate-500 uppercase tracking-wide">{{ $label }}</div>
                            <div class="text-lg font-extrabold mt-1 {{ $color }}">{{ $val }}</div>
                            <div class="relative h-1 mt-2 rounded-full bg-white/10 overflow-hidden">
                                <div class="absolute inset-y-0 left-0 rounded-full {{ $val >= 80 ? 'bg-emerald-400' : ($val >= 60 ? 'bg-amber-400' : 'bg-red-400') }}" style="width: {{ $val }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="grid md:grid-cols-2 gap-3 text-xs">
                        <div class="rounded-xl border border-amber-400/30 bg-amber-400/10 p-3.5 text-amber-300">
                            <b>Fix first:</b> No Product schema on product pages — enable Schema Builder (one click)
                        </div>
                        <div class="rounded-xl border border-emerald-400/30 bg-emerald-400/10 p-3.5 text-emerald-300">
                            <b>Good:</b> AI crawlers allowed in robots.txt · Sitemap healthy (142 URLs)
                        </div>
                    </div>
                </div>
                <!-- floating chip -->
                <div class="absolute -bottom-5 -right-2 md:-right-6 glass-strong rounded-2xl px-4 py-3 flex items-center gap-3 animate-float shadow-xl">
                    <span class="relative flex w-2.5 h-2.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-60"></span><span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-400"></span></span>
                    <div class="text-left">
                        <div class="text-[10px] text-slate-400">Mentions today</div>
                        <div class="text-sm font-bold text-white">ChatGPT 42% · Gemini 31%</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ STATS ============ -->
    <section class="relative -mt-1 border-y border-white/10 bg-surface-900/60">
        <div class="max-w-6xl mx-auto px-4 py-12 grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            @foreach ([
                ['100M+', 'weekly ChatGPT users in India', 'India is OpenAI’s #2 market'],
                ['15.9%', 'AI-referred visitors convert', 'vs 1.76% for Google organic'],
                ['388%', 'Gemini referral growth YoY', 'fastest-rising AI channel'],
                ['₹999', 'from / month', 'pays for itself in 2 AI orders'],
            ] as [$num, $label, $sub])
            <div class="card-dark card-dark-hover p-5">
                <div class="font-display text-3xl font-extrabold grad-text">{{ $num }}</div>
                <div class="text-sm font-semibold text-slate-300 mt-1.5">{{ $label }}</div>
                <div class="text-[11px] text-slate-500 mt-1">{{ $sub }}</div>
            </div>
            @endforeach
        </div>
    </section>

    <!-- ============ HOW IT WORKS ============ -->
    <section id="how" class="py-24 section-glow">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center max-w-2xl mx-auto">
                <div class="pill">One loop, every week</div>
                <h2 class="font-display mt-4 text-3xl md:text-4xl font-extrabold">Score → Fix → Track → Publish</h2>
                <p class="text-slate-400 mt-4 leading-relaxed">Indian shoppers now ask AI what to buy. We make sure the answer mentions your brand — with measurable, honest progress.</p>
            </div>
            <div class="grid md:grid-cols-4 gap-4 mt-14">
                @foreach ([
                    ['01', 'Score', 'AI Readiness Score', 'A 30+ point audit of crawlability, schema, content and brand — weighted to a shareable 0–100 scorecard.'],
                    ['02', 'Fix', 'Fix the signals', 'One-click robots.txt AI-bot rules, JSON-LD schema, llms.txt and sitemap — no developer needed.'],
                    ['03', 'Track', 'Track mentions', 'Daily snapshots of how often ChatGPT, Gemini and Perplexity mention you for real shopping queries.'],
                    ['04', 'Publish', 'Publish content', 'Smart Blogger writes India-flavoured comparison & FAQ articles and publishes them to your Shopify blog.'],
                ] as [$num, $step, $title, $desc])
                <div class="card-dark card-dark-hover p-6 relative overflow-hidden">
                    <div class="absolute top-4 right-5 font-display text-5xl font-extrabold text-white/5">{{ $num }}</div>
                    <div class="text-[11px] font-bold uppercase tracking-widest text-brand-400">{{ $step }}</div>
                    <h3 class="font-display font-bold text-white mt-3 text-lg">{{ $title }}</h3>
                    <p class="text-sm text-slate-400 mt-2 leading-relaxed">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ============ FEATURES ============ -->
    <section id="features" class="py-24 bg-surface-900/40 border-y border-white/5">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center max-w-2xl mx-auto">
                <div class="pill">Everything from the plan, built in</div>
                <h2 class="font-display mt-4 text-3xl md:text-4xl font-extrabold">A full AI visibility stack for Indian D2C</h2>
                <p class="text-slate-400 mt-4">Not a black box. Every feature measures or fixes a real signal — so you always know what to do next.</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 mt-14">
                @foreach ([
                    ['AI Readiness Score', '30+ evidence-based checks weighted to a 0–100 score with a prioritised fix list — and a shareable scorecard.', '<path d="M4 19V5m0 14h16M8 16l3-4 3 3 5-7"/>'],
                    ['AI Visibility Tracker', 'Mention & citation rates per query per engine, tracked daily at 6 AM IST. Competitor comparison included.', '<path d="M4 19V9m5 10V5m5 14v-7m5 7v-5"/>'],
                    ['Smart Blogger', 'AI-written comparison & FAQ articles in Indian English or Hinglish — built from your real catalog, published with one click.', '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>'],
                    ['AI Traffic Attribution', 'See orders that came from ChatGPT, Gemini and Perplexity — revenue, AOV and channel breakdown from your orders/paid webhook.', '<path d="M3 17l6-6 4 4 7-8m0 0h-4m4 0v4"/>'],
                    ['GA4 Data API', 'AI-sourced sessions, transactions and purchase revenue straight from Google Analytics 4 — service-account setup, no OAuth popup.', '<path d="M9 19v-6m4 6V9m4 10V5M5 19v-3"/>'],
                    ['AI Sentiment Analysis', 'How do AI answers currently feel about your brand? Score, themes and one concrete recommendation.', '<path d="M12 21a9 9 0 1 0-9-9c0 2 .6 3.9 1.7 5.4L4 21l3.9-1.4A9 9 0 0 0 12 21z"/>'],
                    ['Schema Builder', 'Organization, Product and FAQ JSON-LD with live ₹ pricing — injected natively via the theme app extension.', '<path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>'],
                    ['llms.txt + robots.txt', 'AI reading list and crawler rules for GPTBot, ClaudeBot, PerplexityBot, Google-Extended & more.', '<path d="M4 7V5h16v2M9 20h6M12 5v15"/>'],
                    ['INR billing + WhatsApp support', 'Priced for India (₹999 / ₹1,999 / ₹4,999), monthly or annual. Support in English & Hinglish on WhatsApp.', '<path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/><path d="M9 12l2 2 4-4"/>'],
                ] as [$title, $desc, $icon])
                <div class="card-dark card-dark-hover p-6">
                    <div class="feature-icon bg-gradient-to-br from-brand-500/25 to-sky-400/15 border border-brand-500/30 text-brand-400">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg>
                    </div>
                    <h3 class="font-display font-bold text-white mt-4">{{ $title }}</h3>
                    <p class="text-sm text-slate-400 mt-2 leading-relaxed">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ============ PRODUCT PROOF ============ -->
    <section class="py-24">
        <div class="max-w-6xl mx-auto px-4 grid lg:grid-cols-2 gap-14 items-center">
            <div>
                <div class="pill">Proof, not promises</div>
                <h2 class="font-display mt-4 text-3xl md:text-4xl font-extrabold leading-tight">Your customers are asking AI.<br><span class="grad-text">Make sure it answers with you.</span></h2>
                <p class="text-slate-400 mt-5 leading-relaxed text-[15px]">
                    AI answers are built from search indexes, structured data and citation-worthy content.
                    We track your real mention rate per query, per engine — and tell you exactly which signal to
                    fix next. No rank guarantees, no snake oil. Just compounding, measurable visibility.
                </p>
                <ul class="mt-7 space-y-3.5 text-sm text-slate-300">
                    @foreach ([
                        'Daily mention & citation tracking across ChatGPT, Gemini, Perplexity & more',
                        'One-click fixes for schema, robots.txt and llms.txt',
                        'Articles written for AI citation — in Indian English or Hinglish',
                        'Orders traced back to AI platforms with revenue & AOV',
                    ] as $li)
                    <li class="flex gap-3">
                        <span class="mt-0.5 w-5 h-5 rounded-full bg-emerald-400/15 border border-emerald-400/40 text-emerald-400 flex items-center justify-center shrink-0">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                        </span>{{ $li }}
                    </li>
                    @endforeach
                </ul>
                <div class="mt-9 flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('scorecard') }}" class="btn-primary px-6 py-3 text-sm !bg-brand-500 hover:!bg-brand-400">Get my free AI Score</a>
                    <a href="{{ route('pricing') }}" class="btn-secondary px-6 py-3 text-sm">See pricing</a>
                </div>
            </div>

            <!-- Tracker mockup -->
            <div class="relative">
                <div class="absolute -inset-6 bg-gradient-to-tr from-brand-500/15 via-transparent to-emerald-400/10 blur-2xl rounded-full"></div>
                <div class="glass-strong rounded-3xl p-6 shadow-2xl shadow-black/40">
                    <div class="flex items-center justify-between mb-5">
                        <div class="text-sm font-bold text-white">AI Visibility Tracker</div>
                        <span class="badge-green !bg-emerald-400/15 !text-emerald-300">Daily snapshot · 6 AM IST</span>
                    </div>
                    @foreach ([
                        ['ChatGPT', '#10a37f', 42, 5],
                        ['Gemini', '#4285f4', 31, 2],
                        ['Perplexity', '#20b8cd', 18, 1],
                    ] as [$engine, $color, $pct, $delta])
                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4 mb-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-semibold text-slate-200">{{ $engine }}</span>
                            <span class="font-display font-extrabold" style="color: {{ $color }}">{{ $pct }}%</span>
                        </div>
                        <div class="relative h-1.5 mt-2.5 rounded-full bg-white/10 overflow-hidden">
                            <div class="absolute inset-y-0 left-0 rounded-full" style="width: {{ $pct }}%; background: {{ $color }}"></div>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-2">▲ {{ $delta }}% vs last week · 3 of 6 queries mentioned you</div>
                    </div>
                    @endforeach
                    <div class="rounded-xl border border-brand-500/30 bg-brand-500/10 p-3.5 text-xs text-brand-300 leading-relaxed">
                        “best vitamin c serum for indian skin under 1000” → <b>Aurelia Naturals</b> mentioned with a citation ✓
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ TESTIMONIALS ============ -->
    <section class="py-24 bg-surface-900/40 border-y border-white/5">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center max-w-2xl mx-auto">
                <div class="pill">What Indian founders say</div>
                <h2 class="font-display mt-4 text-3xl md:text-4xl font-extrabold">Trusted by D2C brands getting found</h2>
            </div>
            <div class="grid md:grid-cols-3 gap-4 mt-12">
                @foreach ([
                    ['“We went from invisible to cited in ChatGPT answers within 2 months. The scorecard told us exactly what to fix — schema first, then content.”', 'Priya S.', 'Founder, skincare D2C · Mumbai'],
                    ['“The Smart Blogger articles in Hinglish are gold. Published straight to our Shopify blog, and Gemini now quotes us for ‘best sunscreen for oily skin’.”', 'Rohan M.', 'Co-founder, beauty brand · Bengaluru'],
                    ['“Finally an AI SEO tool that doesn’t promise rankings. It shows real mention rates and links orders to ChatGPT. That honesty won our renewal.”', 'Ananya K.', 'Marketing lead, fashion D2C · Delhi'],
                ] as [$quote, $name, $role])
                <div class="card-dark card-dark-hover p-6 flex flex-col">
                    <div class="flex gap-0.5 text-amber-400">
                        @for ($i = 0; $i < 5; $i++)<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/></svg>@endfor
                    </div>
                    <p class="text-sm text-slate-300 leading-relaxed mt-4 flex-1">“{{ $quote }}”</p>
                    <div class="mt-5 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-brand-500 to-sky-400 flex items-center justify-center text-xs font-extrabold text-white">{{ strtoupper(substr($name, 0, 1)) }}</div>
                        <div>
                            <div class="text-sm font-semibold text-white">{{ $name }}</div>
                            <div class="text-[11px] text-slate-500">{{ $role }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ============ FAQ ============ -->
    <section class="py-24">
        <div class="max-w-3xl mx-auto px-4">
            <div class="text-center">
                <div class="pill">Questions</div>
                <h2 class="font-display mt-4 text-3xl md:text-4xl font-extrabold">Frequently asked</h2>
            </div>
            <div class="mt-10 space-y-3">
                @foreach ([
                    ['Can you guarantee I’ll rank #1 in ChatGPT?', 'No — and anyone who promises that is selling snake oil. AI rankings depend on retrieval, structured data and content, which compound over 2–6 months. We measure your real mention rate weekly and fix the signals that move it. That’s the honest version of “AI SEO”, and it works.'],
                    ['How is my AI Readiness Score calculated?', 'A 30+ point evidence-based audit across 5 weighted categories: crawlability (30), schema (25), content (25), brand (15) and speed (5). We actually fetch your storefront — robots.txt, sitemap, homepage and product pages — and deduct for missing or broken signals.'],
                    ['Do I need a Shopify account to try it?', 'No. Run the free scorecard for any store, or explore the full demo dashboard with seeded data — no login needed. To install on your live store, use the Shopify App Store flow.'],
                    ['What is the retrieval-proxy tracking mode?', 'Without an OpenAI/Gemini API key, we check whether your brand appears in the live web results that feed AI answers — a genuine, free proxy for “would an AI engine find you”. Add a key in .env to switch to asking the actual LLM per query.'],
                    ['Does llms.txt actually help?', 'As of 2026, no major AI engine has confirmed reading llms.txt — we treat it as cheap future-proofing hygiene. Your real visibility comes from schema, crawlability and citation-ready content, which the app also fixes and tracks.'],
                    ['What does it cost?', 'Free plan forever. Grow ₹999/mo, Scale ₹1,999/mo, Agency ₹4,999/mo — monthly or annual (save ~17%). Billed by Shopify in INR, 3-day free trial on paid plans, cancel anytime.'],
                ] as [$q, $a])
                <details class="card-dark group p-5 open:border-brand-500/50">
                    <summary class="list-none cursor-pointer flex items-center justify-between gap-4 text-sm font-semibold text-white">
                        {{ $q }}
                        <span class="shrink-0 w-6 h-6 rounded-full border border-white/15 flex items-center justify-center text-slate-400 group-open:rotate-45 transition-transform">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        </span>
                    </summary>
                    <p class="text-sm text-slate-400 leading-relaxed mt-3">{{ $a }}</p>
                </details>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ============ CTA ============ -->
    <section class="pb-24">
        <div class="max-w-6xl mx-auto px-4">
            <div class="relative overflow-hidden rounded-3xl border border-brand-500/30 bg-gradient-to-br from-brand-600/20 via-surface-800 to-surface-900 p-10 md:p-16 text-center">
                <div class="hero-orb w-[420px] h-[420px] bg-brand-500/25 -top-32 left-1/2 -translate-x-1/2"></div>
                <div class="relative">
                    <h2 class="font-display text-3xl md:text-5xl font-extrabold leading-tight">Be the brand AI<br><span class="grad-text">recommends next.</span></h2>
                    <p class="text-slate-400 mt-5 max-w-xl mx-auto">Most Indian D2C stores have zero AI visibility work done. The window is open — first-movers become the default answers.</p>
                    <div class="mt-9 flex flex-col sm:flex-row justify-center gap-3">
                        <a href="{{ route('scorecard') }}" class="btn-primary px-8 py-3.5 text-sm !bg-brand-500 hover:!bg-brand-400">Get my free AI Score — 30 seconds</a>
                        <a href="{{ route('app', ['demo' => 1]) }}" class="btn-secondary px-8 py-3.5 text-sm">Explore the demo</a>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-4">Free plan forever · ₹999/mo from · No credit card for the scorecard</p>
                </div>
            </div>
        </div>
    </section>

    @include('marketing.partials.footer')
</body>
</html>
