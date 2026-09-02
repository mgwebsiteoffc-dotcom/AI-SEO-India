<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\IndexNowService;
use Illuminate\Console\Command;

class FlushIndexNow extends Command
{
    protected $signature = 'indexnow:flush {--all : Flush for every store with pending URLs}';
    protected $description = 'Send queued IndexNow (instant indexing) pings for changed storefront URLs';

    public function handle(): int
    {
        $service = app(IndexNowService::class);
        if (! $service->enabled()) {
            $this->warn('IndexNow is disabled — enable it + set a key in /admin/settings first.');
            return self::SUCCESS;
        }

        $stores = Store::query()
            ->whereIn('id', function ($q) {
                $q->select('store_id')->distinct()
                    ->from('indexnow_submissions')
                    ->whereNull('submitted_at');
            })
            ->get();

        $totalSent = 0;
        $totalFailed = 0;
        $active = 0;
        foreach ($stores as $store) {
            if (! $service->storeEnabled($store)) {
                continue;
            }
            $active++;
            $result = $service->flushStore($store);
            $totalSent += $result['sent'] ?? 0;
            $totalFailed += $result['failed'] ?? 0;
            if (($result['sent'] ?? 0) > 0) {
                $this->line("  {$store->shop}: {$result['sent']} URL(s) submitted");
            }
        }

        $this->info("IndexNow flush complete — {$active} store(s), {$totalSent} sent, {$totalFailed} failed.");
        return self::SUCCESS;
    }
}
