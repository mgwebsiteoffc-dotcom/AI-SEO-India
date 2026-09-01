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
Route::get('/auth/install', [AuthController::class, 'install']);
Route::get('/auth/callback', [AuthController::class, 'callback']);
Route::get('/auth/demo', [AuthController::class, 'demo']);

// Public marketing website
Route::get('/', [MarketingController::class, 'home'])->name('home');
Route::get('/pricing', [MarketingController::class, 'pricing'])->name('pricing');
Route::get('/scorecard', [MarketingController::class, 'scorecard'])->name('scorecard');
Route::post('/lead', [MarketingController::class, 'captureLead'])->name('lead');
Route::get('/blog', [MarketingController::class, 'blog'])->name('blog');
Route::get('/blog/{slug}', [MarketingController::class, 'blogShow'])->name('blog.show');

// Embedded app shell (session OR demo)
Route::get('/app', [AppController::class, 'index'])->name('app');

// Billing callback (no session — merchant returns from Shopify checkout)
Route::get('/billing/callback', [ApiController::class, 'billingCallback']);

// Webhooks (verified by SDK HMAC) — topic can contain slashes, e.g. app/uninstalled
Route::post('/webhooks/{topic}', [WebhookController::class, 'handle'])->where('topic', '.*');

// App Proxy (signed by Shopify, served on the store's own domain)
Route::prefix('apps/ai-visibility')->middleware(VerifyProxyRequest::class)->group(function () {
    Route::get('/llms.txt', [ProxyController::class, 'llmsTxt']);
    Route::get('/robots.txt', [ProxyController::class, 'robotsTxt']);
    Route::get('/sitemap.xml', [ProxyController::class, 'sitemapXml']);
    Route::get('/schema', [ProxyController::class, 'schema']);
});

// Embedded app JSON API (JWT-session protected)
Route::prefix('api')->middleware(VerifyShopifySession::class)->group(function () {
    Route::get('/dashboard', [ApiController::class, 'dashboard']);
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
