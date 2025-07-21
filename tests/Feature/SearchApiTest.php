<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Company;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
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
            'engagement_count' => 50,
            'published_at' => Carbon::now()->subDays(5),
        ]);
    }

    #[Test]
    public function test_企業名での検索が正常に動作する()
    {
        $response = $this->getJson('/api/search/companies?q=Test');

        $response->assertStatus(Response::HTTP_OK)
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

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'articles' => [
                        '*' => [
                            'id',
                            'title',
                            'url',
                            'author_name',
                            'engagement_count',
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

        $response->assertStatus(Response::HTTP_OK)
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

        $response->assertStatus(Response::HTTP_OK)
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

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
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

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
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
            'engagement_count' => 5,
            'published_at' => Carbon::now()->subDays(40),
        ]);

        $response = $this->getJson('/api/search/articles?q=Laravel&days=30&min_engagement=10');

        $response->assertStatus(Response::HTTP_OK);
        $articles = $response->json('data.articles');

        foreach ($articles as $article) {
            $this->assertGreaterThanOrEqual(10, $article['engagement_count']);
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

        $response->assertStatus(Response::HTTP_OK);
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

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
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

        $response->assertStatus(Response::HTTP_OK);
        $this->assertLessThanOrEqual(5, count($response->json('data.companies')));
    }

    #[Test]
    public function test_著者名での記事検索が正常に動作する()
    {
        $response = $this->getJson('/api/search/articles?q=test_author');

        $response->assertStatus(Response::HTTP_OK);
        $articles = $response->json('data.articles');

        $this->assertCount(1, $articles);
        $this->assertEquals('test_author', $articles[0]['author_name']);
    }

    #[Test]
    public function test_統合検索_apiの詳細レスポンス構造が正しい()
    {
        $response = $this->getJson('/api/search?q=Test');

        $response->assertStatus(Response::HTTP_OK)
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
                        'min_engagement',
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

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure([
                'error',
                'details',
            ]);

        $errorData = $response->json();
        $this->assertArrayHasKey('error', $errorData);
        $this->assertIsString($errorData['error']);
    }

    #[Test]
    public function test_企業検索の関連度スコア計算が複数条件で正しく動作する()
    {
        // 完全一致企業（最高スコア）
        $exactMatch = Company::factory()->create([
            'name' => 'TestCorp',
            'domain' => 'testcorp.com',
            'description' => 'Test company description',
            'is_active' => true,
        ]);

        // 部分一致企業（中程度スコア）
        $partialMatch = Company::factory()->create([
            'name' => 'TestCorp Solutions',
            'domain' => 'testsolutions.com',
            'description' => 'Solutions company',
            'is_active' => true,
        ]);

        // ドメイン一致企業
        $domainMatch = Company::factory()->create([
            'name' => 'Other Company',
            'domain' => 'test.com',
            'description' => 'Another company',
            'is_active' => true,
        ]);

        // 説明文一致企業
        $descriptionMatch = Company::factory()->create([
            'name' => 'Different Company',
            'domain' => 'different.com',
            'description' => 'This is a test company for testing purposes',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/search/companies?q=TestCorp');

        $response->assertStatus(Response::HTTP_OK);
        $companies = $response->json('data.companies');

        // 完全一致企業が最高スコアを持つことを確認
        $exactMatchResult = collect($companies)->firstWhere('name', 'TestCorp');
        $partialMatchResult = collect($companies)->firstWhere('name', 'TestCorp Solutions');

        $this->assertNotNull($exactMatchResult);
        $this->assertNotNull($partialMatchResult);
        $this->assertGreaterThan($partialMatchResult['match_score'], $exactMatchResult['match_score']);
    }

    #[Test]
    public function test_記事検索の関連度スコア計算が複数条件で正しく動作する()
    {
        // 高ブックマーク数記事
        $highBookmarkArticle = Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
            'title' => 'Laravel advanced techniques',
            'author_name' => 'expert_author',
            'engagement_count' => 100,
            'published_at' => Carbon::now()->subDays(2),
        ]);

        // 中ブックマーク数記事
        $mediumBookmarkArticle = Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
            'title' => 'Laravel basic guide',
            'author_name' => 'regular_author',
            'engagement_count' => 30,
            'published_at' => Carbon::now()->subDays(5),
        ]);

        // 低ブックマーク数記事
        $lowBookmarkArticle = Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
            'title' => 'Laravel introduction',
            'author_name' => 'beginner_author',
            'engagement_count' => 5,
            'published_at' => Carbon::now()->subDays(10),
        ]);

        // 古い記事（ペナルティ対象）
        $oldArticle = Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
            'title' => 'Old Laravel article',
            'author_name' => 'old_author',
            'engagement_count' => 50,
            'published_at' => Carbon::now()->subDays(150),
        ]);

        $response = $this->getJson('/api/search/articles?q=Laravel&days=365');

        $response->assertStatus(Response::HTTP_OK);
        $articles = $response->json('data.articles');

        // 高ブックマーク数記事が最高スコアを持つことを確認
        $this->assertGreaterThan(0, count($articles));

        // 記事が関連度スコア順にソートされていることを確認
        $scores = collect($articles)->pluck('match_score');
        $sortedScores = $scores->sortDesc();
        $this->assertEquals($sortedScores->toArray(), $scores->toArray());
    }

    #[Test]
    public function test_ドメイン名での企業検索でスコア計算が正しく動作する()
    {
        $company = Company::factory()->create([
            'name' => 'Example Corp',
            'domain' => 'example.com',
            'description' => 'Example company',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/search/companies?q=example.com');

        $response->assertStatus(Response::HTTP_OK);
        $companies = $response->json('data.companies');

        $this->assertCount(1, $companies);
        $this->assertGreaterThan(0, $companies[0]['match_score']);
        $this->assertEquals('Example Corp', $companies[0]['name']);
    }

    #[Test]
    public function test_説明文での企業検索でスコア計算が正しく動作する()
    {
        $company = Company::factory()->create([
            'name' => 'Tech Solutions',
            'domain' => 'techsolutions.com',
            'description' => 'Advanced technology solutions provider',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/search/companies?q=technology');

        $response->assertStatus(Response::HTTP_OK);
        $companies = $response->json('data.companies');

        $this->assertCount(1, $companies);
        $this->assertGreaterThan(0, $companies[0]['match_score']);
        $this->assertEquals('Tech Solutions', $companies[0]['name']);
    }

    #[Test]
    public function test_記事の日付による関連度スコア調整が正しく動作する()
    {
        // 最新記事（高スコア）
        $recentArticle = Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
            'title' => 'React recent tutorial',
            'author_name' => 'recent_author',
            'engagement_count' => 20,
            'published_at' => Carbon::now()->subDays(3),
        ]);

        // やや古い記事（中スコア）
        $somewhatOldArticle = Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
            'title' => 'React somewhat old tutorial',
            'author_name' => 'somewhat_old_author',
            'engagement_count' => 20,
            'published_at' => Carbon::now()->subDays(20),
        ]);

        // 古い記事（ペナルティ）
        $oldArticle = Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
            'title' => 'React old tutorial',
            'author_name' => 'old_author',
            'engagement_count' => 20,
            'published_at' => Carbon::now()->subDays(120),
        ]);

        $response = $this->getJson('/api/search/articles?q=React&days=365');

        $response->assertStatus(Response::HTTP_OK);
        $articles = $response->json('data.articles');

        $this->assertCount(3, $articles);

        // 最新記事が最高スコアを持つことを確認
        $recentResult = collect($articles)->firstWhere('author_name', 'recent_author');
        $somewhatOldResult = collect($articles)->firstWhere('author_name', 'somewhat_old_author');
        $oldResult = collect($articles)->firstWhere('author_name', 'old_author');

        $this->assertNotNull($recentResult);
        $this->assertNotNull($somewhatOldResult);
        $this->assertNotNull($oldResult);

        $this->assertGreaterThan($somewhatOldResult['match_score'], $recentResult['match_score']);
        $this->assertGreaterThan($oldResult['match_score'], $somewhatOldResult['match_score']);
    }

    #[Test]
    public function test_ブックマーク数による記事関連度スコア調整が正しく動作する()
    {
        // 高ブックマーク記事
        $highBookmarkArticle = Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
            'title' => 'Vue.js high bookmark article',
            'author_name' => 'high_author',
            'engagement_count' => 200,
            'published_at' => Carbon::now()->subDays(10),
        ]);

        // 中ブックマーク記事
        $mediumBookmarkArticle = Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
            'title' => 'Vue.js medium bookmark article',
            'author_name' => 'medium_author',
            'engagement_count' => 25,
            'published_at' => Carbon::now()->subDays(10),
        ]);

        // 低ブックマーク記事
        $lowBookmarkArticle = Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
            'title' => 'Vue.js low bookmark article',
            'author_name' => 'low_author',
            'engagement_count' => 3,
            'published_at' => Carbon::now()->subDays(10),
        ]);

        $response = $this->getJson('/api/search/articles?q=Vue.js');

        $response->assertStatus(Response::HTTP_OK);
        $articles = $response->json('data.articles');

        $this->assertCount(3, $articles);

        // ブックマーク数によるスコア順序を確認
        $highResult = collect($articles)->firstWhere('author_name', 'high_author');
        $mediumResult = collect($articles)->firstWhere('author_name', 'medium_author');
        $lowResult = collect($articles)->firstWhere('author_name', 'low_author');

        $this->assertNotNull($highResult);
        $this->assertNotNull($mediumResult);
        $this->assertNotNull($lowResult);

        $this->assertGreaterThan($mediumResult['match_score'], $highResult['match_score']);
        $this->assertGreaterThan($lowResult['match_score'], $mediumResult['match_score']);
    }

    #[Test]
    public function test_記事検索で無効なパラメータの場合バリデーションエラーが返される()
    {
        // 無効なdays値でバリデーションエラー
        $response = $this->getJson('/api/search/articles?q=Laravel&days=0');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'error' => '検索クエリが無効です',
            ])
            ->assertJsonStructure([
                'error',
                'details',
            ]);

        // 負のmin_engagement値でバリデーションエラー
        $response = $this->getJson('/api/search/articles?q=Laravel&min_engagement=-1');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'error' => '検索クエリが無効です',
            ])
            ->assertJsonStructure([
                'error',
                'details',
            ]);
    }

    #[Test]
    public function test_企業にランキングがある場合スコアボーナスが加算される()
    {
        // ランキングありの企業
        $companyWithRanking = Company::factory()->create([
            'name' => 'Company With Ranking',
            'domain' => 'with-ranking.com',
            'description' => 'テスト企業',
            'is_active' => true,
        ]);

        // ランキングデータを作成
        $ranking = \App\Models\CompanyRanking::factory()->create([
            'company_id' => $companyWithRanking->id,
            'ranking_period' => 'weekly',
            'rank_position' => 1,
            'total_score' => 100,
            'calculated_at' => Carbon::now(),
        ]);

        // ランキングなしの企業
        $companyWithoutRanking = Company::factory()->create([
            'name' => 'Company Without Ranking',
            'domain' => 'without-ranking.com',
            'description' => 'テスト企業',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/search/companies?q=Company');

        $response->assertStatus(Response::HTTP_OK);
        $companies = $response->json('data.companies');

        // ランキングありの企業が高スコアを持つことを確認
        $withRankingResult = collect($companies)->firstWhere('name', 'Company With Ranking');
        $withoutRankingResult = collect($companies)->firstWhere('name', 'Company Without Ranking');

        $this->assertNotNull($withRankingResult);
        $this->assertNotNull($withoutRankingResult);

        // ランキングありの企業の方が高いスコアを持つことを確認
        $this->assertGreaterThan($withoutRankingResult['match_score'], $withRankingResult['match_score']);
    }
}
