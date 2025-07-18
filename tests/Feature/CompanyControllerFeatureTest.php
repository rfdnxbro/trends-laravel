<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Company;
use App\Models\CompanyInfluenceScore;
use App\Models\CompanyRanking;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CompanyControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_企業一覧取得_apiで基本的なパラメータなしの場合に正常なレスポンスが返される()
    {
        Company::factory()->count(3)->create();

        $response = $this->getJson('/api/companies');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'domain',
                        'description',
                        'logo_url',
                        'website_url',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'filters',
                ],
            ]);
    }

    public function test_企業一覧取得_apiで検索パラメータが正しく動作する()
    {
        Company::factory()->create(['name' => 'Test Company A']);
        Company::factory()->create(['name' => 'Sample Company B']);

        $response = $this->getJson('/api/companies?search=Test');

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json();
        $this->assertEquals(1, $data['meta']['total']);
        $this->assertStringContainsString('Test', $data['data'][0]['name']);
    }

    public function test_企業一覧取得_apiでドメイン検索が正しく動作する()
    {
        Company::factory()->create(['domain' => 'example.com']);
        Company::factory()->create(['domain' => 'test.jp']);

        $response = $this->getJson('/api/companies?domain=example');

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json();
        $this->assertEquals(1, $data['meta']['total']);
        $this->assertStringContainsString('example.com', $data['data'][0]['domain']);
    }

    public function test_企業一覧取得_apiでアクティブ状態フィルタが正しく動作する()
    {
        Company::factory()->create(['is_active' => true]);
        Company::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/companies?is_active=1');

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json();
        $this->assertEquals(1, $data['meta']['total']);
        $this->assertTrue($data['data'][0]['is_active']);
    }

    public function test_企業一覧取得_apiでソート機能が正しく動作する()
    {
        Company::factory()->create(['name' => 'Z Company', 'created_at' => now()->subDay()]);
        Company::factory()->create(['name' => 'A Company', 'created_at' => now()]);

        $response = $this->getJson('/api/companies?sort_by=name&sort_order=asc');

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json();
        $this->assertEquals('A Company', $data['data'][0]['name']);
        $this->assertEquals('Z Company', $data['data'][1]['name']);
    }

    public function test_企業一覧取得_apiでページネーションが正しく動作する()
    {
        Company::factory()->count(25)->create();

        $response = $this->getJson('/api/companies?page=2&per_page=10');

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json();
        $this->assertEquals(2, $data['meta']['current_page']);
        $this->assertEquals(10, $data['meta']['per_page']);
        $this->assertEquals(25, $data['meta']['total']);
        $this->assertEquals(10, count($data['data']));
    }

    public function test_企業一覧取得_apiで無効なパラメータの場合にバリデーションエラーが返される()
    {
        $response = $this->getJson('/api/companies?page=invalid&per_page=-1');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'error' => 'リクエストパラメータが無効です',
            ]);
    }

    public function test_企業詳細取得_apiで有効な企業idの場合に正常なレスポンスが返される()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();
        Article::factory()->count(3)->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $response = $this->getJson("/api/companies/{$company->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'domain',
                    'description',
                    'logo_url',
                    'website_url',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_企業詳細取得_apiで存在しない企業idの場合に400が返される()
    {
        $response = $this->getJson('/api/companies/999');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'error' => '企業IDが無効です',
            ]);
    }

    public function test_企業詳細取得_apiで無効な企業idの場合にバリデーションエラーが返される()
    {
        $response = $this->getJson('/api/companies/invalid');

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function test_企業記事一覧取得_apiで有効な企業idの場合に正常なレスポンスが返される()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();
        Article::factory()->count(5)->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => now()->subDays(1),
        ]);

        $response = $this->getJson("/api/companies/{$company->id}/articles");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'url',
                        'bookmark_count',
                        'published_at',
                        'platform',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'company_id',
                    'filters',
                ],
            ]);
    }

    public function test_企業記事一覧取得_apiで存在しない企業idの場合に400が返される()
    {
        $response = $this->getJson('/api/companies/999/articles');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'error' => '企業IDが無効です',
            ]);
    }

    public function test_企業記事一覧取得_apiでページネーションパラメータが正しく動作する()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();
        Article::factory()->count(25)->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => now()->subDays(1),
        ]);

        $response = $this->getJson("/api/companies/{$company->id}/articles?page=2&per_page=10");

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json();
        $this->assertEquals(2, $data['meta']['current_page']);
        $this->assertEquals(10, $data['meta']['per_page']);
    }

    public function test_企業スコア履歴取得_apiで有効な企業idの場合に正常なレスポンスが返される()
    {
        $company = Company::factory()->create();
        CompanyInfluenceScore::factory()->count(3)->create([
            'company_id' => $company->id,
        ]);

        $response = $this->getJson("/api/companies/{$company->id}/scores");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'company_id',
                    'scores',
                ],
                'meta' => [
                    'period',
                    'days',
                    'total',
                ],
            ]);
    }

    public function test_企業スコア履歴取得_apiで存在しない企業idの場合に400が返される()
    {
        $response = $this->getJson('/api/companies/999/scores');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'error' => '企業IDが無効です',
            ]);
    }

    public function test_企業ランキング取得_apiで有効な企業idの場合に正常なレスポンスが返される()
    {
        $company = Company::factory()->create();
        CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'ranking_period' => '1d',
            'period_start' => now()->subDay()->format('Y-m-d'),
            'period_end' => now()->format('Y-m-d'),
        ]);

        $response = $this->getJson("/api/companies/{$company->id}/rankings");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'company_id',
                    'rankings',
                ],
            ]);
    }

    public function test_企業ランキング取得_apiで存在しない企業idの場合に400が返される()
    {
        $response = $this->getJson('/api/companies/999/rankings');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'error' => '企業IDが無効です',
            ]);
    }

    public function test_企業ランキング取得_apiで履歴を含む場合に履歴データも返される()
    {
        $company = Company::factory()->create();
        CompanyRanking::factory()->count(3)->create([
            'company_id' => $company->id,
            'ranking_period' => '1d',
        ]);

        $response = $this->getJson("/api/companies/{$company->id}/rankings?include_history=true");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'company_id',
                    'rankings',
                    'history',
                ],
            ]);
    }

    public function test_企業記事一覧取得_apiでmin_bookmarksフィルタが正しく動作する()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'bookmark_count' => 5,
            'published_at' => now()->subDays(1),
        ]);

        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'bookmark_count' => 15,
            'published_at' => now()->subDays(1),
        ]);

        $response = $this->getJson("/api/companies/{$company->id}/articles?min_bookmarks=10");

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json();
        $this->assertEquals(1, $data['meta']['total']);
    }

    public function test_企業記事一覧取得_apiでdaysフィルタが正しく動作する()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => now()->subDays(5),
        ]);

        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => now()->subDays(25),
        ]);

        $response = $this->getJson("/api/companies/{$company->id}/articles?days=7");

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json();
        $this->assertEquals(1, $data['meta']['total']);
    }
}
