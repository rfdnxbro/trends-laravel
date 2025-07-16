<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\SearchController;
use App\Models\Article;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    private SearchController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new SearchController;
    }

    #[Test]
    public function 企業の関連度スコア計算が正常に動作する()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'domain' => 'test-company.com',
            'description' => 'テスト用企業です',
        ]);

        $query = 'Test';

        // プライベートメソッドを実行するためのリフレクション
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $company, $query);

        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(2.0, $score);
    }

    #[Test]
    public function 記事の関連度スコア計算が正常に動作する()
    {
        $article = Article::factory()->create([
            'title' => 'Laravel テストに関する記事',
            'author_name' => 'test_author',
            'bookmark_count' => 50,
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $query = 'Laravel';

        // プライベートメソッドを実行するためのリフレクション
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, $query);

        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(2.0, $score);
    }

    #[Test]
    public function 企業名完全一致の場合最高スコアが返される()
    {
        $company = Company::factory()->create([
            'name' => 'TestExact',
            'domain' => 'testexact.com',
            'description' => 'テスト用企業です',
        ]);

        $query = 'TestExact';

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $company, $query);

        $this->assertGreaterThanOrEqual(1.0, $score);
    }

    #[Test]
    public function 記事のブックマーク数が多いほど高スコアが返される()
    {
        $highBookmarkArticle = Article::factory()->create([
            'title' => 'Laravel テスト記事',
            'bookmark_count' => 150,
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $lowBookmarkArticle = Article::factory()->create([
            'title' => 'Laravel テスト記事',
            'bookmark_count' => 5,
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $query = 'Laravel';

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $highScore = $method->invoke($this->controller, $highBookmarkArticle, $query);
        $lowScore = $method->invoke($this->controller, $lowBookmarkArticle, $query);

        $this->assertGreaterThan($lowScore, $highScore);
    }

    #[Test]
    public function 新しい記事ほど高スコアが返される()
    {
        $recentArticle = Article::factory()->create([
            'title' => 'Laravel テスト記事',
            'bookmark_count' => 50,
            'published_at' => Carbon::now()->subDays(3),
        ]);

        $oldArticle = Article::factory()->create([
            'title' => 'Laravel テスト記事',
            'bookmark_count' => 50,
            'published_at' => Carbon::now()->subDays(150),
        ]);

        $query = 'Laravel';

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $recentScore = $method->invoke($this->controller, $recentArticle, $query);
        $oldScore = $method->invoke($this->controller, $oldArticle, $query);

        $this->assertGreaterThan($oldScore, $recentScore);
    }

    #[Test]
    public function 企業のドメイン一致でスコアが加算される()
    {
        $company = Company::factory()->create([
            'name' => 'Sample Company',
            'domain' => 'test-company.com',
            'description' => 'サンプル企業です',
        ]);

        $query = 'test';

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $company, $query);

        $this->assertGreaterThan(0, $score);
    }

    #[Test]
    public function 記事の著者名一致でスコアが加算される()
    {
        $article = Article::factory()->create([
            'title' => 'サンプル記事',
            'author_name' => 'test_author',
            'bookmark_count' => 50,
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $query = 'test';

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, $query);

        $this->assertGreaterThan(0, $score);
    }

    #[Test]
    public function test_search_companies_正常なリクエストで企業検索が実行される()
    {
        // テスト用企業データ作成
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'domain' => 'test.com',
            'description' => 'Test Description',
            'is_active' => true,
        ]);

        // モックリクエストを作成
        $request = Request::create('/api/search/companies', 'GET', [
            'q' => 'Test',
            'limit' => 10,
        ]);

        // メソッドを実行
        $response = $this->controller->searchCompanies($request);

        // レスポンスをアサート
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('companies', $data['data']);
        $this->assertArrayHasKey('total_results', $data['meta']);
        $this->assertArrayHasKey('search_time', $data['meta']);
        $this->assertArrayHasKey('query', $data['meta']);
    }

    #[Test]
    public function test_search_companies_無効なクエリでバリデーションエラーが返される()
    {
        // 空のクエリパラメータ
        $request = Request::create('/api/search/companies', 'GET', [
            'q' => '',
        ]);

        $response = $this->controller->searchCompanies($request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('details', $data);
    }

    #[Test]
    public function test_search_articles_正常なリクエストで記事検索が実行される()
    {
        // テスト用データ作成
        $company = Company::factory()->create();
        $platform = \App\Models\Platform::factory()->create();
        $article = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'title' => 'Laravel Test Article',
            'author_name' => 'test_author',
            'bookmark_count' => 50,
            'published_at' => Carbon::now()->subDays(5),
        ]);

        // モックリクエストを作成
        $request = Request::create('/api/search/articles', 'GET', [
            'q' => 'Laravel',
            'limit' => 20,
            'days' => 30,
            'min_bookmarks' => 10,
        ]);

        $response = $this->controller->searchArticles($request);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('articles', $data['data']);
        $this->assertArrayHasKey('filters', $data['meta']);
    }

    #[Test]
    public function test_search_articles_無効なパラメータでバリデーションエラーが返される()
    {
        $request = Request::create('/api/search/articles', 'GET', [
            'q' => 'test',
            'days' => 0, // 無効な日数
        ]);

        $response = $this->controller->searchArticles($request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('details', $data);
    }

    #[Test]
    public function test_search_統合検索が正常に実行される()
    {
        // テスト用データ作成
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);
        $platform = \App\Models\Platform::factory()->create();
        $article = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'title' => 'Test Article',
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $request = Request::create('/api/search', 'GET', [
            'q' => 'Test',
            'type' => 'all',
        ]);

        $response = $this->controller->search($request);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('type', $data['meta']);
    }

    #[Test]
    public function test_search_特定タイプの検索が正常に実行される()
    {
        // 企業のみ検索
        Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $request = Request::create('/api/search', 'GET', [
            'q' => 'Test',
            'type' => 'companies',
        ]);

        $response = $this->controller->search($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('companies', $data['data']);
        $this->assertArrayNotHasKey('articles', $data['data']);
    }

    #[Test]
    public function test_search_無効なタイプでバリデーションエラーが返される()
    {
        $request = Request::create('/api/search', 'GET', [
            'q' => 'test',
            'type' => 'invalid',
        ]);

        $response = $this->controller->search($request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
    }

    #[Test]
    public function test_calculate_relevance_score_nullドメインと説明文の処理()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'domain' => 'test.com',
            'description' => null,
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $company, 'test');

        // nullでもエラーにならないことを確認
        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
    }

    #[Test]
    public function test_calculate_article_relevance_score_null著者名の処理()
    {
        $article = Article::factory()->create([
            'title' => 'Test Article',
            'author_name' => null,
            'bookmark_count' => 50,
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, 'test');

        // nullでもエラーにならないことを確認
        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
    }

    #[Test]
    public function test_calculate_relevance_score_ランキングありの企業でボーナススコアが加算される()
    {
        $company = Company::factory()->create([
            'name' => 'Company with Ranking',
            'domain' => 'company.com',
            'is_active' => true,
        ]);

        // ランキングを作成
        $ranking = \App\Models\CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'ranking_period' => 'weekly',
            'rank_position' => 1,
            'total_score' => 100,
            'calculated_at' => Carbon::now(),
        ]);

        // リレーションをロード
        $company->load('rankings');

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $company, 'company');

        // ランキングボーナスが加算されていることを確認
        $this->assertGreaterThan(0, $score);
    }

    #[Test]
    public function test_calculate_article_relevance_score_中ブックマーク数でスコアが加算される()
    {
        $article = Article::factory()->create([
            'title' => 'Medium Bookmark Article',
            'bookmark_count' => 30, // MEDIUM_BOOKMARKS_THRESHOLD = 20
            'published_at' => Carbon::now()->subDays(50),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, 'Medium');

        $this->assertGreaterThan(0, $score);
    }

    #[Test]
    public function test_calculate_article_relevance_score_やや最近の記事でスコアが加算される()
    {
        $article = Article::factory()->create([
            'title' => 'Somewhat Recent Article',
            'bookmark_count' => 10,
            'published_at' => Carbon::now()->subDays(20), // SOMEWHAT_RECENT_DAYS_THRESHOLD = 30
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, 'Recent');

        $this->assertGreaterThan(0, $score);
    }

    #[Test]
    public function test_calculate_article_relevance_score_低ブックマーク数でもスコアが加算される()
    {
        $article = Article::factory()->create([
            'title' => 'Low Bookmark Article',
            'bookmark_count' => 8, // LOW_BOOKMARKS_THRESHOLD = 5
            'published_at' => Carbon::now()->subDays(50),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, 'Low');

        $this->assertGreaterThan(0, $score);
    }
}
