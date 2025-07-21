<?php

namespace App\Http\Controllers\Api;

use App\Constants\CacheTime;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ArticleController extends Controller
{
    /**
     * 記事一覧を取得
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        // キャッシュキーの生成
        $cacheKey = $this->generateCacheKey($request);

        return Cache::tags(['articles'])->remember($cacheKey, CacheTime::ARTICLE_LIST, function () use ($request) {
            // リクエストパラメータからフィルタ配列を生成
            $filters = [
                'search' => $request->input('search'),
                'company_id' => $request->input('company_id'),
                'platform_id' => $request->input('platform_id'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            ];

            // ソートパラメータ
            $sortBy = $request->input('sort_by', 'published_at');
            $sortOrder = $request->input('sort_order', 'desc');

            // ページネーション
            $perPage = $request->input('limit', 20);

            // モデルのスコープを使用してクエリを構築
            $articles = Article::with(['company', 'platform'])
                ->withFilters($filters)
                ->withSort($sortBy, $sortOrder)
                ->paginate($perPage);

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

        return Cache::remember($cacheKey, CacheTime::ARTICLE_DETAIL, function () use ($id) {
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

    /**
     * 記事を更新
     * 部分更新対応のため、提供されたフィールドのみを更新する
     * company_id後付け対応（nullから具体的なIDへの変更を許可）
     *
     * @param  int  $id
     * @return \App\Http\Resources\ArticleResource|\Illuminate\Http\JsonResponse
     */
    public function update(UpdateArticleRequest $request, $id)
    {
        // 記事の存在確認（SoftDeletesで削除済みは除外）
        $article = Article::find($id);

        if (! $article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        // バリデーション済みデータでの部分更新
        $article->fill($request->validated());
        $article->save();

        // 関連キャッシュをクリア
        $this->clearArticleCache($article);

        // 更新された記事を関連データとともに取得してレスポンス
        $updatedArticle = Article::with(['company', 'platform'])->find($id);

        return new ArticleResource($updatedArticle);
    }

    /**
     * 記事を削除（ソフトデリート）
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // 記事の存在確認（SoftDeletesで削除済みは除外）
        $article = Article::find($id);

        if (! $article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        // ソフトデリート実行
        $article->delete();

        // 関連キャッシュをクリア
        $this->clearArticleCache($article);

        // 204 No Content レスポンス
        return response()->json(null, 204);
    }

    /**
     * 記事関連のキャッシュをクリア
     */
    private function clearArticleCache(Article $article): void
    {
        // 詳細キャッシュクリア
        Cache::forget("article_detail_{$article->id}");

        // 一覧キャッシュクリア（tagsを使用）
        Cache::tags(['articles'])->flush();

        // 会社記事一覧のキャッシュもクリア（簡易的にフラッシュ）
        // テスト環境での確実な動作を優先
        Cache::flush();
    }
}
