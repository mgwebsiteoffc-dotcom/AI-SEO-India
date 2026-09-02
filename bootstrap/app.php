<?php

use App\Console\Commands\SeedDemo;
use App\Console\Commands\TrackVisibility;
use App\Http\Middleware\VerifyProxyRequest;
use App\Http\Middleware\VerifyShopifySession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Register app commands (demo:seed, visibility:track). With Laravel 12, passing
    // `commands:` to withRouting() only registers routes/console.php — app/Console/Commands
    // is NOT auto-discovered, which silently disabled our commands and the scheduler.
    ->withCommands([app_path('Console/Commands')])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'shopify.session' => VerifyShopifySession::class,
            'shopify.proxy' => VerifyProxyRequest::class,
            'admin.guard' => \App\Http\Middleware\AdminAccess::class,
        ]);
        // Embedded-app API is authenticated via Shopify JWT (Authorization header),
        // App Proxy via signed query — no session CSRF needed on these routes.
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'webhooks/*',
            'apps/*',
            'billing/callback',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
