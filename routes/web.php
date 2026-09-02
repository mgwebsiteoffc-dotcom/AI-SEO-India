<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\AppController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\ProxyController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\VerifyProxyRequest;
use App\Http\Middleware\VerifyShopifySession;
use Illuminate\Support\Facades\Route;

// Health
Route::get('/health', [HealthController::class, 'index']);

// OAuth
Route::get('/auth/install', [AuthController::class, 'install'])->name('auth.install');
Route::get('/auth/callback', [AuthController::class, 'callback']);
Route::get('/auth/demo', [AuthController::class, 'demo'])->name('auth.demo');

// Public marketing website
Route::get('/', [MarketingController::class, 'home'])->name('home');
Route::get('/pricing', [MarketingController::class, 'pricing'])->name('pricing');
Route::get('/scorecard', [MarketingController::class, 'scorecard'])->name('scorecard');
Route::get('/scorecard/{token}', [MarketingController::class, 'scorecardShare'])->name('scorecard.share');
Route::post('/scorecard/run', [MarketingController::class, 'runScorecard'])->name('scorecard.run');
Route::post('/lead', [MarketingController::class, 'captureLead'])->name('lead');
Route::get('/features', [MarketingController::class, 'features'])->name('features');
Route::get('/how-it-works', [MarketingController::class, 'howItWorks'])->name('how-it-works');
Route::get('/faq', [MarketingController::class, 'faq'])->name('faq');
Route::get('/install', [MarketingController::class, 'install'])->name('install');
Route::get('/demo-store', [MarketingController::class, 'demoStore'])->name('demo-store');
Route::get('/client-report/{token}', [MarketingController::class, 'clientReport'])->name('client.report');
Route::get('/blog', [MarketingController::class, 'blog'])->name('blog');
Route::get('/blog/category/{slug}', [MarketingController::class, 'blogCategory'])->name('blog.category');
Route::get('/blog/{slug}', [MarketingController::class, 'blogShow'])->name('blog.show');
Route::get('/privacy', [MarketingController::class, 'privacy'])->name('privacy');
Route::get('/terms', [MarketingController::class, 'terms'])->name('terms');

// SaaS-owner (super admin) panel — email + password session login.
// Seed the account with: php artisan db:seed --class=AdminSeeder
Route::get('/admin/login', [\App\Http\Controllers\AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [\App\Http\Controllers\AdminAuthController::class, 'login'])->name('admin.login.submit');

Route::prefix('admin')->middleware('admin.guard')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\AdminAuthController::class, 'logout'])->name('admin.logout');
    Route::get('/', [\App\Http\Controllers\AdminController::class, 'overview'])->name('admin.overview');
    Route::get('/stores', [\App\Http\Controllers\AdminController::class, 'stores'])->name('admin.stores');
    Route::get('/leads', [\App\Http\Controllers\AdminController::class, 'leads'])->name('admin.leads');
    Route::get('/activity', [\App\Http\Controllers\AdminController::class, 'activity'])->name('admin.activity');
    Route::get('/settings', [\App\Http\Controllers\AdminController::class, 'settings'])->name('admin.settings');
    Route::post('/settings/engines', [\App\Http\Controllers\AdminController::class, 'saveSettingsEngines'])->name('admin.settings.engines');
    Route::post('/settings/tracking', [\App\Http\Controllers\AdminController::class, 'saveSettingsTracking'])->name('admin.settings.tracking');
    Route::post('/settings/billing', [\App\Http\Controllers\AdminController::class, 'saveSettingsBilling'])->name('admin.settings.billing');
    Route::post('/settings/indexnow', [\App\Http\Controllers\AdminController::class, 'saveSettingsIndexNow'])->name('admin.settings.indexnow');
    Route::post('/settings/indexnow-run', [\App\Http\Controllers\AdminController::class, 'runIndexNow'])->name('admin.settings.indexnow-run');
    Route::post('/settings/weekly', [\App\Http\Controllers\AdminController::class, 'saveSettingsWeekly'])->name('admin.settings.weekly');
    Route::post('/settings/weekly-run', [\App\Http\Controllers\AdminController::class, 'runWeeklyReports'])->name('admin.settings.weekly-run');
    Route::post('/settings/run', [\App\Http\Controllers\AdminController::class, 'runTracking'])->name('admin.settings.run');
    // Blog manager (posts + categories)
    Route::get('/blog', [\App\Http\Controllers\AdminBlogController::class, 'index'])->name('admin.blog');
    Route::get('/blog/categories', [\App\Http\Controllers\AdminBlogController::class, 'categories'])->name('admin.blog.categories');
    Route::post('/blog/categories', [\App\Http\Controllers\AdminBlogController::class, 'storeCategory'])->name('admin.blog.categories.store');
    Route::post('/blog/categories/{category}/delete', [\App\Http\Controllers\AdminBlogController::class, 'deleteCategory'])->name('admin.blog.categories.delete');
    Route::get('/blog/create', [\App\Http\Controllers\AdminBlogController::class, 'create'])->name('admin.blog.create');
    Route::post('/blog', [\App\Http\Controllers\AdminBlogController::class, 'store'])->name('admin.blog.store');
    Route::get('/blog/{post}/edit', [\App\Http\Controllers\AdminBlogController::class, 'edit'])->name('admin.blog.edit');
    Route::post('/blog/{post}', [\App\Http\Controllers\AdminBlogController::class, 'update'])->name('admin.blog.update');
    Route::post('/blog/{post}/delete', [\App\Http\Controllers\AdminBlogController::class, 'destroy'])->name('admin.blog.delete');
    Route::post('/stores/{store}/plan', [\App\Http\Controllers\AdminController::class, 'updatePlan'])->name('admin.store.plan');
    Route::post('/stores/{store}/delete', [\App\Http\Controllers\AdminController::class, 'deleteStore'])->name('admin.store.delete');
    Route::post('/stores/{store}/toggle-tracking', [\App\Http\Controllers\AdminController::class, 'toggleStoreTracking'])->name('admin.store.tracking');
    Route::post('/stores/{store}/toggle-indexnow', [\App\Http\Controllers\AdminController::class, 'toggleStoreIndexNow'])->name('admin.store.indexnow');
    Route::post('/leads/{lead}/delete', [\App\Http\Controllers\AdminController::class, 'deleteLead'])->name('admin.lead.delete');
});

