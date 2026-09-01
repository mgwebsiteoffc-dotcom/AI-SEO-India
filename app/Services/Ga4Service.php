<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Analytics 4 Data API (v1beta) integration.
 *
 * Pulls AI-sourced sessions / transactions / revenue from GA4 using a
 * Google service account (JWT grant — no heavy SDK, just OpenSSL).
 *
 * Configure in .env (or the store's GA4 Property ID setting):
 *   GA4_PROPERTY_ID=123456789
 *   GA4_CLIENT_EMAIL=ga4@project.iam.gserviceaccount.com
 *   GA4_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n..."
 *   (alternatively GA4_SERVICE_ACCOUNT=/path/to/service-account.json)
 *
 * The service account needs "Viewer" access on the GA4 property, and the
 * property must have ecommerce events (purchase) enabled.
 */
class Ga4Service
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const API_BASE = 'https://analyticsdata.googleapis.com/v1beta';
    private const SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';

    public function __construct(private Store $store) {}

    public function configured(): bool
    {
        return $this->propertyId() !== null && $this->credentials() !== null;
    }

    public function propertyId(): ?string
    {
        $storeId = $this->store->settings['ga4_property_id'] ?? null;
        return $storeId ?: (config('services.ga4.property_id') ?: null);
    }

    private function credentials(): ?array
    {
        $json = config('services.ga4.service_account');
        if ($json && is_file($json)) {
            $json = file_get_contents($json);
        }
        if ($json) {
            $data = json_decode((string) $json, true);
            if (is_array($data) && ! empty($data['client_email']) && ! empty($data['private_key'])) {
                return ['email' => $data['client_email'], 'key' => $data['private_key']];
            }
        }
        $email = config('services.ga4.client_email');
        $key = config('services.ga4.private_key');
        if ($email && $key) {
            return ['email' => $email, 'key' => $key];
        }
        return null;
    }

    public function accessToken(): ?string
    {
        $creds = $this->credentials();
        if (! $creds) {
            return null;
        }
        return Cache::remember('ga4_access_token', 3300, function () use ($creds) {
            return $this->fetchToken($creds['email'], $creds['key']);
        });
    }

    private function fetchToken(string $email, string $key): ?string
    {
        $header = self::b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();
        $claims = self::b64url(json_encode([
            'iss' => $email,
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ]));
        $signingInput = $header.'.'.$claims;
        if (! openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256)) {
            Log::warning('GA4: could not sign JWT — check GA4_PRIVATE_KEY');
            return null;
        }
        $assertion = $signingInput.'.'.self::b64url($signature);

        try {
            $res = Http::asForm()->timeout(15)->post(self::TOKEN_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);
        } catch (\Throwable $e) {
            Log::warning('GA4 token network error: '.$e->getMessage());
            return null;
        }
        if (! $res->successful()) {
            Log::warning('GA4 token error '.$res->status().': '.substr($res->body(), 0, 200));
            return null;
        }
        return $res->json('access_token');
    }

    /** Run the AI-traffic report against the GA4 Data API. */
    public function aiTrafficReport(array $opts = []): array
    {
        $property = $this->propertyId();
        $token = $this->accessToken();
        if (! $property || ! $token) {
            return [
                'configured' => false,
                'message' => 'GA4 not configured yet. Set GA4_PROPERTY_ID + GA4_CLIENT_EMAIL + GA4_PRIVATE_KEY '
                    .'(or GA4_SERVICE_ACCOUNT) in .env — see README "GA4 Data API".',
            ];
        }

        $days = (int) ($opts['days'] ?? 30);
        $sourceRegex = (string) config('services.ga4.source_regex', 'chatgpt|openai|perplexity|gemini|bard|grok|claude|deepseek|copilot');

        $body = [
            'dateRanges' => [['startDate' => $days.'daysAgo', 'endDate' => 'today']],
            'dimensions' => [['name' => 'sessionSource'], ['name' => 'date']],
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'totalUsers'],
                ['name' => 'ecommercePurchases'],
                ['name' => 'purchaseRevenue'],
                ['name' => 'engagedSessions'],
            ],
            'dimensionFilter' => [
                'filter' => [
                    'fieldName' => 'sessionSource',
                    'regexFilter' => ['value' => $sourceRegex, 'caseSensitive' => false],
                ],
            ],
            'orderBys' => [['dimension' => ['dimensionName' => 'date']]],
            'limit' => 10000,
        ];

        try {
            $res = Http::withToken($token)->timeout(25)
                ->post(self::API_BASE."/properties/{$property}:runReport", $body);
        } catch (\Throwable $e) {
            return ['configured' => true, 'error' => 'GA4 network error: '.$e->getMessage()];
        }

        if (! $res->successful()) {
            $err = $res->json('error.message', '');
            Log::warning('GA4 runReport error '.$res->status().': '.substr($res->body(), 0, 300));
            return [
                'configured' => true,
                'error' => 'GA4 API error ('.$res->status().'). Check the property ID and that the service '
                    .'account has Viewer access in GA4 Admin. '.$err,
            ];
        }

        $rows = $res->json('rows') ?? [];

        $sources = [];
        $trend = [];
        foreach ($rows as $row) {
            $dims = $row['dimensionValues'] ?? [];
            $mets = $row['metricValues'] ?? [];
            if (count($dims) < 2 || count($mets) < 4) {
                continue;
            }
            $source = $dims[0]['value'];
            $date = $dims[1]['value'];
            $sessions = (int) $mets[0]['value'];
            $users = (int) $mets[1]['value'];
            $transactions = (int) $mets[2]['value'];
            $revenue = (float) $mets[3]['value'];
            $engaged = (int) $mets[4]['value'];

            if (! isset($sources[$source])) {
                $sources[$source] = ['sessions' => 0, 'users' => 0, 'transactions' => 0, 'revenue' => 0.0, 'engaged' => 0];
            }
            $sources[$source]['sessions'] += $sessions;
            $sources[$source]['users'] += $users;
            $sources[$source]['transactions'] += $transactions;
            $sources[$source]['revenue'] += $revenue;
            $sources[$source]['engaged'] += $engaged;

            $trend[$date] = [
                'sessions' => ($trend[$date]['sessions'] ?? 0) + $sessions,
                'transactions' => ($trend[$date]['transactions'] ?? 0) + $transactions,
                'revenue' => round(($trend[$date]['revenue'] ?? 0) + $revenue, 2),
            ];
        }

        ksort($trend);
        $trendList = collect($trend)->map(fn ($t, $date) => [
            'date' => \Carbon\Carbon::parse($date)->format('d M'),
            'sessions' => $t['sessions'],
            'transactions' => $t['transactions'],
            'revenue' => round($t['revenue'], 2),
        ])->values()->take(-$days)->all();

        $sourceList = collect($sources)->map(function ($s, $source) {
            return [
                'source' => $source,
                'sessions' => $s['sessions'],
                'users' => $s['users'],
                'transactions' => $s['transactions'],
                'revenue' => round($s['revenue'], 2),
                'engagement_rate' => $s['sessions'] > 0 ? round($s['engaged'] / $s['sessions'] * 100, 1) : 0,
            ];
        })->sortByDesc('revenue')->values()->all();

        $totals = [
            'sessions' => array_sum(array_column($sourceList, 'sessions')),
            'users' => array_sum(array_column($sourceList, 'users')),
            'transactions' => array_sum(array_column($sourceList, 'transactions')),
            'revenue' => round(array_sum(array_column($sourceList, 'revenue')), 2),
        ];

        return [
            'configured' => true,
            'property_id' => $property,
            'days' => $days,
            'totals' => $totals,
            'sources' => $sourceList,
            'trend' => $trendList,
        ];
    }

    /** Clearly-labelled demo payload (no GA4 credentials needed for the preview). */
    public function demoReport(): array
    {
        $trend = [];
        $revenueByDay = [];
        for ($d = 29; $d >= 0; $d--) {
            $sessions = rand(35, 90);
            $transactions = (int) round($sessions * rand(4, 9) / 100);
            $revenue = $transactions * rand(600, 1400);
            $revenueByDay[] = $revenue;
            $trend[] = [
                'date' => now()->subDays($d)->format('d M'),
                'sessions' => $sessions,
                'transactions' => $transactions,
                'revenue' => round($revenue, 2),
            ];
        }

        return [
            'configured' => false,
            'demo' => true,
            'message' => 'Demo data (GA4 not connected). Connect a real property to see live numbers.',
            'totals' => [
                'sessions' => array_sum(array_column($trend, 'sessions')),
                'users' => (int) round(array_sum(array_column($trend, 'sessions')) * 0.82),
                'transactions' => array_sum(array_column($trend, 'transactions')),
                'revenue' => round(array_sum($revenueByDay), 2),
            ],
            'sources' => [
                ['source' => 'chatgpt.com', 'sessions' => 862, 'users' => 704, 'transactions' => 61, 'revenue' => 53410.0, 'engagement_rate' => 68.4],
                ['source' => 'perplexity.ai', 'sessions' => 310, 'users' => 251, 'transactions' => 19, 'revenue' => 17220.0, 'engagement_rate' => 61.2],
                ['source' => 'gemini.google.com', 'sessions' => 240, 'users' => 198, 'transactions' => 12, 'revenue' => 10890.0, 'engagement_rate' => 55.8],
                ['source' => 'grok.com', 'sessions' => 74, 'users' => 61, 'transactions' => 4, 'revenue' => 3480.0, 'engagement_rate' => 52.7],
                ['source' => 'claude.ai', 'sessions' => 58, 'users' => 49, 'transactions' => 3, 'revenue' => 2610.0, 'engagement_rate' => 50.1],
            ],
            'trend' => $trend,
        ];
    }

    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
