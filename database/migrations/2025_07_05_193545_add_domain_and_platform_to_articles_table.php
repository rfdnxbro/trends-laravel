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
            $table->string('domain')->nullable()->after('url');
            $table->string('platform')->nullable()->after('domain');
            $table->foreignId('platform_id')->nullable()->change();
            $table->foreignId('company_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['domain', 'platform']);
            $table->foreignId('platform_id')->nullable(false)->change();
            $table->foreignId('company_id')->nullable(false)->change();
        });
    }
};
