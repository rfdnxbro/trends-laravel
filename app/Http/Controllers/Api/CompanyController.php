<?php

namespace App\Http\Controllers\Api;

use App\Constants\CacheTime;
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
use Symfony\Component\HttpFoundation\Response;

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
     * @OA\Get(
     *     path="/api/companies/{company_id}",
     *     tags={"企業詳細"},
     *     summary="企業詳細情報取得",
     *     description="企業の詳細情報を取得します。現在のランキング情報と最近の記事（最大5件）を含みます。",
     *
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="企業ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="企業詳細情報",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Company A"),
     *                 @OA\Property(property="domain", type="string", example="company-a.com"),
     *                 @OA\Property(property="description", type="string", example="企業の説明文"),
     *                 @OA\Property(property="logo_url", type="string", example="https://example.com/logo.png"),
     *                 @OA\Property(property="website_url", type="string", example="https://company-a.com"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="current_rankings", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="recent_articles", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="不正なリクエスト",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="企業IDが無効です"),
     *             @OA\Property(property="details", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="企業が見つかりません",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="企業が見つかりません")
     *         )
     *     )
     * )
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
            ], Response::HTTP_BAD_REQUEST);
        }

        $cacheKey = "company_detail_{$companyId}";

        return Cache::remember($cacheKey, CacheTime::COMPANY_DETAIL, function () use ($companyId) {
            $company = Company::with(['rankings', 'articles' => function ($query) {
                $query->recent(config('constants.api.default_article_days'))->orderBy('published_at', 'desc')->limit(config('constants.api.default_article_limit'));
            }])->find($companyId);

            if (! $company) {
                return response()->json([
                    'error' => '企業が見つかりません',
                ], Response::HTTP_NOT_FOUND);
            }

            // 現在のランキング情報を取得
            $currentRankings = $this->rankingService->getCompanyRankings($companyId);

            return response()->json([
                'data' => new CompanyResource($company, $currentRankings),
            ]);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/companies/{company_id}/articles",
     *     tags={"企業詳細"},
     *     summary="企業の記事一覧取得",
     *     description="企業に関連する記事の一覧を取得します。",
     *
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="企業ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="ページ番号",
     *
     *         @OA\Schema(type="integer", default=1, example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="1ページあたりの件数（最大100）",
     *
     *         @OA\Schema(type="integer", default=20, maximum=100, example=20)
     *     ),
     *
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="過去何日分の記事を取得するか",
     *
     *         @OA\Schema(type="integer", default=30, example=30)
     *     ),
     *
     *     @OA\Parameter(
     *         name="min_bookmarks",
     *         in="query",
     *         description="最小ブックマーク数",
     *
     *         @OA\Schema(type="integer", default=0, example=0)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="記事一覧",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="company_id", type="integer"),
     *                 @OA\Property(property="filters", type="object")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="不正なリクエスト",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="企業IDが無効です")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="企業が見つかりません",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="企業が見つかりません")
     *         )
     *     )
     * )
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
            ], Response::HTTP_BAD_REQUEST);
        }

        $page = $request->get('page', 1);
        $perPage = min($request->get('per_page', config('constants.pagination.default_per_page')), config('constants.pagination.max_per_page'));
        $days = $request->get('days', config('constants.api.default_article_days'));
        $minBookmarks = $request->get('min_bookmarks', 0);

        $cacheKey = "company_articles_{$companyId}_{$page}_{$perPage}_{$days}_{$minBookmarks}";

        return Cache::remember($cacheKey, CacheTime::DEFAULT, function () use ($companyId, $page, $perPage, $days, $minBookmarks) {
            $company = Company::find($companyId);

            if (! $company) {
                return response()->json([
                    'error' => '企業が見つかりません',
                ], Response::HTTP_NOT_FOUND);
            }

            $query = $company->articles()
                ->with('platform')
                ->where('published_at', '>=', now()->subDays($days))
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
     * @OA\Get(
     *     path="/api/companies/{company_id}/scores",
     *     tags={"企業詳細"},
     *     summary="企業の影響力スコア履歴取得",
     *     description="企業の影響力スコアの履歴データを取得します。",
     *
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="企業ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="期間タイプ",
     *
     *         @OA\Schema(type="string", default="1d", example="1d")
     *     ),
     *
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="過去何日分のスコアを取得するか",
     *
     *         @OA\Schema(type="integer", default=30, example=30)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="スコア履歴",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="company_id", type="integer", example=1),
     *                 @OA\Property(property="scores", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="date", type="string", format="date", example="2024-01-30"),
     *                     @OA\Property(property="score", type="number", format="float", example=85.5),
     *                     @OA\Property(property="rank_position", type="integer", example=5),
     *                     @OA\Property(property="calculated_at", type="string", format="date-time")
     *                 ))
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="period", type="string"),
     *                 @OA\Property(property="days", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="不正なリクエスト",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="企業IDが無効です")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="企業が見つかりません",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="企業が見つかりません")
     *         )
     *     )
     * )
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
            ], Response::HTTP_BAD_REQUEST);
        }

        $days = $request->get('days', config('constants.api.default_article_days'));
        $period = $request->get('period', '1d');

        $cacheKey = "company_scores_{$companyId}_{$days}_{$period}";

        return Cache::remember($cacheKey, CacheTime::DEFAULT, function () use ($companyId, $days, $period) {
            $company = Company::find($companyId);

            if (! $company) {
                return response()->json([
                    'error' => '企業が見つかりません',
                ], Response::HTTP_NOT_FOUND);
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
     * @OA\Get(
     *     path="/api/companies/{company_id}/rankings",
     *     tags={"企業詳細"},
     *     summary="企業のランキング情報取得",
     *     description="企業の各期間でのランキング情報を取得します。",
     *
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="企業ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="include_history",
     *         in="query",
     *         description="履歴を含める",
     *
     *         @OA\Schema(type="boolean", default=false, example=false)
     *     ),
     *
     *     @OA\Parameter(
     *         name="history_days",
     *         in="query",
     *         description="履歴取得日数",
     *
     *         @OA\Schema(type="integer", default=30, example=30)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="ランキング情報",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="company_id", type="integer", example=1),
     *                 @OA\Property(property="rankings", type="object"),
     *                 @OA\Property(property="history", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="不正なリクエスト",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="企業IDが無効です")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="企業が見つかりません",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="企業が見つかりません")
     *         )
     *     )
     * )
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
            ], Response::HTTP_BAD_REQUEST);
        }

        $includeHistory = $request->boolean('include_history', false);
        $historyDays = $request->get('history_days', config('constants.ranking.history_days'));

        $cacheKey = "company_rankings_{$companyId}_{$includeHistory}_{$historyDays}";

        return Cache::remember($cacheKey, CacheTime::DEFAULT, function () use ($companyId, $includeHistory, $historyDays) {
            $company = Company::find($companyId);

            if (! $company) {
                return response()->json([
                    'error' => '企業が見つかりません',
                ], Response::HTTP_NOT_FOUND);
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
