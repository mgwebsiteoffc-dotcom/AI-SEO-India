<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Daily AI visibility snapshot for every active store. The SaaS owner controls
 * the run from /admin/settings (on/off + preferred IST time); the per-store
 * kill switch (stores.tracking_enabled) is honoured inside the command.
 *
 * Everything reads settings lazily (inside `when` closures) so that booting
 * artisan on a fresh database never queries tables that don't exist yet.
 */
Schedule::command('visibility:track --all')
    ->everyMinute()
    ->when(fn () => app(\App\Services\SaasSettingsService::class)->trackingEnabled())
    ->when(fn () => now('Asia/Kolkata')->format('H:i') === (app(\App\Services\SaasSettingsService::class)->tracking()['time'] ?? '06:00'))
    ->withoutOverlapping();
