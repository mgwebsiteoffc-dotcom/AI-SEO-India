<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\Lead;
use App\Models\Post;
use App\Models\Store;
use App\Services\AuditService;
use App\Services\LlmClient;
use App\Shopify\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MarketingController extends Controller
{
    public function home()
    {
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

        $lead = Lead::firstOrCreate(['email' => $data['email']], [
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
            if (! $live['failed'] && ! empty($run->summary)) {
                // Freeze a shareable snapshot + public link for the growth loop.
                $payload = [
                    'brand' => $data['brand'] ?: ($run->summary['brand'] ?? ucfirst(strtok($domain, '.'))),
                    'domain' => $domain,
                    'score' => $run->summary['total'] ?? $run->score,
                    'grade' => $run->summary['grade'] ?? null,
                    'categories' => $run->summary['categories'] ?? [],
                    'issues' => $issues,
                    'generated_at' => now()->setTimezone('Asia/Kolkata')->format('d M Y, H:i T'),
                ];
                $token = $lead->share_token ?: Str::random(32);
                $lead->update(['share_token' => $token, 'share_payload' => $payload]);
                $live['share_url'] = route('scorecard.share', $token);
            }
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

    /** White-label client report: GET /client-report/{token} (agency tier). */
    public function clientReport(string $token)
    {
        $store = \App\Models\Store::where('report_token', $token)->first();
        abort_unless($store && $store->parentStore, 404);

        $audit = $store->audits()->where('status', 'completed')->latest()->first();
        $signal = $store->brandSignalRuns()->latest()->first();
        $today = now()->startOfDay();

        // Latest per-engine mention rates + 7-day weighted trend.
        $snapshots = $store->snapshots()->whereBetween('snapshot_date', [$today->copy()->subDays(6), $today])->get();
        $engines = $snapshots->groupBy('engine')->map(fn ($rows) => [
            'engine' => $rows->last()->engine,
            'rate' => $rows->last()->mentionRate(),
        ])->values();
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $rows = $snapshots->filter(fn ($r) => $r->snapshot_date->isSameDay($today->copy()->subDays($i)));
            $trend[] = [
                'date' => $today->copy()->subDays($i)->format('d M'),
                'rate' => $rows->sum('total_queries') > 0
                    ? round($rows->sum('mentioned') / $rows->sum('total_queries') * 100, 1) : null,
            ];
        }

        $issues = $audit?->issues()->orderByRaw(
            "CASE severity WHEN 'critical' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END"
        )->take(5)->get(['severity', 'title', 'recommendation'])->toArray() ?? [];

        $agency = $store->parentStore;
        $asettings = $agency->settings ?? [];
        $whiteLabel = (bool) ($asettings['white_label'] ?? false);
        $agencyName = trim((string) ($asettings['agency_name'] ?? '')) ?: ($agency->brand_name ?: ucfirst(strtok($agency->shop, '.')));

        return view('marketing.client-report', [
            'store' => $store,
            'audit' => $audit,
            'signal' => $signal,
            'engines' => $engines,
            'trend' => $trend,
            'issues' => $issues,
            'agencyName' => $agencyName,
            'agencyWebsite' => trim((string) ($asettings['agency_website'] ?? '')),
            'whiteLabel' => $whiteLabel,
            'asOf' => now()->setTimezone('Asia/Kolkata')->format('d M Y, H:i T'),
        ]);
    }

    /** Public share page: GET /scorecard/{token} — renders a frozen score snapshot. */
    public function scorecardShare(string $token)
    {
        $lead = Lead::where('share_token', $token)->first();
        if (! $lead || ! $lead->share_payload) {
            abort(404);
        }

        return view('marketing.scorecard-share', [
            'sharePayload' => $lead->share_payload,
        ]);
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

    public function blog(?BlogCategory $category = null)
    {
        $posts = Post::query()
            ->with('category')
            ->published()
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->orderByDesc('published_at')
            ->get();

        return view('marketing.blog', [
            'posts' => $posts,
            'category' => $category,
            'categories' => \App\Models\BlogCategory::withCount('posts')->orderBy('name')->get(),
        ]);
    }

    /** GET /blog/category/{slug} → category landing (CollectionPage JSON-LD). */
    public function blogCategory(string $slug)
    {
        $category = \App\Models\BlogCategory::where('slug', $slug)->firstOrFail();

        return $this->blog($category);
    }

    public function blogShow(string $slug)
    {
        $post = Post::with('category')->where('slug', $slug)->published()->firstOrFail();
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
