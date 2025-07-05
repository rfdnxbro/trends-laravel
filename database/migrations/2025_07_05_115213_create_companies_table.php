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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('企業名');
            $table->string('domain')->unique()->comment('企業ドメイン');
            $table->text('description')->nullable()->comment('企業説明');
            $table->string('logo_url', 500)->nullable()->comment('ロゴURL');
            $table->string('website_url', 500)->nullable()->comment('ウェブサイトURL');
            $table->boolean('is_active')->default(true)->comment('アクティブ状態');
            $table->timestamps();

            // インデックス設定
            $table->index('name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
