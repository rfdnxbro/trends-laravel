<?php

namespace App\Http\Controllers\Api;

use App\Constants\CacheTime;
use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyArticleResource;
use App\Http\Resources\CompanyResource;
use App\Models\Article;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/search/companies",
     *     tags={"検索"},
     *     summary="企業検索",
     *     description="企業名・ドメイン・説明文から企業を検索します。",
     *
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         description="検索クエリ（1-255文字）",
     *
     *         @OA\Schema(type="string", minLength=1, maxLength=255, example="Google")
     *     ),
     *
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="取得件数（最大100）",
     *
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=20, example=20)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="検索結果",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="companies", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="株式会社サンプル"),
     *                     @OA\Property(property="domain", type="string", example="sample.com"),
     *                     @OA\Property(property="description", type="string", example="サンプル企業の説明"),
     *                     @OA\Property(property="logo_url", type="string", example="https://example.com/logo.png"),
     *                     @OA\Property(property="website_url", type="string", example="https://sample.com"),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="match_score", type="number", format="float", example=0.95),
     *                     @OA\Property(property="current_rankings", type="array", @OA\Items(type="object"))
     *                 ))
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total_results", type="integer", example=25),
     *                 @OA\Property(property="search_time", type="number", format="float", example=0.123),
     *                 @OA\Property(property="query", type="string", example="サンプル")
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
     *             @OA\Property(property="error", type="string", example="検索クエリが無効です"),
     *             @OA\Property(property="details", type="object")
     *         )
     *     )
     * )
     */
    public function searchCompanies(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:1|max:'.config('constants.search.max_query_length'),
            'limit' => 'integer|min:1|max:'.config('constants.pagination.max_per_page'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => '検索クエリが無効です',
                'details' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $query = $request->get('q');
        $limit = $request->get('limit', config('constants.pagination.default_per_page'));

        $cacheKey = 'search_companies_'.md5($query.$limit);

        $result = Cache::remember($cacheKey, CacheTime::DEFAULT, function () use ($query, $limit) {
            $startTime = microtime(true);

            $companies = Company::active()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('domain', 'LIKE', "%{$query}%")
                        ->orWhere('description', 'LIKE', "%{$query}%");
                })
                ->with(['rankings' => function ($q) {
                    $q->latest('calculated_at')->limit(config('constants.search.min_ranking_display'));
                }])
                ->limit($limit)
                ->get()
                ->map(function ($company) use ($query) {
                    // 関連度スコアを計算
                    $score = $this->calculateRelevanceScore($company, $query);
                    $company->match_score = $score;

                    return $company;
                })
                ->sortByDesc('match_score')
                ->values();

            $searchTime = microtime(true) - $startTime;

            return [
                'companies' => $companies,
                'search_time' => round($searchTime, 3),
                'total_results' => $companies->count(),
            ];
        });

        return response()->json([
            'data' => [
                'companies' => CompanyResource::collection($result['companies']),
            ],
            'meta' => [
                'total_results' => $result['total_results'],
                'search_time' => $result['search_time'],
                'query' => $query,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/search/articles",
     *     tags={"検索"},
     *     summary="記事検索",
     *     description="記事タイトル・著者名から記事を検索します。",
     *
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         description="検索クエリ（1-255文字）",
     *
     *         @OA\Schema(type="string", minLength=1, maxLength=255, example="Laravel")
     *     ),
     *
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="取得件数（最大100）",
     *
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=20, example=20)
     *     ),
     *
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="検索対象期間（日数）",
     *
     *         @OA\Schema(type="integer", minimum=1, maximum=365, default=30, example=30)
     *     ),
     *
     *     @OA\Parameter(
     *         name="min_bookmarks",
     *         in="query",
     *         description="最小ブックマーク数",
     *
     *         @OA\Schema(type="integer", minimum=0, default=0, example=0)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="検索結果",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="articles", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Laravel入門ガイド"),
     *                     @OA\Property(property="url", type="string", example="https://qiita.com/sample/items/12345"),
     *                     @OA\Property(property="domain", type="string", example="qiita.com"),
     *                     @OA\Property(property="platform", type="string", example="Qiita"),
     *                     @OA\Property(property="author_name", type="string", example="sample_author"),
     *                     @OA\Property(property="author_url", type="string", example="https://qiita.com/sample_author"),
     *                     @OA\Property(property="published_at", type="string", format="date-time"),
     *                     @OA\Property(property="bookmark_count", type="integer", example=125),
     *                     @OA\Property(property="likes_count", type="integer", example=45),
     *                     @OA\Property(property="match_score", type="number", format="float", example=0.92),
     *                     @OA\Property(property="company", type="object"),
     *                     @OA\Property(property="platform_details", type="object")
     *                 ))
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total_results", type="integer", example=15),
     *                 @OA\Property(property="search_time", type="number", format="float", example=0.089),
     *                 @OA\Property(property="query", type="string", example="Laravel"),
     *                 @OA\Property(property="filters", type="object",
     *                     @OA\Property(property="days", type="integer", example=30),
     *                     @OA\Property(property="min_bookmarks", type="integer", example=0)
     *                 )
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
     *             @OA\Property(property="error", type="string", example="検索クエリが無効です")
     *         )
     *     )
     * )
     */
    public function searchArticles(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:1|max:'.config('constants.search.max_query_length'),
            'limit' => 'integer|min:1|max:'.config('constants.pagination.max_per_page'),
            'days' => 'integer|min:1|max:'.config('constants.api.max_article_days'),
            'min_bookmarks' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => '検索クエリが無効です',
                'details' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $query = $request->get('q');
        $limit = $request->get('limit', config('constants.pagination.default_per_page'));
        $days = $request->get('days', config('constants.api.default_article_days'));
        $minBookmarks = $request->get('min_bookmarks', 0);

        $cacheKey = 'search_articles_'.md5($query.$limit.$days.$minBookmarks);

        $result = Cache::remember($cacheKey, CacheTime::DEFAULT, function () use ($query, $limit, $days, $minBookmarks) {
            $startTime = microtime(true);

            $articles = Article::with(['company', 'platform'])
                ->recent($days)
                ->where('bookmark_count', '>=', $minBookmarks)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                        ->orWhere('author_name', 'LIKE', "%{$query}%");
                })
                ->orderBy('published_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($article) use ($query) {
                    // 関連度スコアを計算
                    $score = $this->calculateArticleRelevanceScore($article, $query);
                    $article->match_score = $score;

                    return $article;
                })
                ->sortByDesc('match_score')
                ->values();

            $searchTime = microtime(true) - $startTime;

            return [
                'articles' => $articles,
                'search_time' => round($searchTime, 3),
                'total_results' => $articles->count(),
            ];
        });

        return response()->json([
            'data' => [
                'articles' => CompanyArticleResource::collection($result['articles']),
            ],
            'meta' => [
                'total_results' => $result['total_results'],
                'search_time' => $result['search_time'],
                'query' => $query,
                'filters' => [
                    'days' => $days,
                    'min_bookmarks' => $minBookmarks,
                ],
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/search",
     *     tags={"検索"},
     *     summary="統合検索",
     *     description="企業・記事を横断的に検索します。",
     *
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         description="検索クエリ（1-255文字）",
     *
     *         @OA\Schema(type="string", minLength=1, maxLength=255, example="Laravel")
     *     ),
     *
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="検索タイプ",
     *
     *         @OA\Schema(type="string", enum={"companies", "articles", "all"}, default="all", example="all")
     *     ),
     *
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="取得件数（最大100）",
     *
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=20, example=20)
     *     ),
     *
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="記事検索の対象期間（日数）",
     *
     *         @OA\Schema(type="integer", minimum=1, maximum=365, default=30, example=30)
     *     ),
     *
     *     @OA\Parameter(
     *         name="min_bookmarks",
     *         in="query",
     *         description="記事検索の最小ブックマーク数",
     *
     *         @OA\Schema(type="integer", minimum=0, default=0, example=0)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="検索結果",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="companies", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="articles", type="array", @OA\Items(type="object"))
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total_results", type="integer", example=40),
     *                 @OA\Property(property="search_time", type="number", format="float", example=0.156),
     *                 @OA\Property(property="query", type="string", example="Laravel"),
     *                 @OA\Property(property="type", type="string", example="all"),
     *                 @OA\Property(property="filters", type="object",
     *                     @OA\Property(property="days", type="integer", example=30),
     *                     @OA\Property(property="min_bookmarks", type="integer", example=0)
     *                 )
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
     *             @OA\Property(property="error", type="string", example="検索クエリが無効です")
     *         )
     *     )
     * )
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:1|max:'.config('constants.search.max_query_length'),
            'type' => 'string|in:companies,articles,all',
            'limit' => 'integer|min:1|max:'.config('constants.pagination.max_per_page'),
            'days' => 'integer|min:1|max:'.config('constants.api.max_article_days'),
            'min_bookmarks' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => '検索クエリが無効です',
                'details' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $query = $request->get('q');
        $type = $request->get('type', 'all');
        $limit = $request->get('limit', config('constants.pagination.default_per_page'));
        $days = $request->get('days', config('constants.api.default_article_days'));
        $minBookmarks = $request->get('min_bookmarks', 0);

        $cacheKey = 'search_all_'.md5($query.$type.$limit.$days.$minBookmarks);

        $result = Cache::remember($cacheKey, CacheTime::DEFAULT, function () use ($query, $type, $limit, $days, $minBookmarks) {
            $startTime = microtime(true);
            $data = [];

            if ($type === 'companies' || $type === 'all') {
                $companies = Company::active()
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'LIKE', "%{$query}%")
                            ->orWhere('domain', 'LIKE', "%{$query}%")
                            ->orWhere('description', 'LIKE', "%{$query}%");
                    })
                    ->with(['rankings' => function ($q) {
                        $q->latest('calculated_at')->limit(config('constants.search.min_ranking_display'));
                    }])
                    ->limit($limit)
                    ->get()
                    ->map(function ($company) use ($query) {
                        $score = $this->calculateRelevanceScore($company, $query);
                        $company->match_score = $score;

                        return $company;
                    })
                    ->sortByDesc('match_score')
                    ->values();

                $data['companies'] = CompanyResource::collection($companies);
            }

            if ($type === 'articles' || $type === 'all') {
                $articles = Article::with(['company', 'platform'])
                    ->recent($days)
                    ->where('bookmark_count', '>=', $minBookmarks)
                    ->where(function ($q) use ($query) {
                        $q->where('title', 'LIKE', "%{$query}%")
                            ->orWhere('author_name', 'LIKE', "%{$query}%");
                    })
                    ->orderBy('published_at', 'desc')
                    ->limit($limit)
                    ->get()
                    ->map(function ($article) use ($query) {
                        $score = $this->calculateArticleRelevanceScore($article, $query);
                        $article->match_score = $score;

                        return $article;
                    })
                    ->sortByDesc('match_score')
                    ->values();

                $data['articles'] = CompanyArticleResource::collection($articles);
            }

            $searchTime = microtime(true) - $startTime;
            $totalResults = ($data['companies'] ?? collect())->count() + ($data['articles'] ?? collect())->count();

            return [
                'data' => $data,
                'search_time' => round($searchTime, 3),
                'total_results' => $totalResults,
            ];
        });

        return response()->json([
            'data' => $result['data'],
            'meta' => [
                'total_results' => $result['total_results'],
                'search_time' => $result['search_time'],
                'query' => $query,
                'type' => $type,
                'filters' => [
                    'days' => $days,
                    'min_bookmarks' => $minBookmarks,
                ],
            ],
        ]);
    }

    /**
     * 企業の関連度スコア計算
     */
    private function calculateRelevanceScore(Company $company, string $query): float
    {
        $score = 0.0;
        $queryLower = strtolower($query);

        // 企業名での完全一致
        if (strtolower($company->name) === $queryLower) {
            $score += config('constants.scoring.company.exact_match_weight');
        }
        // 企業名の部分一致
        elseif (strpos(strtolower($company->name), $queryLower) !== false) {
            $score += config('constants.scoring.company.partial_match_weight');
        }

        // ドメインでの一致
        if (strpos(strtolower($company->domain ?? ''), $queryLower) !== false) {
            $score += config('constants.scoring.company.domain_match_weight');
        }

        // 説明文での一致
        if (strpos(strtolower($company->description ?? ''), $queryLower) !== false) {
            $score += config('constants.scoring.company.description_match_weight');
        }

        // 最新ランキングがある場合はスコアを上げる
        if ($company->rankings && $company->rankings->isNotEmpty()) {
            $score += config('constants.scoring.company.ranking_bonus_weight');
        }

        return $score;
    }

    /**
     * 記事の関連度スコア計算
     */
    private function calculateArticleRelevanceScore(Article $article, string $query): float
    {
        $score = 0.0;
        $queryLower = strtolower($query);

        // タイトルでの完全一致
        if (strpos(strtolower($article->title), $queryLower) !== false) {
            $score += config('constants.scoring.article.title_match_weight');
        }

        // 著者名での一致
        if (strpos(strtolower($article->author_name ?? ''), $queryLower) !== false) {
            $score += config('constants.scoring.article.author_match_weight');
        }

        // ブックマーク数によるスコア調整
        if ($article->bookmark_count > config('constants.scoring.thresholds.high_bookmarks')) {
            $score += config('constants.scoring.article.high_bookmark_weight');
        } elseif ($article->bookmark_count > config('constants.scoring.thresholds.medium_bookmarks')) {
            $score += config('constants.scoring.article.medium_bookmark_weight');
        } elseif ($article->bookmark_count > config('constants.scoring.thresholds.low_bookmarks')) {
            $score += config('constants.scoring.article.low_bookmark_weight');
        }

        // 新しい記事ほど高スコア
        $daysAgo = abs(now()->diffInDays($article->published_at));
        if ($daysAgo <= config('constants.scoring.thresholds.recent_days')) {
            $score += config('constants.scoring.article.recent_bonus_weight');
        } elseif ($daysAgo <= config('constants.scoring.thresholds.somewhat_recent_days')) {
            $score += config('constants.scoring.article.somewhat_recent_bonus_weight');
        } elseif ($daysAgo > config('constants.scoring.thresholds.old_days')) {
            // 100日以上古い記事にはペナルティ
            $score += config('constants.scoring.article.old_penalty_weight');
        }

        return $score;
    }
}
