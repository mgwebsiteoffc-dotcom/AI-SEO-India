<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A URL queued for an IndexNow ping (instant indexing). Flushed by
 * `php artisan indexnow:flush` (scheduled every 15 minutes) once the SaaS
 * owner enables IndexNow in /admin/settings.
 */
class IndexnowSubmission extends Model
{
    protected $table = 'indexnow_submissions';

    protected $fillable = ['store_id', 'url', 'attempts', 'submitted_at'];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
