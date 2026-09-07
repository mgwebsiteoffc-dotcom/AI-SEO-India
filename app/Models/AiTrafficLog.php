<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTrafficLog extends Model
{
    protected $fillable = [
        'store_id', 'source', 'query', 'url', 'utm_source',
        'utm_medium', 'utm_campaign', 'revenue', 'converted', 'visited_at',
    ];
    protected $casts = ['visited_at' => 'datetime', 'revenue' => 'decimal:2', 'converted' => 'boolean'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Log an AI traffic visit.
     */
    public static function log(array $data): self
    {
        return static::create(array_merge($data, [
            'visited_at' => $data['visited_at'] ?? now(),
        ]));
    }

    /**
     * Get traffic summary for a store.
     */
    public static function summary(int $storeId, int $days = 30): array
    {
        $logs = static::where('store_id', $storeId)
            ->where('visited_at', '>=', now()->subDays($days))
            ->get();

        $bySource = $logs->groupBy('source')->map(function ($group) {
            return [
                'visits' => $group->count(),
                'revenue' => $group->sum('revenue'),
                'conversions' => $group->where('converted', true)->count(),
            ];
        });

        $daily = $logs->groupBy(fn($l) => $l->visited_at->format('Y-m-d'))
            ->map(function ($group) {
                return [
                    'visits' => $group->count(),
                    'revenue' => $group->sum('revenue'),
                ];
            });

        return [
            'total_visits' => $logs->count(),
            'total_revenue' => $logs->sum('revenue'),
            'total_conversions' => $logs->where('converted', true)->count(),
            'by_source' => $bySource,
            'daily' => $daily,
        ];
    }
}
