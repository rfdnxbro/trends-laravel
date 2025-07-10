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
        Schema::table('company_influence_scores', function (Blueprint $table) {
            $table->decimal('total_score', 15, 2)->change();
        });

        Schema::table('company_rankings', function (Blueprint $table) {
            $table->decimal('total_score', 15, 2)->change();
        });

        Schema::table('company_ranking_histories', function (Blueprint $table) {
            $table->decimal('total_score', 15, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_influence_scores', function (Blueprint $table) {
            //
        });
    }
};
