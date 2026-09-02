<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Always generate URLs from APP_URL. Laravel otherwise derives the URL
        // root from the request Host, which breaks asset/OG/canonical URLs when
        // the app is served behind a proxy or preview tunnel (internal host).
        $appUrl = (string) config('app.url');
        \Illuminate\Support\Facades\URL::forceRootUrl($appUrl);
        // Match the scheme declared in APP_URL so HTTPS apps (production and
        // the HTTPS preview tunnel) never emit http:// URLs for assets.
        if (str_starts_with($appUrl, 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
