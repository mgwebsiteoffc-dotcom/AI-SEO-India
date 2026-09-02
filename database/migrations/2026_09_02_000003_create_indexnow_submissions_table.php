<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IndexNow submission queue — URLs awaiting (or already sent to)
 * api.indexnow.org so search engines / AI indexes notice store changes fast.
 * Filled by product/webhook changes and Smart Blogger publishes; flushed by
 * `php artisan indexnow:flush`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indexnow_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('url', 500);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indexnow_submissions');
    }
};
