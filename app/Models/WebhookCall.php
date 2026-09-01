<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookCall extends Model
{
    protected $fillable = ['topic', 'shop', 'payload', 'status'];
    protected $casts = ['payload' => 'array'];
}
