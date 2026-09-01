<?php

namespace App\Services;

use App\Models\AttributedOrder;
use App\Models\Store;

/**
 * AI Traffic Attribution — connects orders to AI platforms.
 *
 * ChatGPT auto-appends utm_source=chatgpt.com to outbound links; other engines
 * pass referring domains (perplexity.ai, gemini.google.com, etc.). We inspect the
 * order's landing_site / referring_site (from the orders/paid webhook payload) and
 * attribute it. Note: free-tier ChatGPT visits may arrive without referrer and
 * will appear as Direct — we teach merchants this in the report UI.
 */
class AttributionService
{
    public const CHANNEL_LABELS = [
        'chatgpt' => 'ChatGPT',
        'gemini' => 'Gemini',
        'perplexity' => 'Perplexity',
        'grok' => 'Grok',
        'claude' => 'Claude',
        'deepseek' => 'DeepSeek',
        'copilot' => 'Copilot',
        'other_ai' => 'Other AI',
    ];

    /** Process an orders/paid webhook payload (array form from the Shopify SDK). */
    public function processOrder(Store $store, array $payload): void
    {
        $orderName = $payload['name'] ?? null;
        if (! $orderName) {
            return;
        }
        $landing = (string) ($payload['landing_site'] ?? '');
        $referring = (string) ($payload['referring_site'] ?? '');
        $channel = $this->detectChannel($landing, $referring);

        // Only store AI-attributed orders (keeps the table honest and focused)
        if ($channel === null) {
            return;
        }

        $amount = (float) ($payload['current_total_price'] ?? $payload['total_price'] ?? 0);
        AttributedOrder::updateOrCreate(
            ['store_id' => $store->id, 'order_name' => $orderName],
            [
                'order_id' => $payload['id'] ?? null,
                'total_amount' => $amount,
                'currency' => $payload['currency'] ?? 'INR',
                'ai_channel' => $channel,
                'utm_source' => $this->utmSource($landing),
                'referring_site' => $referring ?: null,
                'landing_site' => $landing ?: null,
                'order_created_at' => isset($payload['created_at']) ? now()->parse($payload['created_at']) : now(),
            ]
        );
    }

    /** Full attribution report for a store. */
    public function report(Store $store): array
    {
        $orders = $store->attributedOrders()->orderByDesc('order_created_at')->get();

        $channels = collect(self::CHANNEL_LABELS)->map(function ($label, $key) use ($orders) {
            $group = $orders->where('ai_channel', $key);
            return [
                'channel' => $key,
                'label' => $label,
                'orders' => $group->count(),
                'revenue' => round($group->sum('total_amount'), 2),
            ];
        })->values();

        $totalRevenue = round($orders->sum('total_amount'), 2);
        $totalOrders = $orders->count();

        $trend = $orders->groupBy(fn ($o) => $o->order_created_at->format('Y-m-d'))
            ->map(fn ($g) => [
                'date' => $g->first()->order_created_at->format('d M'),
                'orders' => $g->count(),
                'revenue' => round($g->sum('total_amount'), 2),
            ])
            ->sortKeys()
            ->values()
            ->take(-14);

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'avg_order_value' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0,
            'channels' => $channels,
            'trend' => $trend,
            'recent' => $orders->take(10)->map(fn ($o) => [
                'order' => $o->order_name,
                'channel' => $o->ai_channel,
                'channel_label' => self::CHANNEL_LABELS[$o->ai_channel] ?? $o->ai_channel,
                'amount' => $o->total_amount,
                'date' => $o->order_created_at->toIso8601String(),
                'utm_source' => $o->utm_source,
            ])->values(),
            'notes' => [
                'ChatGPT adds utm_source=chatgpt.com to outbound links automatically.',
                'Free-tier ChatGPT visits may appear as Direct traffic (no referrer).',
                'For full GA4 reporting: connect GA4 (see AI Traffic tab → GA4 section).',
            ],
        ];
    }

    private function detectChannel(string $landing, string $referring): ?string
    {
        $haystack = strtolower($landing.' '.$referring);
        $rules = [
            'chatgpt' => ['chatgpt.com', 'openai.com', 'utm_source=chatgpt', 'chat.openai'],
            'gemini' => ['gemini.google', 'bard.google', 'utm_source=gemini', 'aistudio.google'],
            'perplexity' => ['perplexity.ai', 'utm_source=perplexity'],
            'grok' => ['grok.com', 'x.ai', 'utm_source=grok'],
            'claude' => ['claude.ai', 'anthropic', 'utm_source=claude'],
            'deepseek' => ['deepseek.com', 'utm_source=deepseek'],
            'copilot' => ['copilot.microsoft', 'bing.com', 'utm_source=copilot', 'utm_source=bing'],
        ];
        foreach ($rules as $channel => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($haystack, $needle)) {
                    return $channel;
                }
            }
        }
        if (preg_match('/utm_source=(ai|llm|chatbot)/i', $landing)) {
            return 'other_ai';
        }
        return null;
    }

    private function utmSource(string $landing): ?string
    {
        parse_str((string) parse_url($landing, PHP_URL_QUERY), $params);
        return $params['utm_source'] ?? null;
    }
}
