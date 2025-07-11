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

    // 企業詳細 API
    Route::prefix('companies')->group(function () {
        // 企業詳細情報
        Route::get('{company_id}', [CompanyController::class, 'show']);

        // 企業の記事一覧
        Route::get('{company_id}/articles', [CompanyController::class, 'articles']);

        // 企業の影響力スコア履歴
        Route::get('{company_id}/scores', [CompanyController::class, 'scores']);

        // 企業のランキング情報
        Route::get('{company_id}/rankings', [CompanyController::class, 'rankings']);
    });

    // 記事 API
    Route::prefix('articles')->group(function () {
        // 記事一覧
        Route::get('', [App\Http\Controllers\Api\ArticleController::class, 'index']);
    });

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
