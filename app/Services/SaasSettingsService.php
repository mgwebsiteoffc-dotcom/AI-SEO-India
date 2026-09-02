<?php

namespace App\Services;

use App\Models\SaasSetting;

/**
 * Central registry + storage for everything the SaaS owner can switch from the
 * /admin panel:
 *
 *   engines   – which AI engines the visibility tracker monitors and how
 *   tracking  – global daily-snapshot scheduler switch
 *   billing   – editable Grow / Scale / Agency monthly prices (INR)
 *
 * Engine entries live in a fixed catalogue so the tracker, the embedded-app
 * charts, the demo seeder and the admin UI all agree on labels/colours.
 */
class SaasSettingsService
{
    /** Fixed catalogue shown in the SaaS admin → Engines. */
    public const ENGINES = [
        'chatgpt' => [
            'label' => 'ChatGPT', 'color' => '#10a37f',
            'provider' => 'openai', 'llm_capable' => true,
            'hint' => 'Real OpenAI answer checks when OPENAI_API_KEY is set; otherwise retrieval-proxy checks.',
        ],
        'gemini' => [
            'label' => 'Gemini', 'color' => '#4285f4',
            'provider' => 'gemini', 'llm_capable' => true,
            'hint' => 'Real Google answer checks when GEMINI_API_KEY is set; otherwise retrieval-proxy checks.',
        ],
        'perplexity' => [
            'label' => 'Perplexity', 'color' => '#20b8cd',
            'provider' => null, 'llm_capable' => false,
            'hint' => 'Retrieval-proxy checks against live web results.',
        ],
        'grok' => [
            'label' => 'Grok', 'color' => '#111827',
            'provider' => null, 'llm_capable' => false,
            'hint' => 'Retrieval-proxy checks against live web results.',
        ],
        'claude' => [
            'label' => 'Claude', 'color' => '#d97757',
            'provider' => null, 'llm_capable' => false,
            'hint' => 'Retrieval-proxy checks against live web results.',
        ],
        'deepseek' => [
            'label' => 'DeepSeek', 'color' => '#4d6bfe',
            'provider' => null, 'llm_capable' => false,
            'hint' => 'Retrieval-proxy checks against live web results.',
        ],
        'copilot' => [
            'label' => 'Microsoft Copilot', 'color' => '#0f6cbd',
            'provider' => null, 'llm_capable' => false,
            'hint' => 'Retrieval-proxy checks against live web results.',
        ],
    ];

    /** Engines switched on out of the box (matches the tracker’s original set). */
    public const DEFAULT_ENABLED_ENGINES = ['chatgpt', 'gemini', 'perplexity', 'grok', 'deepseek'];

    public const DEFAULT_TRACKING = ['enabled' => true, 'time' => '06:00'];

    public const DEFAULT_BILLING = ['grow' => 999, 'scale' => 1999, 'agency' => 4999];

    // ------------------------------------------------------------ engines

    /** Effective engine map: key => ['enabled' => bool] in catalogue order. */
    public function engines(): array
    {
        $stored = SaasSetting::getValue('engines') ?? [];
        $map = [];
        foreach (self::ENGINES as $key => $meta) {
            $map[$key] = ['enabled' => (bool) ($stored[$key]['enabled'] ?? in_array($key, self::DEFAULT_ENABLED_ENGINES, true))];
        }
        return $map;
    }

    public function engineEnabled(string $engine): bool
    {
        return (bool) ($this->engines()[$engine]['enabled'] ?? false);
    }

    /** Keys of engines currently switched on (in catalogue order). */
    public function enabledEngines(): array
    {
        return array_keys(array_filter($this->engines(), fn ($e) => $e['enabled']));
    }

    public function saveEngines(array $enabledKeys): void
    {
        $wanted = array_fill_keys($enabledKeys, true);
        $map = [];
        foreach (self::ENGINES as $key => $meta) {
            $map[$key] = ['enabled' => isset($wanted[$key])];
        }
        SaasSetting::setValue('engines', $map);
    }

    /**
     * Which LLM provider should answer for this engine — null when the engine
     * has no provider, isn't LLM-capable, or the provider key isn't configured.
     */
    public function llmProviderFor(string $engine): ?string
    {
        $meta = self::ENGINES[$engine] ?? null;
        if (! $meta || ! $meta['llm_capable'] || ! $meta['provider']) {
            return null;
        }
        $configured = config("services.{$meta['provider']}.key");
        return $configured ? $meta['provider'] : null;
    }

    public function llmConfigured(): bool
    {
        return (bool) (config('services.openai.key') || config('services.gemini.key'));
    }

    // ------------------------------------------------------------ tracking

    public function tracking(): array
    {
        return array_merge(self::DEFAULT_TRACKING, SaasSetting::getValue('tracking') ?? []);
    }

    public function trackingEnabled(): bool
    {
        return (bool) ($this->tracking()['enabled'] ?? true);
    }

    public function saveTracking(bool $enabled, string $time = '06:00'): void
    {
        SaasSetting::setValue('tracking', [
            'enabled' => $enabled,
            'time' => preg_match('/^\d{2}:\d{2}$/', $time) ? $time : self::DEFAULT_TRACKING['time'],
        ]);
    }

    // ------------------------------------------------------------ billing

    /** Effective monthly prices (INR) with owner overrides applied. */
    public function planPrices(): array
    {
        return array_merge(self::DEFAULT_BILLING, SaasSetting::getValue('billing') ?? []);
    }

    public function planPrice(string $plan): int
    {
        $prices = $this->planPrices();
        return (int) ($prices[$plan] ?? (self::DEFAULT_BILLING[$plan] ?? 0));
    }

    public function savePlanPrices(int $grow, int $scale, int $agency): void
    {
        SaasSetting::setValue('billing', [
            'grow' => max(0, min(99999, $grow)),
            'scale' => max(0, min(99999, $scale)),
            'agency' => max(0, min(99999, $agency)),
        ]);
    }
}
