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
        Schema::create('company_ranking_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('period_type', 10);
            $table->integer('current_rank');
            $table->integer('previous_rank')->nullable();
            $table->integer('rank_change')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            $table->index(['company_id', 'period_type'], 'idx_history_company_period');
            $table->index(['period_type', 'calculated_at'], 'idx_history_period_calculated');
            $table->index(['calculated_at'], 'idx_history_calculated_at');
            $table->unique(['company_id', 'period_type', 'calculated_at'], 'uniq_history_company_period_calc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_ranking_history');
    }
};
