<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Company;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ArticleDeleteFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Platform $platform;
    protected Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        // テストデータの準備
        $this->company = Company::factory()->create();
        $this->platform = Platform::factory()->create();
        
        $this->article = Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
            'title' => 'Test Article',
            'url' => 'https://example.com/test-article',
        ]);
    }

    public function test_正常な記事削除でソフトデリートが実行される()
    {
        $response = $this->deleteJson("/api/articles/{$this->article->id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        // ソフトデリートされていることを確認
        $this->assertSoftDeleted('articles', [
            'id' => $this->article->id,
            'title' => 'Test Article',
        ]);

        // データ自体は物理的に残っている
        $this->assertDatabaseHas('articles', [
            'id' => $this->article->id,
            'title' => 'Test Article',
        ]);

        // deleted_atが設定されている
        $deletedArticle = Article::withTrashed()->find($this->article->id);
        $this->assertNotNull($deletedArticle->deleted_at);
    }

    public function test_存在しない記事ID指定時は404エラーが返される()
    {
        $nonExistentId = 99999;

        $response = $this->deleteJson("/api/articles/{$nonExistentId}");

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'message' => 'Article not found',
            ]);
    }

    public function test_既に削除済み記事の削除時は404エラーが返される()
    {
        // 記事を事前にソフトデリート
        $this->article->delete();

        $response = $this->deleteJson("/api/articles/{$this->article->id}");

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'message' => 'Article not found',
            ]);
    }

    public function test_ソフトデリート後でも記事が通常クエリから除外される()
    {
        // 削除前の確認
        $this->assertDatabaseCount('articles', 1);
        $this->assertEquals(1, Article::count());

        // 記事を削除
        $response = $this->deleteJson("/api/articles/{$this->article->id}");
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        // 通常のクエリでは取得されない
        $this->assertEquals(0, Article::count());
        
        // withTrashedでは取得される
        $this->assertEquals(1, Article::withTrashed()->count());
        
        // 物理的にはデータベースに残っている
        $this->assertDatabaseCount('articles', 1);
    }

    public function test_記事削除時にキャッシュがクリアされる()
    {
        // キャッシュを事前に設定
        $detailCacheKey = "article_detail_{$this->article->id}";
        Cache::put($detailCacheKey, 'cached_data', 3600);
        Cache::tags(['articles'])->put('articles_list_cache', 'cached_list', 3600);

        $this->assertTrue(Cache::has($detailCacheKey));
        $this->assertTrue(Cache::tags(['articles'])->has('articles_list_cache'));

        $response = $this->deleteJson("/api/articles/{$this->article->id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        // キャッシュがクリアされていることを確認
        $this->assertFalse(Cache::has($detailCacheKey));
        $this->assertFalse(Cache::tags(['articles'])->has('articles_list_cache'));
    }

    public function test_削除後のAPI経由での詳細取得は404を返す()
    {
        // 記事を削除
        $this->deleteJson("/api/articles/{$this->article->id}");

        // 削除後に詳細取得を試行
        $response = $this->getJson("/api/articles/{$this->article->id}");

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'message' => 'Article not found',
            ]);
    }

    public function test_削除後のAPI経由での一覧取得から除外される()
    {
        // 追加記事を作成
        $anotherArticle = Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
        ]);

        // 削除前の一覧取得
        $responseBefore = $this->getJson('/api/articles');
        $responseBefore->assertStatus(Response::HTTP_OK);
        
        // レスポンス構造をデバッグ
        $responseData = $responseBefore->json();
        $datasBefore = $responseData['data']['data'] ?? $responseData['data'] ?? [];
        $this->assertCount(2, $datasBefore);

        // 1つの記事を削除
        $deleteResponse = $this->deleteJson("/api/articles/{$this->article->id}");
        $deleteResponse->assertStatus(Response::HTTP_NO_CONTENT);

        // 削除後の一覧取得
        $responseAfter = $this->getJson('/api/articles');
        $responseAfter->assertStatus(Response::HTTP_OK);
        $responseDataAfter = $responseAfter->json();
        
        // デバッグ用：レスポンス内容を確認
        if (isset($responseDataAfter['data']['data'])) {
            $datasAfter = $responseDataAfter['data']['data'];
        } elseif (isset($responseDataAfter['data']) && is_array($responseDataAfter['data'])) {
            $datasAfter = $responseDataAfter['data'];
        } else {
            $this->fail('Unexpected response structure: ' . json_encode($responseDataAfter));
        }
        
        $this->assertCount(1, $datasAfter);

        // 残っている記事が正しいことを確認
        $this->assertEquals($anotherArticle->id, $datasAfter[0]['id']);
    }

    public function test_関連する会社の記事一覧からも削除される()
    {
        // 同じ会社の記事を追加作成
        $anotherArticle = Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
        ]);

        // 削除前の会社記事一覧取得
        $responseBefore = $this->getJson("/api/companies/{$this->company->id}/articles");
        $responseBefore->assertStatus(Response::HTTP_OK);
        $responseData = $responseBefore->json();
        $datasBefore = $responseData['data']['data'] ?? $responseData['data'] ?? [];
        $this->assertCount(2, $datasBefore);

        // 1つの記事を削除
        $this->deleteJson("/api/articles/{$this->article->id}");

        // 削除後の会社記事一覧取得
        $responseAfter = $this->getJson("/api/companies/{$this->company->id}/articles");
        $responseAfter->assertStatus(Response::HTTP_OK);
        $responseDataAfter = $responseAfter->json();
        $datasAfter = $responseDataAfter['data']['data'] ?? $responseDataAfter['data'] ?? [];
        $this->assertCount(1, $datasAfter);

        // 残っている記事が正しいことを確認
        $this->assertEquals($anotherArticle->id, $datasAfter[0]['id']);
    }

    public function test_複数記事削除のバッチ処理的なテスト()
    {
        // 複数記事を作成
        $articles = Article::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
        ]);

        // 各記事を順次削除
        foreach ($articles as $article) {
            $response = $this->deleteJson("/api/articles/{$article->id}");
            $response->assertStatus(Response::HTTP_NO_CONTENT);
        }

        // 元の記事も削除
        $response = $this->deleteJson("/api/articles/{$this->article->id}");
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        // 全ての記事がソフトデリートされていることを確認
        $this->assertEquals(0, Article::count());
        $this->assertEquals(4, Article::withTrashed()->count());
    }

    public function test_削除操作が冪等性を保つ()
    {
        // 1回目の削除
        $response1 = $this->deleteJson("/api/articles/{$this->article->id}");
        $response1->assertStatus(Response::HTTP_NO_CONTENT);

        // 2回目の削除（既に削除済み）
        $response2 = $this->deleteJson("/api/articles/{$this->article->id}");
        $response2->assertStatus(Response::HTTP_NOT_FOUND);

        // 状態は一貫している
        $this->assertSoftDeleted('articles', ['id' => $this->article->id]);
        $this->assertEquals(0, Article::count());
        $this->assertEquals(1, Article::withTrashed()->count());
    }
}