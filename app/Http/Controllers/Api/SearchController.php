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
     * 企業名検索
     */
    public function searchCompanies(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:1|max:255',
            'limit' => 'integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => '検索クエリが無効です',
                'details' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $query = $request->get('q');
        $limit = $request->get('limit', 20);

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
                    $q->latest('calculated_at')->limit(1);
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
     * 記事検索
     */
    public function searchArticles(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:1|max:255',
            'limit' => 'integer|min:1|max:100',
            'days' => 'integer|min:1|max:365',
            'min_bookmarks' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => '検索クエリが無効です',
                'details' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $query = $request->get('q');
        $limit = $request->get('limit', 20);
        $days = $request->get('days', 30);
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
     * 統合検索
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:1|max:255',
            'type' => 'string|in:companies,articles,all',
            'limit' => 'integer|min:1|max:100',
            'days' => 'integer|min:1|max:365',
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
        $limit = $request->get('limit', 20);
        $days = $request->get('days', 30);
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
                        $q->latest('calculated_at')->limit(1);
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
            $score += 1.0;
        }
        // 企業名の部分一致
        elseif (strpos(strtolower($company->name), $queryLower) !== false) {
            $score += 0.8;
        }

        // ドメインでの一致
        if (strpos(strtolower($company->domain ?? ''), $queryLower) !== false) {
            $score += 0.6;
        }

        // 説明文での一致
        if (strpos(strtolower($company->description ?? ''), $queryLower) !== false) {
            $score += 0.4;
        }

        // 最新ランキングがある場合はスコアを上げる
        if ($company->rankings && $company->rankings->isNotEmpty()) {
            $score += 0.2;
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
            $score += 1.0;
        }

        // 著者名での一致
        if (strpos(strtolower($article->author_name ?? ''), $queryLower) !== false) {
            $score += 0.5;
        }

        // ブックマーク数によるスコア調整
        if ($article->bookmark_count > 100) {
            $score += 0.3;
        } elseif ($article->bookmark_count > 50) {
            $score += 0.2;
        } elseif ($article->bookmark_count > 10) {
            $score += 0.1;
        }

        // 新しい記事ほど高スコア
        $daysAgo = abs(now()->diffInDays($article->published_at));
        if ($daysAgo <= 7) {
            $score += 0.2;
        } elseif ($daysAgo <= 30) {
            $score += 0.1;
        } elseif ($daysAgo > 100) {
            // 100日以上古い記事にはペナルティ
            $score -= 0.1;
        }

        return $score;
    }
}
