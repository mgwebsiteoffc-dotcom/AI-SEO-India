<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributedOrder extends Model
{
    protected $fillable = [
        'store_id', 'order_name', 'order_id', 'total_amount', 'currency', 'ai_channel',
        'utm_source', 'referring_site', 'landing_site', 'order_created_at',
    ];
    protected $casts = ['order_created_at' => 'datetime'];

    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
}
