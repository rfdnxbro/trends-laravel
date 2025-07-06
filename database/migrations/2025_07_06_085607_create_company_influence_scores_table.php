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
        Schema::create('company_influence_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('period_type', 50);
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_score', 10, 2);
            $table->integer('article_count')->default(0);
            $table->integer('total_bookmarks')->default(0);
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            $table->index(['company_id', 'period_type', 'period_start'], 'idx_influence_company_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_influence_scores');
    }
};
