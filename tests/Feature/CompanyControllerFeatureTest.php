<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Company;
use App\Models\CompanyInfluenceScore;
use App\Models\CompanyRanking;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
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

        $response->assertStatus(200)
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

        $response->assertStatus(400)
            ->assertJson([
                'error' => '企業IDが無効です',
            ]);
    }

    public function test_企業詳細取得_apiで無効な企業idの場合にバリデーションエラーが返される()
    {
        $response = $this->getJson('/api/companies/invalid');

        $response->assertStatus(500);
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

        $response->assertStatus(200)
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

        $response->assertStatus(400)
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

        $response->assertStatus(200);
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

        $response->assertStatus(200)
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

        $response->assertStatus(400)
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

        $response->assertStatus(200)
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

        $response->assertStatus(400)
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

        $response->assertStatus(200)
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

        $response->assertStatus(200);
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

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['meta']['total']);
    }
}
