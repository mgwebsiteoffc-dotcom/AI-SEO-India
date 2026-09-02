<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', [
        'title' => 'FAQ — AI SEO for Shopify stores in India',
        'description' => 'Answers about AI visibility, llms.txt, schema, ChatGPT/Gemini rankings, pricing in ₹, refunds and what AI Visibility does for Indian D2C brands.',
    ])
</head>
<body class="marketing">
    @include('marketing.partials.header')

    <section class="hero-bg relative overflow-hidden">
        <div class="grid-pattern absolute inset-0"></div>
        <div class="relative max-w-3xl mx-auto px-4 pt-16 pb-10 text-center">
            <div class="pill animate-fade-up">Questions, answered honestly</div>
            <h1 class="font-display mt-5 text-4xl md:text-5xl font-extrabold leading-tight animate-fade-up delay-100">
                Frequently asked <span class="grad-text">questions</span>
            </h1>
        </div>
    </section>

    <section class="pb-16">
        <div class="max-w-3xl mx-auto px-4 space-y-3">
            @php
                $faqs = [
                    ['What is "AI SEO" or "AI visibility"?', 'When shoppers ask ChatGPT, Gemini or Perplexity "best vitamin C serum for oily skin under ₹1,000", the AI answers with a shortlist of brands. AI visibility is how likely your brand is on that shortlist — built from crawlable pages, schema, reviews and content AI engines trust.'],
                    ['Can you guarantee ranking in ChatGPT or Gemini?', 'No — and anyone who promises "rank #1 in AI" is selling snake oil. No major AI engine publishes its ranking rules. What we do is measurable and honest: we fix the signals (robots, schema, llms.txt, content) and track your mention rate every day so you see real movement.'],
                    ['What exactly does the AI Readiness Score measure?', 'A 30+ point audit across five weighted buckets: crawlability (robots.txt, sitemap, AI bot access), schema (Product/FAQ/Organization JSON-LD), content (titles, H1s, word counts, FAQ blocks), brand (name prominence, reviews, trust signals) and speed. It is scored 0–100 with a prioritised fix list.'],
                    ['Does llms.txt actually help?', 'As of 2026 no major AI engine has confirmed reading llms.txt, and public studies show little citation lift. Treat it as cheap hygiene, not a strategy — that is why our scorecard gives it a small weight and never sells it as a silver bullet.'],
                    ['How does the visibility tracker work?', 'Every day at 6 AM IST we run your tracked shopping queries against each AI engine\'s public surface (or an honest retrieval proxy when no API key is set), count whether your brand (and competitors) are mentioned, and store the snapshot. You watch mention-rate trends per query per engine over weeks.'],
                    ['Which platforms do you support?', 'Built for Shopify first (Smart Blogger publishes straight to your Shopify blog, schema installs via a theme app extension, attribution uses the orders/paid webhook). The free AI Readiness Score and audit work for any ecommerce site.'],
                    ['What does pricing look like in ₹?', 'Free plan included. Paid plans start at ₹999/month (Grow), ₹1,999/month (Scale) and ₹4,999/month (Agency), billed via Shopify in INR. Annual billing saves roughly 17%. Every paid plan pays for itself in about two AI-referred orders.'],
                    ['Is there a refund policy?', 'Yes — unused time on a paid plan is refunded if you cancel within the first 7 days. Details are in the Terms of Service.'],
                    ['Do you support WhatsApp / Hinglish?', 'Yes. Set your WhatsApp number in Settings for priority support, and choose English, Hinglish or हिंदी — Smart Blogger content and recommendations follow that language.'],
                    ['How do I install it?', 'Click "Install on Shopify" and approve the OAuth screen in your store admin — no developer needed. Schema, robots.txt and llms.txt deploy through a theme app extension; everything else runs in our app. You can try the full product on a seeded demo store first.'],
                ];
            @endphp
            @foreach ($faqs as [$q, $a])
                <details class="card-dark rounded-2xl group open:border-brand-500/40 transition-colors">
                    <summary class="cursor-pointer list-none px-5 py-4 flex items-center justify-between gap-4 text-sm font-semibold text-white select-none">
                        {{ $q }}
                        <span class="text-brand-400 text-lg leading-none transition-transform group-open:rotate-45">+</span>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-slate-400 leading-relaxed">{{ $a }}</div>
                </details>
            @endforeach
        </div>

        <div class="max-w-3xl mx-auto px-4 mt-10 text-center">
            <p class="text-sm text-slate-400">Still unsure? <a href="https://wa.me/919876543210" target="_blank" class="text-brand-400 hover:text-brand-300 font-semibold">Message us on WhatsApp</a> — we reply in English or Hinglish.</p>
            <div class="flex flex-wrap items-center justify-center gap-3 mt-6">
                <a href="{{ route('app', ['demo' => 1]) }}" class="btn-primary">See it live →</a>
                <a href="{{ route('scorecard') }}" class="btn-secondary">Get your free score</a>
            </div>
        </div>
    </section>

    @include('marketing.partials.footer')
</body>
</html>
