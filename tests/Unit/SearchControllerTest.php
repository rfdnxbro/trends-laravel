<?php

namespace Tests\Unit;

use App\Constants\ScoringConstants;
use App\Constants\SearchConstants;
use App\Http\Controllers\Api\SearchController;
use App\Models\Article;
use App\Models\Company;
use App\Models\CompanyRanking;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
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
        Cache::flush();
    }

    // ===========================================
    // searchCompanies() メソッドのテスト
    // ===========================================

    #[Test]
    public function test_search_companies_正常なリクエストで企業検索が実行される()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'domain' => 'test.com',
            'description' => 'Test Description',
            'is_active' => true,
        ]);

        $request = Request::create('/api/search/companies', 'GET', [
            'q' => 'Test',
            'limit' => 10,
        ]);

        $response = $this->controller->searchCompanies($request);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('companies', $data['data']);
        $this->assertArrayHasKey('total_results', $data['meta']);
        $this->assertArrayHasKey('search_time', $data['meta']);
        $this->assertArrayHasKey('query', $data['meta']);
        $this->assertEquals('Test', $data['meta']['query']);
    }

    #[Test]
    public function test_search_companies_デフォルトリミットで動作する()
    {
        Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $request = Request::create('/api/search/companies', 'GET', [
            'q' => 'Test',
        ]);

        $response = $this->controller->searchCompanies($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('companies', $data['data']);
    }

    #[Test]
    public function test_search_companies_空のクエリでバリデーションエラーが返される()
    {
        $request = Request::create('/api/search/companies', 'GET', [
            'q' => '',
        ]);

        $response = $this->controller->searchCompanies($request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('details', $data);
        $this->assertEquals('検索クエリが無効です', $data['error']);
    }

    #[Test]
    public function test_search_companies_長すぎるクエリでバリデーションエラーが返される()
    {
        $request = Request::create('/api/search/companies', 'GET', [
            'q' => str_repeat('a', SearchConstants::MAX_QUERY_LENGTH + 1),
        ]);

        $response = $this->controller->searchCompanies($request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('details', $data);
    }

    #[Test]
    public function test_search_companies_無効なリミットでバリデーションエラーが返される()
    {
        $request = Request::create('/api/search/companies', 'GET', [
            'q' => 'test',
            'limit' => 0,
        ]);

        $response = $this->controller->searchCompanies($request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('details', $data);
    }

    #[Test]
    public function test_search_companies_最大リミットを超えるとバリデーションエラーが返される()
    {
        $request = Request::create('/api/search/companies', 'GET', [
            'q' => 'test',
            'limit' => 101,
        ]);

        $response = $this->controller->searchCompanies($request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('details', $data);
    }

    #[Test]
    public function test_search_companies_非アクティブな企業は検索されない()
    {
        Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => false,
        ]);

        $request = Request::create('/api/search/companies', 'GET', [
            'q' => 'Test',
        ]);

        $response = $this->controller->searchCompanies($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals(0, $data['meta']['total_results']);
    }

    #[Test]
    public function test_search_companies_企業名でマッチする()
    {
        Company::factory()->create([
            'name' => 'Test Company',
            'domain' => 'other.com',
            'description' => 'Other description',
            'is_active' => true,
        ]);

        $request = Request::create('/api/search/companies', 'GET', [
            'q' => 'Test',
        ]);

        $response = $this->controller->searchCompanies($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertGreaterThan(0, $data['meta']['total_results']);
    }

    #[Test]
    public function test_search_companies_ドメインでマッチする()
    {
        Company::factory()->create([
            'name' => 'Other Company',
            'domain' => 'test.com',
            'description' => 'Other description',
            'is_active' => true,
        ]);

        $request = Request::create('/api/search/companies', 'GET', [
            'q' => 'test',
        ]);

        $response = $this->controller->searchCompanies($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertGreaterThan(0, $data['meta']['total_results']);
    }

    #[Test]
    public function test_search_companies_説明文でマッチする()
    {
        Company::factory()->create([
            'name' => 'Other Company',
            'domain' => 'other.com',
            'description' => 'Test description',
            'is_active' => true,
        ]);

        $request = Request::create('/api/search/companies', 'GET', [
            'q' => 'Test',
        ]);

        $response = $this->controller->searchCompanies($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertGreaterThan(0, $data['meta']['total_results']);
    }

    #[Test]
    public function test_search_companies_結果が見つからない場合()
    {
        Company::factory()->create([
            'name' => 'Other Company',
            'domain' => 'other.com',
            'description' => 'Other description',
            'is_active' => true,
        ]);

        $request = Request::create('/api/search/companies', 'GET', [
            'q' => 'NonExistent',
        ]);

        $response = $this->controller->searchCompanies($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals(0, $data['meta']['total_results']);
    }

    #[Test]
    public function test_search_companies_キャッシュが動作する()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $request = Request::create('/api/search/companies', 'GET', [
            'q' => 'Test',
            'limit' => 10,
        ]);

        // 初回実行
        $response1 = $this->controller->searchCompanies($request);
        $this->assertEquals(200, $response1->getStatusCode());

        // 2回目実行（キャッシュから取得）
        $response2 = $this->controller->searchCompanies($request);
        $this->assertEquals(200, $response2->getStatusCode());

        // 同じ結果が返されることを確認
        $this->assertEquals(
            $response1->getData(true),
            $response2->getData(true)
        );
    }

    // ===========================================
    // searchArticles() メソッドのテスト
    // ===========================================

    #[Test]
    public function test_search_articles_正常なリクエストで記事検索が実行される()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();
        $article = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'title' => 'Laravel Test Article',
            'author_name' => 'test_author',
            'engagement_count' => 50,
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $request = Request::create('/api/search/articles', 'GET', [
            'q' => 'Laravel',
            'limit' => 20,
            'days' => 30,
            'min_engagement' => 10,
        ]);

        $response = $this->controller->searchArticles($request);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('articles', $data['data']);
        $this->assertArrayHasKey('filters', $data['meta']);
        $this->assertEquals('Laravel', $data['meta']['query']);
        $this->assertEquals(30, $data['meta']['filters']['days']);
        $this->assertEquals(10, $data['meta']['filters']['min_engagement']);
    }

    #[Test]
    public function test_search_articles_デフォルトパラメータで動作する()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();
        $article = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'title' => 'Test Article',
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $request = Request::create('/api/search/articles', 'GET', [
            'q' => 'Test',
        ]);

        $response = $this->controller->searchArticles($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('articles', $data['data']);
        $this->assertArrayHasKey('filters', $data['meta']);
    }

    #[Test]
    public function test_search_articles_空のクエリでバリデーションエラーが返される()
    {
        $request = Request::create('/api/search/articles', 'GET', [
            'q' => '',
        ]);

        $response = $this->controller->searchArticles($request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('details', $data);
        $this->assertEquals('検索クエリが無効です', $data['error']);
    }

    #[Test]
    public function test_search_articles_無効な日数でバリデーションエラーが返される()
    {
        $request = Request::create('/api/search/articles', 'GET', [
            'q' => 'test',
            'days' => 0,
        ]);

        $response = $this->controller->searchArticles($request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('details', $data);
    }

    #[Test]
    public function test_search_articles_最大日数を超えるとバリデーションエラーが返される()
    {
        $request = Request::create('/api/search/articles', 'GET', [
            'q' => 'test',
            'days' => 366,
        ]);

        $response = $this->controller->searchArticles($request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('details', $data);
    }

    #[Test]
    public function test_search_articles_無効なmin_engagementでバリデーションエラーが返される()
    {
        $request = Request::create('/api/search/articles', 'GET', [
            'q' => 'test',
            'min_engagement' => -1,
        ]);

        $response = $this->controller->searchArticles($request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('details', $data);
    }

    #[Test]
    public function test_search_articles_タイトルでマッチする()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();
        $article = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'title' => 'Test Article',
            'author_name' => 'other_author',
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $request = Request::create('/api/search/articles', 'GET', [
            'q' => 'Test',
        ]);

        $response = $this->controller->searchArticles($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertGreaterThan(0, $data['meta']['total_results']);
    }

    #[Test]
    public function test_search_articles_著者名でマッチする()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();
        $article = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'title' => 'Other Article',
            'author_name' => 'test_author',
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $request = Request::create('/api/search/articles', 'GET', [
            'q' => 'test',
        ]);

        $response = $this->controller->searchArticles($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertGreaterThan(0, $data['meta']['total_results']);
    }

    #[Test]
    public function test_search_articles_最小ブックマーク数フィルタが動作する()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        // 低いブックマーク数の記事
        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'title' => 'Test Article 1',
            'engagement_count' => 5,
            'published_at' => Carbon::now()->subDays(5),
        ]);

        // 高いブックマーク数の記事
        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'title' => 'Test Article 2',
            'engagement_count' => 50,
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $request = Request::create('/api/search/articles', 'GET', [
            'q' => 'Test',
            'min_engagement' => 20,
        ]);

        $response = $this->controller->searchArticles($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals(1, $data['meta']['total_results']);
    }

    #[Test]
    public function test_search_articles_日数フィルタが動作する()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        // 最近の記事
        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'title' => 'Test Article Recent',
            'published_at' => Carbon::now()->subDays(5),
        ]);

        // 古い記事
        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'title' => 'Test Article Old',
            'published_at' => Carbon::now()->subDays(50),
        ]);

        $request = Request::create('/api/search/articles', 'GET', [
            'q' => 'Test',
            'days' => 10,
        ]);

        $response = $this->controller->searchArticles($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals(1, $data['meta']['total_results']);
    }

    #[Test]
    public function test_search_articles_結果が見つからない場合()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();
        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'title' => 'Other Article',
            'author_name' => 'other_author',
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $request = Request::create('/api/search/articles', 'GET', [
            'q' => 'NonExistent',
        ]);

        $response = $this->controller->searchArticles($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals(0, $data['meta']['total_results']);
    }

    // ===========================================
    // search() メソッドのテスト
    // ===========================================

    #[Test]
    public function test_search_統合検索が正常に実行される()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);
        $platform = Platform::factory()->create();
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
        $this->assertEquals('all', $data['meta']['type']);
        $this->assertEquals('Test', $data['meta']['query']);
    }

    #[Test]
    public function test_search_デフォルトタイプで動作する()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $request = Request::create('/api/search', 'GET', [
            'q' => 'Test',
        ]);

        $response = $this->controller->search($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('all', $data['meta']['type']);
    }

    #[Test]
    public function test_search_企業のみ検索が実行される()
    {
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
        $this->assertEquals('companies', $data['meta']['type']);
    }

    #[Test]
    public function test_search_記事のみ検索が実行される()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();
        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'title' => 'Test Article',
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $request = Request::create('/api/search', 'GET', [
            'q' => 'Test',
            'type' => 'articles',
        ]);

        $response = $this->controller->search($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('articles', $data['data']);
        $this->assertArrayNotHasKey('companies', $data['data']);
        $this->assertEquals('articles', $data['meta']['type']);
    }

    #[Test]
    public function test_search_空のクエリでバリデーションエラーが返される()
    {
        $request = Request::create('/api/search', 'GET', [
            'q' => '',
        ]);

        $response = $this->controller->search($request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('details', $data);
        $this->assertEquals('検索クエリが無効です', $data['error']);
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
        $this->assertArrayHasKey('details', $data);
    }

    #[Test]
    public function test_search_総結果数の計算が正しい()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);
        $platform = Platform::factory()->create();
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

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals(2, $data['meta']['total_results']);
    }

    #[Test]
    public function test_search_結果が見つからない場合()
    {
        $request = Request::create('/api/search', 'GET', [
            'q' => 'NonExistent',
            'type' => 'all',
        ]);

        $response = $this->controller->search($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals(0, $data['meta']['total_results']);
    }

    // ===========================================
    // calculateRelevanceScore() メソッドのテスト
    // ===========================================

    #[Test]
    public function test_calculate_relevance_score_企業名完全一致で最高スコアが返される()
    {
        $company = Company::factory()->create([
            'name' => 'TestExact',
            'domain' => 'testexact.com',
            'description' => 'テスト用企業です',
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $company, 'TestExact');

        $this->assertGreaterThanOrEqual(ScoringConstants::COMPANY_EXACT_MATCH_WEIGHT, $score);
    }

    #[Test]
    public function test_calculate_relevance_score_企業名部分一致でスコアが返される()
    {
        $company = Company::factory()->create([
            'name' => 'TestPartial Company',
            'domain' => 'other.com',
            'description' => 'Other description',
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $company, 'TestPartial');

        $this->assertGreaterThanOrEqual(ScoringConstants::COMPANY_PARTIAL_MATCH_WEIGHT, $score);
    }

    #[Test]
    public function test_calculate_relevance_score_ドメイン一致でスコアが加算される()
    {
        $company = Company::factory()->create([
            'name' => 'Other Company',
            'domain' => 'test-domain.com',
            'description' => 'Other description',
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $company, 'test');

        $this->assertGreaterThanOrEqual(ScoringConstants::COMPANY_DOMAIN_MATCH_WEIGHT, $score);
    }

    #[Test]
    public function test_calculate_relevance_score_説明文一致でスコアが加算される()
    {
        $company = Company::factory()->create([
            'name' => 'Other Company',
            'domain' => 'other.com',
            'description' => 'Test description here',
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $company, 'Test');

        $this->assertGreaterThanOrEqual(ScoringConstants::COMPANY_DESCRIPTION_MATCH_WEIGHT, $score);
    }

    #[Test]
    public function test_calculate_relevance_score_ランキングありでボーナススコアが加算される()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'domain' => 'test.com',
            'is_active' => true,
        ]);

        $ranking = CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'ranking_period' => 'weekly',
            'rank_position' => 1,
            'total_score' => 100,
            'calculated_at' => Carbon::now(),
        ]);

        $company->load('rankings');

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $company, 'Test');

        $this->assertGreaterThan(ScoringConstants::COMPANY_PARTIAL_MATCH_WEIGHT, $score);
    }

    #[Test]
    public function test_calculate_relevance_score_nullドメインでもエラーにならない()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'domain' => 'example.com',
            'description' => 'Test description',
        ]);

        // ドメインをnullに設定してnullハンドリングをテスト
        $company->domain = null;

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $company, 'Test');

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
    }

    #[Test]
    public function test_calculate_relevance_score_null説明文でもエラーにならない()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'domain' => 'test.com',
            'description' => 'Test description',
        ]);

        // 説明文をnullに設定してnullハンドリングをテスト
        $company->description = null;

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $company, 'Test');

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
    }

    #[Test]
    public function test_calculate_relevance_score_ランキングなしでもエラーにならない()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'domain' => 'test.com',
            'description' => 'Test description',
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $company, 'Test');

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
    }

    #[Test]
    public function test_calculate_relevance_score_複数の要素で高スコアが返される()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'domain' => 'test.com',
            'description' => 'Test description',
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $company, 'Test');

        $expectedScore = ScoringConstants::COMPANY_PARTIAL_MATCH_WEIGHT +
                        ScoringConstants::COMPANY_DOMAIN_MATCH_WEIGHT +
                        ScoringConstants::COMPANY_DESCRIPTION_MATCH_WEIGHT;

        $this->assertGreaterThanOrEqual($expectedScore, $score);
    }

    #[Test]
    public function test_calculate_relevance_score_大文字小文字を区別しない()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'domain' => 'test.com',
            'description' => 'Test description',
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score1 = $method->invoke($this->controller, $company, 'test');
        $score2 = $method->invoke($this->controller, $company, 'TEST');
        $score3 = $method->invoke($this->controller, $company, 'Test');

        $this->assertEquals($score1, $score2);
        $this->assertEquals($score1, $score3);
    }

    // ===========================================
    // calculateArticleRelevanceScore() メソッドのテスト
    // ===========================================

    #[Test]
    public function test_calculate_article_relevance_score_タイトル一致でスコアが返される()
    {
        $article = Article::factory()->create([
            'title' => 'Test Article Title',
            'author_name' => 'other_author',
            'engagement_count' => 5,
            'published_at' => Carbon::now()->subDays(50),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, 'Test');

        $this->assertGreaterThanOrEqual(ScoringConstants::ARTICLE_TITLE_MATCH_WEIGHT, $score);
    }

    #[Test]
    public function test_calculate_article_relevance_score_著者名一致でスコアが返される()
    {
        $article = Article::factory()->create([
            'title' => 'Other Article',
            'author_name' => 'test_author',
            'engagement_count' => 5,
            'published_at' => Carbon::now()->subDays(50),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, 'test');

        $this->assertGreaterThanOrEqual(ScoringConstants::ARTICLE_AUTHOR_MATCH_WEIGHT, $score);
    }

    #[Test]
    public function test_calculate_article_relevance_score_高ブックマーク数でスコアが加算される()
    {
        $article = Article::factory()->create([
            'title' => 'Test Article',
            'engagement_count' => 150,
            'published_at' => Carbon::now()->subDays(50),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, 'Test');

        $expectedScore = ScoringConstants::ARTICLE_TITLE_MATCH_WEIGHT +
                        ScoringConstants::ARTICLE_HIGH_BOOKMARK_WEIGHT;

        $this->assertGreaterThanOrEqual($expectedScore, $score);
    }

    #[Test]
    public function test_calculate_article_relevance_score_中ブックマーク数でスコアが加算される()
    {
        $article = Article::factory()->create([
            'title' => 'Test Article',
            'engagement_count' => 75,
            'published_at' => Carbon::now()->subDays(50),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, 'Test');

        $expectedScore = ScoringConstants::ARTICLE_TITLE_MATCH_WEIGHT +
                        ScoringConstants::ARTICLE_MEDIUM_BOOKMARK_WEIGHT;

        $this->assertGreaterThanOrEqual($expectedScore, $score);
    }

    #[Test]
    public function test_calculate_article_relevance_score_低ブックマーク数でスコアが加算される()
    {
        $article = Article::factory()->create([
            'title' => 'Test Article',
            'engagement_count' => 15,
            'published_at' => Carbon::now()->subDays(50),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, 'Test');

        $expectedScore = ScoringConstants::ARTICLE_TITLE_MATCH_WEIGHT +
                        ScoringConstants::ARTICLE_LOW_BOOKMARK_WEIGHT;

        $this->assertGreaterThanOrEqual($expectedScore, $score);
    }

    #[Test]
    public function test_calculate_article_relevance_score_最近の記事でボーナススコアが加算される()
    {
        $article = Article::factory()->create([
            'title' => 'Test Article',
            'engagement_count' => 5,
            'published_at' => Carbon::now()->subDays(3),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, 'Test');

        $expectedScore = ScoringConstants::ARTICLE_TITLE_MATCH_WEIGHT +
                        ScoringConstants::ARTICLE_RECENT_BONUS_WEIGHT;

        $this->assertGreaterThanOrEqual($expectedScore, $score);
    }

    #[Test]
    public function test_calculate_article_relevance_score_やや最近の記事でボーナススコアが加算される()
    {
        $article = Article::factory()->create([
            'title' => 'Test Article',
            'engagement_count' => 5,
            'published_at' => Carbon::now()->subDays(20),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, 'Test');

        $expectedScore = ScoringConstants::ARTICLE_TITLE_MATCH_WEIGHT +
                        ScoringConstants::ARTICLE_SOMEWHAT_RECENT_BONUS_WEIGHT;

        $this->assertGreaterThanOrEqual($expectedScore, $score);
    }

    #[Test]
    public function test_calculate_article_relevance_score_古い記事でペナルティスコアが適用される()
    {
        $article = Article::factory()->create([
            'title' => 'Test Article',
            'engagement_count' => 5,
            'published_at' => Carbon::now()->subDays(150),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, 'Test');

        $expectedScore = ScoringConstants::ARTICLE_TITLE_MATCH_WEIGHT +
                        ScoringConstants::ARTICLE_OLD_PENALTY_WEIGHT;

        $this->assertEquals($expectedScore, $score);
    }

    #[Test]
    public function test_calculate_article_relevance_score_null著者名でもエラーにならない()
    {
        $article = Article::factory()->create([
            'title' => 'Test Article',
            'author_name' => 'test_author',
            'engagement_count' => 50,
            'published_at' => Carbon::now()->subDays(5),
        ]);

        // 著者名をnullに設定してnullハンドリングをテスト
        $article->author_name = null;

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, 'Test');

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
    }

    #[Test]
    public function test_calculate_article_relevance_score_新しい記事ほど高スコアが返される()
    {
        $recentArticle = Article::factory()->create([
            'title' => 'Test Article',
            'engagement_count' => 50,
            'published_at' => Carbon::now()->subDays(3),
        ]);

        $oldArticle = Article::factory()->create([
            'title' => 'Test Article',
            'engagement_count' => 50,
            'published_at' => Carbon::now()->subDays(150),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $recentScore = $method->invoke($this->controller, $recentArticle, 'Test');
        $oldScore = $method->invoke($this->controller, $oldArticle, 'Test');

        $this->assertGreaterThan($oldScore, $recentScore);
    }

    #[Test]
    public function test_calculate_article_relevance_score_ブックマーク数が多いほど高スコアが返される()
    {
        $highBookmarkArticle = Article::factory()->create([
            'title' => 'Test Article',
            'engagement_count' => 150,
            'published_at' => Carbon::now()->subDays(50),
        ]);

        $lowBookmarkArticle = Article::factory()->create([
            'title' => 'Test Article',
            'engagement_count' => 5,
            'published_at' => Carbon::now()->subDays(50),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $highScore = $method->invoke($this->controller, $highBookmarkArticle, 'Test');
        $lowScore = $method->invoke($this->controller, $lowBookmarkArticle, 'Test');

        $this->assertGreaterThan($lowScore, $highScore);
    }

    #[Test]
    public function test_calculate_article_relevance_score_大文字小文字を区別しない()
    {
        $article = Article::factory()->create([
            'title' => 'Test Article',
            'author_name' => 'Test Author',
            'engagement_count' => 50,
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score1 = $method->invoke($this->controller, $article, 'test');
        $score2 = $method->invoke($this->controller, $article, 'TEST');
        $score3 = $method->invoke($this->controller, $article, 'Test');

        $this->assertEquals($score1, $score2);
        $this->assertEquals($score1, $score3);
    }

    #[Test]
    public function test_calculate_article_relevance_score_複数の要素で高スコアが返される()
    {
        $article = Article::factory()->create([
            'title' => 'Test Article',
            'author_name' => 'test_author',
            'engagement_count' => 150,
            'published_at' => Carbon::now()->subDays(3),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, 'test');

        $expectedScore = ScoringConstants::ARTICLE_TITLE_MATCH_WEIGHT +
                        ScoringConstants::ARTICLE_AUTHOR_MATCH_WEIGHT +
                        ScoringConstants::ARTICLE_HIGH_BOOKMARK_WEIGHT +
                        ScoringConstants::ARTICLE_RECENT_BONUS_WEIGHT;

        $this->assertGreaterThanOrEqual($expectedScore, $score);
    }

    // ===========================================
    // 境界値テスト
    // ===========================================

    #[Test]
    public function test_scoring_constants_境界値でのブックマーク判定()
    {
        // 境界値での判定テスト
        $highArticle = Article::factory()->create([
            'title' => 'Test Article',
            'engagement_count' => ScoringConstants::HIGH_BOOKMARKS_THRESHOLD,
            'published_at' => Carbon::now()->subDays(50),
        ]);

        $mediumArticle = Article::factory()->create([
            'title' => 'Test Article',
            'engagement_count' => ScoringConstants::MEDIUM_BOOKMARKS_THRESHOLD,
            'published_at' => Carbon::now()->subDays(50),
        ]);

        $lowArticle = Article::factory()->create([
            'title' => 'Test Article',
            'engagement_count' => ScoringConstants::LOW_BOOKMARKS_THRESHOLD,
            'published_at' => Carbon::now()->subDays(50),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $highScore = $method->invoke($this->controller, $highArticle, 'Test');
        $mediumScore = $method->invoke($this->controller, $mediumArticle, 'Test');
        $lowScore = $method->invoke($this->controller, $lowArticle, 'Test');

        $this->assertGreaterThan($mediumScore, $highScore);
        $this->assertGreaterThan($lowScore, $mediumScore);
    }

    #[Test]
    public function test_scoring_constants_境界値での日付判定()
    {
        // 境界値での判定テスト
        $recentArticle = Article::factory()->create([
            'title' => 'Test Article',
            'engagement_count' => 5,
            'published_at' => Carbon::now()->subDays(ScoringConstants::RECENT_DAYS_THRESHOLD),
        ]);

        $somewhatRecentArticle = Article::factory()->create([
            'title' => 'Test Article',
            'engagement_count' => 5,
            'published_at' => Carbon::now()->subDays(ScoringConstants::SOMEWHAT_RECENT_DAYS_THRESHOLD),
        ]);

        $oldArticle = Article::factory()->create([
            'title' => 'Test Article',
            'engagement_count' => 5,
            'published_at' => Carbon::now()->subDays(ScoringConstants::OLD_DAYS_THRESHOLD + 1),
        ]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $recentScore = $method->invoke($this->controller, $recentArticle, 'Test');
        $somewhatRecentScore = $method->invoke($this->controller, $somewhatRecentArticle, 'Test');
        $oldScore = $method->invoke($this->controller, $oldArticle, 'Test');

        $this->assertGreaterThan($somewhatRecentScore, $recentScore);
        $this->assertGreaterThan($oldScore, $somewhatRecentScore);
    }

    #[Test]
    public function test_search_query_length_max_boundary()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $maxQuery = str_repeat('a', SearchConstants::MAX_QUERY_LENGTH);
        $request = Request::create('/api/search/companies', 'GET', [
            'q' => $maxQuery,
        ]);

        $response = $this->controller->searchCompanies($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals($maxQuery, $data['meta']['query']);
    }
}
