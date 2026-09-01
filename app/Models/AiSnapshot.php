<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSnapshot extends Model
{
    protected $fillable = ['store_id', 'snapshot_date', 'engine', 'total_queries', 'mentioned', 'cited', 'samples'];
    protected $casts = ['samples' => 'array', 'snapshot_date' => 'date'];

    public function mentionRate(): float
    {
        return $this->total_queries > 0 ? round($this->mentioned / $this->total_queries * 100, 1) : 0;
    }
}
