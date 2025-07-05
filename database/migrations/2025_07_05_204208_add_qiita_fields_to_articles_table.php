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
        Schema::table('articles', function (Blueprint $table) {
            $table->integer('likes_count')->default(0)->after('bookmark_count')->comment('いいね数（Qiita用）');
            $table->string('author')->nullable()->after('author_name')->comment('投稿者情報');
            $table->string('author_url', 500)->nullable()->after('author')->comment('投稿者URL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['likes_count', 'author', 'author_url']);
        });
    }
};
