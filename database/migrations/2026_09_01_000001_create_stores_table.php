<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('shop')->unique()->index();               // mybrand.myshopify.com
            $table->string('shopify_token')->nullable();             // API access token
            $table->string('scopes')->nullable();
            $table->string('plan')->default('free');                 // free|grow|scale|agency
            $table->string('billing_id')->nullable();                // Shopify subscription id
            $table->string('billing_status')->default('inactive');   // active|pending|cancelled|declined
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('billing_ends_at')->nullable();
            $table->string('domain')->nullable();                    // custom domain (e.g. mybrand.in)
            $table->string('brand_name')->nullable();
            $table->string('currency')->default('INR');
            $table->string('country')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->json('settings')->nullable();                    // theme ext enabled, bots, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
