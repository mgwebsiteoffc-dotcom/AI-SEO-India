<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Post;
use App\Models\Store;
use App\Services\AuditService;
use App\Services\LlmClient;
use App\Shopify\ShopifyService;
use Illuminate\Http\Request;

class MarketingController extends Controller
{
    public function home(Request $request)
    {
        // When Shopify loads the embedded app, it hits the configured app URL
        // with ?shop=...&host=... query params. Detect this and redirect into
        // the embedded app shell instead of showing the marketing homepage.
        if ($request->query('shop') && $request->query('host')) {
            return redirect()->route('app', [
                'shop' => $request->query('shop'),
                'host' => $request->query('host'),
            ]);
        }

        // If only ?shop= is present (no host), check if the store exists.
        // This handles the case where a custom app install redirects here
        // without the host param.
        $shop = strtolower(trim((string) $request->query('shop', '')));
        if ($shop && preg_match('/\.myshopify\.com$/', $shop)) {
            $store = Store::where('shop', $shop)->first();
            if ($store) {
                return redirect()->route('app', ['shop' => $shop]);
            }
        }

        return view('marketing.home');
    }

    public function pricing()
    {
        return view('marketing.pricing');
    }

    // ------------------------------------------------------------- scorecard

    public function scorecard()
    {
        return view('marketing.scorecard', $this->scorecardViewData());
    }

    /**
     * Free AI Score runner: saves the lead, and when a store URL is provided
     * performs a real AI Readiness audit and shows the live result inline.
     */
    public function runScorecard(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'brand' => ['nullable', 'string', 'max:120'],
            'shop_url' => ['nullable', 'string', 'max:160'],
        ]);

        Lead::firstOrCreate(['email' => $data['email']], [
            'brand' => $data['brand'] ?? null,
            'shop_url' => $data['shop_url'] ?? null,
            'source' => 'scorecard',
        ]);

        $domain = $this->normalizeDomain($data['shop_url'] ?? '');
        if ($domain === '') {
            return view('marketing.scorecard', array_merge($this->scorecardViewData(), [
                'liveScore' => null,
                'status' => 'Thanks! We saved your email — enter your store URL above and we will scan it live right now (no email wait).',
            ]));
        }

        // Run a genuine audit against the given domain using a throwaway probe
        // store, then clean up so public store records stay pristine.
        $probe = Store::firstOrCreate(
            ['shop' => 'probe.scorecard.local'],
            ['brand_name' => $data['brand'] ?: ucfirst(strtok($domain, '.')), 'is_demo' => false]
        );
        $probe->forceFill(['domain' => $domain])->save();

        try {
            $run = app(AuditService::class)->run($probe, ['domain' => $domain]);
            $issues = $run->issues()->orderByRaw(
                "CASE severity WHEN 'critical' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END"
            )->get(['severity', 'category', 'title', 'recommendation', 'code'])->toArray();
            $live = [
                'domain' => $domain,
                'summary' => $run->summary,
                'issues' => $issues,
                'failed' => ($run->status ?? null) === 'failed',
            ];
        } catch (\Throwable $e) {
            $live = ['domain' => $domain, 'summary' => null, 'issues' => [], 'failed' => true];
        } finally {
            // keep the probe store row if the audit is mid-flight? we are synchronous — clean up always.
            try {
                if (isset($run) && $run->exists) {
                    $run->issues()->delete();
                    $run->delete();
                }
                $probe->delete();
            } catch (\Throwable $e) {
                // ignore cleanup errors
            }
        }

        return view('marketing.scorecard', array_merge($this->scorecardViewData(), [
            'liveScore' => $live,
            'status' => 'Fresh scan complete for '.$domain.' — see your live score below.',
        ]));
    }

    private function normalizeDomain(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }
        $host = parse_url($url, PHP_URL_HOST) ?: preg_replace('#^https?://#', '', $url);
        $host = strtolower(trim((string) $host, '/'));
        $host = preg_replace('/^www\./', '', (string) $host);
        // chop any path/query that survived parsing
        $host = strtok($host, '/') ?: $host;

        return preg_match('/^[a-z0-9\-\.]+\.[a-z]{2,}$/i', $host) ? $host : '';
    }

    public function captureLead(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'brand' => ['nullable', 'string', 'max:120'],
            'shop_url' => ['nullable', 'string', 'max:160'],
        ]);
        Lead::firstOrCreate(['email' => $data['email']], [
            'brand' => $data['brand'] ?? null,
            'shop_url' => $data['shop_url'] ?? null,
            'source' => $request->input('source', 'scorecard'),
        ]);
        return back()->with('status', 'Thanks! We will email your free AI Readiness Score within 24 hours.');
    }

    private function scorecardViewData(): array
    {
        $demo = Store::where('is_demo', true)->first();
        $score = null;
        if ($demo) {
            $latest = $demo->audits()->where('status', 'completed')->latest()->first();
            $score = $latest ? $latest->summary : null;
        }
        return [
            'demoScore' => $score,
            'llmConfigured' => app(LlmClient::class)->available(),
        ];
    }

    // ----------------------------------------------------- static marketing pages

    public function features()
    {
        return view('marketing.features');
    }

    public function howItWorks()
    {
        return view('marketing.how-it-works');
    }

    public function faq()
    {
        return view('marketing.faq');
    }

    public function install()
    {
        $configured = ShopifyService::init();
        return view('marketing.install', [
            'configured' => $configured,
            'listingUrl' => config('shopify.app_store_url', ''),
        ]);
    }

    /** Lightweight storefront preview showing what a store gets after install. */
    public function demoStore()
    {
        $store = Store::where('is_demo', true)->firstOrFail();
        $products = \App\Models\LlmsEntry::where('store_id', $store->id)->where('kind', 'product')->get();
        $org = \App\Models\LlmsEntry::where('store_id', $store->id)->where('kind', 'organization')->first();

        return view('marketing.demo-store', [
            'store' => $store,
            'products' => $products,
            'org' => $org,
        ]);
    }

    public function blog()
    {
        $posts = Post::whereNotNull('published_at')->orderByDesc('published_at')->get();
        return view('marketing.blog', ['posts' => $posts]);
    }

    public function blogShow(string $slug)
    {
        $post = Post::where('slug', $slug)->whereNotNull('published_at')->firstOrFail();
        return view('marketing.post', ['post' => $post]);
    }

    public function privacy()
    {
        return view('marketing.privacy');
    }

    public function terms()
    {
        return view('marketing.terms');
    }
}
