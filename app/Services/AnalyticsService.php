<?php

namespace App\Services;

use App\Models\AiTrafficLog;
use App\Models\AnalysisSnapshot;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

/**
 * Analytics Service — comprehensive analytics and reporting.
 *
 * Provides:
 * - Historical analysis snapshots for comparison
 * - AI traffic analytics with source breakdown
 * - Trend analysis and growth tracking
 * - Export-ready reports
 */
class AnalyticsService
{
    /**
     * Get comprehensive analytics dashboard data.
     */
    public function dashboard(Store $store, int $days = 30): array
    {
        return [
            'ai_visibility' => $this->aiVisibilityTrend($store, $days),
            'brand_signals' => $this->brandSignalsTrend($store, $days),
            'speed_analysis' => $this->speedTrend($store, $days),
            'ai_traffic' => AiTrafficLog::summary($store->id, $days),
            'content_performance' => $this->contentPerformance($store, $days),
        ];
    }

    /**
     * AI visibility trend over time.
     */
    public function aiVisibilityTrend(Store $store, int $days = 30): array
    {
        $snapshots = AnalysisSnapshot::history($store->id, 'ai_visibility', $days);

        return [
            'current' => $snapshots->last()?->score ?? 0,
            'previous' => $snapshots->first()?->score ?? 0,
            'change' => ($snapshots->last()?->score ?? 0) - ($snapshots->first()?->score ?? 0),
            'trend' => $snapshots->map(fn($s) => [
                'date' => $s->snapshot_date->format('d M'),
                'score' => $s->score,
            ])->toArray(),
        ];
    }

    /**
     * Brand signals trend over time.
     */
    public function brandSignalsTrend(Store $store, int $days = 30): array
    {
        $snapshots = AnalysisSnapshot::history($store->id, 'brand_signals', $days);

        return [
            'current' => $snapshots->last()?->score ?? 0,
            'previous' => $snapshots->first()?->score ?? 0,
            'change' => ($snapshots->last()?->score ?? 0) - ($snapshots->first()?->score ?? 0),
            'trend' => $snapshots->map(fn($s) => [
                'date' => $s->snapshot_date->format('d M'),
                'score' => $s->score,
            ])->toArray(),
        ];
    }

    /**
     * Speed analysis trend over time.
     */
    public function speedTrend(Store $store, int $days = 30): array
    {
        $snapshots = AnalysisSnapshot::history($store->id, 'speed_analysis', $days);

        return [
            'current' => $snapshots->last()?->score ?? 0,
            'previous' => $snapshots->first()?->score ?? 0,
            'change' => ($snapshots->last()?->score ?? 0) - ($snapshots->first()?->score ?? 0),
            'trend' => $snapshots->map(fn($s) => [
                'date' => $s->snapshot_date->format('d M'),
                'score' => $s->score,
            ])->toArray(),
        ];
    }

    /**
     * Content performance analytics.
     */
    public function contentPerformance(Store $store, int $days = 30): array
    {
        $posts = $store->contentPosts()
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        return [
            'total_posts' => $posts->count(),
            'published' => $posts->where('status', 'published')->count(),
            'draft' => $posts->where('status', 'generated')->count(),
            'scheduled' => $posts->where('status', 'scheduled')->count(),
            'total_words' => $posts->sum('word_count'),
        ];
    }

    /**
     * Generate a comprehensive report for export.
     */
    public function generateReport(Store $store, int $days = 30): array
    {
        $brand = $store->brand_name ?: ucfirst(strtok($store->shop, '.'));
        $domain = $store->hostname();

        return [
            'report' => [
                'title' => "AI Visibility Report — {$brand}",
                'period' => "Last {$days} days",
                'generated_at' => now()->toIso8601String(),
                'store' => [
                    'name' => $brand,
                    'domain' => $domain,
                    'plan' => $store->plan,
                ],
            ],
            'summary' => [
                'ai_visibility_score' => AnalysisSnapshot::latest($store->id, 'ai_visibility')?->score ?? 0,
                'brand_signals_score' => AnalysisSnapshot::latest($store->id, 'brand_signals')?->score ?? 0,
                'speed_score' => AnalysisSnapshot::latest($store->id, 'speed_analysis')?->score ?? 0,
                'total_ai_traffic' => AiTrafficLog::where('store_id', $store->id)
                    ->where('visited_at', '>=', now()->subDays($days))
                    ->count(),
                'total_ai_revenue' => AiTrafficLog::where('store_id', $store->id)
                    ->where('visited_at', '>=', now()->subDays($days))
                    ->sum('revenue'),
            ],
            'trends' => $this->dashboard($store, $days),
        ];
    }
}
