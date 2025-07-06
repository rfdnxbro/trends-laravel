<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyArticleResource;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Services\CompanyInfluenceScoreService;
use App\Services\CompanyRankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    private CompanyRankingService $rankingService;

    private CompanyInfluenceScoreService $scoreService;

    public function __construct(
        CompanyRankingService $rankingService,
        CompanyInfluenceScoreService $scoreService
    ) {
        $this->rankingService = $rankingService;
        $this->scoreService = $scoreService;
    }

    /**
     * 企業詳細情報取得
     */
    public function show(int $companyId): JsonResponse
    {
        $validator = Validator::make(['company_id' => $companyId], [
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => '企業IDが無効です',
                'details' => $validator->errors(),
            ], 400);
        }

        $cacheKey = "company_detail_{$companyId}";
        $cacheTime = 300; // 5分

        return Cache::remember($cacheKey, $cacheTime, function () use ($companyId) {
            $company = Company::with(['rankings', 'articles' => function ($query) {
                $query->recent(30)->orderBy('published_at', 'desc')->limit(5);
            }])->find($companyId);

            if (! $company) {
                return response()->json([
                    'error' => '企業が見つかりません',
                ], 404);
            }

            // 現在のランキング情報を取得
            $currentRankings = $this->rankingService->getCompanyRankings($companyId);

            return response()->json([
                'data' => new CompanyResource($company, $currentRankings),
            ]);
        });
    }

    /**
     * 企業の記事一覧取得
     */
    public function articles(Request $request, int $companyId): JsonResponse
    {
        $validator = Validator::make(['company_id' => $companyId], [
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => '企業IDが無効です',
                'details' => $validator->errors(),
            ], 400);
        }

        $page = $request->get('page', 1);
        $perPage = min($request->get('per_page', 20), 100);
        $days = $request->get('days', 30);
        $minBookmarks = $request->get('min_bookmarks', 0);

        $cacheKey = "company_articles_{$companyId}_{$page}_{$perPage}_{$days}_{$minBookmarks}";
        $cacheTime = 300; // 5分

        return Cache::remember($cacheKey, $cacheTime, function () use ($companyId, $page, $perPage, $days, $minBookmarks) {
            $company = Company::find($companyId);

            if (! $company) {
                return response()->json([
                    'error' => '企業が見つかりません',
                ], 404);
            }

            $query = $company->articles()
                ->with('platform')
                ->recent($days)
                ->where('bookmark_count', '>=', $minBookmarks)
                ->orderBy('published_at', 'desc');

            $articles = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data' => CompanyArticleResource::collection($articles),
                'meta' => [
                    'current_page' => $articles->currentPage(),
                    'per_page' => $articles->perPage(),
                    'total' => $articles->total(),
                    'last_page' => $articles->lastPage(),
                    'company_id' => $companyId,
                    'filters' => [
                        'days' => $days,
                        'min_bookmarks' => $minBookmarks,
                    ],
                ],
            ]);
        });
    }

    /**
     * 企業の影響力スコア履歴取得
     */
    public function scores(Request $request, int $companyId): JsonResponse
    {
        $validator = Validator::make(['company_id' => $companyId], [
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => '企業IDが無効です',
                'details' => $validator->errors(),
            ], 400);
        }

        $days = $request->get('days', 30);
        $period = $request->get('period', '1d');

        $cacheKey = "company_scores_{$companyId}_{$days}_{$period}";
        $cacheTime = 300; // 5分

        return Cache::remember($cacheKey, $cacheTime, function () use ($companyId, $days, $period) {
            $company = Company::find($companyId);

            if (! $company) {
                return response()->json([
                    'error' => '企業が見つかりません',
                ], 404);
            }

            $scores = $this->scoreService->getCompanyScoreHistory($companyId, $period, $days);

            return response()->json([
                'data' => [
                    'company_id' => $companyId,
                    'scores' => $scores,
                ],
                'meta' => [
                    'period' => $period,
                    'days' => $days,
                    'total' => count($scores),
                ],
            ]);
        });
    }

    /**
     * 企業のランキング情報取得
     */
    public function rankings(Request $request, int $companyId): JsonResponse
    {
        $validator = Validator::make(['company_id' => $companyId], [
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => '企業IDが無効です',
                'details' => $validator->errors(),
            ], 400);
        }

        $includeHistory = $request->boolean('include_history', false);
        $historyDays = $request->get('history_days', 30);

        $cacheKey = "company_rankings_{$companyId}_{$includeHistory}_{$historyDays}";
        $cacheTime = 300; // 5分

        return Cache::remember($cacheKey, $cacheTime, function () use ($companyId, $includeHistory, $historyDays) {
            $company = Company::find($companyId);

            if (! $company) {
                return response()->json([
                    'error' => '企業が見つかりません',
                ], 404);
            }

            $rankings = $this->rankingService->getCompanyRankings($companyId);

            $response = [
                'data' => [
                    'company_id' => $companyId,
                    'rankings' => [],
                ],
            ];

            foreach ($rankings as $period => $ranking) {
                if ($ranking) {
                    $response['data']['rankings'][$period] = [
                        'rank_position' => $ranking->rank_position,
                        'total_score' => $ranking->total_score,
                        'article_count' => $ranking->article_count,
                        'total_bookmarks' => $ranking->total_bookmarks,
                        'period_start' => $ranking->period_start,
                        'period_end' => $ranking->period_end,
                        'calculated_at' => $ranking->calculated_at,
                    ];
                }
            }

            if ($includeHistory) {
                $response['data']['history'] = $this->rankingService->getCompanyRankingHistory($companyId, $historyDays);
            }

            return response()->json($response);
        });
    }
}
