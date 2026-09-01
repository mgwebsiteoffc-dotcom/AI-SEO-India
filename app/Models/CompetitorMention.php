<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitorMention extends Model
{
    protected $fillable = ['store_id', 'snapshot_date', 'engine', 'competitor_domain', 'mentioned', 'total_queries'];
    protected $casts = ['snapshot_date' => 'date'];
}
