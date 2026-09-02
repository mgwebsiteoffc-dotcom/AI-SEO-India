<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEO/AEO field set for marketing posts: category taxonomy, meta title,
 * meta keywords, JSON-LD FAQ store, published-flag semantics via published_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('title')
                ->constrained('blog_categories')->nullOnDelete();
            $table->string('meta_title', 200)->nullable()->after('meta_description');
            $table->string('meta_keywords', 500)->nullable()->after('meta_title');
            $table->json('faqs')->nullable()->after('body');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Drops the FK + its index and the category_id column.
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['meta_title', 'meta_keywords', 'faqs']);
        });
    }
};
