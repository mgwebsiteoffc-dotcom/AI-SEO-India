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
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * SaaS-owner ("super admin") panel — a read/act overview of every tenant:
 * stores + plans, subscription revenue, leads, content and webhook activity.
 */
class AdminController extends Controller
{
    private const PLAN_ORDER = ['free' => 0, 'grow' => 1, 'scale' => 2, 'agency' => 3];

    // ----------------------------------------------------------------- Auth

    public function loginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $email = (string) config('admin.email', env('ADMIN_EMAIL', ''));
        $password = (string) config('admin.password', env('ADMIN_PASSWORD', ''));

        $inputEmail = trim((string) $request->input('email', ''));
        $inputPassword = (string) $request->input('password', '');

        // No credentials configured + not production → open access for preview
        if ($email === '' && $password === '' && ! app()->environment('production')) {
            session(['admin_logged_in' => true]);
            return redirect()->route('admin.overview');
        }

        if ($email !== '' && hash_equals($email, $inputEmail) && hash_equals($password, $inputPassword)) {
            session(['admin_logged_in' => true]);
            $request->session()->regenerate();
            return redirect()->route('admin.overview');
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        session()->forget('admin_logged_in');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    public function overview()
    {
        $plans = BillingService::PLANS; // key => [name, price, annual...]

        $stores = Store::query()->withCount(['audits', 'contentPosts', 'queries'])->orderByDesc('id')->get();
        $billingActive = $stores->where('billing_status', 'active');
        $mrr = 0;
        foreach ($billingActive as $s) {
            $price = $plans[$s->plan]['price'] ?? 0;
            $mrr += (int) $price;
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

    // -------------------------------------------------------- AI / LLM settings

    public function settings()
    {
        return view('admin.settings', [
            'openrouterKey' => config('services.openrouter.key') ?: '',
            'openrouterModel' => config('services.openrouter.model', 'nvidia/nemotron-3.5-lightning:free'),
            'openaiKey' => config('services.openai.key') ?: '',
            'openaiModel' => config('services.openai.model', 'gpt-4o-mini'),
            'geminiKey' => config('services.gemini.key') ?: '',
            'geminiModel' => config('services.gemini.model', 'gemini-1.5-flash'),
            'activeProvider' => app(\App\Services\LlmClient::class)->provider(),
            'llmAvailable' => app(\App\Services\LlmClient::class)->available(),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $envPath = base_path('.env');
        if (! file_exists($envPath) || ! is_writable($envPath)) {
            return back()->with('error', '.env file is not writable. Update it manually.');
        }

        $env = file_get_contents($envPath);

        $fields = [
            'OPENROUTER_API_KEY' => trim((string) $request->input('openrouter_key', '')),
            'OPENROUTER_MODEL' => trim((string) $request->input('openrouter_model', 'nvidia/nemotron-3.5-lightning:free')),
            'OPENAI_API_KEY' => trim((string) $request->input('openai_key', '')),
            'OPENAI_MODEL' => trim((string) $request->input('openai_model', 'gpt-4o-mini')),
            'GEMINI_API_KEY' => trim((string) $request->input('gemini_key', '')),
            'GEMINI_MODEL' => trim((string) $request->input('gemini_model', 'gemini-1.5-flash')),
        ];

        foreach ($fields as $key => $value) {
            $pattern = '/^'.preg_quote($key, '/').'=.*/m';
            $line = "$key=$value";
            if (preg_match($pattern, $env)) {
                $env = preg_replace($pattern, $line, $env);
            } else {
                $env = rtrim($env)."\n".$line."\n";
            }
        }

        file_put_contents($envPath, $env);

        // Clear config cache so changes take effect
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($envPath, true);
        }

        return back()->with('status', 'AI/LLM settings saved. Changes take effect on next request.');
    }

    // -------------------------------------------------------- Blog management

    public function blogs()
    {
        $posts = Post::orderByDesc('published_at')->orderByDesc('id')->paginate(20);
        return view('admin.blogs', ['posts' => $posts]);
    }

    public function blogCreate()
    {
        return view('admin.blog-form', ['post' => null]);
    }

    public function blogStore(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:posts,slug',
            'meta_description' => 'nullable|string|max:300',
            'excerpt' => 'nullable|string|max:500',
            'body' => 'required|string',
            'author' => 'nullable|string|max:120',
            'faq_json' => 'nullable|string',
            'seo_notes' => 'nullable|string',
            'publish' => 'nullable|boolean',
        ]);

        $slug = $data['slug'] ?: Str::slug($data['title']);

        $post = Post::create([
            'title' => $data['title'],
            'slug' => $slug,
            'meta_description' => $data['meta_description'] ?? null,
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'],
            'author' => $data['author'] ?? 'AI Visibility Team',
            'published_at' => ($data['publish'] ?? false) ? now() : null,
        ]);

        return redirect()->route('admin.blogs')->with('status', "Post \"{$post->title}\" created.");
    }

    public function blogEdit(Post $post)
    {
        return view('admin.blog-form', ['post' => $post]);
    }

    public function blogUpdate(Request $request, Post $post)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:posts,slug,' . $post->id,
            'meta_description' => 'nullable|string|max:300',
            'excerpt' => 'nullable|string|max:500',
            'body' => 'required|string',
            'author' => 'nullable|string|max:120',
            'faq_json' => 'nullable|string',
            'seo_notes' => 'nullable|string',
            'publish' => 'nullable|boolean',
        ]);

        $wasPublished = $post->published_at !== null;
        $shouldBePublished = ($data['publish'] ?? false);

        $post->update([
            'title' => $data['title'],
            'slug' => $data['slug'] ?: Str::slug($data['title']),
            'meta_description' => $data['meta_description'] ?? null,
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'],
            'author' => $data['author'] ?? $post->author,
            'published_at' => $shouldBePublished ? ($post->published_at ?? now()) : null,
        ]);

        return redirect()->route('admin.blogs')->with('status', "Post \"{$post->title}\" updated.");
    }

    public function blogDelete(Post $post)
    {
        $title = $post->title;
        $post->delete();
        return redirect()->route('admin.blogs')->with('status', "Post \"{$title}\" deleted.");
    }
}
