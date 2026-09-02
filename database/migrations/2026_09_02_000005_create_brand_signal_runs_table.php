<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One stored "Brand Signals" run per store — live probes of the signals that
 * AI engines weigh when deciding whether to cite a brand: rating schema,
 * review content, third-party review-platform presence, off-site mentions and
 * social profiles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_signal_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->unsignedSmallInteger('score')->default(0);
            $table->json('summary')->nullable();
            $table->json('checks')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_signal_runs');
    }
};
