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
        Schema::table('companies', function (Blueprint $table) {
            $table->json('url_patterns')->nullable()->comment('URLパターンのJSONリスト');
            $table->json('domain_patterns')->nullable()->comment('ドメインパターンのJSONリスト');
            $table->json('keywords')->nullable()->comment('キーワードのJSONリスト');
            $table->json('zenn_organizations')->nullable()->comment('Zenn組織名のJSONリスト');
            // qiita_usernameとzenn_usernameは既存なのでスキップ
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'url_patterns',
                'domain_patterns',
                'keywords',
                'zenn_organizations',
            ]);
        });
    }
};
