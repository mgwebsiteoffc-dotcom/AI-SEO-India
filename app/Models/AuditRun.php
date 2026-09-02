<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditRun extends Model
{
    protected $fillable = ['store_id', 'score', 'summary', 'status', 'error', 'started_at', 'completed_at'];
    protected $casts = ['summary' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];

    public function issues(): HasMany { return $this->hasMany(AuditIssue::class); }

    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
}
