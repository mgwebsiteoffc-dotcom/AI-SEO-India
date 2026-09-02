<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public sharing of a scorecard result: each lead that runs a live scan gets
 * a random share token + a frozen JSON snapshot of the score, so
 * /scorecard/{token} can render a shareable public page (with OG tags) that
 * does not re-run the audit or expose the email.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('share_token', 40)->nullable()->unique()->after('source');
            $table->json('share_payload')->nullable()->after('share_token');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['share_token', 'share_payload']);
        });
    }
};
