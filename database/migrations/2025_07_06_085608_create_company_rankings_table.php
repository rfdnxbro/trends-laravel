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
        Schema::create('company_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('ranking_period', 10);
            $table->integer('rank_position');
            $table->decimal('total_score', 10, 2);
            $table->integer('article_count')->default(0);
            $table->integer('total_bookmarks')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index(['ranking_period', 'rank_position'], 'idx_rankings_period_rank');
            $table->index(['company_id', 'ranking_period'], 'idx_rankings_company_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_rankings');
    }
};
