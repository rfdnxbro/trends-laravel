<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Company;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ArticleUpdateFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Company $anotherCompany;

    protected Platform $platform;

    protected Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        // テストデータの準備
        $this->company = Company::factory()->create();
        $this->anotherCompany = Company::factory()->create();
        $this->platform = Platform::factory()->create();

        $this->article = Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
            'title' => 'Original Title',
            'url' => 'https://example.com/original',
        ]);
    }

    public function test_正常な記事更新が正しく処理される()
    {
        $updateData = [
            'title' => 'Updated Title',
            'company_id' => $this->anotherCompany->id,
            'bookmark_count' => 100,
        ];

        $response = $this->putJson("/api/articles/{$this->article->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'url',
                    'company',
                    'platform',
                    'bookmark_count',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.company.id', $this->anotherCompany->id)
            ->assertJsonPath('data.bookmark_count', 100);

        // データベースの確認
        $this->assertDatabaseHas('articles', [
            'id' => $this->article->id,
            'title' => 'Updated Title',
            'company_id' => $this->anotherCompany->id,
            'bookmark_count' => 100,
        ]);
    }

    public function test_company_id後付け更新が正常に動作する()
    {
        // company_idがnullの記事を作成
        $articleWithoutCompany = Article::factory()->create([
            'company_id' => null,
            'platform_id' => $this->platform->id,
            'title' => 'Article without company',
        ]);

        $updateData = [
            'company_id' => $this->company->id,
        ];

        $response = $this->putJson("/api/articles/{$articleWithoutCompany->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.company.id', $this->company->id);

        // データベースの確認
        $this->assertDatabaseHas('articles', [
            'id' => $articleWithoutCompany->id,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_部分更新が正常に動作する()
    {
        $originalData = [
            'title' => $this->article->title,
            'url' => $this->article->url,
            'company_id' => $this->article->company_id,
        ];

        // タイトルのみ更新
        $updateData = [
            'title' => 'Only Title Updated',
        ];

        $response = $this->putJson("/api/articles/{$this->article->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.title', 'Only Title Updated')
            ->assertJsonPath('data.url', $originalData['url'])
            ->assertJsonPath('data.company.id', $originalData['company_id']);

        // データベースの確認（タイトルのみ変更、その他は維持）
        $this->assertDatabaseHas('articles', [
            'id' => $this->article->id,
            'title' => 'Only Title Updated',
            'url' => $originalData['url'],
            'company_id' => $originalData['company_id'],
        ]);
    }

    public function test_存在しない記事_i_d指定時は404エラーが返される()
    {
        $nonExistentId = 99999;
        $updateData = [
            'title' => 'Updated Title',
        ];

        $response = $this->putJson("/api/articles/{$nonExistentId}", $updateData);

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'message' => 'Article not found',
            ]);
    }

    public function test_無効なcompany_id指定時は422エラーが返される()
    {
        $updateData = [
            'company_id' => 99999, // 存在しないcompany_id
        ];

        $response = $this->putJson("/api/articles/{$this->article->id}", $updateData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['company_id']);
    }

    public function test_バリデーションエラー時に適切なレスポンスが返される()
    {
        $invalidData = [
            'title' => str_repeat('a', 501), // 500文字制限超過
            'url' => 'invalid-url', // 無効なURL
            'bookmark_count' => -1, // 負の値
            'platform_id' => 'invalid', // 文字列
        ];

        $response = $this->putJson("/api/articles/{$this->article->id}", $invalidData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors([
                'title',
                'url',
                'bookmark_count',
                'platform_id',
            ]);
    }

    public function test_ur_l重複時はバリデーションエラーが返される()
    {
        // 別の記事を作成
        $anotherArticle = Article::factory()->create([
            'url' => 'https://example.com/another',
        ]);

        // 既存のURLで更新を試行
        $updateData = [
            'url' => $anotherArticle->url,
        ];

        $response = $this->putJson("/api/articles/{$this->article->id}", $updateData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_自分の_ur_lでの更新は許可される()
    {
        $updateData = [
            'url' => $this->article->url, // 自分と同じURL
            'title' => 'Updated Title',
        ];

        $response = $this->putJson("/api/articles/{$this->article->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.title', 'Updated Title');
    }

    public function test_記事更新時にキャッシュがクリアされる()
    {
        // キャッシュを事前に設定
        $detailCacheKey = "article_detail_{$this->article->id}";
        Cache::put($detailCacheKey, 'cached_data', 3600);
        Cache::tags(['articles'])->put('articles_list_cache', 'cached_list', 3600);

        $this->assertTrue(Cache::has($detailCacheKey));
        $this->assertTrue(Cache::tags(['articles'])->has('articles_list_cache'));

        $updateData = [
            'title' => 'Updated Title',
        ];

        $response = $this->putJson("/api/articles/{$this->article->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK);

        // キャッシュがクリアされていることを確認
        $this->assertFalse(Cache::has($detailCacheKey));
        $this->assertFalse(Cache::tags(['articles'])->has('articles_list_cache'));
    }

    public function test_company_idをnullに設定できる()
    {
        $updateData = [
            'company_id' => null,
        ];

        $response = $this->putJson("/api/articles/{$this->article->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.company', null);

        // データベースの確認
        $this->assertDatabaseHas('articles', [
            'id' => $this->article->id,
            'company_id' => null,
        ]);
    }

    public function test_削除済み記事は更新できない()
    {
        // 記事をソフトデリート
        $this->article->delete();

        $updateData = [
            'title' => 'Updated Title',
        ];

        $response = $this->putJson("/api/articles/{$this->article->id}", $updateData);

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'message' => 'Article not found',
            ]);
    }
}
