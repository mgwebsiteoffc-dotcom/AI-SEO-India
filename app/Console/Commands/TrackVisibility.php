<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\AiVisibilityService;
use Illuminate\Console\Command;

class TrackVisibility extends Command
{
    protected $signature = 'visibility:track {shop?} {--all : Track all stores}';
    protected $description = 'Run the daily AI visibility snapshot (mentions/citations per query per engine)';

    public function handle(): int
    {
        $service = app(AiVisibilityService::class);

        if ($this->option('all')) {
            $stores = Store::whereNotNull('shopify_token')->orWhere('is_demo', true)->get();
        } else {
            $shop = $this->argument('shop');
            $stores = $shop
                ? Store::where('shop', $shop)->get()
                : Store::where('is_demo', true)->orWhereNotNull('shopify_token')->limit(5)->get();
        }

        foreach ($stores as $store) {
            $this->info("Tracking {$store->shop}...");
            $snapshots = $service->runSnapshot($store);
            foreach ($snapshots as $s) {
                $this->line("  {$s->engine}: {$s->mentioned}/{$s->total_queries} mentioned ({$s->mentionRate()}%)");
            }
        }
        return self::SUCCESS;
    }
}
