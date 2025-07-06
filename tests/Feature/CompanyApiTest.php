<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Company;
use App\Models\CompanyRanking;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestData();
    }

    private function setupTestData()
    {
        $companies = Company::factory()->count(2)->create();
        $platform = Platform::factory()->create();

        foreach ($companies as $index => $company) {
            CompanyRanking::factory()->create([
                'company_id' => $company->id,
                'ranking_period' => '1m',
                'rank_position' => $index + 1,
                'total_score' => 100 - ($index * 10),
                'article_count' => 10 - $index,
                'total_bookmarks' => 1000 - ($index * 100),
                'period_start' => now()->subMonth()->startOfDay(),
                'period_end' => now()->endOfDay(),
                'calculated_at' => now(),
            ]);

            Article::factory()->count(2)->create([
                'company_id' => $company->id,
                'platform_id' => $platform->id,
                'published_at' => now()->subDays(rand(1, 30)),
                'bookmark_count' => rand(10, 100),
                'likes_count' => rand(0, 50),
            ]);
        }
    }

    public function test_get_company_detail()
    {
        $company = Company::first();
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
                    'current_rankings' => [
                        '*' => [
                            'period',
                            'rank_position',
                            'total_score',
                            'article_count',
                            'total_bookmarks',
                            'calculated_at',
                        ],
                    ],
                    'recent_articles' => [
                        '*' => [
                            'id',
                            'title',
                            'url',
                            'platform',
                            'published_at',
                            'bookmark_count',
                            'likes_count',
                        ],
                    ],
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.id', $company->id);
    }

    public function test_get_company_detail_with_invalid_id()
    {
        $response = $this->getJson('/api/companies/99999');

        $response->assertStatus(400)
            ->assertJsonStructure([
                'error',
                'details',
            ]);
    }

    public function test_get_company_articles()
    {
        $company = Company::first();
        $response = $this->getJson("/api/companies/{$company->id}/articles");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'url',
                        'domain',
                        'platform',
                        'author_name',
                        'author_url',
                        'published_at',
                        'bookmark_count',
                        'likes_count',
                        'company' => [
                            'id',
                            'name',
                            'domain',
                        ],
                        'scraped_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'company_id',
                    'filters' => [
                        'days',
                        'min_bookmarks',
                    ],
                ],
            ])
            ->assertJsonPath('meta.company_id', $company->id);
    }

    public function test_get_company_articles_with_pagination()
    {
        $company = Company::first();
        $response = $this->getJson("/api/companies/{$company->id}/articles?page=1&per_page=2");

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.current_page', 1);
    }

    public function test_get_company_articles_with_filters()
    {
        $company = Company::first();
        $response = $this->getJson("/api/companies/{$company->id}/articles?days=7&min_bookmarks=50");

        $response->assertStatus(200)
            ->assertJsonPath('meta.filters.days', 7)
            ->assertJsonPath('meta.filters.min_bookmarks', 50);
    }

    public function test_get_company_articles_with_invalid_company_id()
    {
        $response = $this->getJson('/api/companies/99999/articles');

        $response->assertStatus(400)
            ->assertJsonStructure([
                'error',
                'details',
            ]);
    }

    public function test_get_company_scores()
    {
        $company = Company::first();
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
            ])
            ->assertJsonPath('data.company_id', $company->id);
    }

    public function test_get_company_scores_with_parameters()
    {
        $company = Company::first();
        $response = $this->getJson("/api/companies/{$company->id}/scores?period=1w&days=14");

        $response->assertStatus(200)
            ->assertJsonPath('meta.period', '1w')
            ->assertJsonPath('meta.days', 14);
    }

    public function test_get_company_scores_with_invalid_company_id()
    {
        $response = $this->getJson('/api/companies/99999/scores');

        $response->assertStatus(400)
            ->assertJsonStructure([
                'error',
                'details',
            ]);
    }

    public function test_get_company_rankings()
    {
        $company = Company::first();
        $response = $this->getJson("/api/companies/{$company->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'company_id',
                    'rankings' => [
                        '*' => [
                            'rank_position',
                            'total_score',
                            'article_count',
                            'total_bookmarks',
                            'period_start',
                            'period_end',
                            'calculated_at',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.company_id', $company->id);
    }

    public function test_get_company_rankings_with_history()
    {
        $company = Company::first();
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

    public function test_get_company_rankings_with_invalid_company_id()
    {
        $response = $this->getJson('/api/companies/99999/rankings');

        $response->assertStatus(400)
            ->assertJsonStructure([
                'error',
                'details',
            ]);
    }

    public function test_api_rate_limiting()
    {
        $company = Company::first();

        for ($i = 0; $i < 65; $i++) {
            $response = $this->getJson("/api/companies/{$company->id}");

            if ($i < 60) {
                $response->assertStatus(200);
            } else {
                $response->assertStatus(429);
                break;
            }
        }
    }

    public function test_api_caching()
    {
        $company = Company::first();

        $response1 = $this->getJson("/api/companies/{$company->id}");
        $response2 = $this->getJson("/api/companies/{$company->id}");

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $this->assertEquals($response1->json(), $response2->json());
    }

    public function test_company_detail_includes_recent_articles()
    {
        $company = Company::first();
        $response = $this->getJson("/api/companies/{$company->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('recent_articles', $data);

        if (! empty($data['recent_articles'])) {
            $this->assertLessThanOrEqual(5, count($data['recent_articles']));

            foreach ($data['recent_articles'] as $article) {
                $this->assertArrayHasKey('title', $article);
                $this->assertArrayHasKey('url', $article);
                $this->assertArrayHasKey('platform', $article);
                $this->assertArrayHasKey('bookmark_count', $article);
            }
        }
    }

    public function test_company_detail_includes_current_rankings()
    {
        $company = Company::first();
        $response = $this->getJson("/api/companies/{$company->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('current_rankings', $data);

        if (! empty($data['current_rankings'])) {
            foreach ($data['current_rankings'] as $ranking) {
                $this->assertArrayHasKey('period', $ranking);
                $this->assertArrayHasKey('rank_position', $ranking);
                $this->assertArrayHasKey('total_score', $ranking);
                $this->assertArrayHasKey('article_count', $ranking);
                $this->assertArrayHasKey('total_bookmarks', $ranking);
            }
        }
    }

    public function test_company_articles_are_sorted_by_date()
    {
        $company = Company::first();
        $response = $this->getJson("/api/companies/{$company->id}/articles");

        $response->assertStatus(200);

        $articles = $response->json('data');

        if (count($articles) > 1) {
            for ($i = 1; $i < count($articles); $i++) {
                $this->assertLessThanOrEqual(
                    strtotime($articles[$i - 1]['published_at']),
                    strtotime($articles[$i]['published_at'])
                );
            }
        }
    }

    public function test_company_articles_filter_by_days()
    {
        $company = Company::first();
        $response = $this->getJson("/api/companies/{$company->id}/articles?days=7");

        $response->assertStatus(200);

        $articles = $response->json('data');
        $sevenDaysAgo = now()->subDays(7);

        foreach ($articles as $article) {
            $this->assertGreaterThanOrEqual(
                $sevenDaysAgo->toDateString(),
                date('Y-m-d', strtotime($article['published_at']))
            );
        }
    }

    public function test_company_articles_filter_by_min_bookmarks()
    {
        $company = Company::first();
        $response = $this->getJson("/api/companies/{$company->id}/articles?min_bookmarks=50");

        $response->assertStatus(200);

        $articles = $response->json('data');

        foreach ($articles as $article) {
            $this->assertGreaterThanOrEqual(50, $article['bookmark_count']);
        }
    }
}
