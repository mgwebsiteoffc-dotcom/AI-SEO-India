<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Global "SaaS admin" settings — the switches the app owner controls from
 * /admin/settings (AI engines toggled, daily snapshot scheduler, plan prices).
 *
 * Each row is a JSON-encoded value stored under a stable key, e.g.:
 *   engines  => { "chatgpt": {"enabled": true}, ... }
 *   tracking => { "enabled": true, "time": "06:00" }
 *   billing  => { "grow": 999, "scale": 1999, "agency": 4999 }
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_settings');
    }
};
