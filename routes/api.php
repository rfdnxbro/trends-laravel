<?php

use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CompanyRankingController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

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

    // 企業 API (Resource Route + 独自メソッド)
    Route::apiResource('companies', CompanyController::class, [
        'parameters' => ['companies' => 'company_id'],
    ]);

    // 企業関連の追加エンドポイント
    Route::prefix('companies/{company_id}')->group(function () {
        Route::get('articles', [CompanyController::class, 'articles']);
        Route::get('scores', [CompanyController::class, 'scores']);
        Route::get('rankings', [CompanyController::class, 'rankings']);
        Route::delete('force', [CompanyController::class, 'forceDestroy']);
    });

    // 記事 API (Resource Route - CRUD対応)
    Route::apiResource('articles', App\Http\Controllers\Api\ArticleController::class)->only(['index', 'show', 'update', 'destroy']);

    // 検索 API
    Route::prefix('search')->group(function () {
        // 企業検索
        Route::get('companies', [SearchController::class, 'searchCompanies']);

        // 記事検索
        Route::get('articles', [SearchController::class, 'searchArticles']);

        // 統合検索
        Route::get('', [SearchController::class, 'search']);
    });
});
