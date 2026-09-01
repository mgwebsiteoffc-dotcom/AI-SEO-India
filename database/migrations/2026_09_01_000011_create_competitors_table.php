<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('name');
            $table->string('domain');
            $table->timestamps();
        });

        Schema::create('competitor_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->string('engine');
            $table->string('competitor_domain');
            $table->integer('mentioned')->default(0);
            $table->integer('total_queries')->default(0);
            $table->timestamps();
            $table->unique(['store_id', 'snapshot_date', 'engine', 'competitor_domain'], 'cm_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_mentions');
        Schema::dropIfExists('competitors');
    }
};
