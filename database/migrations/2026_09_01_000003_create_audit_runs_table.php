<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->integer('score')->default(0);                    // 0-100 AI Readiness Score
            $table->json('summary')->nullable();                     // per-category scores
            $table->string('status')->default('running');            // running|completed|failed
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_run_id')->constrained('audit_runs')->cascadeOnDelete();
            $table->string('category');                              // crawlability|schema|content|brand|speed
            $table->string('severity');                              // critical|warning|info
            $table->string('code')->index();                         // e.g. robots_missing
            $table->string('title');
            $table->text('detail')->nullable();
            $table->text('recommendation')->nullable();
            $table->boolean('is_fixed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_issues');
        Schema::dropIfExists('audit_runs');
    }
};
