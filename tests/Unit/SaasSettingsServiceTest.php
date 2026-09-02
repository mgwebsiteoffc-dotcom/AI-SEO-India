<?php

namespace Tests\Unit;

use App\Services\SaasSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_engines_match_the_tracker_original_set(): void
    {
        $service = app(SaasSettingsService::class);

        $this->assertSame(
            ['chatgpt', 'gemini', 'perplexity', 'grok', 'deepseek'],
            $service->enabledEngines()
        );
        $this->assertFalse($service->engineEnabled('claude'));
        $this->assertFalse($service->engineEnabled('copilot'));
    }

    public function test_saving_engines_persists_and_updates_available_set(): void
    {
        $service = app(SaasSettingsService::class);
        $service->saveEngines(['chatgpt', 'perplexity', 'claude']);

        $this->assertSame(
            ['chatgpt', 'perplexity', 'claude'],
            $service->enabledEngines()
        );

        // Disabling every engine = tracking paused (empty availableEngines downstream).
        $service->saveEngines([]);
        $this->assertSame([], $service->enabledEngines());
    }

    public function test_llm_provider_only_returns_for_configured_keys(): void
    {
        $service = app(SaasSettingsService::class);

        $this->assertNull($service->llmProviderFor('chatgpt')); // no key configured
        $this->assertNull($service->llmProviderFor('perplexity')); // no provider, ever

        config(['services.openai.key' => 'sk-test']);
        $this->assertSame('openai', $service->llmProviderFor('chatgpt'));
        $this->assertNull($service->llmProviderFor('gemini')); // gemini key still unset
    }

    public function test_tracking_defaults_and_overrides(): void
    {
        $service = app(SaasSettingsService::class);
        $this->assertTrue($service->trackingEnabled());
        $this->assertSame('06:00', $service->tracking()['time']);

        $service->saveTracking(false, '07:30');
        $this->assertFalse($service->trackingEnabled());
        $this->assertSame('07:30', $service->tracking()['time']);
    }

    public function test_plan_prices_default_and_override(): void
    {
        $service = app(SaasSettingsService::class);
        $this->assertSame(999, $service->planPrice('grow'));

        $service->savePlanPrices(1099, 2499, 5499);
        $this->assertSame(1099, $service->planPrice('grow'));
        $this->assertSame(2499, $service->planPrice('scale'));
        $this->assertSame(5499, $service->planPrice('agency'));
    }
}
