<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ArticleController extends Controller
{
    /**
     * 記事一覧を取得
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // キャッシュキーの生成
        $cacheKey = $this->generateCacheKey($request);
        $cacheTtl = 600; // 10分間キャッシュ

        return Cache::remember($cacheKey, $cacheTtl, function () use ($request) {
            $query = Article::with(['company', 'platform']);

            // 検索機能（タイトル、著者名）
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('author_name', 'LIKE', "%{$search}%");
                });
            }

            // 企業でフィルタリング
            if ($request->has('company_id')) {
                $query->where('company_id', $request->company_id);
            }

            // プラットフォームでフィルタリング
            if ($request->has('platform_id')) {
                $query->where('platform_id', $request->platform_id);
            }

            // 期間でフィルタリング
            if ($request->has('start_date')) {
                $query->where('published_at', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->where('published_at', '<=', $request->end_date.' 23:59:59');
            }

            // ソート機能
            $sortBy = $request->input('sort_by', 'published_at');
            $sortOrder = $request->input('sort_order', 'desc');

            // 許可されたソートカラムのみ受け付ける
            $allowedSortColumns = ['published_at', 'likes_count', 'bookmark_count'];
            if (in_array($sortBy, $allowedSortColumns)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('published_at', 'desc');
            }

            // ページネーション
            $perPage = $request->input('limit', 20);
            $articles = $query->paginate($perPage);

            return ArticleResource::collection($articles);
        });
    }

    /**
     * 記事詳細を取得
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $cacheKey = "article_detail_{$id}";
        $cacheTtl = 600; // 10分間キャッシュ

        return Cache::remember($cacheKey, $cacheTtl, function () use ($id) {
            $article = Article::with(['company', 'platform'])->find($id);

            if (! $article) {
                return response()->json(['message' => 'Article not found'], 404);
            }

            return new ArticleResource($article);
        });
    }

    /**
     * キャッシュキーを生成
     */
    private function generateCacheKey(Request $request): string
    {
        $params = [
            'page' => $request->input('page', 1),
            'per_page' => $request->input('limit', 20),
            'search' => $request->input('search', ''),
            'platform_id' => $request->input('platform_id', ''),
            'company_id' => $request->input('company_id', ''),
            'start_date' => $request->input('start_date', ''),
            'end_date' => $request->input('end_date', ''),
            'sort_by' => $request->input('sort_by', 'published_at'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];

        $keyParts = [];
        foreach ($params as $key => $value) {
            $keyParts[] = "{$key}={$value}";
        }

        return 'articles:'.implode(':', $keyParts);
    }
}
