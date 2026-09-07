<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Store historical analysis results for comparison
        Schema::create('analysis_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // brand_signals, speed_analysis, ai_visibility, shopping_feed
            $table->json('data'); // full analysis result
            $table->integer('score')->nullable(); // quick-access score (0-100)
            $table->timestamp('snapshot_date');
            $table->timestamps();

            $table->index(['store_id', 'type', 'snapshot_date']);
        });

        // Store AI traffic analytics over time
        Schema::create('ai_traffic_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('source'); // chatgpt, gemini, perplexity, grok, etc.
            $table->string('query')->nullable(); // the search query
            $table->string('url')->nullable(); // the page visited
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->decimal('revenue', 10, 2)->default(0);
            $table->boolean('converted')->default(false);
            $table->timestamp('visited_at');
            $table->timestamps();

            $table->index(['store_id', 'source', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_traffic_logs');
        Schema::dropIfExists('analysis_snapshots');
    }
};
