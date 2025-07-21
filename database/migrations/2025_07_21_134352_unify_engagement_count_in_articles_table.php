<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // 新しい統一カラムを追加
            $table->integer('engagement_count')->default(0)->after('likes_count')
                ->comment('エンゲージメント数（はてなブックマーク数・Qiita/Zennいいね数の統一カラム）');
        });

        // データ移行: 既存のbookmark_countとlikes_countの最大値をengagement_countに統合
        // SQLite対応のため条件分岐で実装
        DB::statement('UPDATE articles SET engagement_count = CASE 
            WHEN COALESCE(bookmark_count, 0) > COALESCE(likes_count, 0) THEN COALESCE(bookmark_count, 0)
            ELSE COALESCE(likes_count, 0)
        END');

        Schema::table('articles', function (Blueprint $table) {
            // 古いカラムを削除
            $table->dropColumn(['bookmark_count', 'likes_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // 元のカラムを復元
            $table->integer('bookmark_count')->default(0)->after('published_at');
            $table->integer('likes_count')->default(0)->after('bookmark_count')->comment('いいね数（Qiita用）');
        });

        // データを元に戻す（プラットフォームに応じて分離）
        // PostgreSQL対応のUPDATE構文
        DB::statement("
            UPDATE articles 
            SET 
                bookmark_count = CASE 
                    WHEN EXISTS (SELECT 1 FROM platforms WHERE platforms.id = articles.platform_id AND platforms.name = 'はてなブックマーク') 
                    THEN engagement_count 
                    ELSE 0 
                END,
                likes_count = CASE 
                    WHEN EXISTS (SELECT 1 FROM platforms WHERE platforms.id = articles.platform_id AND platforms.name IN ('Qiita', 'Zenn')) 
                    THEN engagement_count 
                    ELSE 0 
                END
        ");

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('engagement_count');
        });
    }
};
