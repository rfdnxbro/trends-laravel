<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CompanyRankingService;
use App\Services\CompanyRankingHistoryService;
use App\Http\Resources\CompanyRankingResource;
use App\Http\Resources\CompanyRankingCollection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class CompanyRankingController extends Controller
{
    private const PERIOD_TYPES = [
        '1w' => 7,
        '1m' => 30,
        '3m' => 90,
        '6m' => 180,
        '1y' => 365,
        '3y' => 1095,
        'all' => null,
    ];

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
     * 期間別ランキング取得
     */
    public function getRankingByPeriod(Request $request, string $period): JsonResponse
    {
        $validator = Validator::make(['period' => $period], [
            'period' => 'required|in:' . implode(',', array_keys(self::PERIOD_TYPES)),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid period. Must be one of: ' . implode(', ', array_keys(self::PERIOD_TYPES))
            ], 400);
        }

        $page = $request->get('page', 1);
        $perPage = min($request->get('per_page', 20), 100);
        $sortBy = $request->get('sort_by', 'rank_position');
        $sortOrder = $request->get('sort_order', 'asc');

        $cacheKey = "company_ranking_{$period}_{$page}_{$perPage}_{$sortBy}_{$sortOrder}";
        $cacheTime = 300; // 5分

        return Cache::remember($cacheKey, $cacheTime, function () use ($period, $page, $perPage, $sortBy, $sortOrder) {
            $rankings = $this->rankingService->getRankingForPeriod($period, $perPage * 10);
            
            if (empty($rankings)) {
                return response()->json([
                    'data' => [],
                    'meta' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => 0,
                        'last_page' => 1,
                    ]
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
                ]
            ]);
        });
    }

    /**
     * 上位N件のランキング取得
     */
    public function getTopRanking(Request $request, string $period, int $limit): JsonResponse
    {
        $validator = Validator::make([
            'period' => $period,
            'limit' => $limit,
        ], [
            'period' => 'required|in:' . implode(',', array_keys(self::PERIOD_TYPES)),
            'limit' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors(),
            ], 400);
        }

        $cacheKey = "company_ranking_top_{$period}_{$limit}";
        $cacheTime = 300; // 5分

        return Cache::remember($cacheKey, $cacheTime, function () use ($period, $limit) {
            $rankings = $this->rankingService->getRankingForPeriod($period, $limit);
            
            return response()->json([
                'data' => CompanyRankingResource::collection($rankings),
                'meta' => [
                    'period' => $period,
                    'limit' => $limit,
                    'total' => count($rankings),
                ]
            ]);
        });
    }

    /**
     * 特定企業のランキング取得
     */
    public function getCompanyRanking(Request $request, int $companyId): JsonResponse
    {
        $validator = Validator::make(['company_id' => $companyId], [
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid company ID',
                'details' => $validator->errors(),
            ], 400);
        }

        $includeHistory = $request->boolean('include_history', false);
        $historyDays = $request->get('history_days', 30);

        $cacheKey = "company_ranking_company_{$companyId}_{$includeHistory}_{$historyDays}";
        $cacheTime = 300; // 5分

        return Cache::remember($cacheKey, $cacheTime, function () use ($companyId, $includeHistory, $historyDays) {
            $rankings = $this->rankingService->getCompanyRankings($companyId);
            
            $response = [
                'data' => [
                    'company_id' => $companyId,
                    'rankings' => []
                ]
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
                foreach (array_keys(self::PERIOD_TYPES) as $period) {
                    $history = $this->historyService->getCompanyRankingHistory($companyId, $period, $historyDays);
                    if (!empty($history)) {
                        $response['data']['history'][$period] = $history;
                    }
                }
            }

            return response()->json($response);
        });
    }

    /**
     * ランキング統計情報取得
     */
    public function getStatistics(): JsonResponse
    {
        $cacheKey = "company_ranking_statistics";
        $cacheTime = 600; // 10分

        return Cache::remember($cacheKey, $cacheTime, function () {
            $statistics = $this->rankingService->getRankingStatistics();
            
            return response()->json([
                'data' => $statistics
            ]);
        });
    }

    /**
     * 順位変動上位企業取得
     */
    public function getRankingRisers(Request $request, string $period): JsonResponse
    {
        $validator = Validator::make(['period' => $period], [
            'period' => 'required|in:' . implode(',', array_keys(self::PERIOD_TYPES)),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid period. Must be one of: ' . implode(', ', array_keys(self::PERIOD_TYPES))
            ], 400);
        }

        $limit = min($request->get('limit', 10), 50);

        $cacheKey = "company_ranking_risers_{$period}_{$limit}";
        $cacheTime = 300; // 5分

        return Cache::remember($cacheKey, $cacheTime, function () use ($period, $limit) {
            $risers = $this->historyService->getTopRankingRisers($period, $limit);
            
            return response()->json([
                'data' => $risers,
                'meta' => [
                    'period' => $period,
                    'limit' => $limit,
                    'total' => count($risers),
                ]
            ]);
        });
    }

    /**
     * 順位変動下位企業取得
     */
    public function getRankingFallers(Request $request, string $period): JsonResponse
    {
        $validator = Validator::make(['period' => $period], [
            'period' => 'required|in:' . implode(',', array_keys(self::PERIOD_TYPES)),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid period. Must be one of: ' . implode(', ', array_keys(self::PERIOD_TYPES))
            ], 400);
        }

        $limit = min($request->get('limit', 10), 50);

        $cacheKey = "company_ranking_fallers_{$period}_{$limit}";
        $cacheTime = 300; // 5分

        return Cache::remember($cacheKey, $cacheTime, function () use ($period, $limit) {
            $fallers = $this->historyService->getTopRankingFallers($period, $limit);
            
            return response()->json([
                'data' => $fallers,
                'meta' => [
                    'period' => $period,
                    'limit' => $limit,
                    'total' => count($fallers),
                ]
            ]);
        });
    }

    /**
     * 順位変動統計取得
     */
    public function getRankingChangeStatistics(Request $request, string $period): JsonResponse
    {
        $validator = Validator::make(['period' => $period], [
            'period' => 'required|in:' . implode(',', array_keys(self::PERIOD_TYPES)),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid period. Must be one of: ' . implode(', ', array_keys(self::PERIOD_TYPES))
            ], 400);
        }

        $cacheKey = "company_ranking_change_stats_{$period}";
        $cacheTime = 300; // 5分

        return Cache::remember($cacheKey, $cacheTime, function () use ($period) {
            $stats = $this->historyService->getRankingChangeStatistics($period);
            
            return response()->json([
                'data' => $stats
            ]);
        });
    }

    /**
     * 使用可能な期間タイプ一覧取得
     */
    public function getPeriodTypes(): JsonResponse
    {
        return response()->json([
            'data' => array_keys(self::PERIOD_TYPES)
        ]);
    }
}