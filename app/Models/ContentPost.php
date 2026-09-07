<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentPost extends Model
{
    protected $fillable = [
        'store_id', 'title', 'keyword', 'category', 'tone', 'status', 'body',
        'meta_title', 'meta_description', 'faqs', 'word_count',
        'shopify_article_id', 'shopify_article_url', 'error', 'scheduled_at',
    ];
    protected $casts = ['faqs' => 'array', 'scheduled_at' => 'datetime'];

    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
}
