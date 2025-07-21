<?php

namespace Tests\Feature\Api;

use App\Models\Article;
use App\Models\Company;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用のユーザーを作成
        $this->user = User::factory()->create();
    }

    #[Test]
    public function test_記事一覧が取得できること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->count(5)->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/articles');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'url',
                        'author_name',
                        'published_at',
                        'bookmark_count',
                        'likes_count',
                        'company' => [
                            'id',
                            'name',
                            'domain',
                            'logo_url',
                            'website_url',
                        ],
                        'platform' => [
                            'id',
                            'name',
                            'base_url',
                        ],
                    ],
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);
    }

    #[Test]
    public function test_企業idでフィルタリングできること()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->count(3)->create([
            'company_id' => $company1->id,
            'platform_id' => $platform->id,
        ]);

        Article::factory()->count(2)->create([
            'company_id' => $company2->id,
            'platform_id' => $platform->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/articles?company_id={$company1->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(3, $response->json('data'));

        foreach ($response->json('data') as $article) {
            $this->assertEquals($company1->id, $article['company']['id']);
        }
    }

    #[Test]
    public function test_プラットフォームidでフィルタリングできること()
    {
        $company = Company::factory()->create();
        $platform1 = Platform::factory()->create();
        $platform2 = Platform::factory()->create();

        Article::factory()->count(3)->create([
            'company_id' => $company->id,
            'platform_id' => $platform1->id,
        ]);

        Article::factory()->count(2)->create([
            'company_id' => $company->id,
            'platform_id' => $platform2->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/articles?platform_id={$platform1->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(3, $response->json('data'));

        foreach ($response->json('data') as $article) {
            $this->assertEquals($platform1->id, $article['platform']['id']);
        }
    }

    #[Test]
    public function test_記事が日付順でソートされていること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        $article1 = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => now()->subDays(3),
        ]);

        $article2 = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => now()->subDays(1),
        ]);

        $article3 = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/articles');

        $response->assertStatus(Response::HTTP_OK);
        $articles = $response->json('data');

        $this->assertEquals($article2->id, $articles[0]['id']);
        $this->assertEquals($article3->id, $articles[1]['id']);
        $this->assertEquals($article1->id, $articles[2]['id']);
    }

    #[Test]
    public function test_ページネーションが正しく動作すること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->count(25)->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/articles');

        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(20, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
        $this->assertEquals(2, $response->json('meta.last_page'));
    }

    #[Test]
    public function test_記事詳細が取得できること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        $article = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/articles/{$article->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'url',
                    'author_name',
                    'published_at',
                    'bookmark_count',
                    'likes_count',
                    'company' => [
                        'id',
                        'name',
                        'domain',
                        'logo_url',
                        'website_url',
                    ],
                    'platform' => [
                        'id',
                        'name',
                        'base_url',
                    ],
                ],
            ])
            ->assertJsonPath('data.id', $article->id)
            ->assertJsonPath('data.title', $article->title)
            ->assertJsonPath('data.url', $article->url);
    }

    #[Test]
    public function test_存在しない記事id指定時は404が返される()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/articles/99999');

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'message' => 'Article not found',
            ]);
    }

    #[Test]
    public function test_limitパラメータが正常に動作すること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->count(10)->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/articles?limit=5');

        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(5, $response->json('data'));
    }

    #[Test]
    public function test_複数パラメータでのフィルタリングが正常に動作すること()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $platform1 = Platform::factory()->create();
        $platform2 = Platform::factory()->create();

        // company1 + platform1 の記事を3件作成
        Article::factory()->count(3)->create([
            'company_id' => $company1->id,
            'platform_id' => $platform1->id,
        ]);

        // company1 + platform2 の記事を2件作成
        Article::factory()->count(2)->create([
            'company_id' => $company1->id,
            'platform_id' => $platform2->id,
        ]);

        // company2 + platform1 の記事を1件作成
        Article::factory()->create([
            'company_id' => $company2->id,
            'platform_id' => $platform1->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/articles?company_id={$company1->id}&platform_id={$platform1->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(3, $response->json('data'));

        foreach ($response->json('data') as $article) {
            $this->assertEquals($company1->id, $article['company']['id']);
            $this->assertEquals($platform1->id, $article['platform']['id']);
        }
    }

    #[Test]
    public function test_記事が存在しない場合は空の配列が返される()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/articles');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function test_無効なパラメータでもエラーにならないこと()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->count(3)->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        // 存在しないcompany_idを指定
        $response = $this->actingAs($this->user)
            ->getJson('/api/articles?company_id=99999');
        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(0, $response->json('data'));

        // 存在しないplatform_idを指定
        $response = $this->actingAs($this->user)
            ->getJson('/api/articles?platform_id=99999');
        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(0, $response->json('data'));
    }

    // TODO: 認証機能を実装後に有効化
    // #[Test]
    // public function test_認証なしではアクセスできないこと()
    // {
    //     $response = $this->getJson('/api/articles');

    //     $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    // }

    #[Test]
    public function test_タイトルで検索できること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        // 検索対象の記事
        Article::factory()->create([
            'title' => 'Laravel APIの開発方法',
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);
        Article::factory()->create([
            'title' => 'Laravelによる認証機能の実装',
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);
        // 検索対象外の記事
        Article::factory()->create([
            'title' => 'React入門ガイド',
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/articles?search=Laravel');

        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(2, $response->json('data'));

        // 検索結果のタイトルにLaravelが含まれることを確認
        foreach ($response->json('data') as $article) {
            $this->assertStringContainsStringIgnoringCase('Laravel', $article['title']);
        }
    }

    #[Test]
    public function test_著者名で検索できること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->create([
            'author_name' => 'John Smith',
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);
        Article::factory()->create([
            'author_name' => 'John Doe',
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);
        Article::factory()->create([
            'author_name' => 'Jane Smith',
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/articles?search=John');

        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(2, $response->json('data'));

        // 検索結果の著者名にJohnが含まれることを確認
        foreach ($response->json('data') as $article) {
            $this->assertStringContainsStringIgnoringCase('John', $article['author_name']);
        }
    }

    #[Test]
    public function test_期間でフィルタリングできること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        // 異なる日付の記事を作成
        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => '2024-01-01 00:00:00',
        ]);
        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => '2024-01-15 00:00:00',
        ]);
        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => '2024-02-01 00:00:00',
        ]);

        // 2024年1月の記事のみ取得
        $response = $this->actingAs($this->user)
            ->getJson('/api/articles?start_date=2024-01-01&end_date=2024-01-31');

        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(2, $response->json('data'));
    }

    #[Test]
    public function test_いいね数でソートできること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        $article1 = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'likes_count' => 100,
        ]);
        $article2 = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'likes_count' => 50,
        ]);
        $article3 = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'likes_count' => 200,
        ]);

        // いいね数の降順でソート
        $response = $this->actingAs($this->user)
            ->getJson('/api/articles?sort_by=likes_count&sort_order=desc');

        $response->assertStatus(Response::HTTP_OK);

        $data = $response->json('data');
        $this->assertEquals($article3->id, $data[0]['id']); // 200いいね
        $this->assertEquals($article1->id, $data[1]['id']); // 100いいね
        $this->assertEquals($article2->id, $data[2]['id']); // 50いいね
    }

    #[Test]
    public function test_ブックマーク数でソートできること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        $article1 = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'bookmark_count' => 30,
        ]);
        $article2 = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'bookmark_count' => 10,
        ]);
        $article3 = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'bookmark_count' => 50,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/articles?sort_by=bookmark_count&sort_order=desc');

        $response->assertStatus(Response::HTTP_OK);

        $data = $response->json('data');
        $this->assertEquals($article3->id, $data[0]['id']); // 50ブックマーク
        $this->assertEquals($article1->id, $data[1]['id']); // 30ブックマーク
        $this->assertEquals($article2->id, $data[2]['id']); // 10ブックマーク
    }

    #[Test]
    public function test_キャッシュが正しく動作すること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->count(5)->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        // 1回目のリクエスト（キャッシュ生成）
        $response1 = $this->actingAs($this->user)
            ->getJson('/api/articles');

        $response1->assertStatus(Response::HTTP_OK);

        // キャッシュキーを生成
        $cacheKey = 'articles:page=1:per_page=20:search=:platform_id=:company_id=:start_date=:end_date=:sort_by=published_at:sort_order=desc';

        // tagsを使用しているキャッシュの存在確認
        $this->assertTrue(Cache::tags(['articles'])->has($cacheKey));

        // 2回目のリクエスト（キャッシュから取得）
        $response2 = $this->actingAs($this->user)
            ->getJson('/api/articles');

        $response2->assertStatus(Response::HTTP_OK);

        // レスポンスが同じであることを確認
        $this->assertEquals($response1->json(), $response2->json());
    }
}
