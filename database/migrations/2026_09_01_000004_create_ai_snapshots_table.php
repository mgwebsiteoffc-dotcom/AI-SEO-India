<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->date('snapshot_date')->index();
            $table->string('engine');                                // chatgpt|gemini|perplexity|grok|deepseek
            $table->integer('total_queries')->default(0);
            $table->integer('mentioned')->default(0);                // queries where brand appeared
            $table->integer('cited')->default(0);                    // queries with a citation/link
            $table->json('samples')->nullable();                     // sample answers
            $table->timestamps();
            $table->unique(['store_id', 'snapshot_date', 'engine']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_snapshots');
    }
};
