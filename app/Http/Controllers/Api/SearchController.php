<?php

namespace App\Http\Controllers\Api;

use App\Constants\CacheTime;
use App\Constants\ScoringConstants;
use App\Constants\SearchConstants;
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
        $validatedData = $this->validateSearchRequest($request, ['q', 'limit']);
        if ($validatedData instanceof JsonResponse) {
            return $validatedData;
        }

        $query = $validatedData['q'];
        $limit = $validatedData['limit'] ?? config('constants.pagination.default_per_page');

        $result = $this->searchCompaniesWithCache($query, $limit);

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
     *         name="min_engagement",
     *         in="query",
     *         description="最小エンゲージメント数",
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
     *                     @OA\Property(property="engagement_count", type="integer", example=170),
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
     *                     @OA\Property(property="min_engagement", type="integer", example=0)
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
        $validatedData = $this->validateSearchRequest($request, ['q', 'limit', 'days', 'min_engagement']);
        if ($validatedData instanceof JsonResponse) {
            return $validatedData;
        }

        $query = $validatedData['q'];
        $limit = $validatedData['limit'] ?? config('constants.pagination.default_per_page');
        $days = $validatedData['days'] ?? config('constants.api.default_article_days');
        $minEngagement = $validatedData['min_engagement'] ?? 0;

        $result = $this->searchArticlesWithCache($query, $limit, $days, $minEngagement);

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
                    'min_engagement' => $minEngagement,
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
     *         name="min_engagement",
     *         in="query",
     *         description="記事検索の最小エンゲージメント数",
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
     *                     @OA\Property(property="min_engagement", type="integer", example=0)
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
        $validatedData = $this->validateSearchRequest($request, ['q', 'type', 'limit', 'days', 'min_engagement']);
        if ($validatedData instanceof JsonResponse) {
            return $validatedData;
        }

        $query = $validatedData['q'];
        $type = $validatedData['type'] ?? 'all';
        $limit = $validatedData['limit'] ?? config('constants.pagination.default_per_page');
        $days = $validatedData['days'] ?? config('constants.api.default_article_days');
        $minEngagement = $validatedData['min_engagement'] ?? 0;

        $result = $this->searchAllWithCache($query, $type, $limit, $days, $minEngagement);

        return response()->json([
            'data' => $result['data'],
            'meta' => [
                'total_results' => $result['total_results'],
                'search_time' => $result['search_time'],
                'query' => $query,
                'type' => $type,
                'filters' => [
                    'days' => $days,
                    'min_engagement' => $minEngagement,
                ],
            ],
        ]);
    }

    /**
     * 検索リクエストのバリデーション処理を共通化
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  array  $fields  バリデーション対象フィールド配列
     * @return array|JsonResponse バリデーション済みデータまたはエラーレスポンス
     */
    private function validateSearchRequest(Request $request, array $fields): array|JsonResponse
    {
        $rules = [];

        if (in_array('q', $fields)) {
            $rules['q'] = SearchConstants::getQueryValidationRule();
        }
        if (in_array('type', $fields)) {
            $rules['type'] = 'string|in:companies,articles,all';
        }
        if (in_array('limit', $fields)) {
            $rules['limit'] = 'integer|min:1|max:'.config('constants.pagination.max_per_page');
        }
        if (in_array('days', $fields)) {
            $rules['days'] = 'integer|min:1|max:'.config('constants.api.max_article_days');
        }
        if (in_array('min_engagement', $fields)) {
            $rules['min_engagement'] = 'integer|min:0';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => '検索クエリが無効です',
                'details' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return $validator->validated();
    }

    /**
     * 企業検索のキャッシュ処理
     *
     * @param  string  $query  検索クエリ
     * @param  int  $limit  取得件数
     * @return array 検索結果配列（companies, search_time, total_results）
     */
    private function searchCompaniesWithCache(string $query, int $limit): array
    {
        $cacheKey = 'search_companies_'.md5($query.$limit);

        return Cache::remember($cacheKey, CacheTime::DEFAULT, function () use ($query, $limit) {
            return $this->executeCompanySearch($query, $limit);
        });
    }

    /**
     * 記事検索のキャッシュ処理
     *
     * @param  string  $query  検索クエリ
     * @param  int  $limit  取得件数
     * @param  int  $days  検索対象期間（日数）
     * @param  int  $minEngagement  最小エンゲージメント数
     * @return array 検索結果配列（articles, search_time, total_results）
     */
    private function searchArticlesWithCache(string $query, int $limit, int $days, int $minEngagement): array
    {
        $cacheKey = 'search_articles_'.md5($query.$limit.$days.$minEngagement);

        return Cache::remember($cacheKey, CacheTime::DEFAULT, function () use ($query, $limit, $days, $minEngagement) {
            return $this->executeArticleSearch($query, $limit, $days, $minEngagement);
        });
    }

    /**
     * 統合検索のキャッシュ処理
     *
     * @param  string  $query  検索クエリ
     * @param  string  $type  検索タイプ（companies|articles|all）
     * @param  int  $limit  取得件数
     * @param  int  $days  記事検索の対象期間（日数）
     * @param  int  $minEngagement  記事検索の最小エンゲージメント数
     * @return array 検索結果配列（data, search_time, total_results）
     */
    private function searchAllWithCache(string $query, string $type, int $limit, int $days, int $minEngagement): array
    {
        $cacheKey = 'search_all_'.md5($query.$type.$limit.$days.$minEngagement);

        return Cache::remember($cacheKey, CacheTime::DEFAULT, function () use ($query, $type, $limit, $days, $minEngagement) {
            return $this->executeUnifiedSearch($query, $type, $limit, $days, $minEngagement);
        });
    }

    /**
     * 企業検索の実行
     *
     * @param  string  $query  検索クエリ
     * @param  int  $limit  取得件数
     * @return array 検索結果配列（companies, search_time, total_results）
     */
    private function executeCompanySearch(string $query, int $limit): array
    {
        $startTime = microtime(true);

        $companies = $this->buildCompanyQuery($query, $limit)
            ->get()
            ->map(function ($company) use ($query) {
                /** @var \App\Models\Company $company */
                $company->match_score = $this->calculateRelevanceScore($company, $query);

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
    }

    /**
     * 記事検索の実行
     *
     * @param  string  $query  検索クエリ
     * @param  int  $limit  取得件数
     * @param  int  $days  検索対象期間（日数）
     * @param  int  $minEngagement  最小エンゲージメント数
     * @return array 検索結果配列（articles, search_time, total_results）
     */
    private function executeArticleSearch(string $query, int $limit, int $days, int $minEngagement): array
    {
        $startTime = microtime(true);

        $articles = $this->buildArticleQuery($query, $limit, $days, $minEngagement)
            ->get()
            ->map(function ($article) use ($query) {
                /** @var \App\Models\Article $article */
                $article->match_score = $this->calculateArticleRelevanceScore($article, $query);

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
    }

    /**
     * 統合検索の実行
     *
     * @param  string  $query  検索クエリ
     * @param  string  $type  検索タイプ（companies|articles|all）
     * @param  int  $limit  取得件数
     * @param  int  $days  記事検索の対象期間（日数）
     * @param  int  $minEngagement  記事検索の最小エンゲージメント数
     * @return array 検索結果配列（data, search_time, total_results）
     */
    private function executeUnifiedSearch(string $query, string $type, int $limit, int $days, int $minEngagement): array
    {
        $startTime = microtime(true);
        $data = [];

        if ($type === 'companies' || $type === 'all') {
            $companies = $this->buildCompanyQuery($query, $limit)
                ->get()
                ->map(function ($company) use ($query) {
                    /** @var \App\Models\Company $company */
                    $company->match_score = $this->calculateRelevanceScore($company, $query);

                    return $company;
                })
                ->sortByDesc('match_score')
                ->values();

            $data['companies'] = CompanyResource::collection($companies);
        }

        if ($type === 'articles' || $type === 'all') {
            $articles = $this->buildArticleQuery($query, $limit, $days, $minEngagement)
                ->get()
                ->map(function ($article) use ($query) {
                    /** @var \App\Models\Article $article */
                    $article->match_score = $this->calculateArticleRelevanceScore($article, $query);

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
    }

    /**
     * 企業検索クエリの構築
     *
     * @param  string  $query  検索クエリ
     * @param  int  $limit  取得件数
     * @return \Illuminate\Database\Eloquent\Builder 企業検索用クエリビルダー
     */
    private function buildCompanyQuery(string $query, int $limit)
    {
        return Company::active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('domain', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->with(['rankings' => function ($q) {
                $q->latest('calculated_at')->limit(SearchConstants::MIN_RANKING_DISPLAY);
            }])
            ->limit($limit);
    }

    /**
     * 記事検索クエリの構築
     *
     * @param  string  $query  検索クエリ
     * @param  int  $limit  取得件数
     * @param  int  $days  検索対象期間（日数）
     * @param  int  $minEngagement  最小エンゲージメント数
     * @return \Illuminate\Database\Eloquent\Builder 記事検索用クエリビルダー
     */
    private function buildArticleQuery(string $query, int $limit, int $days, int $minEngagement)
    {
        return Article::with(['company', 'platform'])
            ->recent($days)
            ->where('engagement_count', '>=', $minEngagement)
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('author_name', 'LIKE', "%{$query}%");
            })
            ->orderBy('published_at', 'desc')
            ->limit($limit);
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
            $score += ScoringConstants::COMPANY_EXACT_MATCH_WEIGHT;
        }
        // 企業名の部分一致
        elseif (strpos(strtolower($company->name), $queryLower) !== false) {
            $score += ScoringConstants::COMPANY_PARTIAL_MATCH_WEIGHT;
        }

        // ドメインでの一致
        if (strpos(strtolower($company->domain ?? ''), $queryLower) !== false) {
            $score += ScoringConstants::COMPANY_DOMAIN_MATCH_WEIGHT;
        }

        // 説明文での一致
        if (strpos(strtolower($company->description ?? ''), $queryLower) !== false) {
            $score += ScoringConstants::COMPANY_DESCRIPTION_MATCH_WEIGHT;
        }

        // 最新ランキングがある場合はスコアを上げる
        if ($company->rankings && $company->rankings->isNotEmpty()) {
            $score += ScoringConstants::COMPANY_RANKING_BONUS_WEIGHT;
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
            $score += ScoringConstants::ARTICLE_TITLE_MATCH_WEIGHT;
        }

        // 著者名での一致
        if (strpos(strtolower($article->author_name ?? ''), $queryLower) !== false) {
            $score += ScoringConstants::ARTICLE_AUTHOR_MATCH_WEIGHT;
        }

        // エンゲージメント数によるスコア調整
        if ($article->engagement_count > ScoringConstants::HIGH_BOOKMARKS_THRESHOLD) {
            $score += ScoringConstants::ARTICLE_HIGH_BOOKMARK_WEIGHT;
        } elseif ($article->engagement_count > ScoringConstants::MEDIUM_BOOKMARKS_THRESHOLD) {
            $score += ScoringConstants::ARTICLE_MEDIUM_BOOKMARK_WEIGHT;
        } elseif ($article->engagement_count > ScoringConstants::LOW_BOOKMARKS_THRESHOLD) {
            $score += ScoringConstants::ARTICLE_LOW_BOOKMARK_WEIGHT;
        }

        // 新しい記事ほど高スコア
        $daysAgo = abs(now()->diffInDays($article->published_at));
        if ($daysAgo <= ScoringConstants::RECENT_DAYS_THRESHOLD) {
            $score += ScoringConstants::ARTICLE_RECENT_BONUS_WEIGHT;
        } elseif ($daysAgo <= ScoringConstants::SOMEWHAT_RECENT_DAYS_THRESHOLD) {
            $score += ScoringConstants::ARTICLE_SOMEWHAT_RECENT_BONUS_WEIGHT;
        } elseif ($daysAgo > ScoringConstants::OLD_DAYS_THRESHOLD) {
            // 100日以上古い記事にはペナルティ
            $score += ScoringConstants::ARTICLE_OLD_PENALTY_WEIGHT;
        }

        return $score;
    }
}
