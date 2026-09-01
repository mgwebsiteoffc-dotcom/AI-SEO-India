<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributed_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('order_name');
            $table->string('order_id')->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->string('currency')->default('INR');
            $table->string('ai_channel')->default('other_ai');
            $table->string('utm_source')->nullable();
            $table->string('referring_site')->nullable();
            $table->string('landing_site')->nullable();
            $table->timestamp('order_created_at');
            $table->timestamps();
            $table->unique(['store_id', 'order_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributed_orders');
    }
};
