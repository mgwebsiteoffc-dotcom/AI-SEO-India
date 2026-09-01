<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LlmsEntry extends Model
{
    protected $fillable = ['store_id', 'kind', 'title', 'path', 'description', 'position'];
}
