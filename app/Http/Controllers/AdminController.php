<?php

namespace App\Http\Controllers;

use App\Models\AuditRun;
use App\Models\ContentPost;
use App\Models\Lead;
use App\Models\Post;
use App\Models\Store;
use App\Models\TrackedQuery;
use App\Models\WebhookCall;
use App\Services\BillingService;
use App\Services\SaasSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * SaaS-owner ("super admin") panel — a read/act overview of every tenant:
 * stores + plans, subscription revenue, leads, content and webhook activity,
 * plus the owner Settings tab (AI engine toggles, tracker schedule, plan prices).
 */
class AdminController extends Controller
{
    private const PLAN_ORDER = ['free' => 0, 'grow' => 1, 'scale' => 2, 'agency' => 3];

    public function overview()
    {
        $stores = Store::query()->withCount(['audits', 'contentPosts', 'queries'])->orderByDesc('id')->get();
        $billingActive = $stores->where('billing_status', 'active');
        $mrr = 0;
        foreach ($billingActive as $s) {
            $mrr += (int) BillingService::price($s->plan);
        }

        $planCounts = $stores->groupBy('plan')->map->count();

        return view('admin.overview', [
            'kpis' => [
                'stores' => $stores->count(),
                'mrr' => $mrr,
                'arr' => $mrr * 12,
                'leads' => Lead::count(),
                'posts' => Post::count(),
                'content_posts' => ContentPost::count(),
                'audits' => AuditRun::count(),
                'tracked_queries' => TrackedQuery::count(),
                'webhooks' => WebhookCall::count(),
            ],
            'planCounts' => $planCounts,
            'recentStores' => $stores->take(6),
            'recentLeads' => Lead::latest()->take(6)->get(),
            'recentActivity' => WebhookCall::latest()->take(6)->get(),
        ]);
    }

    public function stores(Request $request)
    {
        $q = Store::query();

        $filter = strtolower(trim((string) $request->query('filter', '')));
        $plan = (string) $request->query('plan', '');
        if ($filter !== '') {
            $q->where(function ($qq) use ($filter) {
                $qq->where('shop', 'like', "%{$filter}%")
                    ->orWhere('domain', 'like', "%{$filter}%")
                    ->orWhere('brand_name', 'like', "%{$filter}%");
            });
        }
        if (in_array($plan, array_keys(self::PLAN_ORDER), true)) {
            $q->where('plan', $plan);
        }

        return view('admin.stores', [
            'stores' => $q->withCount(['audits', 'contentPosts', 'queries'])->orderByDesc('id')->paginate(25)->withQueryString(),
            'filter' => $filter,
            'plan' => $plan,
        ]);
    }

    public function leads(Request $request)
    {
        $q = Lead::query();
        $filter = strtolower(trim((string) $request->query('q', '')));
        if ($filter !== '') {
            $q->where(fn ($qq) => $qq->where('email', 'like', "%{$filter}%")
                ->orWhere('brand', 'like', "%{$filter}%")
                ->orWhere('shop_url', 'like', "%{$filter}%"));
        }

        return view('admin.leads', [
            'leads' => $q->latest()->paginate(25)->withQueryString(),
            'q' => $filter,
            'sources' => Lead::query()->select('source')->distinct()->pluck('source'),
        ]);
    }

    public function activity()
    {
        return view('admin.activity', [
            'webhooks' => WebhookCall::latest()->take(60)->get(),
            'audits' => AuditRun::with('store')->latest()->take(60)->get(),
        ]);
    }

    public function updatePlan(Request $request, Store $store)
    {
        $plan = (string) $request->input('plan');
        if (! isset(self::PLAN_ORDER[$plan])) {
            return back()->with('error', 'Unknown plan.');
        }
        $store->forceFill([
            'plan' => $plan,
            'billing_status' => $plan === 'free' ? 'cancelled' : 'active',
        ])->save();

        return back()->with('status', "{$store->shop} moved to {$plan}.");
    }

    public function deleteStore(Request $request, Store $store)
    {
        if ($store->is_demo) {
            return back()->with('error', 'The demo store cannot be deleted — re-seed with `php artisan demo:seed`.');
        }
        $store->delete();

        return back()->with('status', "{$store->shop} deleted.");
    }

