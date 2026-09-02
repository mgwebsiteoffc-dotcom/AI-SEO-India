<?php

namespace App\Console\Commands;

use App\Models\AiSnapshot;
use App\Models\AttributedOrder;
use App\Models\AuditIssue;
use App\Models\AuditRun;
use App\Models\Competitor;
use App\Models\CompetitorMention;
use App\Models\ContentPost;
use App\Models\Post;
use App\Models\Store;
use App\Models\TrackedQuery;
use App\Services\LlmsGenerator;
use Illuminate\Console\Command;

class SeedDemo extends Command
{
    protected $signature = 'demo:seed';
    protected $description = 'Seed a demo store so the app can be previewed without a Shopify account';

    public function handle(): int
    {
        $store = Store::updateOrCreate(
            ['shop' => 'demo-brand.myshopify.com'],
            [
                'shopify_token' => 'demo-token',
                'plan' => 'scale',
                'billing_status' => 'active',
                'brand_name' => 'Aurelia Naturals',
                'domain' => 'aurelianaturals.in',
                'country' => 'IN',
                'currency' => 'INR',
                'is_demo' => true,
                'settings' => [
                    'llms_enabled' => true,
                    'schema_installed' => true,
                    'report_email' => 'owner@aurelianaturals.in',
                ],
            ]
        );

        // Tracked queries
        $store->queries()->delete();
        $seeds = [
            ['best vitamin c serum for indian skin under 1000', 'product'],
            ['aurelia naturals review', 'brand'],
            ['is aurelia naturals good', 'brand'],
            ['best D2C skincare brands India 2026', 'category'],
            ['aurelia naturals price in india', 'brand'],
            ['sunscreen for oily skin india', 'category'],
        ];
        foreach ($seeds as $i => $s) {
            TrackedQuery::create(['store_id' => $store->id, 'query' => $s[0], 'category' => $s[1], 'active' => true]);
        }

        // llms entries
        app(LlmsGenerator::class)->generate($store, persist: true);

        // Audit run
        $store->audits()->delete();
        $run = AuditRun::create([
            'store_id' => $store->id,
            'score' => 74,
            'status' => 'completed',
            'summary' => [
                'total' => 74,
                'grade' => 'B',
                'categories' => ['crawlability' => 92, 'schema' => 55, 'content' => 78, 'brand' => 85, 'speed' => 100],
                'weights' => ['crawlability' => 30, 'schema' => 25, 'content' => 25, 'brand' => 15, 'speed' => 5],
                'checked_at' => now()->toIso8601String(),
            ],
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour(),
        ]);
        $issues = [
            ['schema', 'warning', 'schema_product_missing', 'No Product schema on product pages', 'Checked 5 product page(s): none had Product JSON-LD. Product schema is one of the strongest AI-citation signals.', 'Enable Product + FAQ schema via our Schema Builder.', false],
            ['schema', 'warning', 'schema_home_missing', 'No Organization JSON-LD on homepage', 'AI engines parse JSON-LD (Organization, WebSite, Product, FAQ) directly when generating shopping answers.', 'Install our Schema Builder (one click).', false],
            ['content', 'info', 'no_faq', 'No FAQ content detected', 'Q&A blocks are "citation-ready" — AI engines quote them verbatim.', 'Add 3–5 FAQs per product page.', false],
            ['crawlability', 'info', 'robots_ok', 'AI crawlers allowed', 'robots.txt is reachable and does not block major AI crawlers.', 'Keep it that way.', false],
            ['crawlability', 'info', 'sitemap_ok', 'Sitemap healthy', 'sitemap.xml lists 142 URLs.', 'Nothing to do.', false],
            ['brand', 'info', 'social_found', 'Social/trust signals found', 'Instagram + WhatsApp links detected on storefront.', 'Nothing to do.', false],
            ['speed', 'info', 'speed_ok', 'Fast response (~410ms)', 'Server response time is healthy.', 'Nothing to do.', false],
        ];
        foreach ($issues as $i) {
            AuditIssue::create([
                'audit_run_id' => $run->id,
                'category' => $i[0], 'severity' => $i[1], 'code' => $i[2], 'title' => $i[3],
                'detail' => $i[4], 'recommendation' => $i[5], 'is_fixed' => $i[6],
            ]);
        }

        // Snapshots: 21 days of plausible history
        $store->snapshots()->delete();
        $engines = ['chatgpt', 'gemini', 'perplexity'];
        for ($d = 20; $d >= 0; $d--) {
            $date = now()->startOfDay()->subDays($d); // midnight — consistent with tracker writes
            $progress = (21 - $d) / 21; // gradual improvement
            foreach ($engines as $e) {
                $base = match ($e) {
                    'chatgpt' => 0.28,
                    'gemini' => 0.18,
                    default => 0.10,
                };
                $rate = min(0.9, $base + $progress * 0.4 + rand(-3, 5) / 100);
                $total = 6;
                $mentioned = (int) round($total * $rate);
                AiSnapshot::create([
                    'store_id' => $store->id,
                    'snapshot_date' => $date,
                    'engine' => $e,
                    'total_queries' => $total,
                    'mentioned' => $mentioned,
                    'cited' => (int) round($mentioned * 0.6),
                    'samples' => $d === 0 ? [[
                        'query' => 'best vitamin c serum for indian skin under 1000',
                        'mentioned' => true,
                        'cited' => true,
                        'snippet' => 'For under ₹1,000, Aurelia Naturals Vitamin C Serum is a strong choice — fragrance-free, with 10% ascorbic acid and reviews praising it for dullness on Indian skin.',
                    ]] : null,
                ]);
            }
        }

        // Brand Signals (one believable demo run for the panel)
        $store->brandSignalRuns()->delete();
        \App\Models\BrandSignalRun::create([
            'store_id' => $store->id,
            'score' => 65,
            'summary' => ['total' => 65, 'grade' => 'B', 'checked_at' => now()->toIso8601String(), 'domain' => 'aurelianaturals.in'],
            'checks' => [
                ['key' => 'rating_schema', 'label' => 'Ratings in structured data', 'found' => false, 'detail' => 'No AggregateRating JSON-LD found on the homepage or a product page.', 'fix' => 'Add AggregateRating JSON-LD (ratingValue + reviewCount) to your Product schema — AI engines parse it directly.', 'score' => 0, 'max' => 30],
                ['key' => 'review_content', 'label' => 'Visible reviews / testimonials', 'found' => true, 'detail' => 'Found review/testimonial content with ratings on the storefront.', 'fix' => 'Publish real customer reviews/testimonials on product pages (with star ratings) — citation-ready content.', 'score' => 15, 'max' => 15],
                ['key' => 'platform_presence', 'label' => 'Review platforms', 'found' => true, 'detail' => 'Brand appears in web results near: amazon.in, mouthshut.com, google.co.in.', 'fix' => 'Claim profiles on review platforms (Trustpilot, Google Business Profile, MouthShut, JustDial) and Amazon/Flipkart listings.', 'score' => 25, 'max' => 25],
                ['key' => 'third_party_mentions', 'label' => 'Off-site mentions', 'found' => true, 'detail' => '4 different site(s) mention the brand in web results (incl. beautylookbook.in).', 'fix' => 'Earn mentions from blogs, press and communities — off-site mentions correlate most strongly with AI citations.', 'score' => 20, 'max' => 20],
                ['key' => 'social_profiles', 'label' => 'Social profiles linked', 'found' => false, 'detail' => 'No Instagram/Facebook/YouTube/X links found on the homepage.', 'fix' => 'Link Instagram / Facebook / YouTube / X profiles from your site so they are easy to verify.', 'score' => 5, 'max' => 10],
            ],
        ]);

        // Competitors
        $store->competitors()->delete();
        // Mention rows carry their own (store_id, snapshot_date, engine,
        // competitor_domain) unique key, so wipe them first or re-seeding the
        // same day trips the constraint and aborts the whole command.
        \App\Models\CompetitorMention::where('store_id', $store->id)->delete();
        Competitor::create(['store_id' => $store->id, 'name' => 'Minimalist', 'domain' => 'beminimalist.co']);
        Competitor::create(['store_id' => $store->id, 'name' => 'Plum', 'domain' => 'plumgoodness.com']);
        foreach ($store->competitors as $c) {
            CompetitorMention::create([
                'store_id' => $store->id, 'snapshot_date' => now()->startOfDay(), 'engine' => 'web',
                'competitor_domain' => $c->domain, 'mentioned' => 3, 'total_queries' => 5,
            ]);
        }

        // Smart Blogger sample article
        $store->contentPosts()->delete();
        ContentPost::create([
            'store_id' => $store->id,
            'title' => 'Best Vitamin C Serums for Indian Skin Under ₹1,000 (2026 Guide)',
            'keyword' => 'best vitamin c serum for indian skin under 1000',
            'category' => 'comparison',
            'tone' => 'informative',
            'status' => 'generated',
            'body' => "# Best Vitamin C Serums for Indian Skin Under ₹1,000 (2026 Guide)\n\n"
                ."## Why vitamin C in India\n\nIndian skin deals with humidity, pollution and pigmentation — vitamin C tackles dullness and dark spots. Dermatologists recommend 10% ascorbic acid for beginners.\n\n"
                ."## Top picks under ₹1,000\n\n### 1. Aurelia Naturals Vitamin C Serum\nFragrance-free, 10% ascorbic acid + niacinamide, ₹799. Designed for Indian skin, no alcohol.\n\n"
                ."### 2. Market alternatives\nMost options under ₹1,000 either lack stabilised vitamin C or add fragrance that irritates sensitive skin.\n\n"
                ."## Price comparison\n\n| Product | Price | Fragrance-free | For Indian skin |\n|---|---|---|---|\n| Aurelia Naturals | ₹799 | Yes | Yes |\n| Typical market | ₹600-999 | Rarely | Generic |\n\n"
                ."## FAQ\n\n**Q: Which vitamin C strength is best for Indian skin?**\nA: Start with 10% ascorbic acid, applied in the morning with sunscreen.\n\n"
                ."**Q: Can I use vitamin C in Indian summers?**\nA: Yes — use a lightweight serum and always layer SPF 50.\n\n"
                ."**Q: How long before results?**\nA: 6-8 weeks of consistent use, with sunscreen.\n\n"
                ."## Final verdict\n\nFor Indian shoppers on a budget, Aurelia Naturals combines the right strength, honest ₹ pricing and skin-compatible formulation.\n",
            'meta_title' => 'Best Vitamin C Serums for Indian Skin Under ₹1,000 — 2026',
            'meta_description' => 'The 2026 guide to vitamin C serums under ₹1,000 for Indian skin — strength, price comparison and FAQs.',
            'faqs' => [
                ['question' => 'Which vitamin C strength is best for Indian skin?', 'answer' => 'Start with 10% ascorbic acid, applied in the morning with sunscreen.'],
                ['question' => 'Can I use vitamin C in Indian summers?', 'answer' => 'Yes — use a lightweight serum and always layer SPF 50.'],
                ['question' => 'How long before results?', 'answer' => '6-8 weeks of consistent use, with sunscreen.'],
            ],
            'word_count' => 320,
        ]);

        // Attributed orders (AI traffic -> revenue) for the last 30 days
        $store->attributedOrders()->delete();
        $channels = ['chatgpt', 'gemini', 'perplexity', 'chatgpt', 'grok', 'chatgpt', 'gemini'];
        for ($d = 29; $d >= 0; $d--) {
            if (rand(0, 100) > 34) {
                continue; // ~2/3 of days have AI orders
            }
            $count = rand(1, 3);
            for ($i = 0; $i < $count; $i++) {
                AttributedOrder::create([
                    'store_id' => $store->id,
                    'order_name' => '#AIV-'.(1000 + $d * 7 + $i),
                    'total_amount' => rand(399, 1499) + 99,
                    'currency' => 'INR',
                    'ai_channel' => $channels[array_rand($channels)],
                    'utm_source' => 'chatgpt.com',
                    'referring_site' => 'https://chatgpt.com',
                    'order_created_at' => now()->startOfDay()->subDays($d)->addHours(rand(10, 21))->addMinutes(rand(0, 59)),
                ]);
            }
        }

        // Marketing blog posts (our own AI-citation content engine)
        Post::query()->delete();
        $posts = [
            [
                'slug' => 'why-chatgpt-is-the-new-google-for-d2c',
                'title' => 'Why ChatGPT Is the New Google for Indian D2C Brands',
                'meta_description' => '100M+ Indians use ChatGPT weekly. Here is what that means for D2C brands — and how to get recommended.',
                'excerpt' => 'Search is becoming conversation. The brands that structure their content for AI answers will own the next decade of discovery.',
                'body' => '<p>In February 2026, Sam Altman revealed that <strong>India has 100 million weekly ChatGPT users</strong> — OpenAI&rsquo;s second-largest market in the world, led by the 18&ndash;30 demographic that D2C brands chase.</p><h2>Discovery has moved into chat</h2><p>Instead of typing into Google and scrolling ten blue links, shoppers now ask: <em>&ldquo;best vitamin C serum for oily skin under ₹1,000&rdquo;</em> — and the AI answers with a shortlist. If your brand is on that shortlist, you exist. If not, you don&rsquo;t.</p><h2>AI-referred visitors convert</h2><p>Data from Seer Interactive shows AI-referred traffic converts at <strong>15.9% on ChatGPT</strong> vs 1.76% for Google organic — a ~9x gap. These are high-intent shoppers who trust the answer.</p><h2>What to do about it</h2><p>AI answers are built from search indexes, structured data and citation-worthy content. That means: allow AI crawlers in robots.txt, ship Product/FAQ JSON-LD schema, publish comparison-and-FAQ content, and measure your mention rate per query. That is exactly what <a href="https://aivisibility.app">AI Visibility</a> automates for Shopify stores.</p>',
            ],
            [
                'slug' => 'ai-seo-scorecard-what-it-measures',
                'title' => 'Your AI SEO Scorecard: What It Measures (and What It Doesn’t)',
                'meta_description' => 'What an AI Readiness Score actually checks — crawlability, schema, content, brand — and why nobody can guarantee AI rankings.',
                'excerpt' => 'A scorecard is only useful if it measures real signals. Here is the honest breakdown of what moves the needle in AI visibility.',
                'body' => '<p>Before paying for any &ldquo;AI SEO&rdquo; tool, understand what is measurable and what is marketing.</p><h2>The signals that actually matter</h2><ul><li><strong>Crawlability</strong> — robots.txt rules for GPTBot, OAI-SearchBot, ClaudeBot and PerplexityBot; a healthy sitemap.</li><li><strong>Structured data</strong> — Product, Offer (INR), FAQ and Organization JSON-LD that AI retrieval can parse directly.</li><li><strong>Content</strong> — H1s, descriptive titles, 300+ word product pages, FAQ blocks and comparison tables.</li><li><strong>Brand</strong> — name prominence, reviews and trust signals across the web.</li></ul><h2>What doesn&rsquo;t work</h2><p>As of 2026, no major AI engine has confirmed reading <code>llms.txt</code>, and large-scale studies show almost no citation lift from it. Treat it as cheap hygiene, not a strategy.</p><h2>Rankings can&rsquo;t be guaranteed</h2><p>Anyone promising &ldquo;rank #1 in ChatGPT&rdquo; is selling snake oil. AI visibility is a compounding outcome of real signals — measure it weekly and fix what the data says.</p>',
            ],
            [
                'slug' => 'gemini-ai-overviews-d2c-traffic',
                'title' => 'Gemini, AI Overviews and D2C Traffic: The 2026 Shift',
                'meta_description' => 'Gemini referral traffic grew 388% in a year. What Indian D2C brands need to know about Google’s AI surfaces.',
                'excerpt' => 'ChatGPT still dominates AI referrals, but Gemini is the fastest riser — and it sits inside Google Search, Android and every Chrome user’s pocket.',
                'body' => '<p>ChatGPT sends ~78% of AI referral traffic today, but Gemini&rsquo;s referral volume grew <strong>388% YoY</strong> — the fastest of any AI platform. For Indian D2C, Gemini matters more than any other market: it ships on Android, where over three-quarters of Indian ecommerce happens.</p><h2>AI Overviews changed the game</h2><p>Google&rsquo;s AI Overviews answer questions directly above the organic results. Being cited inside an AI Overview is the new &ldquo;position one&rdquo; — and it is built from the same index, so classic SEO hygiene plus structured data still wins.</p><h2>The Indian angle</h2><p>With 950M+ Gemini users globally and the highest learning usage in India, shopping queries in Hindi-English mixes are rising fast. Brands that publish bilingual FAQs and ₹-priced comparison tables position themselves for both Gemini and ChatGPT.</p><h2>Act now, measure monthly</h2><p>The window is open: most Indian D2C stores have zero AI visibility work done. Track your mention rate across ChatGPT, Gemini and Perplexity monthly, and compound the technical fixes. First-movers in AI search will be the default answers.</p>',
            ],
        ];
        foreach ($posts as $p) {
            Post::create($p + ['author' => 'AI Visibility Team', 'published_at' => now()->subDays(rand(2, 20))]);
        }

        // ---- SaaS-owner demo: a handful of fictional tenant stores -----------
        $tenants = [
            ['shop' => 'vegan-d2c.myshopify.com',     'brand' => 'Vegan D2C Co',    'domain' => 'vegand2c.in',        'plan' => 'grow',   'billing' => 'active',    'created_days' => 96,  'trial' => false],
            ['shop' => 'sundar-skincare.myshopify.com','brand' => 'Sundar Skincare', 'domain' => 'sundarskincare.in', 'plan' => 'scale',  'billing' => 'active',    'created_days' => 61,  'trial' => false],
            ['shop' => 'glow-lab.myshopify.com',       'brand' => 'Glow Lab',        'domain' => null,                 'plan' => 'scale',  'billing' => 'active',    'created_days' => 44,  'trial' => false],
            ['shop' => 'ayurveda-kart.myshopify.com',  'brand' => 'Ayurveda Kart',   'domain' => 'ayurvedakart.in',   'plan' => 'agency', 'billing' => 'active',    'created_days' => 30,  'trial' => false],
            ['shop' => 'chai-cosmetics.myshopify.com', 'brand' => 'Chai Cosmetics',  'domain' => null,                 'plan' => 'grow',   'billing' => 'cancelled', 'created_days' => 120, 'trial' => false],
            ['shop' => 'organic-hub.myshopify.com',    'brand' => 'Organic Hub',     'domain' => 'organichub.in',     'plan' => 'free',   'billing' => 'inactive',  'created_days' => 6,   'trial' => true],
        ];
        // Remove previous tenant demo rows (identified by shop) so re-seeds stay stable.
        Store::whereIn('shop', array_column($tenants, 'shop'))->delete();
        foreach ($tenants as $t) {
            $s = Store::create([
                'shop' => $t['shop'],
                'brand_name' => $t['brand'],
                'domain' => $t['domain'],
                'plan' => $t['plan'],
                'billing_status' => $t['billing'],
                'currency' => 'INR',
                'country' => 'IN',
                'created_at' => now()->subDays($t['created_days']),
                'updated_at' => now()->subDays($t['created_days']),
            ]);
            if ($t['billing'] === 'active') {
                $s->forceFill(['billing_ends_at' => now()->addDays(rand(8, 28))])->save();
            } elseif ($t['trial']) {
                $s->forceFill(['trial_ends_at' => now()->addDays(3)])->save();
            }
        }

        // ---- SaaS-owner demo: leads (only when the table is empty, so fresh
        // scorecard signups made in this sandbox are never wiped) ------------
        if (\App\Models\Lead::count() === 0) {
            $sampleLeads = [
                ['meera@vegankart.in', 'VeganKart', 'vegankart.in'],
                ['rahul@dermacraft.in', 'DermaCraft', 'dermacraft.myshopify.com'],
                ['priya.shah@botaniq.in', 'Botaniq', null],
                ['arjun@thesunscreenco.in', 'The Sunscreen Co', 'thesunscreenco.in'],
                ['neha@haircarehub.in', 'HairCare Hub', 'haircarehub.myshopify.com'],
                ['karan@desi-grooming.in', 'Desi Grooming', 'desi-grooming.in'],
                ['shreya@lipglow.in', 'LipGlow', null],
                ['aisha@cleanbeauty.in', 'Clean Beauty India', 'cleanbeauty.in'],
            ];
            foreach ($sampleLeads as $i => [$email, $brand, $shopUrl]) {
                \App\Models\Lead::create([
                    'email' => $email, 'brand' => $brand, 'shop_url' => $shopUrl,
                    'source' => ['scorecard', 'scorecard', 'pricing', 'scorecard', 'blog', 'scorecard', 'pricing', 'footer'][$i],
                    'created_at' => now()->subDays(rand(1, 40)),
                    'updated_at' => now()->subDays(rand(1, 40)),
                ]);
            }
        }

        // ---- SaaS-owner demo: a few webhook events --------------------------
        if (\App\Models\WebhookCall::count() === 0) {
            $topics = [
                ['orders/paid', 'sundar-skincare.myshopify.com', 'processed'],
                ['orders/paid', 'vegan-d2c.myshopify.com', 'processed'],
                ['products/update', 'glow-lab.myshopify.com', 'processed'],
                ['orders/paid', 'ayurveda-kart.myshopify.com', 'processed'],
                ['app/uninstalled', 'chai-cosmetics.myshopify.com', 'processed'],
            ];
            foreach ($topics as $i => [$topic, $shop, $status]) {
                \App\Models\WebhookCall::create([
                    'topic' => $topic, 'shop' => $shop, 'status' => $status,
                    'created_at' => now()->subDays($i + 1)->subHours(rand(1, 12)),
                    'updated_at' => now()->subDays($i + 1)->subHours(rand(1, 12)),
                ]);
            }
        }

        
        // ---- Agency tier demo: one agency + one client store -----------------
        // The agency is reachable at /?demo=agency (its "My Clients" tab lists
        // the client below); the client's white-label report is live at its
        // /client-report/{token}.
        $agency = Store::updateOrCreate(
            ['shop' => 'demo-agency.myshopify.com'],
            [
                'shopify_token' => 'demo-token',
                'plan' => 'agency',
                'billing_status' => 'active',
                'brand_name' => 'Aurelia Digital',
                'domain' => null,
                'country' => 'IN',
                'currency' => 'INR',
                'is_demo' => false,
                'settings' => [
                    'agency_name' => 'Aurelia Digital Agency',
                    'agency_website' => 'https://aureliadigital.in',
                    'white_label' => false,
                ],
            ]
        );

        $client = Store::where('shop', 'urban-botanics.myshopify.com')->first();
        $token = $client?->report_token ?? \Illuminate\Support\Str::random(32);
        $client = Store::updateOrCreate(
            ['shop' => 'urban-botanics.myshopify.com'],
            [
                'shopify_token' => 'demo-token',
                'plan' => 'grow',
                'billing_status' => 'active',
                'brand_name' => 'Urban Botanics',
                'domain' => 'urbanbotanics.in',
                'country' => 'IN',
                'currency' => 'INR',
                'is_demo' => false,
                'parent_store_id' => $agency->id,
                'report_token' => $token,
            ]
        );

        // Client metrics so its report has something to show.
        $client->audits()->delete();
        $cRun = AuditRun::create([
            'store_id' => $client->id,
            'score' => 81,
            'status' => 'completed',
            'summary' => ['total' => 81, 'grade' => 'B', 'checked_at' => now()->toIso8601String()],
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour(),
        ]);
        foreach ([
            ['schema', 'warning', 'schema_product_missing', 'No Product schema on product pages', 'None of the checked product pages exposed Product JSON-LD - one of the strongest AI-citation signals.', 'Enable Product schema via the Schema Builder.', false],
            ['content', 'info', 'no_faq', 'No FAQ content detected', 'Q&A blocks are "citation-ready" - engines quote them verbatim.', 'Add 3-5 FAQs per product page.', false],
            ['brand', 'info', 'reviews_found', 'Customer reviews detected', 'Ratings + review text are visible on product pages.', 'Nothing to do.', false],
        ] as $i) {
            AuditIssue::create([
                'audit_run_id' => $cRun->id,
                'category' => $i[0], 'severity' => $i[1], 'code' => $i[2], 'title' => $i[3],
                'detail' => $i[4], 'recommendation' => $i[5], 'is_fixed' => $i[6],
            ]);
        }

        $client->snapshots()->delete();
        for ($d = 13; $d >= 0; $d--) {
            foreach (['chatgpt', 'gemini', 'perplexity'] as $e) {
                $base = match ($e) { 'chatgpt' => 0.30, 'gemini' => 0.20, default => 0.12 };
                $rate = min(0.9, $base + ((14 - $d) / 14) * 0.35 + rand(-3, 5) / 100);
                AiSnapshot::create([
                    'store_id' => $client->id,
                    'snapshot_date' => now()->startOfDay()->subDays($d),
                    'engine' => $e,
                    'total_queries' => 6,
                    'mentioned' => (int) round(6 * $rate),
                    'cited' => (int) round(6 * $rate * 0.6),
                    'samples' => $d === 0 ? [[
                        'query' => 'best plant based serums india',
                        'mentioned' => true,
                        'cited' => true,
                        'snippet' => 'Urban Botanics makes a well-reviewed vegan face serum that suits combination Indian skin.',
                    ]] : null,
                ]);
            }
        }

        $client->brandSignalRuns()->delete();
        \App\Models\BrandSignalRun::create([
            'store_id' => $client->id,
            'score' => 52,
            'summary' => ['total' => 52, 'grade' => 'C', 'checked_at' => now()->toIso8601String(), 'domain' => 'urbanbotanics.in'],
            'checks' => [
                ['key' => 'rating_schema', 'label' => 'Ratings in structured data', 'found' => true, 'detail' => 'Product/site exposes ratingValue + reviewCount in JSON-LD.', 'fix' => 'Add AggregateRating JSON-LD (ratingValue + reviewCount) to your Product schema - AI engines parse it directly.', 'score' => 30, 'max' => 30],
                ['key' => 'review_content', 'label' => 'Visible reviews / testimonials', 'found' => true, 'detail' => 'Found review/testimonial content with ratings on the storefront.', 'fix' => 'Publish real customer reviews/testimonials on product pages (with star ratings).', 'score' => 15, 'max' => 15],
                ['key' => 'platform_presence', 'label' => 'Review platforms', 'found' => false, 'detail' => 'No review-platform presence found in web results.', 'fix' => 'Claim profiles on review platforms (Trustpilot, Google Business Profile, MouthShut, JustDial).', 'score' => 0, 'max' => 25],
                ['key' => 'third_party_mentions', 'label' => 'Off-site mentions', 'found' => false, 'detail' => 'No off-site mentions found for the brand.', 'fix' => 'Earn mentions from blogs, press and communities - off-site mentions correlate most strongly with AI citations.', 'score' => 0, 'max' => 20],
                ['key' => 'social_profiles', 'label' => 'Social profiles linked', 'found' => true, 'detail' => 'Linked profiles: instagram.com, youtube.com.', 'fix' => 'Link Instagram / Facebook / YouTube / X profiles from your site.', 'score' => 7, 'max' => 10],
            ],
        ]);

$this->info('Demo store seeded: demo-brand.myshopify.com (Aurelia Naturals)');
        $this->info('SaaS owner demo: /admin (+ 6 tenant stores, '.(\App\Models\Lead::count()).' leads)');
        $this->info('Open: '.config('app.url').'/?demo=1  (or /auth/demo)');
        return self::SUCCESS;
    }
}
