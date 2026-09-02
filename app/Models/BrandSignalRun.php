<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandSignalRun extends Model
{
    protected $fillable = ['store_id', 'score', 'summary', 'checks'];

    protected $casts = [
        'summary' => 'array',
        'checks' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
