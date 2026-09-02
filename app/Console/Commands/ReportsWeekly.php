<?php

namespace App\Console\Commands;

use App\Mail\WeeklyReportMail;
use App\Models\Store;
use App\Services\WeeklyReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ReportsWeekly extends Command
{
    protected $signature = 'reports:weekly {shop?} {--dry : Render and preview digests without sending email}';
    protected $description = 'Send the weekly AI Visibility Report digest to stores with a report email';

    public function handle(): int
    {
        $service = app(WeeklyReportService::class);
        if (! $service->enabled()) {
            $this->warn('Weekly email reports are disabled in /admin/settings.');
            return self::SUCCESS;
        }

        $query = Store::query();
        if ($shop = $this->argument('shop')) {
            $query->where('shop', $shop);
        } else {
            $query->where(fn ($q) => $q->whereNotNull('shopify_token')->orWhere('is_demo', true));
        }

        $sent = 0;
        $skipped = 0;
        foreach ($query->get() as $store) {
            if (! $service->eligible($store)) {
                $skipped++;
                continue;
            }
            $digest = $service->buildDigest($store);
            if (! $digest) {
                $skipped++;
                continue;
            }

            $email = $service->reportEmail($store);
            if ($this->option('dry')) {
                $this->line("  [dry] {$store->shop} -> {$email}: overall ".($digest['overall_this'] ?? '—').'% (delta '.($digest['overall_delta'] ?? 'n/a').')');
                $sent++;
                continue;
            }

            try {
                Mail::to($email)->send(new WeeklyReportMail($digest));
                $sent++;
                $this->line("  sent {$store->shop} -> {$email}");
            } catch (\Throwable $e) {
                $this->error("  failed {$store->shop}: ".$e->getMessage());
            }
        }

        $this->info("Weekly report run complete — {$sent} sent, {$skipped} skipped (no report email / no data / tracking paused).");
        return self::SUCCESS;
    }
}
