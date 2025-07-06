<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompanyRankingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['throttle:api'])->group(function () {
    // 企業ランキング API
    Route::prefix('rankings')->group(function () {
        // 期間タイプ一覧
        Route::get('periods', [CompanyRankingController::class, 'getPeriodTypes']);
        
        // 統計情報
        Route::get('statistics', [CompanyRankingController::class, 'getStatistics']);
        
        // 期間別ランキング
        Route::get('{period}', [CompanyRankingController::class, 'getRankingByPeriod']);
        
        // 上位N件のランキング
        Route::get('{period}/top/{limit}', [CompanyRankingController::class, 'getTopRanking']);
        
        // 順位変動上位企業
        Route::get('{period}/risers', [CompanyRankingController::class, 'getRankingRisers']);
        
        // 順位変動下位企業
        Route::get('{period}/fallers', [CompanyRankingController::class, 'getRankingFallers']);
        
        // 順位変動統計
        Route::get('{period}/statistics', [CompanyRankingController::class, 'getRankingChangeStatistics']);
        
        // 特定企業のランキング
        Route::get('company/{company_id}', [CompanyRankingController::class, 'getCompanyRanking']);
    });
});