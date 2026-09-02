<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', [
        'title' => 'Pricing — Free, ₹999, ₹1,999, ₹4,999/month',
        'description' => 'AI SEO for Indian D2C brands. Free AI Readiness Score. Grow ₹999/mo, Scale ₹1,999/mo, Agency ₹4,999/mo. Annual plans save ~17%. 3-day free trial. Billed by Shopify in INR.',
    ])
</head>
<body class="marketing">
    @include('marketing.partials.header')

    <section class="hero-bg relative overflow-hidden">
        <div class="grid-pattern absolute inset-0"></div>
        <div class="relative max-w-6xl mx-auto px-4 pt-20 pb-14 text-center">
            <div class="pill animate-fade-up">Priced for India · billed by Shopify in ₹</div>
            <h1 class="font-display mt-5 text-4xl md:text-5xl font-extrabold leading-tight animate-fade-up delay-100">
                Simple pricing, <span class="grad-text">in rupees</span>,<br>for Indian D2C
            </h1>
            <p class="text-slate-400 mt-5 max-w-xl mx-auto animate-fade-up delay-200">
                Pays for itself with 2–3 AI-referred orders a month. 3-day free trial on paid plans. Cancel anytime.
            </p>

            <!-- Monthly / Annual toggle -->
            <div class="inline-flex items-center gap-3 mt-9 animate-fade-up delay-300">
                <span id="lbl-monthly" class="text-sm font-semibold text-white">Monthly</span>
                <button id="billing-toggle" type="button" aria-label="Toggle annual billing"
                        class="relative w-14 h-8 rounded-full border border-white/15 bg-white/10 transition-colors">
                    <span id="billing-knob" class="absolute top-1 left-1 w-6 h-6 rounded-full bg-brand-500 shadow transition-transform"></span>
                </button>
                <span id="lbl-annual" class="text-sm font-semibold text-slate-400">Annual <span class="text-emerald-400 text-xs font-bold">save ~17%</span></span>
            </div>
        </div>
    </section>

    <section class="pb-16">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-4 items-stretch">
                @foreach ([
                    ['free', 'Free', '₹0', '0', ['AI Readiness Score', '25 tracked queries / month', '1 competitor', 'AI SEO guides', 'Community support'], false, false],
                    ['grow', 'Grow', '₹999', '9990', ['Everything in Free', '300 tracked queries / month', '5 competitors', 'llms.txt + robots.txt automation', 'Schema Builder', 'AI Traffic Attribution', 'Standard WhatsApp support'], true, false],
                    ['scale', 'Scale', '₹1,999', '19990', ['Everything in Grow', '2,000 tracked queries / month', '10 competitors', 'Smart Blogger + publish to blog', 'AI Sentiment Analysis', 'Priority WhatsApp support'], false, true],
                    ['agency', 'Agency', '₹4,999', '49990', ['Everything in Scale', '10,000 tracked queries / month', '100 competitors', 'Multi-store dashboard', 'White-label client reports', 'Dedicated manager'], false, false],
                ] as [$key, $name, $price, $annual, $features, $popular, $best])
                <div class="relative flex flex-col rounded-3xl border p-7 transition-all duration-300
                    {{ $best ? 'border-brand-500 bg-gradient-to-b from-brand-500/15 to-surface-800 shadow-2xl shadow-brand-500/15 scale-[1.02]' : ($popular ? 'border-white/20 bg-surface-800/80' : 'border-white/10 bg-surface-800/40 hover:border-white/25') }}">
                    @if ($best)
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-brand-500 to-sky-400 px-4 py-1 text-[10px] font-extrabold uppercase tracking-widest text-white shadow-lg">Best value</div>
                    @endif
                    <div class="flex items-center justify-between">
                        <span class="font-display font-bold text-white">{{ $name }}</span>
                        @if ($popular)<span class="badge-green">Most popular</span>@endif
                    </div>
                    <div class="mt-4 flex items-baseline gap-1">
                        <span class="font-display text-4xl font-extrabold text-white price-monthly">{{ $price }}</span>
                        <span class="text-xs text-slate-500 price-monthly">/month</span>
                        <span class="font-display text-4xl font-extrabold text-white price-annual hidden">₹{{ number_format((int) str_replace(',', '', $annual)) }}</span>
                        <span class="text-xs text-slate-500 price-annual hidden">/year</span>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1 price-monthly">or ₹{{ number_format((int) str_replace(',', '', $annual)) }}/year — save ~17%</div>
                    <div class="text-[11px] text-emerald-400 font-semibold mt-1 price-annual hidden">2 months free vs monthly</div>
                    <ul class="mt-6 space-y-3 text-[13px] text-slate-300 flex-1">
                        @foreach ($features as $f)
                        <li class="flex gap-2.5">
                            <span class="mt-0.5 w-4.5 h-4.5 shrink-0 rounded-full bg-emerald-400/15 border border-emerald-400/40 text-emerald-400 flex items-center justify-center">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                            </span>{{ $f }}
                        </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('scorecard') }}" class="mt-7 btn-primary w-full text-xs {{ $best ? '!bg-brand-500 hover:!bg-brand-400' : '!bg-white/5 !border-white/15 hover:!bg-white/10 !text-white' }}">
                        {{ $key === 'free' ? 'Start free — no card' : 'Start 3-day free trial' }}
                    </a>
                </div>
                @endforeach
            </div>
            <p class="text-center text-xs text-slate-500 mt-8">All plans billed by Shopify in INR · 18% GST applies · 3-day free trial · App Store review required for listing</p>
        </div>
    </section>

    <!-- Compare table -->
    <section class="py-16 border-t border-white/5">
        <div class="max-w-4xl mx-auto px-4">
            <h2 class="font-display text-2xl md:text-3xl font-extrabold text-center">Compare plans</h2>
            <div class="mt-8 overflow-x-auto">
                <table class="w-full text-sm text-slate-300">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-slate-500 border-b border-white/10">
                            <th class="py-3 pr-4 font-semibold">Capability</th>
                            <th class="py-3 px-3 font-semibold text-center">Free</th>
                            <th class="py-3 px-3 font-semibold text-center">Grow</th>
                            <th class="py-3 px-3 font-semibold text-center text-brand-400">Scale</th>
                            <th class="py-3 px-3 font-semibold text-center">Agency</th>
                        </tr>
                    </thead>
                    <tbody class="text-[13px]">
                        @foreach ([
                            ['AI Readiness Score + action plan', '✓', '✓', '✓', '✓'],
                            ['Tracked queries / month', '25', '150', '500', '2,000'],
                            ['Engines tracked', 'Web proxy', 'All majors', 'All majors', 'All majors'],
                            ['llms.txt + robots.txt automation', '—', '✓', '✓', '✓'],
                            ['Schema Builder (JSON-LD)', '—', '✓', '✓', '✓'],
                            ['AI Traffic → Orders attribution', '—', '✓', '✓', '✓'],
                            ['Smart Blogger + publish to blog', '—', '—', '✓', '✓'],
                            ['AI Sentiment Analysis', '—', '—', '✓', '✓'],
                            ['Competitor tracking', '—', '—', '2 brands', '10 brands'],
                            ['Multi-store dashboard', '—', '—', '—', '✓'],
                            ['White-label client reports', '—', '—', '—', '✓'],
                            ['Support', 'Community', 'WhatsApp', 'Priority WhatsApp', 'Dedicated manager'],
                        ] as [$cap, $free, $grow, $scale, $agency])
                        <tr class="border-b border-white/5">
                            <td class="py-3 pr-4 text-slate-400">{{ $cap }}</td>
                            <td class="py-3 px-3 text-center">{{ $free }}</td>
                            <td class="py-3 px-3 text-center">{{ $grow }}</td>
                            <td class="py-3 px-3 text-center text-white font-semibold">{{ $scale }}</td>
                            <td class="py-3 px-3 text-center">{{ $agency }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Guarantees -->
    <section class="pb-24">
        <div class="max-w-4xl mx-auto px-4 grid sm:grid-cols-3 gap-4 text-center">
            @foreach ([
                ['No lock-in', 'Cancel anytime from the Shopify billing screen — downgrade to Free with one click.'],
                ['Honest by design', 'We measure real mention rates and fix real signals. No guaranteed-rank snake oil, ever.'],
                ['India-first support', 'English & Hinglish WhatsApp support. GST invoicing. Built for Indian payment behaviour.'],
            ] as [$t, $d])
            <div class="card-dark card-dark-hover p-6">
                <div class="text-xl">🛡️</div>
                <div class="font-display font-bold text-white mt-3">{{ $t }}</div>
                <p class="text-xs text-slate-400 mt-2 leading-relaxed">{{ $d }}</p>
            </div>
            @endforeach
        </div>
    </section>

    @include('marketing.partials.footer')

    <script>
        (function () {
            var annual = false;
            var toggle = document.getElementById('billing-toggle');
            var knob = document.getElementById('billing-knob');
            function render() {
                document.querySelectorAll('.price-monthly').forEach(function (el) { el.classList.toggle('hidden', annual); });
                document.querySelectorAll('.price-annual').forEach(function (el) { el.classList.toggle('hidden', !annual); });
                knob.style.transform = annual ? 'translateX(24px)' : 'translateX(0)';
                document.getElementById('lbl-monthly').classList.toggle('text-slate-400', annual);
                document.getElementById('lbl-monthly').classList.toggle('text-white', !annual);
                document.getElementById('lbl-annual').classList.toggle('text-slate-400', !annual);
                document.getElementById('lbl-annual').classList.toggle('text-white', annual);
            }
            toggle.addEventListener('click', function () { annual = !annual; render(); });
        })();
    </script>
</body>
</html>
