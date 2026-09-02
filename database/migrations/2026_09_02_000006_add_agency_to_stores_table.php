<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agency / white-label tier:
 *  - stores.parent_store_id — links a client store under its Agency-plan
 *    store (agency can manage + report on its clients).
 *  - stores.report_token — public token for the white-label client report
 *    (/client-report/{token}); cleared on detach to revoke access.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_store_id')->nullable()->after('tracking_enabled');
            $table->foreign('parent_store_id')->references('id')->on('stores')->nullOnDelete();
            $table->index('parent_store_id');

            $table->string('report_token', 40)->nullable()->unique()->after('parent_store_id');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropUnique(['report_token']);
            $table->dropColumn(['report_token']);
            $table->dropForeign(['parent_store_id']);
            $table->dropColumn('parent_store_id');
        });
    }
};
