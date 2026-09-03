<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'shop', 'shopify_token', 'scopes', 'plan', 'billing_id', 'billing_status',
        'trial_ends_at', 'billing_ends_at', 'domain', 'brand_name', 'currency',
        'country', 'is_demo', 'onboarding_completed', 'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_demo' => 'boolean',
        'onboarding_completed' => 'boolean',
        'trial_ends_at' => 'datetime',
        'billing_ends_at' => 'datetime',
    ];

    public function snapshots(): HasMany { return $this->hasMany(AiSnapshot::class); }
    public function audits(): HasMany { return $this->hasMany(AuditRun::class); }
    public function queries(): HasMany { return $this->hasMany(TrackedQuery::class); }
    public function llmsEntries(): HasMany { return $this->hasMany(LlmsEntry::class); }
    public function billing(): HasMany { return $this->hasMany(AppBilling::class); }
    public function competitors(): HasMany { return $this->hasMany(Competitor::class); }
    public function competitorMentions(): HasMany { return $this->hasMany(CompetitorMention::class); }
    public function attributedOrders(): HasMany { return $this->hasMany(AttributedOrder::class); }
    public function contentPosts(): HasMany { return $this->hasMany(ContentPost::class); }

    public function hostname(): string
    {
        $host = $this->domain ?? $this->shop;
        // Strip protocol if user entered full URL (e.g. "https://midorii.in")
        $host = preg_replace('#^https?://#', '', (string) $host);
        $host = rtrim($host, '/');
        return $host;
    }

    public function queryLimit(): int
    {
        return match ($this->plan) {
            'scale' => 500,
            'agency' => 2000,
            'grow' => 150,
            default => 25,
        };
    }

    public function planName(): string
    {
        return match ($this->plan) {
            'grow' => 'Grow',
            'scale' => 'Scale',
            'agency' => 'Agency',
            default => 'Free',
        };
    }
}
