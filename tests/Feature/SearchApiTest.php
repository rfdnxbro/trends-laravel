<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Company;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Platform $platform;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'domain' => 'test-company.com',
            'description' => 'テスト用企業です',
            'is_active' => true,
        ]);

        $this->platform = Platform::factory()->create([
            'name' => 'Qiita',
            'base_url' => 'https://qiita.com',
        ]);

        $this->article = Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
            'platform' => 'Qiita',
            'title' => 'Laravel テストに関する記事',
            'author_name' => 'test_author',
            'bookmark_count' => 50,
            'published_at' => Carbon::now()->subDays(5),
        ]);
    }

    #[Test]
    public function test_企業名での検索が正常に動作する()
    {
        $response = $this->getJson('/api/search/companies?q=Test');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'companies' => [
                        '*' => [
                            'id',
                            'name',
                            'domain',
                            'description',
                            'match_score',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
                'meta' => [
                    'total_results',
                    'search_time',
                    'query',
                ],
            ]);

        $this->assertCount(1, $response->json('data.companies'));
        $this->assertEquals('Test Company', $response->json('data.companies.0.name'));
    }

    #[Test]
    public function test_記事タイトルでの検索が正常に動作する()
    {
        $response = $this->getJson('/api/search/articles?q=Laravel');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'articles' => [
                        '*' => [
                            'id',
                            'title',
                            'url',
                            'author_name',
                            'bookmark_count',
                            'match_score',
                            'company',
                            'published_at',
                        ],
                    ],
                ],
                'meta' => [
                    'total_results',
                    'search_time',
                    'query',
                    'filters',
                ],
            ]);

        $this->assertCount(1, $response->json('data.articles'));
        $this->assertStringContainsString('Laravel', $response->json('data.articles.0.title'));
    }

    #[Test]
    public function test_統合検索が正常に動作する()
    {
        $response = $this->getJson('/api/search?q=Test');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'companies' => [
                        '*' => [
                            'id',
                            'name',
                            'domain',
                            'match_score',
                        ],
                    ],
                    'articles' => [
                        '*' => [
                            'id',
                            'title',
                            'author_name',
                            'match_score',
                        ],
                    ],
                ],
                'meta' => [
                    'total_results',
                    'search_time',
                    'query',
                    'type',
                ],
            ]);
    }

    #[Test]
    public function test_検索タイプを指定した統合検索が正常に動作する()
    {
        $response = $this->getJson('/api/search?q=Test&type=companies');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'companies' => [
                        '*' => [
                            'id',
                            'name',
                            'domain',
                            'match_score',
                        ],
                    ],
                ],
                'meta' => [
                    'total_results',
                    'search_time',
                    'query',
                    'type',
                ],
            ]);

        $this->assertArrayNotHasKey('articles', $response->json('data'));
    }

    #[Test]
    public function test_検索クエリが空の場合はバリデーションエラーが返される()
    {
        $response = $this->getJson('/api/search/companies?q=');

        $response->assertStatus(400)
            ->assertJsonStructure([
                'error',
                'details',
            ]);
    }

    #[Test]
    public function test_検索クエリが長すぎる場合はバリデーションエラーが返される()
    {
        $longQuery = str_repeat('a', 256);
        $response = $this->getJson("/api/search/companies?q={$longQuery}");

        $response->assertStatus(400)
            ->assertJsonStructure([
                'error',
                'details',
            ]);
    }

    #[Test]
    public function test_記事検索でフィルタリングが正常に動作する()
    {
        // 古い記事を作成
        Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
            'platform' => 'Qiita',
            'title' => 'Laravel 古い記事',
            'author_name' => 'old_author',
            'bookmark_count' => 5,
            'published_at' => Carbon::now()->subDays(40),
        ]);

        $response = $this->getJson('/api/search/articles?q=Laravel&days=30&min_bookmarks=10');

        $response->assertStatus(200);
        $articles = $response->json('data.articles');

        foreach ($articles as $article) {
            $this->assertGreaterThanOrEqual(10, $article['bookmark_count']);
        }
    }

    #[Test]
    public function test_検索結果の関連度スコアが正常に計算される()
    {
        // 完全一致の企業を作成
        $exactMatch = Company::factory()->create([
            'name' => 'TestExact',
            'domain' => 'testexact.com',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/search/companies?q=TestExact');

        $response->assertStatus(200);
        $companies = $response->json('data.companies');

        // 完全一致の企業が最初に来ることを確認
        $this->assertEquals('TestExact', $companies[0]['name']);
        $this->assertGreaterThan(0, $companies[0]['match_score']);
    }

    #[Test]
    public function test_レート制限が正常に動作する()
    {
        // 60回リクエストして制限に達することを確認
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/search/companies?q=test');

            if ($i < 60) {
                $this->assertNotEquals(429, $response->status());
            } else {
                $this->assertEquals(429, $response->status());
            }
        }
    }

    #[Test]
    public function test_無効な検索タイプを指定した場合バリデーションエラーが返される()
    {
        $response = $this->getJson('/api/search?q=test&type=invalid');

        $response->assertStatus(400)
            ->assertJsonStructure([
                'error',
                'details',
            ]);
    }

    #[Test]
    public function test_企業検索でlimitパラメータが正常に動作する()
    {
        // 複数の企業を作成
        Company::factory()->count(10)->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/search/companies?q=Test&limit=5');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(5, count($response->json('data.companies')));
    }

    #[Test]
    public function test_著者名での記事検索が正常に動作する()
    {
        $response = $this->getJson('/api/search/articles?q=test_author');

        $response->assertStatus(200);
        $articles = $response->json('data.articles');

        $this->assertCount(1, $articles);
        $this->assertEquals('test_author', $articles[0]['author_name']);
    }

    #[Test]
    public function test_統合検索_apiの詳細レスポンス構造が正しい()
    {
        $response = $this->getJson('/api/search?q=Test');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    // companies または articles が含まれる
                ],
                'meta' => [
                    'total_results',
                    'search_time',
                    'query',
                    'type',
                    'filters' => [
                        'days',
                        'min_bookmarks',
                    ],
                ],
            ]);

        // データオブジェクトの構造確認
        $data = $response->json('data');
        $this->assertIsArray($data);

        // メタデータの型確認
        $meta = $response->json('meta');
        $this->assertIsInt($meta['total_results']);
        $this->assertIsFloat($meta['search_time']);
        $this->assertIsString($meta['query']);
    }

    #[Test]
    public function test_apiエラーハンドリングが適切に動作する()
    {
        // 空のクエリパラメータでエラーテスト
        $response = $this->getJson('/api/search?q=');

        $response->assertStatus(400)
            ->assertJsonStructure([
                'error',
                'details',
            ]);

        $errorData = $response->json();
        $this->assertArrayHasKey('error', $errorData);
        $this->assertIsString($errorData['error']);
    }
}
