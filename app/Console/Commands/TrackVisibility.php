<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\AiVisibilityService;
use App\Services\SaasSettingsService;
use Illuminate\Console\Command;

class TrackVisibility extends Command
{
    protected $signature = 'visibility:track {shop?} {--all : Track all stores}';
    protected $description = 'Run the AI visibility snapshot (mentions/citations per query per engine)';

    public function handle(): int
    {
        $settings = app(SaasSettingsService::class);

        // SaaS-owner master switch (Scheduled runs are also gated in the
        // scheduler, but manual runs respect it too).
        if (! $settings->trackingEnabled()) {
            $this->warn('Daily AI tracking is disabled in /admin/settings — nothing to do.');
            return self::SUCCESS;
        }
        if (empty($settings->enabledEngines())) {
            $this->warn('No AI engines are enabled in /admin/settings — nothing to do.');
            return self::SUCCESS;
        }

        $service = app(AiVisibilityService::class);

        if ($this->option('all')) {
            $stores = Store::whereNotNull('shopify_token')->orWhere('is_demo', true)
                ->where('tracking_enabled', true)->get();
        } else {
            $shop = $this->argument('shop');
            $stores = $shop
                ? Store::where('shop', $shop)->where('tracking_enabled', true)->get()
                : Store::where('is_demo', true)->orWhereNotNull('shopify_token')
                    ->where('tracking_enabled', true)->limit(5)->get();
        }

        $total = 0;
        foreach ($stores as $store) {
            $this->info("Tracking {$store->shop}...");
            $snapshots = $service->runSnapshot($store);
            $total += count($snapshots);
            foreach ($snapshots as $s) {
                $this->line("  {$s->engine}: {$s->mentioned}/{$s->total_queries} mentioned ({$s->mentionRate()}%)");
            }
        }

        $this->info("Done — {$stores->count()} store(s), {$total} engine snapshot(s).");
        return self::SUCCESS;
    }
}
