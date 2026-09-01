<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_billing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('plan');                                  // grow|scale|agency
            $table->decimal('amount', 10, 2);                        // INR
            $table->string('charge_id')->nullable();                 // Shopify one-time/recurring charge id
            $table->string('charge_status')->nullable();             // pending|accepted|active|cancelled|declined
            $table->string('charge_type')->default('recurring');     // recurring|one_time
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_billing');
    }
};
