<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['slug', 'title', 'meta_description', 'excerpt', 'body', 'author', 'published_at'];
    protected $casts = ['published_at' => 'datetime'];
}
