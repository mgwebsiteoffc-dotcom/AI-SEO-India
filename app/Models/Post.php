<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Post extends Model
{
    protected $fillable = [
        'slug', 'title', 'meta_description', 'meta_title', 'meta_keywords',
        'excerpt', 'body', 'faqs', 'author', 'category_id', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'faqs' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->whereNotNull('published_at');
    }

    /** SEO title with a sane fallback, capped for search snippets. */
    public function seoTitle(): string
    {
        return $this->meta_title ?: $this->title;
    }

    /** Meta keywords split into an array of tags. */
    public function keywords(): array
    {
        return array_values(array_filter(array_map(
            fn ($k) => trim((string) $k),
            explode(',', (string) $this->meta_keywords)
        )));
    }

    public function readingMinutes(): int
    {
        $words = str_word_count(strip_tags((string) $this->body));
        return max(1, (int) ceil($words / 200));
    }

    /** Unique slug helper (suffix on collision), shared by create + edit. */
    public static function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug) ?: 'post';
        $candidate = $base;
        $i = 2;
        while (static::where('slug', $candidate)->where('id', '!=', $ignoreId)->exists()) {
            $candidate = $base.'-'.$i++;
        }
        return $candidate;
    }
}
