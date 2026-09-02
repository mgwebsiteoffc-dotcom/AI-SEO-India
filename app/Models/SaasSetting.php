<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per SaaS-owner setting key; `value` is always stored as JSON so new
 * owner switches can be added without migrations.
 */
class SaasSetting extends Model
{
    protected $table = 'saas_settings';

    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];

    /** Fetch a decoded setting value, or null. */
    public static function getValue(string $key): ?array
    {
        return static::where('key', $key)->first()?->value;
    }

    /** Upsert a JSON-able value under a key. */
    public static function setValue(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
