<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')->constrained('platforms');
            $table->foreignId('company_id')->constrained('companies');
            $table->string('title', 500);
            $table->string('url', 1000)->unique();
            $table->string('author_name', 255)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->integer('bookmark_count')->default(0);
            $table->timestamp('scraped_at');
            $table->timestamps();
            
            // インデックス作成
            $table->index(['company_id', 'published_at'], 'idx_articles_company_published');
            $table->index(['platform_id', 'scraped_at'], 'idx_articles_platform_scraped');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
