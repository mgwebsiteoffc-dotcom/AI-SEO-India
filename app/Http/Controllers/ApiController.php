<?php

namespace App\Http\Controllers;

use App\Models\AuditRun;
use App\Models\Store;
use App\Services\AuditService;
use App\Services\AiVisibilityService;
use App\Services\BillingService;
use App\Services\Ga4Service;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    private function store(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    // ---------------------------------------------------------------- dashboard

    public function dashboard(Request $request)
    {
        $store = $this->store($request);
        $latestAudit = $store->audits()->where('status', 'completed')->latest()->first();
        $snapshots = $store->snapshots()->orderBy('snapshot_date')->get();
        $today = $store->snapshots()->where('snapshot_date', today())->get();

        $trend = $snapshots
            ->groupBy(fn ($s) => $s->snapshot_date->format('Y-m-d'))
            ->map(fn ($group) => [
                'date' => $group->first()->snapshot_date->format('d M'),
                'rate' => round($group->avg(fn ($s) => $s->mentionRate()), 1),
                'mentioned' => $group->sum('mentioned'),
                'total' => $group->sum('total_queries'),
            ])
            ->values();

        return response()->json([
            'store' => [
                'shop' => $store->shop,
                'domain' => $store->hostname(),
                'brand' => $store->brand_name ?: ucfirst(strtok($store->shop, '.')),
                'plan' => $store->planName(),
                'plan_key' => $store->plan,
                'query_limit' => $store->queryLimit(),
                'is_demo' => $store->is_demo,
            ],
            'score' => $latestAudit?->score,
            'grade' => isset($latestAudit->summary['grade']) ? $latestAudit->summary['grade'] : null,
            'categories' => isset($latestAudit->summary['categories']) ? $latestAudit->summary['categories'] : null,
            'audit_count' => $store->audits()->count(),
            'last_audit_at' => $latestAudit?->completed_at?->toIso8601String(),
            'mentions_today' => [
                'mentioned' => $today->sum('mentioned'),
                'total' => $today->sum('total_queries'),
            ],
            'trend' => $trend->take(-14)->values(),
            'engines' => $today->map(fn ($s) => [
                'engine' => $s->engine,
                'rate' => $s->mentionRate(),
                'mentioned' => $s->mentioned,
                'total' => $s->total_queries,
                'samples' => $s->samples,
            ])->values(),
            'open_issues' => $latestAudit ? $latestAudit->issues()->where('is_fixed', false)->whereIn('severity', ['critical', 'warning'])->count() : 0,
        ]);
    }

    // ------------------------------------------------------------------- audit

    public function runAudit(Request $request)
    {
        $store = $this->store($request);
        $overrides = $request->input('overrides', []);
        if (! empty($overrides['domain'])) {
            $store->update(['domain' => $overrides['domain']]);
        }
        if (! empty($overrides['brand'])) {
            $store->update(['brand_name' => $overrides['brand']]);
        }

        $run = app(AuditService::class)->run($store->fresh());

        return response()->json([
            'run_id' => $run->id,
            'score' => $run->score,
            'summary' => $run->summary,
            'status' => $run->status,
            'issues' => $run->issues->map(fn ($i) => $this->issueShape($i)),
        ]);
    }

    public function latestAudit(Request $request)
    {
        $store = $this->store($request);
        $run = $store->audits()->where('status', 'completed')->latest()->first();
        if (! $run) {
            return response()->json(['run' => null]);
        }
        return response()->json([
            'run' => [
                'id' => $run->id,
                'score' => $run->score,
                'summary' => $run->summary,
                'completed_at' => $run->completed_at?->toIso8601String(),
                'issues' => $run->issues->map(fn ($i) => $this->issueShape($i)),
            ],
        ]);
    }

    private function issueShape($issue): array
    {
        return [
            'id' => $issue->id,
            'category' => $issue->category,
            'severity' => $issue->severity,
            'code' => $issue->code,
            'title' => $issue->title,
            'detail' => $issue->detail,
            'recommendation' => $issue->recommendation,
            'is_fixed' => (bool) $issue->is_fixed,
        ];
    }

    // ---------------------------------------------------------------- tracker

    public function tracker(Request $request)
    {
        $store = $this->store($request);
        $queries = $store->queries()->orderByDesc('active')->orderBy('id')->get();

        $latest = $store->snapshots()->orderByDesc('snapshot_date')->get()
            ->groupBy('engine')
            ->map(fn ($g) => $g->first());

        return response()->json([
            'queries' => $queries->map(fn ($q) => [
                'id' => $q->id, 'query' => $q->query, 'category' => $q->category, 'active' => (bool) $q->active,
            ]),
            'query_limit' => $store->queryLimit(),
            'engines' => $latest->map(fn ($s) => [
                'engine' => $s->engine,
                'date' => $s->snapshot_date->format('Y-m-d'),
                'rate' => $s->mentionRate(),
                'mentioned' => $s->mentioned,
                'cited' => $s->cited,
                'total' => $s->total_queries,
                'samples' => $s->samples,
            ])->values(),
            'llm_mode' => (bool) (config('services.openai.key') || config('services.gemini.key')),
        ]);
    }

    public function addQuery(Request $request)
    {
        $store = $this->store($request);
        $query = trim((string) $request->input('query'));
        if ($query === '' || mb_strlen($query) > 120) {
            return response()->json(['error' => 'Invalid query'], 422);
        }
        $q = $store->queries()->create([
            'query' => $query,
            'category' => $request->input('category', 'general'),
            'active' => true,
        ]);
        return response()->json(['query' => $q], 201);
    }

    public function deleteQuery(Request $request, int $id)
    {
        $store = $this->store($request);
        $store->queries()->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    public function runTracker(Request $request)
    {
        $store = $this->store($request);
        $snapshots = app(AiVisibilityService::class)->runSnapshot($store);
        return response()->json([
            'ok' => true,
            'engines' => collect($snapshots)->map(fn ($s) => [
                'engine' => $s->engine,
                'rate' => $s->mentionRate(),
                'mentioned' => $s->mentioned,
                'total' => $s->total_queries,
            ]),
        ]);
    }

    // ------------------------------------------------------------ competitors

    public function competitors(Request $request)
    {
        $store = $this->store($request);
        $competitors = $store->competitors()->get()->map(function ($c) use ($store) {
            $today = $store->competitorMentions()->where('competitor_domain', $c->domain)->latest('snapshot_date')->first();
            $mine = $store->snapshots()->latest('snapshot_date')->first();
            return [
                'id' => $c->id,
                'name' => $c->name,
                'domain' => $c->domain,
                'mentioned' => $today?->mentioned ?? 0,
                'total' => $today?->total_queries ?? 0,
                'rate' => $today ? round($today->mentioned / max(1, $today->total_queries) * 100, 1) : 0,
                'my_rate' => $mine ? $mine->mentionRate() : 0,
            ];
        })->values();
        return response()->json(['competitors' => $competitors]);
    }

    public function addCompetitor(Request $request)
    {
        $store = $this->store($request);
        $name = trim((string) $request->input('name'));
        $domain = strtolower(trim((string) $request->input('domain')));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');
        if ($name === '' || $domain === '' || ! preg_match('/^[a-z0-9\-\.]+$/i', $domain)) {
            return response()->json(['error' => 'Valid name and domain required'], 422);
        }
        $c = $store->competitors()->firstOrCreate(['domain' => $domain], ['name' => $name]);
        return response()->json(['competitor' => $c], 201);
    }

    public function deleteCompetitor(Request $request, int $id)
    {
        $store = $this->store($request);
        $store->competitors()->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    // -------------------------------------------------------------------- llms

    public function llms(Request $request)
    {
        $store = $this->store($request);
        $settings = $store->settings ?? [];
        return response()->json([
            'enabled' => (bool) ($settings['llms_enabled'] ?? false),
            'entries' => $store->llmsEntries()->orderBy('position')->limit(200)->get(['id', 'kind', 'title', 'path', 'description']),
            'proxy_url' => config('shopify.proxy_prefix') ? "https://{$store->shop}/".config('shopify.proxy_prefix').'/llms.txt' : null,
        ]);
    }

    public function generateLlms(Request $request)
    {
        $store = $this->store($request);
        $generator = app(\App\Services\LlmsGenerator::class);
        $content = $generator->generate($store, persist: true);
        return response()->json(['ok' => true, 'content' => $content]);
    }

    public function toggleLlms(Request $request)
    {
        $store = $this->store($request);
        $settings = $store->settings ?? [];
        $settings['llms_enabled'] = (bool) $request->input('enabled', false);
        $store->update(['settings' => $settings]);
        return response()->json(['ok' => true, 'enabled' => $settings['llms_enabled']]);
    }

    // ------------------------------------------------------------------ schema

    public function schemaStatus(Request $request)
    {
        $store = $this->store($request);
        $settings = $store->settings ?? [];
        return response()->json([
            'installed' => (bool) ($settings['schema_installed'] ?? false),
            'org_jsonld' => (bool) ($settings['schema_org'] ?? false),
        ]);
    }

    public function installSchema(Request $request)
    {
        $store = $this->store($request);
        $results = app(\App\Services\SchemaService::class)->installOrganization($store);
        $ok = collect($results)->every(fn ($r) => $r['ok']);
        $settings = $store->settings ?? [];
        $settings['schema_installed'] = $ok;
        $settings['schema_org'] = $ok;
        $store->update(['settings' => $settings]);
        return response()->json(['ok' => $ok, 'results' => $results]);
    }

    // ---------------------------------------------------------------- billing

    public function plans(Request $request)
    {
        $monthly = fn (string $key) => $key === 'free' ? 0 : BillingService::price($key);

        return response()->json([
            'plans' => [
                ['key' => 'free', 'name' => 'Free', 'price' => 0, 'annual_price' => 0, 'features' => ['AI Readiness Score', '25 tracked queries/mo', '1 store', 'AI SEO guides']],
                ['key' => 'grow', 'name' => 'Grow', 'price' => $monthly('grow'), 'annual_price' => $monthly('grow') * 10, 'features' => ['Everything in Free', '150 tracked queries/mo', 'llms.txt + robots.txt automation', 'Schema builder', 'AI traffic attribution']],
                ['key' => 'scale', 'name' => 'Scale', 'price' => $monthly('scale'), 'annual_price' => $monthly('scale') * 10, 'features' => ['Everything in Grow', '500 tracked queries/mo', 'Smart Blogger + publish to blog', 'AI sentiment analysis', 'Competitor tracking (2)', 'Priority WhatsApp support']],
                ['key' => 'agency', 'name' => 'Agency', 'price' => $monthly('agency'), 'annual_price' => $monthly('agency') * 10, 'features' => ['Everything in Scale', '2000 tracked queries/mo', 'Multi-store dashboard', 'Competitor tracking (10)', 'White-label client reports']],
            ],
            'current' => $this->store($request)->plan,
        ]);
    }

    public function subscribe(Request $request)
    {
        $store = $this->store($request);
        $result = app(BillingService::class)->subscribe(
            $store,
            (string) $request->input('plan'),
            in_array($request->input('interval'), ['monthly', 'annual'], true) ? $request->input('interval') : 'monthly',
        );
        if (! $result['ok']) {
            return response()->json($result, 422);
        }
        return response()->json($result);
    }

    public function billingCallback(Request $request)
    {
        $plan = (string) $request->query('plan', 'grow');
        $interval = $request->query('interval', 'monthly') === 'annual' ? 'annual' : 'monthly';
        $shop = strtolower((string) $request->query('shop', ''));
        $store = Store::where('shop', $shop)->first();
        if ($store && $request->query('charge_id')) {
            app(BillingService::class)->activate($store, $plan, $interval);
            return redirect()->away("https://{$shop}/admin/apps");
        }
        return response('Billing not confirmed', 400);
    }

    public function cancelBilling(Request $request)
    {
        $store = $this->store($request);
        return response()->json(app(BillingService::class)->cancel($store));
    }

    // ------------------------------------------------------------ attribution

    public function attribution(Request $request)
    {
        $store = $this->store($request);
        return response()->json(app(\App\Services\AttributionService::class)->report($store));
    }

    /** GA4 Data API — AI-sourced sessions/transactions/revenue (service account). */
    public function ga4(Request $request)
    {
        $store = $this->store($request);
        $service = new Ga4Service($store);

        // Demo store without credentials → clearly-labelled demo payload
        if ($store->is_demo && ! $service->configured()) {
            return response()->json($service->demoReport());
        }
        return response()->json($service->aiTrafficReport(['days' => (int) $request->input('days', 30)]));
    }

    // ---------------------------------------------------------------- settings

    public function settings(Request $request)
    {
        $store = $this->store($request);
        $settings = $store->settings ?? [];
        return response()->json([
            'brand_name' => $store->brand_name,
            'domain' => $store->domain ?? $store->shop,
            'plan' => $store->plan,
            'whatsapp_number' => $settings['whatsapp_number'] ?? null,
            'language' => $settings['language'] ?? 'en',
            'ga4_property_id' => $settings['ga4_property_id'] ?? null,
        ]);
    }

    public function saveSettings(Request $request)
    {
        $store = $this->store($request);
        $settings = $store->settings ?? [];
        $settings['whatsapp_number'] = trim((string) $request->input('whatsapp_number', $settings['whatsapp_number'] ?? ''));
        $settings['language'] = in_array($request->input('language'), ['en', 'hi', 'hinglish'], true)
            ? $request->input('language') : ($settings['language'] ?? 'en');
        $ga4Id = trim((string) $request->input('ga4_property_id', $settings['ga4_property_id'] ?? ''));
        $settings['ga4_property_id'] = preg_match('/^\d{6,12}$/', $ga4Id) ? $ga4Id : null;
        $store->update([
            'brand_name' => trim((string) $request->input('brand_name', $store->brand_name)),
            'domain' => trim((string) $request->input('domain', $store->domain)),
            'settings' => $settings,
        ]);
        return response()->json(['ok' => true]);
    }
}
