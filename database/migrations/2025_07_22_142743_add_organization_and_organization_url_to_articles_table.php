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
            $table->string('organization', 255)->nullable()->after('organization_name')->comment('組織スラグ名（Qiita/Zenn）');
            $table->string('organization_url', 1000)->nullable()->after('organization')->comment('組織URL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['organization', 'organization_url']);
        });
    }
};
