<?php

namespace App\Webhooks;

use App\Models\Lead;
use App\Models\Store;
use Illuminate\Support\Facades\Log;
use Shopify\Webhooks\Handler;

/**
 * customers/data_request (GDPR) — Shopify tells us a customer asked for the
 * data we hold about them. We respond 200 (acknowledged) and write the audit
 * log. AI Visibility stores minimal personal data: a lead row (email/brand)
 * when someone runs the public scorecard, and merchant store rows. Customer
 * PII from order webhooks is never stored — attribution keeps order amounts
 * and channels only.
 */
class CustomerDataRequestHandler implements Handler
{
    public function handle(string $topic, string $shop, array $body): void
    {
        $emails = collect($body['customer']['email'] ?? ($body['customers'] ?? []))
            ->pipe(fn ($c) => is_array($c) && isset($c['email']) ? collect([$c['email']]) : $c)
            ->map(fn ($c) => is_array($c) ? ($c['email'] ?? null) : $c)
            ->filter();

        Log::info("GDPR customers/data_request for {$shop}", [
            'customer_id' => $body['customer']['id'] ?? ($body['customers'][0]['id'] ?? null),
            'emails_found_in_leads' => $emails
                ->map(fn ($e) => Lead::where('email', $e)->first()?->only(['id', 'email', 'brand', 'source', 'created_at']))
                ->filter()->values()->all(),
        ]);

        // Per Shopify's guidance a data request must be answered within 30 days.
        // The log above is the retrieval point; in production, wire this to your
        // support inbox (e.g. export + email). Data held per email is listed in
        // docs/SHOPIFY_APP_STORE_SUBMISSION.md.
    }
}
