<?php

namespace App\Webhooks;

use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use Shopify\Webhooks\Handler;

/**
 * customers/redact (GDPR) — erase all personal data we hold for the customer.
 * The only customer PII in this app lives in the public-lead table (email,
 * brand, optional shop_url, source). Order data webhooks intentionally never
 * persist customer names/emails — only amounts, channels and dates.
 */
class CustomerRedactHandler implements Handler
{
    public function handle(string $topic, string $shop, array $body): void
    {
        $emails = collect($body['customer']['email'] ?? ($body['customers'] ?? []))
            ->pipe(fn ($c) => is_array($c) && isset($c['email']) ? collect([$c['email']]) : $c)
            ->map(fn ($c) => is_array($c) ? ($c['email'] ?? null) : $c)
            ->filter();

        $deleted = 0;
        foreach ($emails as $email) {
            $deleted += Lead::where('email', $email)->delete();
        }

        Log::info("GDPR customers/redact for {$shop} — {$deleted} lead row(s) erased.");
    }
}
