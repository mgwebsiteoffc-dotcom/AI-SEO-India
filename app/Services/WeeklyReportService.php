<?php

namespace App\Services;

use App\Models\AiSnapshot;
use App\Models\Store;

/**
 * Builds the merchant-facing "Weekly AI Visibility Report" digest.
 *
 * Windows (rolling):
 *   this week = the last 7 days ending today,
 *   previous  = the 7 days before that.
 * Rates are weighted (sum mentioned / sum total) per engine so sparse days
 * don't distort the numbers, and per-engine deltas are honest "no data yet"
 * when there is nothing to compare.
 */
class WeeklyReportService
{
    public const DEFAULT_CONFIG = ['enabled' => true, 'time' => '07:00'];

    public function config(): array
    {
        return array_merge(self::DEFAULT_CONFIG, \App\Models\SaasSetting::getValue('reports') ?? []);
    }

    public function enabled(): bool
    {
        return (bool) ($this->config()['enabled'] ?? true);
    }

    public function saveConfig(bool $enabled): void
    {
        \App\Models\SaasSetting::setValue('reports', ['enabled' => $enabled, 'time' => self::DEFAULT_CONFIG['time']]);
    }

    public function reportEmail(Store $store): ?string
    {
        $email = trim((string) ($store->settings['report_email'] ?? ''));
        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /** Is the store eligible to receive a weekly digest right now? */
    public function eligible(Store $store): bool
    {
        return $this->reportEmail($store) !== null
            && $store->tracking_enabled
            && $store->snapshots()->count() > 0;
    }

    /**
     * Compute the digest payload for a store.
     * Returns null when there is no snapshot data at all.
     */
    public function buildDigest(Store $store): ?array
    {
        if ($store->snapshots()->count() === 0) {
            return null;
        }

        $today = now()->startOfDay();
        $thisFrom = $today->copy()->subDays(6);
        $prevFrom = $today->copy()->subDays(13);
        $prevTo = $today->copy()->subDays(7);

        $snapshots = $store->snapshots()
            ->whereBetween('snapshot_date', [$prevFrom, $today])
            ->orderBy('snapshot_date')->get();

        $engines = [];
        foreach ($snapshots->groupBy('engine') as $engine => $rows) {
            $thisRows = $rows->filter(fn ($r) => $r->snapshot_date->between($thisFrom, $today));
            $prevRows = $rows->filter(fn ($r) => $r->snapshot_date->between($prevFrom, $prevTo));

            $rate = fn ($g) => $g->sum('total_queries') > 0
                ? round($g->sum('mentioned') / $g->sum('total_queries') * 100, 1) : null;

            $engines[] = [
                'engine' => $engine,
                'this' => $rate($thisRows),
                'prev' => $rate($prevRows),
                'delta' => ($rate($thisRows) !== null && $rate($prevRows) !== null)
                    ? round($rate($thisRows) - $rate($prevRows), 1) : null,
                'days' => $thisRows->count(),
            ];
        }

        $overall = function ($from, $to) use ($snapshots) {
            $rows = $snapshots->filter(fn ($r) => $r->snapshot_date->between($from, $to));
            return $rows->sum('total_queries') > 0
                ? round($rows->sum('mentioned') / $rows->sum('total_queries') * 100, 1) : null;
        };

        // Trend for the mini chart (last 7 days, weighted across engines).
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);
            $rows = $snapshots->filter(fn ($r) => $r->snapshot_date->isSameDay($day));
            $trend[] = [
                'date' => $day->format('d M'),
                'rate' => $rows->sum('total_queries') > 0
                    ? round($rows->sum('mentioned') / $rows->sum('total_queries') * 100, 1) : null,
            ];
        }

        // Sample cites worth celebrating (from any engine's latest snapshot).
        $samples = [];
        foreach ($store->snapshots()->orderByDesc('snapshot_date')->limit(6)->get() as $s) {
            foreach (($s->samples ?? []) as $sample) {
                if (! empty($sample['mentioned']) && ! empty($sample['snippet'])) {
                    $samples[] = ['engine' => $s->engine, 'query' => $sample['query'], 'snippet' => $sample['snippet']];
                }
                if (count($samples) >= 3) {
                    break;
                }
            }
            if (count($samples) >= 3) {
                break;
            }
        }

        $audit = $store->audits()->where('status', 'completed')->latest()->first();

        $meta = \App\Services\SaasSettingsService::ENGINES;

        return [
            'brand' => $store->brand_name ?: ucfirst(strtok($store->shop, '.')),
            'domain' => $store->hostname(),
            'period' => $thisFrom->format('d M').' – '.$today->format('d M Y'),
            'overall_this' => $overall($thisFrom, $today),
            'overall_prev' => $overall($prevFrom, $prevTo),
            'overall_delta' => (function () use ($overall, $thisFrom, $today, $prevFrom, $prevTo) {
                $a = $overall($thisFrom, $today);
                $b = $overall($prevFrom, $prevTo);
                return ($a !== null && $b !== null) ? round($a - $b, 1) : null;
            })(),
            'engines' => array_map(fn ($e) => [
                'label' => $meta[$e['engine']]['label'] ?? ucfirst($e['engine']),
                'this' => $e['this'],
                'prev' => $e['prev'],
                'delta' => $e['delta'],
                'days' => $e['days'],
            ], $engines),
            'trend' => $trend,
            'samples' => $samples,
            'query_count' => $store->queries()->where('active', true)->count(),
            'audit_score' => $audit?->score,
            'audit_grade' => $audit?->summary['grade'] ?? null,
            'generated_at' => now()->setTimezone('Asia/Kolkata')->format('d M Y, H:i T'),
        ];
    }
}
