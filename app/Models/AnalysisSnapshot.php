<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisSnapshot extends Model
{
    protected $fillable = ['store_id', 'type', 'data', 'score', 'snapshot_date'];
    protected $casts = ['data' => 'array', 'snapshot_date' => 'datetime'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the latest snapshot of a type for a store.
     */
    public static function latest(int $storeId, string $type): ?self
    {
        return static::where('store_id', $storeId)
            ->where('type', $type)
            ->orderByDesc('snapshot_date')
            ->first();
    }

    /**
     * Get historical snapshots for trend analysis.
     */
    public static function history(int $storeId, string $type, int $days = 30)
    {
        return static::where('store_id', $storeId)
            ->where('type', $type)
            ->where('snapshot_date', '>=', now()->subDays($days))
            ->orderBy('snapshot_date')
            ->get();
    }

    /**
     * Store a new snapshot.
     */
    public static function store_snapshot(int $storeId, string $type, array $data, ?int $score = null): self
    {
        return static::create([
            'store_id' => $storeId,
            'type' => $type,
            'data' => $data,
            'score' => $score,
            'snapshot_date' => now(),
        ]);
    }
}
