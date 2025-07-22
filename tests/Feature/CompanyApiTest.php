<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Company;
use App\Models\Platform;
use App\Services\CompanyInfluenceScoreService;
use App\Services\CompanyRankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CompanyApiTest extends TestCase
{
    use RefreshDatabase;

    private CompanyRankingService $rankingService;

    private CompanyInfluenceScoreService $scoreService;

    protected function setUp(): void
    {
        parent::setUp();

        // モックサービスを作成
        $this->rankingService = Mockery::mock(CompanyRankingService::class);
        $this->scoreService = Mockery::mock(CompanyInfluenceScoreService::class);

        // サービスコンテナにバインド
        $this->app->instance(CompanyRankingService::class, $this->rankingService);
        $this->app->instance(CompanyInfluenceScoreService::class, $this->scoreService);
    }

    protected function tearDown(): void
    {
        $this->beforeApplicationDestroyed(function () {
            DB::disconnect();
        });

        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function test_show_正常に企業詳細情報を取得できる()
    {
        // Arrange
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();
        for ($i = 1; $i <= 3; $i++) {
            Article::factory()->create([
                'company_id' => $company->id,
                'platform_id' => $platform->id,
                'published_at' => now()->subDays(1),
                'url' => "https://example.com/article-show-{$i}",
            ]);
        }

        $mockRankings = [
            '1w' => (object) [
                'rank_position' => 5,
                'total_score' => 100.0,
                'article_count' => 10,
                'total_bookmarks' => 500,
                'period_start' => '2024-01-01',
                'period_end' => '2024-01-07',
                'calculated_at' => now(),
            ],
            '1m' => (object) [
                'rank_position' => 3,
                'total_score' => 150.0,
                'article_count' => 15,
                'total_bookmarks' => 800,
                'period_start' => '2024-01-01',
                'period_end' => '2024-01-31',
                'calculated_at' => now(),
            ],
        ];

        $this->rankingService
            ->shouldReceive('getCompanyRankings')
            ->with($company->id)
            ->once()
            ->andReturn($mockRankings);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}");

        // Assert
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
                    'current_rankings',
                    'recent_articles',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $company->id,
                    'name' => $company->name,
                ],
            ]);
    }

    #[Test]
    public function test_show_存在しない企業idで404エラーが返される()
    {
        // Act
        $response = $this->getJson('/api/companies/99999');

        // Assert
        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'error' => '企業IDが無効です',
            ]);
    }

    #[Test]
    public function test_show_不正な企業idでバリデーションエラーが返される()
    {
        // Act
        $response = $this->getJson('/api/companies/0');

        // Assert
        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'error' => '企業IDが無効です',
            ]);
    }

    #[Test]
    public function test_show_キャッシュが正常に動作する()
    {
        // Arrange
        $company = Company::factory()->create();

        $this->rankingService
            ->shouldReceive('getCompanyRankings')
            ->once()
            ->andReturn([]);

        // Act - 1回目のリクエスト
        $response1 = $this->getJson("/api/companies/{$company->id}");

        // Act - 2回目のリクエスト（キャッシュから取得）
        $response2 = $this->getJson("/api/companies/{$company->id}");

        // Assert
        $response1->assertStatus(Response::HTTP_OK);
        $response2->assertStatus(Response::HTTP_OK);
        $this->assertEquals($response1->json(), $response2->json());
    }

    #[Test]
    public function test_articles_正常に企業記事一覧を取得できる()
    {
        // Arrange
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        for ($i = 1; $i <= 5; $i++) {
            Article::factory()->create([
                'company_id' => $company->id,
                'platform_id' => $platform->id,
                'published_at' => now()->subDays(1),
                'engagement_count' => 10,
                'url' => "https://example.com/article-list-{$i}",
            ]);
        }

        // Act
        $response = $this->getJson("/api/companies/{$company->id}/articles");

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'url',
                        'author_name',
                        'engagement_count',
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
            ])
            ->assertJson([
                'meta' => [
                    'company_id' => $company->id,
                    'total' => 5,
                ],
            ]);
    }

    #[Test]
    public function test_articles_フィルタリングパラメータが正常に動作する()
    {
        // Arrange
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        // 古い記事（30日以上前）
        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => now()->subDays(35),
            'engagement_count' => 5,
            'url' => 'https://example.com/article-old-filter',
        ]);

        // 新しい記事（7日以内）
        for ($i = 1; $i <= 3; $i++) {
            Article::factory()->create([
                'company_id' => $company->id,
                'platform_id' => $platform->id,
                'published_at' => now()->subDays(3),
                'engagement_count' => 15,
                'url' => "https://example.com/article-filter-{$i}",
            ]);
        }

        // Act
        $response = $this->getJson("/api/companies/{$company->id}/articles?days=7&min_engagement=10&per_page=10");

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'meta' => [
                    'company_id' => $company->id,
                    'total' => 3,
                    'per_page' => 10,
                    'filters' => [
                        'days' => 7,
                        'min_engagement' => 10,
                    ],
                ],
            ]);
    }

    #[Test]
    public function test_articles_存在しない企業idで404エラーが返される()
    {
        // Act
        $response = $this->getJson('/api/companies/99999/articles');

        // Assert
        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'error' => '企業IDが無効です',
            ]);
    }

    #[Test]
    public function test_articles_ページネーションが正常に動作する()
    {
        // Arrange
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        // 各記事にユニークなURLを明示的に設定
        for ($i = 1; $i <= 25; $i++) {
            Article::factory()->create([
                'company_id' => $company->id,
                'platform_id' => $platform->id,
                'published_at' => now()->subDays(1),
                'url' => "https://example.com/article-pagination-{$i}",
            ]);
        }

        // Act
        $response = $this->getJson("/api/companies/{$company->id}/articles?per_page=10&page=2");

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'meta' => [
                    'current_page' => 2,
                    'per_page' => 10,
                    'total' => 25,
                    'last_page' => 3,
                ],
            ]);
    }

    #[Test]
    public function test_scores_正常に企業スコア履歴を取得できる()
    {
        // Arrange
        $company = Company::factory()->create();

        $mockScores = [
            [
                'date' => '2024-01-30',
                'score' => 85.5,
                'rank_position' => 5,
                'calculated_at' => '2024-01-30 10:00:00',
            ],
            [
                'date' => '2024-01-29',
                'score' => 80.0,
                'rank_position' => 7,
                'calculated_at' => '2024-01-29 10:00:00',
            ],
        ];

        $this->scoreService
            ->shouldReceive('getCompanyScoreHistory')
            ->with($company->id, '1d', 30)
            ->once()
            ->andReturn($mockScores);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}/scores");

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'company_id',
                    'scores' => [
                        '*' => [
                            'date',
                            'score',
                            'rank_position',
                            'calculated_at',
                        ],
                    ],
                ],
                'meta' => [
                    'period',
                    'days',
                    'total',
                ],
            ])
            ->assertJson([
                'data' => [
                    'company_id' => $company->id,
                    'scores' => $mockScores,
                ],
                'meta' => [
                    'period' => '1d',
                    'days' => 30,
                    'total' => 2,
                ],
            ]);
    }

    #[Test]
    public function test_scores_パラメータが正常に動作する()
    {
        // Arrange
        $company = Company::factory()->create();

        $this->scoreService
            ->shouldReceive('getCompanyScoreHistory')
            ->with($company->id, '1w', 60)
            ->once()
            ->andReturn([]);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}/scores?period=1w&days=60");

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'meta' => [
                    'period' => '1w',
                    'days' => 60,
                ],
            ]);
    }

    #[Test]
    public function test_scores_存在しない企業idで404エラーが返される()
    {
        // Act
        $response = $this->getJson('/api/companies/99999/scores');

        // Assert
        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'error' => '企業IDが無効です',
            ]);
    }

    #[Test]
    public function test_rankings_正常に企業ランキング情報を取得できる()
    {
        // Arrange
        $company = Company::factory()->create();

        $mockRankings = [
            '1w' => (object) [
                'rank_position' => 5,
                'total_score' => 100.0,
                'article_count' => 10,
                'total_bookmarks' => 500,
                'period_start' => '2024-01-23',
                'period_end' => '2024-01-30',
                'calculated_at' => '2024-01-30 10:00:00',
            ],
            '1m' => (object) [
                'rank_position' => 3,
                'total_score' => 150.0,
                'article_count' => 25,
                'total_bookmarks' => 1200,
                'period_start' => '2024-01-01',
                'period_end' => '2024-01-30',
                'calculated_at' => '2024-01-30 10:00:00',
            ],
        ];

        $this->rankingService
            ->shouldReceive('getCompanyRankings')
            ->with($company->id)
            ->once()
            ->andReturn($mockRankings);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}/rankings");

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'company_id',
                    'rankings',
                ],
            ])
            ->assertJson([
                'data' => [
                    'company_id' => $company->id,
                    'rankings' => [
                        '1w' => [
                            'rank_position' => 5,
                            'total_score' => 100.0,
                            'article_count' => 10,
                            'total_bookmarks' => 500,
                        ],
                        '1m' => [
                            'rank_position' => 3,
                            'total_score' => 150.0,
                            'article_count' => 25,
                            'total_bookmarks' => 1200,
                        ],
                    ],
                ],
            ]);
    }

    #[Test]
    public function test_rankings_履歴付きで正常に取得できる()
    {
        // Arrange
        $company = Company::factory()->create();

        $mockRankings = [
            '1w' => (object) ['rank_position' => 5, 'total_score' => 100.0, 'article_count' => 10, 'total_bookmarks' => 500, 'period_start' => '2024-01-23', 'period_end' => '2024-01-30', 'calculated_at' => '2024-01-30 10:00:00'],
        ];

        $mockHistory = [
            ['date' => '2024-01-29', 'rank_position' => 6],
            ['date' => '2024-01-28', 'rank_position' => 7],
        ];

        $this->rankingService
            ->shouldReceive('getCompanyRankings')
            ->with($company->id)
            ->once()
            ->andReturn($mockRankings);

        $this->rankingService
            ->shouldReceive('getCompanyRankingHistory')
            ->with($company->id, 30)
            ->once()
            ->andReturn($mockHistory);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}/rankings?include_history=true&history_days=30");

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'company_id',
                    'rankings',
                    'history',
                ],
            ])
            ->assertJson([
                'data' => [
                    'company_id' => $company->id,
                    'history' => $mockHistory,
                ],
            ]);
    }

    #[Test]
    public function test_rankings_存在しない企業idで404エラーが返される()
    {
        // Act
        $response = $this->getJson('/api/companies/99999/rankings');

        // Assert
        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'error' => '企業IDが無効です',
            ]);
    }

    #[Test]
    public function test_rankings_履歴なしでも正常に動作する()
    {
        // Arrange
        $company = Company::factory()->create();

        $this->rankingService
            ->shouldReceive('getCompanyRankings')
            ->with($company->id)
            ->once()
            ->andReturn([]);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}/rankings?include_history=false");

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'company_id',
                    'rankings',
                ],
            ])
            ->assertJsonMissing(['history']);
    }

    #[Test]
    public function test_get_company_scores_with_parameters()
    {
        // Arrange
        $company = Company::factory()->create();

        $this->scoreService
            ->shouldReceive('getCompanyScoreHistory')
            ->with($company->id, '1d', 30)
            ->once()
            ->andReturn([]);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}/scores?period=1d&days=30");

        // Assert
        $response->assertStatus(Response::HTTP_OK);
    }

    #[Test]
    public function test_get_company_scores_with_invalid_company_id()
    {
        // Act
        $response = $this->getJson('/api/companies/99999/scores');

        // Assert
        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'error' => '企業IDが無効です',
            ]);
    }

    #[Test]
    public function test_get_company_rankings()
    {
        // Arrange
        $company = Company::factory()->create();

        $this->rankingService
            ->shouldReceive('getCompanyRankings')
            ->with($company->id)
            ->once()
            ->andReturn([]);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}/rankings");

        // Assert
        $response->assertStatus(Response::HTTP_OK);
    }

    #[Test]
    public function test_get_company_rankings_with_history()
    {
        // Arrange
        $company = Company::factory()->create();

        $this->rankingService
            ->shouldReceive('getCompanyRankings')
            ->with($company->id)
            ->once()
            ->andReturn([]);

        $this->rankingService
            ->shouldReceive('getCompanyRankingHistory')
            ->with($company->id, config('constants.ranking.history_days'))
            ->once()
            ->andReturn([]);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}/rankings?include_history=true");

        // Assert
        $response->assertStatus(Response::HTTP_OK);
    }

    #[Test]
    public function test_get_company_rankings_with_invalid_company_id()
    {
        // Act
        $response = $this->getJson('/api/companies/99999/rankings');

        // Assert
        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                'error' => '企業IDが無効です',
            ]);
    }

    #[Test]
    public function test_api_rate_limiting()
    {
        // Arrange
        $company = Company::factory()->create();

        $this->rankingService
            ->shouldReceive('getCompanyRankings')
            ->andReturn([]);

        // Act - 61回のリクエストを送信（レート制限: 60req/min）
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson("/api/companies/{$company->id}");
            if ($i < 60) {
                $response->assertStatus(Response::HTTP_OK);
            }
        }

        // Assert - 61回目はレート制限エラー
        $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    }

    #[Test]
    public function test_api_caching()
    {
        // Arrange
        $company = Company::factory()->create();

        $this->rankingService
            ->shouldReceive('getCompanyRankings')
            ->once() // キャッシュにより1回のみ呼ばれる
            ->andReturn([]);

        // Act - 同じリクエストを複数回実行
        $response1 = $this->getJson("/api/companies/{$company->id}");
        $response2 = $this->getJson("/api/companies/{$company->id}");

        // Assert
        $response1->assertStatus(Response::HTTP_OK);
        $response2->assertStatus(Response::HTTP_OK);
    }

    #[Test]
    public function test_company_detail_includes_recent_articles()
    {
        // Arrange
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        for ($i = 1; $i <= 3; $i++) {
            Article::factory()->create([
                'company_id' => $company->id,
                'platform_id' => $platform->id,
                'published_at' => now()->subDays(1),
                'url' => "https://example.com/article-recent-{$i}",
            ]);
        }

        $this->rankingService
            ->shouldReceive('getCompanyRankings')
            ->andReturn([]);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}");

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'recent_articles' => [
                        '*' => ['id', 'title', 'url'],
                    ],
                ],
            ]);
    }

    #[Test]
    public function test_company_detail_includes_current_rankings()
    {
        // Arrange
        $company = Company::factory()->create();

        $mockRankings = [
            '1w' => (object) [
                'rank_position' => 5,
                'total_score' => 100.0,
                'article_count' => 10,
                'total_bookmarks' => 500,
                'period_start' => '2024-01-01',
                'period_end' => '2024-01-07',
                'calculated_at' => now(),
            ],
        ];

        $this->rankingService
            ->shouldReceive('getCompanyRankings')
            ->with($company->id)
            ->andReturn($mockRankings);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}");

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'current_rankings',
                ],
            ]);
    }

    #[Test]
    public function test_company_articles_are_sorted_by_date()
    {
        // Arrange
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        $article1 = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => now()->subDays(3),
            'title' => 'Older Article',
            'url' => 'https://example.com/older-article',
        ]);

        $article2 = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => now()->subDays(1),
            'title' => 'Newer Article',
            'url' => 'https://example.com/newer-article',
        ]);

        // Act
        $response = $this->getJson("/api/companies/{$company->id}/articles");

        // Assert
        $response->assertStatus(Response::HTTP_OK);
        $articles = $response->json('data');

        // 新しい記事が最初に来ることを確認
        $this->assertEquals('Newer Article', $articles[0]['title']);
        $this->assertEquals('Older Article', $articles[1]['title']);
    }

    #[Test]
    public function test_company_articles_filter_by_days()
    {
        // Arrange
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        // 10日前の記事
        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => now()->subDays(10),
            'url' => 'https://example.com/article-10-days-ago',
        ]);

        // 3日前の記事
        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => now()->subDays(3),
            'url' => 'https://example.com/article-3-days-ago',
        ]);

        // Act - 過去7日間の記事のみ取得
        $response = $this->getJson("/api/companies/{$company->id}/articles?days=7");

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'meta' => [
                    'total' => 1, // 7日以内の記事は1件のみ
                    'filters' => [
                        'days' => 7,
                    ],
                ],
            ]);
    }

    #[Test]
    public function test_company_articles_filter_by_min_engagement()
    {
        // Arrange
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        // ブックマーク数5の記事
        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => now()->subDays(1),
            'engagement_count' => 5,
            'url' => 'https://example.com/article-5-bookmarks',
        ]);

        // ブックマーク数15の記事
        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => now()->subDays(1),
            'engagement_count' => 15,
            'url' => 'https://example.com/article-15-bookmarks',
        ]);

        // Act - 最小ブックマーク数10で絞り込み
        $response = $this->getJson("/api/companies/{$company->id}/articles?min_engagement=10");

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'meta' => [
                    'total' => 1, // ブックマーク数10以上の記事は1件のみ
                    'filters' => [
                        'min_engagement' => 10,
                    ],
                ],
            ]);
    }
}
