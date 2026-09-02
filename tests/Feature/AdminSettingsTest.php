<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Services\AiVisibilityService;
use App\Services\SaasSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Non-production + empty ADMIN_* credentials = panel open for local preview.
        config(['admin.email' => '', 'admin.password' => '']);
    }

    public function test_owner_settings_page_loads_with_catalog(): void
    {
        $this->get('/admin/settings')
            ->assertOk()
            ->assertSee('AI engines monitored')
            ->assertSee('ChatGPT')
            ->assertSee('Microsoft Copilot')
            ->assertSee('Plan pricing');
    }

    public function test_engine_toggles_update_the_tracker(): void
    {
        $store = Store::create([
            'shop' => 'test.myshopify.com',
            'shopify_token' => 'tok',
            'brand_name' => 'Test Brand',
            'is_demo' => false,
        ]);

        // Switch everything off except Gemini.
        $this->post('/admin/settings/engines', ['engines' => ['gemini' => '1']])
            ->assertSessionHasNoErrors();

        $this->assertSame(['gemini'], app(SaasSettingsService::class)->enabledEngines());
        // The visibility service reads the same switches.
        $this->assertSame(['gemini'], app(AiVisibilityService::class)->availableEngines());

        // Full wipe → nothing left to run.
        $this->post('/admin/settings/engines', ['engines' => []])
            ->assertSessionHasNoErrors();
        $this->assertSame([], app(AiVisibilityService::class)->availableEngines());
    }

    public function test_tracking_and_billing_updates_persist(): void
    {
        $this->post('/admin/settings/tracking', ['tracking_enabled' => '0', 'tracking_time' => '23:15'])
            ->assertSessionHasNoErrors();
        $service = app(SaasSettingsService::class);
        $this->assertFalse($service->trackingEnabled());
        $this->assertSame('23:15', $service->tracking()['time']);

        $this->post('/admin/settings/billing', [
            'price_grow' => 1499,
            'price_scale' => 2999,
            'price_agency' => 6999,
        ])->assertSessionHasNoErrors();
        $this->assertSame(1499, $service->planPrice('grow'));
        $this->assertSame(2999, $service->planPrice('scale'));
        $this->assertSame(6999, $service->planPrice('agency'));
    }

    public function test_store_tracking_toggle_works(): void
    {
        $store = Store::create([
            'shop' => 'toggle-me.myshopify.com',
            'shopify_token' => 'tok',
            'brand_name' => 'Toggle Me',
            'is_demo' => false,
        ]);

        $this->post("/admin/stores/{$store->id}/toggle-tracking")->assertSessionHasNoErrors();
        $this->assertFalse($store->fresh()->tracking_enabled);

        $this->post("/admin/stores/{$store->id}/toggle-tracking")->assertSessionHasNoErrors();
        $this->assertTrue($store->fresh()->tracking_enabled);
    }
}
