<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditIssue extends Model
{
    protected $fillable = ['audit_run_id', 'category', 'severity', 'code', 'title', 'detail', 'recommendation', 'is_fixed'];
    protected $casts = ['is_fixed' => 'boolean'];

    public function run(): BelongsTo { return $this->belongsTo(AuditRun::class, 'audit_run_id'); }
}
