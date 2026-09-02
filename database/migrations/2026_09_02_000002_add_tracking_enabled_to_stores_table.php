<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-store kill switch for the daily AI visibility snapshot, controlled from
 * the SaaS owner panel. A store with tracking disabled is skipped by the
 * scheduled tracker and by "run all" from the admin panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->boolean('tracking_enabled')->default(true)->after('is_demo');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('tracking_enabled');
        });
    }
};
