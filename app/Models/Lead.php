<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = ['email', 'brand', 'shop_url', 'source', 'share_token', 'share_payload'];

    protected $casts = [
        'share_payload' => 'array',
    ];
}
