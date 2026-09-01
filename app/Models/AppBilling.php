<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppBilling extends Model
{
    protected $table = 'app_billing';
    protected $fillable = ['store_id', 'plan', 'amount', 'charge_id', 'charge_status', 'charge_type', 'activated_at', 'expires_at', 'payload'];
    protected $casts = ['payload' => 'array', 'activated_at' => 'datetime', 'expires_at' => 'datetime'];
}
