<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategory extends Model
{
    protected $fillable = ['name', 'slug', 'meta_description'];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'category_id');
    }

    /** Unique slug, suffixed when taken. */
    public static function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = \Illuminate\Support\Str::slug($slug) ?: 'category';
        $candidate = $base;
        $i = 2;
        while (static::where('slug', $candidate)->where('id', '!=', $ignoreId)->exists()) {
            $candidate = $base.'-'.$i++;
        }
        return $candidate;
    }
}
