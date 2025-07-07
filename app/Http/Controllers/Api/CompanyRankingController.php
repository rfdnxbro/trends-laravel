<?php

namespace App\Http\Controllers\Api;

use App\Constants\CacheTime;
use App\Constants\RankingPeriod;
use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyRankingResource;
use App\Services\CompanyRankingHistoryService;
use App\Services\CompanyRankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class CompanyRankingController extends Controller
{
    private CompanyRankingService $rankingService;

    private CompanyRankingHistoryService $historyService;

    public function __construct(
        CompanyRankingService $rankingService,
        CompanyRankingHistoryService $historyService
    ) {
        $this->rankingService = $rankingService;
        $this->historyService = $historyService;
    }

    /**
     * @OA\Get(
     *     path="/api/rankings/{period}",
     *     tags={"企業ランキング"},
     *     summary="期間別ランキング取得",
     *     description="指定した期間の企業ランキングを取得します。",
     *     @OA\Parameter(
     *         name="period",
     *         in="path",
     *         required=true,
     *         description="期間タイプ (1w, 1m, 3m, 6m, 1y, 3y, all)",
     *         @OA\Schema(type="string", enum={"1w", "1m", "3m", "6m", "1y", "3y", "all"}, example="1m")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="ページ番号",
     *         @OA\Schema(type="integer", default=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="1ページあたりの件数（最大100）",
     *         @OA\Schema(type="integer", default=20, maximum=100, example=20)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="ソート項目",
     *         @OA\Schema(type="string", default="rank_position", example="rank_position")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="ソート順",
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="asc", example="asc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ランキングリスト",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="last_page", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="不正な期間タイプ",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request, string $period): JsonResponse
    {
        $validator = Validator::make(['period' => $period], [
            'period' => 'required|'.RankingPeriod::getValidationRule(),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => RankingPeriod::getErrorMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $page = $request->get('page', 1);
        $perPage = min($request->get('per_page', 20), 100);
        $sortBy = $request->get('sort_by', 'rank_position');
        $sortOrder = $request->get('sort_order', 'asc');

        $cacheKey = "company_ranking_{$period}_{$page}_{$perPage}_{$sortBy}_{$sortOrder}";

        return Cache::remember($cacheKey, CacheTime::RANKING, function () use ($period, $page, $perPage, $sortBy, $sortOrder) {
            $rankings = $this->rankingService->getRankingForPeriod($period, $perPage * 10);

            if (empty($rankings)) {
                return response()->json([
                    'data' => [],
                    'meta' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => 0,
                        'last_page' => 1,
                    ],
                ]);
            }

            // ソート処理
            $rankings = collect($rankings)->sortBy($sortBy, SORT_REGULAR, $sortOrder === 'desc');

            // ページネーション
            $total = $rankings->count();
            $offset = ($page - 1) * $perPage;
            $paginatedRankings = $rankings->slice($offset, $perPage)->values();

            return response()->json([
                'data' => CompanyRankingResource::collection($paginatedRankings),
                'meta' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                ],
            ]);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/rankings/{period}/top/{limit}",
     *     tags={"企業ランキング"},
     *     summary="上位N件のランキング取得",
     *     description="指定した期間の上位N件のランキングを取得します。",
     *     @OA\Parameter(
     *         name="period",
     *         in="path",
     *         required=true,
     *         description="期間タイプ",
     *         @OA\Schema(type="string", enum={"1w", "1m", "3m", "6m", "1y", "3y", "all"}, example="1m")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="path",
     *         required=true,
     *         description="取得件数 (1-100)",
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="上位ランキング",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="period", type="string"),
     *                 @OA\Property(property="limit", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="不正なパラメータ",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function top(Request $request, string $period, int $limit): JsonResponse
    {
        $validator = Validator::make([
            'period' => $period,
            'limit' => $limit,
        ], [
            'period' => 'required|'.RankingPeriod::getValidationRule(),
            'limit' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $cacheKey = "company_ranking_top_{$period}_{$limit}";

        return Cache::remember($cacheKey, CacheTime::RANKING, function () use ($period, $limit) {
            $rankings = $this->rankingService->getRankingForPeriod($period, $limit);

            return response()->json([
                'data' => CompanyRankingResource::collection($rankings),
                'meta' => [
                    'period' => $period,
                    'limit' => $limit,
                    'total' => count($rankings),
                ],
            ]);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/rankings/company/{company_id}",
     *     tags={"企業ランキング"},
     *     summary="特定企業のランキング取得",
     *     description="特定企業の全期間ランキング情報を取得します。",
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="企業ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="include_history",
     *         in="query",
     *         description="履歴を含める",
     *         @OA\Schema(type="boolean", default=false, example=false)
     *     ),
     *     @OA\Parameter(
     *         name="history_days",
     *         in="query",
     *         description="履歴取得日数",
     *         @OA\Schema(type="integer", default=30, example=30)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="企業ランキング情報",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="company_id", type="integer"),
     *                 @OA\Property(property="rankings", type="object"),
     *                 @OA\Property(property="history", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="不正な企業ID",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function company(Request $request, int $companyId): JsonResponse
    {
        $validator = Validator::make(['company_id' => $companyId], [
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid company ID',
                'details' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $includeHistory = $request->boolean('include_history', false);
        $historyDays = $request->get('history_days', 30);

        $cacheKey = "company_ranking_company_{$companyId}_{$includeHistory}_{$historyDays}";

        return Cache::remember($cacheKey, CacheTime::RANKING, function () use ($companyId, $includeHistory, $historyDays) {
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
                $response['data']['history'] = [];
                foreach (RankingPeriod::getValidPeriods() as $period) {
                    $history = $this->historyService->getCompanyRankingHistory($companyId, $period, $historyDays);
                    if (! empty($history)) {
                        $response['data']['history'][$period] = $history;
                    }
                }
            }

            return response()->json($response);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/rankings/statistics",
     *     tags={"企業ランキング"},
     *     summary="ランキング統計情報取得",
     *     description="全期間のランキング統計情報を取得します。",
     *     @OA\Response(
     *         response=200,
     *         description="統計情報",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="1m", type="object",
     *                     @OA\Property(property="total_companies", type="integer", example=100),
     *                     @OA\Property(property="average_score", type="number", format="float", example=50.25),
     *                     @OA\Property(property="max_score", type="number", format="float", example=150.75),
     *                     @OA\Property(property="min_score", type="number", format="float", example=5.10),
     *                     @OA\Property(property="total_articles", type="integer", example=1000),
     *                     @OA\Property(property="total_bookmarks", type="integer", example=50000),
     *                     @OA\Property(property="last_calculated", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function statistics(): JsonResponse
    {
        $cacheKey = 'company_ranking_statistics';

        return Cache::remember($cacheKey, CacheTime::STATISTICS, function () {
            $statistics = $this->rankingService->getRankingStatistics();

            return response()->json([
                'data' => $statistics,
            ]);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/rankings/{period}/risers",
     *     tags={"企業ランキング"},
     *     summary="順位上昇企業取得",
     *     description="指定した期間で順位が上昇した企業を取得します。",
     *     @OA\Parameter(
     *         name="period",
     *         in="path",
     *         required=true,
     *         description="期間タイプ",
     *         @OA\Schema(type="string", enum={"1w", "1m", "3m", "6m", "1y", "3y", "all"}, example="1m")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="取得件数（最大50）",
     *         @OA\Schema(type="integer", default=10, maximum=50, example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="順位上昇企業リスト",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="company_name", type="string", example="Rising Company"),
     *                 @OA\Property(property="domain", type="string", example="rising.com"),
     *                 @OA\Property(property="current_rank", type="integer", example=5),
     *                 @OA\Property(property="previous_rank", type="integer", example=10),
     *                 @OA\Property(property="rank_change", type="integer", example=5),
     *                 @OA\Property(property="calculated_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="period", type="string"),
     *                 @OA\Property(property="limit", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="不正な期間タイプ",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function risers(Request $request, string $period): JsonResponse
    {
        $validator = Validator::make(['period' => $period], [
            'period' => 'required|'.RankingPeriod::getValidationRule(),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => RankingPeriod::getErrorMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $limit = min($request->get('limit', 10), 50);

        $cacheKey = "company_ranking_risers_{$period}_{$limit}";

        return Cache::remember($cacheKey, CacheTime::RANKING, function () use ($period, $limit) {
            $risers = $this->historyService->getTopRankingRisers($period, $limit);

            return response()->json([
                'data' => $risers,
                'meta' => [
                    'period' => $period,
                    'limit' => $limit,
                    'total' => count($risers),
                ],
            ]);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/rankings/{period}/fallers",
     *     tags={"企業ランキング"},
     *     summary="順位下降企業取得",
     *     description="指定した期間で順位が下降した企業を取得します。",
     *     @OA\Parameter(
     *         name="period",
     *         in="path",
     *         required=true,
     *         description="期間タイプ",
     *         @OA\Schema(type="string", enum={"1w", "1m", "3m", "6m", "1y", "3y", "all"}, example="1m")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="取得件数（最大50）",
     *         @OA\Schema(type="integer", default=10, maximum=50, example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="順位下降企業リスト",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="company_name", type="string", example="Falling Company"),
     *                 @OA\Property(property="domain", type="string", example="falling.com"),
     *                 @OA\Property(property="current_rank", type="integer", example=15),
     *                 @OA\Property(property="previous_rank", type="integer", example=8),
     *                 @OA\Property(property="rank_change", type="integer", example=-7),
     *                 @OA\Property(property="calculated_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="period", type="string"),
     *                 @OA\Property(property="limit", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="不正な期間タイプ",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function fallers(Request $request, string $period): JsonResponse
    {
        $validator = Validator::make(['period' => $period], [
            'period' => 'required|'.RankingPeriod::getValidationRule(),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => RankingPeriod::getErrorMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $limit = min($request->get('limit', 10), 50);

        $cacheKey = "company_ranking_fallers_{$period}_{$limit}";

        return Cache::remember($cacheKey, CacheTime::RANKING, function () use ($period, $limit) {
            $fallers = $this->historyService->getTopRankingFallers($period, $limit);

            return response()->json([
                'data' => $fallers,
                'meta' => [
                    'period' => $period,
                    'limit' => $limit,
                    'total' => count($fallers),
                ],
            ]);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/rankings/{period}/statistics",
     *     tags={"企業ランキング"},
     *     summary="順位変動統計取得",
     *     description="指定した期間の順位変動統計情報を取得します。",
     *     @OA\Parameter(
     *         name="period",
     *         in="path",
     *         required=true,
     *         description="期間タイプ",
     *         @OA\Schema(type="string", enum={"1w", "1m", "3m", "6m", "1y", "3y", "all"}, example="1m")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="順位変動統計",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_companies", type="integer", example=100),
     *                 @OA\Property(property="rising_companies", type="integer", example=35),
     *                 @OA\Property(property="falling_companies", type="integer", example=40),
     *                 @OA\Property(property="unchanged_companies", type="integer", example=25),
     *                 @OA\Property(property="average_change", type="number", format="float", example=0.8),
     *                 @OA\Property(property="max_rise", type="integer", example=12),
     *                 @OA\Property(property="max_fall", type="integer", example=8),
     *                 @OA\Property(property="calculated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="不正な期間タイプ",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function changeStatistics(Request $request, string $period): JsonResponse
    {
        $validator = Validator::make(['period' => $period], [
            'period' => 'required|'.RankingPeriod::getValidationRule(),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => RankingPeriod::getErrorMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $cacheKey = "company_ranking_change_stats_{$period}";

        return Cache::remember($cacheKey, CacheTime::RANKING, function () use ($period) {
            $stats = $this->historyService->getRankingChangeStatistics($period);

            return response()->json([
                'data' => $stats,
            ]);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/rankings/periods",
     *     tags={"企業ランキング"},
     *     summary="期間タイプ一覧取得",
     *     description="使用可能な期間タイプの一覧を取得します。",
     *     @OA\Response(
     *         response=200,
     *         description="期間タイプ一覧",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 type="string", 
     *                 enum={"1w", "1m", "3m", "6m", "1y", "3y", "all"}
     *             ), example={"1w", "1m", "3m", "6m", "1y", "3y", "all"})
     *         )
     *     )
     * )
     */
    public function periods(): JsonResponse
    {
        return response()->json([
            'data' => RankingPeriod::getValidPeriods(),
        ]);
    }
}
