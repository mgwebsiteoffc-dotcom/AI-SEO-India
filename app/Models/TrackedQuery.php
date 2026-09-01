<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackedQuery extends Model
{
    protected $fillable = ['store_id', 'query', 'category', 'active'];
    protected $casts = ['active' => 'boolean'];
}