    public function deleteLead(Request $request, Lead $lead)
    {
        $lead->delete();

        return back()->with('status', 'Lead deleted.');
    }

    // ------------------------------------------------------------ owner settings

    /**
     * Owner "Settings" hub: which AI engines the tracker monitors, the daily
     * snapshot scheduler, and editable plan prices — everything without a code
     * deploy.
     */
    public function settings()
    {
        $saas = app(SaasSettingsService::class);

        $engines = [];
        foreach ($saas->engines() as $key => $state) {
            $meta = SaasSettingsService::ENGINES[$key];
            $engines[$key] = array_merge($meta, [
                'enabled' => $state['enabled'],
                'method' => $saas->llmProviderFor($key) ? 'llm' : 'proxy',
            ]);
        }

        return view('admin.settings', [
            'engines' => $engines,
            'tracking' => $saas->tracking(),
            'billing' => $saas->planPrices(),
            'llm' => [
                'openai' => ['configured' => (bool) config('services.openai.key'), 'label' => 'OpenAI (ChatGPT)'],
                'gemini' => ['configured' => (bool) config('services.gemini.key'), 'label' => 'Google (Gemini)'],
            ],
            'storeCounts' => [
                'tracking' => Store::where('tracking_enabled', true)->where(fn ($q) => $q->whereNotNull('shopify_token')->orWhere('is_demo', true))->count(),
                'all' => Store::count(),
            ],
        ]);
    }

    /** POST /admin/settings/engines — which engines the tracker monitors. */
    public function saveSettingsEngines(Request $request)
    {
        $enabled = array_values(array_filter(
            array_keys(SaasSettingsService::ENGINES),
            fn ($key) => $request->boolean('engines.'.$key)
        ));
        app(SaasSettingsService::class)->saveEngines($enabled);

        $on = implode(', ', array_map(
            fn ($k) => SaasSettingsService::ENGINES[$k]['label'],
            app(SaasSettingsService::class)->enabledEngines()
        ));

        return back()->with('status', $on === ''
            ? 'All engines switched off — the tracker is paused until at least one is enabled.'
            : 'Tracker engines updated: '.$on.'.');
    }

    /** POST /admin/settings/tracking — global daily snapshot scheduler. */
    public function saveSettingsTracking(Request $request)
    {
        app(SaasSettingsService::class)->saveTracking(
            $request->boolean('tracking_enabled'),
            (string) $request->input('tracking_time', '06:00')
        );

        return back()->with('status', 'Daily snapshot scheduler updated.');
    }

    /** POST /admin/settings/billing — editable Grow/Scale/Agency monthly prices (INR). */
    public function saveSettingsBilling(Request $request)
    {
        app(SaasSettingsService::class)->savePlanPrices(
            (int) $request->input('price_grow', 999),
            (int) $request->input('price_scale', 1999),
            (int) $request->input('price_agency', 4999)
        );

        return back()->with('status', 'Plan prices updated — new charges use these amounts.');
    }

    /** POST /admin/settings/run — run the visibility snapshot now for all active stores. */
    public function runTracking(Request $request)
    {
        if (! app(SaasSettingsService::class)->trackingEnabled()) {
            return back()->with('error', 'Tracking is disabled — enable it in Settings → Tracker first.');
        }
        $exit = Artisan::call('visibility:track --all');
        $output = trim((string) Artisan::output());

        return back()->with('status', $exit === 0
            ? 'Snapshot run finished. '.collect(explode("\n", $output))->filter(fn ($l) => str_contains($l, ':'))->count().' engine snapshot(s).'
            : 'Snapshot run had errors — see the server log.');
    }

    /** POST /admin/stores/{store}/toggle-tracking — per-store kill switch. */
    public function toggleStoreTracking(Request $request, Store $store)
    {
        $store->update(['tracking_enabled' => ! $store->tracking_enabled]);

        return back()->with('status', ($store->tracking_enabled ? 'Tracking enabled for ' : 'Tracking paused for ').$store->shop.'.');
    }
}
