<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', [
        'title' => 'Aurelia Naturals — storefront with AI Visibility installed (demo)',
        'description' => 'What the Aurelia Naturals storefront looks like after installing AI Visibility: live Product JSON-LD, llms.txt and AI-bot robots rules served through the Shopify app proxy.',
    ])
    {{-- These are the signals AI Visibility serves on the real storefront --}}
    <link rel="llms.txt" href="{{ url('/apps/ai-visibility/llms.txt?demo=1') }}">
    <link rel="alternate" type="text/plain" title="llms.txt" href="{{ url('/apps/ai-visibility/llms.txt?demo=1') }}">
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => 'Aurelia Naturals', 'url' => 'https://aurelianaturals.in'], JSON_UNESCAPED_SLASHES) !!}</script>
</head>
<body class="marketing" style="background:#f6f7fb">

    {{-- "Installed" ribbon --}}
    <div style="background:linear-gradient(90deg,#065f46,#0a84ff);color:#fff;font-size:12px;font-weight:600;text-align:center;padding:7px 16px">
        ⚡ This storefront runs <strong>AI Visibility</strong> — Product JSON-LD, llms.txt and AI-crawler rules are live below · <a href="{{ route('app', ['demo' => 1]) }}" style="color:#fff;text-decoration:underline">Open the owner panel →</a>
    </div>

    <header style="max-width:1100px;margin:0 auto;padding:18px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px">
        <div style="display:flex;align-items:center;gap:10px">
            <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#059669,#0a84ff);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:16px">AN</div>
            <div>
                <div style="font-weight:800;color:#0f172a;font-size:16px">Aurelia Naturals</div>
                <div style="font-size:11px;color:#64748b">Clean Ayurvedic beauty · India</div>
            </div>
        </div>
        <div style="font-size:12px;color:#475569;display:flex;gap:14px">
            <span>Shop</span><span>Collections</span><span>About</span>
        </div>
    </header>

    <main style="max-width:1100px;margin:0 auto;padding:0 16px 40px">
        <div style="border-radius:20px;padding:26px;color:#fff;background:radial-gradient(600px 300px at 80% -20%, rgba(10,132,255,.55), transparent 60%), linear-gradient(135deg,#0f172a,#1e293b)">
            <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#7dd3fc">Skincare for Indian skin · results Indian weather can't undo</div>
            <h1 style="font-size:26px;font-weight:800;margin:8px 0 6px">Clean beauty, made for our sun, humidity &amp; pigmentation</h1>
            <p style="font-size:13px;color:#cbd5e1;max-width:520px;line-height:1.6">Free shipping over ₹499 · COD available · Dermatologically tested</p>
            <a href="{{ route('demo-store') }}#products" style="display:inline-block;margin-top:14px;background:#0a84ff;color:#fff;font-size:13px;font-weight:700;padding:11px 22px;border-radius:999px">Shop bestsellers</a>
        </div>

        {{-- Signal chips: proof of what's served --}}
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin:18px 0 26px">
            <a href="{{ url('/apps/ai-visibility/llms.txt?demo=1') }}" target="_blank" style="font-size:11px;font-weight:700;background:#fff;border:1px solid #cbd5e1;color:#0f172a;border-radius:999px;padding:6px 12px">📄 llms.txt — live</a>
            <a href="{{ url('/apps/ai-visibility/robots.txt?demo=1') }}" target="_blank" style="font-size:11px;font-weight:700;background:#fff;border:1px solid #cbd5e1;color:#0f172a;border-radius:999px;padding:6px 12px">🤖 robots.txt (GPTBot, ClaudeBot…) — live</a>
            <a href="{{ url('/apps/ai-visibility/schema?path=/&demo=1') }}" target="_blank" style="font-size:11px;font-weight:700;background:#fff;border:1px solid #cbd5e1;color:#0f172a;border-radius:999px;padding:6px 12px">🧩 Organization JSON-LD — live</a>
            <a href="{{ url('/apps/ai-visibility/sitemap.xml?demo=1') }}" target="_blank" style="font-size:11px;font-weight:700;background:#fff;border:1px solid #cbd5e1;color:#0f172a;border-radius:999px;padding:6px 12px">🗺️ sitemap.xml — live</a>
            <span style="font-size:11px;font-weight:700;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:999px;padding:6px 12px">✓ Schema Builder ON</span>
        </div>

        <h2 id="products" style="font-size:18px;font-weight:800;color:#0f172a;margin-bottom:14px">Bestsellers</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px">
            @foreach ($products as $i => $p)
                @php
                    preg_match('/[₹]\s*(\d+)/u', (string) $p->description, $m);
                    $price = isset($m[1]) ? '₹'.$m[1] : '₹799';
                @endphp
                <article style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden">
                    <div style="aspect-ratio:4/3;background:linear-gradient(135deg,#0a84ff22,#05966922);display:flex;align-items:center;justify-content:center;color:#0a84ff;font-weight:800;font-size:30px">AN</div>
                    <div style="padding:14px">
                        <div style="font-size:13px;font-weight:700;color:#0f172a;min-height:34px">{{ $p->title }}</div>
                        <div style="font-size:11px;color:#64748b;margin:6px 0 10px;line-height:1.5">{{ $p->description }}</div>
                        <div style="display:flex;align-items:center;justify-content:space-between">
                            <span style="font-weight:800;color:#0f172a">{{ $price }}</span>
                            <a href="{{ url('/apps/ai-visibility/schema?path='.$p->path.'&demo=1') }}" target="_blank" style="font-size:11px;font-weight:700;color:#0a84ff;border:1px solid #0a84ff55;border-radius:999px;padding:5px 11px">JSON-LD</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div style="margin-top:28px;border-radius:18px;background:#0f172a;color:#fff;padding:22px;text-align:center">
            <div style="font-size:14px;font-weight:700">Want your store to look like this to AI engines?</div>
            <p style="font-size:12px;color:#94a3b8;margin:6px 0 0">Schema, llms.txt, robots rules and blog content — installed in one click.</p>
            <a href="{{ route('install') }}" style="display:inline-block;margin-top:14px;background:#0a84ff;color:#fff;font-size:13px;font-weight:700;padding:11px 24px;border-radius:999px">Install AI Visibility</a>
            <a href="{{ route('app', ['demo' => 1]) }}" style="display:inline-block;margin-top:14px;margin-left:10px;border:1px solid #334155;color:#e2e8f0;font-size:13px;font-weight:700;padding:11px 24px;border-radius:999px">Owner panel</a>
        </div>
    </main>

    <footer style="text-align:center;font-size:11px;color:#94a3b8;padding:18px">
        Demo storefront preview · Aurelia Naturals is a fictional seeded store
    </footer>
</body>
</html>
