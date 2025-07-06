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

Route::middleware(['throttle:60,1'])->group(function () {
    // 企業ランキング API
    Route::prefix('rankings')->group(function () {
        // 期間タイプ一覧
        Route::get('periods', [CompanyRankingController::class, 'periods']);
        
        // 統計情報
        Route::get('statistics', [CompanyRankingController::class, 'statistics']);
        
        // 期間別ランキング
        Route::get('{period}', [CompanyRankingController::class, 'index']);
        
        // 上位N件のランキング
        Route::get('{period}/top/{limit}', [CompanyRankingController::class, 'top']);
        
        // 順位変動上位企業
        Route::get('{period}/risers', [CompanyRankingController::class, 'risers']);
        
        // 順位変動下位企業
        Route::get('{period}/fallers', [CompanyRankingController::class, 'fallers']);
        
        // 順位変動統計
        Route::get('{period}/statistics', [CompanyRankingController::class, 'changeStatistics']);
        
        // 特定企業のランキング
        Route::get('company/{company_id}', [CompanyRankingController::class, 'company']);
    });
});