// Embedded app shell (session OR demo)
Route::get('/app', [AppController::class, 'index'])->name('app');

// Billing callback (no session — merchant returns from Shopify checkout)
Route::get('/billing/callback', [ApiController::class, 'billingCallback']);

// Webhooks (verified by SDK HMAC) — topic can contain slashes, e.g. app/uninstalled
Route::post('/webhooks/{topic}', [WebhookController::class, 'handle'])->where('topic', '.*');

// App Proxy (signed by Shopify, served on the store's own domain)
Route::prefix('apps/ai-visibility')->middleware(VerifyProxyRequest::class)->group(function () {
    Route::get('/llms.txt', [ProxyController::class, 'llmsTxt']);
    Route::get('/agent.md', [ProxyController::class, 'agentMd']);
    Route::get('/robots.txt', [ProxyController::class, 'robotsTxt']);
    Route::get('/sitemap.xml', [ProxyController::class, 'sitemapXml']);
    Route::get('/schema', [ProxyController::class, 'schema']);
});

// Embedded app JSON API (JWT-session protected)
Route::prefix('api')->middleware(VerifyShopifySession::class)->group(function () {
    Route::get('/dashboard', [ApiController::class, 'dashboard']);
    Route::get('/clients', [ApiController::class, 'clients']);
    Route::post('/clients/invite', [ApiController::class, 'inviteClient']);
    Route::delete('/clients/{id}', [ApiController::class, 'detachClient']);
    Route::get('/brand-signals', [ApiController::class, 'brandSignals']);
    Route::post('/brand-signals/run', [ApiController::class, 'runBrandSignals']);
    Route::get('/audit/latest', [ApiController::class, 'latestAudit']);
    Route::post('/audit/run', [ApiController::class, 'runAudit']);
    Route::get('/tracker', [ApiController::class, 'tracker']);
    Route::post('/tracker/query', [ApiController::class, 'addQuery']);
    Route::delete('/tracker/query/{id}', [ApiController::class, 'deleteQuery']);
    Route::post('/tracker/run', [ApiController::class, 'runTracker']);
    Route::get('/tracker/competitors', [ApiController::class, 'competitors']);
    Route::post('/tracker/competitors', [ApiController::class, 'addCompetitor']);
    Route::delete('/tracker/competitors/{id}', [ApiController::class, 'deleteCompetitor']);
    Route::get('/llms', [ApiController::class, 'llms']);
    Route::post('/llms/generate', [ApiController::class, 'generateLlms']);
    Route::post('/llms/toggle', [ApiController::class, 'toggleLlms']);
    Route::get('/schema/status', [ApiController::class, 'schemaStatus']);
    Route::post('/schema/install', [ApiController::class, 'installSchema']);
    Route::get('/billing/plans', [ApiController::class, 'plans']);
    Route::post('/billing/subscribe', [ApiController::class, 'subscribe']);
    Route::post('/billing/cancel', [ApiController::class, 'cancelBilling']);
    Route::get('/attribution', [ApiController::class, 'attribution']);
    Route::get('/attribution/ga4', [ApiController::class, 'ga4']);
    Route::get('/content', [ContentController::class, 'index']);
    Route::post('/content/generate', [ContentController::class, 'generate']);
    Route::post('/content/{id}/regenerate', [ContentController::class, 'regenerate']);
    Route::post('/content/{id}/publish', [ContentController::class, 'publish']);
    Route::delete('/content/{id}', [ContentController::class, 'destroy']);
    Route::get('/content/sentiment', [ContentController::class, 'sentiment']);
    Route::get('/settings', [ApiController::class, 'settings']);
    Route::post('/settings', [ApiController::class, 'saveSettings']);
});
