<?php

namespace Tests\Unit\Services;

use App\Constants\RankingPeriod;
use App\Models\Company;
use App\Models\CompanyRanking;
use App\Services\CompanyInfluenceScoreService;
use App\Services\CompanyRankingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyRankingServiceTest extends TestCase
{
    use RefreshDatabase;

    private CompanyRankingService $service;

    private CompanyInfluenceScoreService $scoreService;

    protected function setUp(): void
    {
        parent::setUp();

        // モックサービスを作成
        $this->scoreService = Mockery::mock(CompanyInfluenceScoreService::class);
        $this->service = new CompanyRankingService($this->scoreService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function generate_all_rankings_全期間のランキングを生成する()
    {
        // Arrange
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $mockScores = [
            (object) [
                'company_id' => $company1->id,
                'total_score' => 200.0,
                'article_count' => 10,
                'total_bookmarks' => 500,
            ],
            (object) [
                'company_id' => $company2->id,
                'total_score' => 100.0,
                'article_count' => 5,
                'total_bookmarks' => 250,
            ],
        ];

        $this->scoreService
            ->shouldReceive('calculateAllCompaniesScore')
            ->andReturn($mockScores);

        $referenceDate = Carbon::create(2024, 1, 7);

        // Act
        $results = $this->service->generateAllRankings($referenceDate);

        // Assert
        $this->assertIsArray($results);

        foreach (RankingPeriod::TYPES as $periodType => $days) {
            $this->assertArrayHasKey($periodType, $results);
            $this->assertIsArray($results[$periodType]);
        }
    }

    #[Test]
    public function generate_ranking_for_period_指定期間のランキングを生成する()
    {
        // Arrange
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $mockScores = [
            (object) [
                'company_id' => $company1->id,
                'total_score' => 200.0,
                'article_count' => 10,
                'total_bookmarks' => 500,
            ],
            (object) [
                'company_id' => $company2->id,
                'total_score' => 100.0,
                'article_count' => 5,
                'total_bookmarks' => 250,
            ],
        ];

        $this->scoreService
            ->shouldReceive('calculateAllCompaniesScore')
            ->once()
            ->andReturn($mockScores);

        Log::shouldReceive('info')->twice(); // 開始と終了のログ

        $referenceDate = Carbon::create(2024, 1, 7);

        // Act
        $rankings = $this->service->generateRankingForPeriod('1w', $referenceDate);

        // Assert
        $this->assertCount(2, $rankings);

        // 1位はcompany1（高スコア）
        $this->assertEquals($company1->id, $rankings[0]['company_id']);
        $this->assertEquals(1, $rankings[0]['rank_position']);
        $this->assertEquals(200.0, $rankings[0]['total_score']);

        // 2位はcompany2（低スコア）
        $this->assertEquals($company2->id, $rankings[1]['company_id']);
        $this->assertEquals(2, $rankings[1]['rank_position']);
        $this->assertEquals(100.0, $rankings[1]['total_score']);
    }

    #[Test]
    public function generate_ranking_for_period_同じスコアの場合同じ順位になる()
    {
        // Arrange
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $company3 = Company::factory()->create();

        $mockScores = [
            (object) [
                'company_id' => $company1->id,
                'total_score' => 200.0,
                'article_count' => 10,
                'total_bookmarks' => 500,
            ],
            (object) [
                'company_id' => $company2->id,
                'total_score' => 200.0, // 同じスコア
                'article_count' => 8,
                'total_bookmarks' => 400,
            ],
            (object) [
                'company_id' => $company3->id,
                'total_score' => 100.0,
                'article_count' => 5,
                'total_bookmarks' => 250,
            ],
        ];

        $this->scoreService
            ->shouldReceive('calculateAllCompaniesScore')
            ->once()
            ->andReturn($mockScores);

        Log::shouldReceive('info')->twice();

        // Act
        $rankings = $this->service->generateRankingForPeriod('1w');

        // Assert
        $this->assertCount(3, $rankings);

        // 1位と2位は同じ順位
        $this->assertEquals(1, $rankings[0]['rank_position']);
        $this->assertEquals(1, $rankings[1]['rank_position']);

        // 3位は順位が飛ぶ
        $this->assertEquals(3, $rankings[2]['rank_position']);
    }

    #[Test]
    public function get_ranking_for_period_指定期間のランキングを取得する()
    {
        // Arrange
        $company = Company::factory()->create();

        CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'ranking_period' => '1w',
            'rank_position' => 1,
            'total_score' => 200.0,
        ]);

        // Act
        $rankings = $this->service->getRankingForPeriod('1w', 10);

        // Assert
        $this->assertCount(1, $rankings);
        $this->assertEquals(1, $rankings[0]->rank_position);
        $this->assertEquals($company->name, $rankings[0]->company_name);
    }

    #[Test]
    public function get_ranking_for_period_非アクティブ企業は除外される()
    {
        // Arrange
        $activeCompany = Company::factory()->create(['is_active' => true]);
        $inactiveCompany = Company::factory()->create(['is_active' => false]);

        CompanyRanking::factory()->create([
            'company_id' => $activeCompany->id,
            'ranking_period' => '1w',
            'rank_position' => 1,
        ]);

        CompanyRanking::factory()->create([
            'company_id' => $inactiveCompany->id,
            'ranking_period' => '1w',
            'rank_position' => 2,
        ]);

        // Act
        $rankings = $this->service->getRankingForPeriod('1w');

        // Assert
        $this->assertCount(1, $rankings);
        $this->assertEquals($activeCompany->id, $rankings[0]->company_id);
    }

    #[Test]
    public function get_company_rankings_企業の全期間ランキングを取得する()
    {
        // Arrange
        $company = Company::factory()->create();

        CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'ranking_period' => '1w',
            'rank_position' => 1,
            'total_score' => 200.0,
        ]);

        CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'ranking_period' => '1m',
            'rank_position' => 3,
            'total_score' => 150.0,
        ]);

        // Act
        $rankings = $this->service->getCompanyRankings($company->id);

        // Assert
        $this->assertArrayHasKey('1w', $rankings);
        $this->assertArrayHasKey('1m', $rankings);

        $this->assertEquals(1, $rankings['1w']->rank_position);
        $this->assertEquals(3, $rankings['1m']->rank_position);
    }

    #[Test]
    public function get_company_rankings_ランキングがない期間はnullを返す()
    {
        // Arrange
        $company = Company::factory()->create();

        // 1w期間のランキングのみ作成
        CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'ranking_period' => '1w',
            'rank_position' => 1,
        ]);

        // Act
        $rankings = $this->service->getCompanyRankings($company->id);

        // Assert
        $this->assertNotNull($rankings['1w']);
        $this->assertNull($rankings['1m']);
        $this->assertNull($rankings['3m']);
    }

    #[Test]
    public function get_top_companies_ranking_history_上位企業の履歴を取得する()
    {
        // Arrange
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        // 上位企業のランキング
        CompanyRanking::factory()->create([
            'company_id' => $company1->id,
            'ranking_period' => '1w',
            'rank_position' => 1,
            'calculated_at' => now()->subDays(1),
        ]);

        CompanyRanking::factory()->create([
            'company_id' => $company2->id,
            'ranking_period' => '1w',
            'rank_position' => 2,
            'calculated_at' => now()->subDays(1),
        ]);

        // 下位企業（取得対象外）
        CompanyRanking::factory()->create([
            'company_id' => $company1->id,
            'ranking_period' => '1w',
            'rank_position' => 11,
            'calculated_at' => now()->subDays(2),
        ]);

        // Act
        $history = $this->service->getTopCompaniesRankingHistory(10, 30);

        // Assert
        $this->assertIsArray($history);

        // 上位10位以内のランキングのみ含まれる
        foreach ($history as $companyHistory) {
            foreach ($companyHistory as $periodHistory) {
                foreach ($periodHistory as $ranking) {
                    $this->assertLessThanOrEqual(10, $ranking->rank_position);
                }
            }
        }
    }

    #[Test]
    public function get_ranking_statistics_ランキング統計を取得する()
    {
        // Arrange
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        CompanyRanking::factory()->create([
            'company_id' => $company1->id,
            'ranking_period' => '1w',
            'total_score' => 200.0,
            'article_count' => 10,
            'total_bookmarks' => 500,
        ]);

        CompanyRanking::factory()->create([
            'company_id' => $company2->id,
            'ranking_period' => '1w',
            'total_score' => 100.0,
            'article_count' => 5,
            'total_bookmarks' => 250,
        ]);

        // Act
        $statistics = $this->service->getRankingStatistics();

        // Assert
        $this->assertArrayHasKey('1w', $statistics);

        $weeklyStats = $statistics['1w'];
        $this->assertEquals(2, $weeklyStats['total_companies']);
        $this->assertEquals(150.0, $weeklyStats['average_score']); // (200+100)/2
        $this->assertEquals(200.0, $weeklyStats['max_score']);
        $this->assertEquals(100.0, $weeklyStats['min_score']);
        $this->assertEquals(15, $weeklyStats['total_articles']); // 10+5
        $this->assertEquals(750, $weeklyStats['total_bookmarks']); // 500+250
    }

    #[Test]
    public function get_company_ranking_history_企業のランキング履歴を取得する()
    {
        // Arrange
        $company = Company::factory()->create();

        // 現在のランキング
        CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'ranking_period' => '1w',
            'rank_position' => 3,
            'calculated_at' => now()->subDays(1),
        ]);

        // 過去のランキング
        CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'ranking_period' => '1w',
            'rank_position' => 5,
            'calculated_at' => now()->subDays(2),
        ]);

        // 履歴期間外（取得対象外）
        CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'ranking_period' => '1w',
            'rank_position' => 10,
            'calculated_at' => now()->subDays(35),
        ]);

        // Act
        $history = $this->service->getCompanyRankingHistory($company->id, 30);

        // Assert
        $this->assertIsArray($history);
        $this->assertNotEmpty($history);

        // 最新のランキング履歴
        $latestHistory = $history[0];
        $this->assertEquals(3, $latestHistory['current_rank']);
        $this->assertEquals(5, $latestHistory['previous_rank']);
        $this->assertEquals(2, $latestHistory['rank_change']); // 5位から3位へ上昇
    }

    #[Test]
    public function calculate_period_dates_期間の開始日と終了日を正しく計算する()
    {
        // Arrange
        $referenceDate = Carbon::create(2024, 1, 15, 12, 0, 0);

        // Act & Assert - リフレクションで内部メソッドにアクセス
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculatePeriodDates');
        $method->setAccessible(true);

        // 1週間期間
        $weeklyPeriods = $method->invoke($this->service, '1w', $referenceDate);
        $this->assertEquals(
            Carbon::create(2024, 1, 8, 0, 0, 0),
            $weeklyPeriods['start']
        );
        $this->assertEquals(
            Carbon::create(2024, 1, 15, 23, 59, 59),
            $weeklyPeriods['end']
        );

        // 全期間
        $allTimePeriods = $method->invoke($this->service, 'all', $referenceDate);
        $this->assertEquals(2020, $allTimePeriods['start']->year); // ALL_TIME_START_YEAR
    }

    #[Test]
    public function save_rankings_ランキングをデータベースに保存する()
    {
        // Arrange
        $company = Company::factory()->create();

        $rankings = [
            [
                'company_id' => $company->id,
                'ranking_period' => '1w',
                'rank_position' => 1,
                'total_score' => 200.0,
                'article_count' => 10,
                'total_bookmarks' => 500,
                'period_start' => '2024-01-01',
                'period_end' => '2024-01-07',
                'calculated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Act - リフレクションで内部メソッドにアクセス
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('saveRankings');
        $method->setAccessible(true);
        $method->invoke($this->service, $rankings);

        // Assert
        $this->assertDatabaseHas('company_rankings', [
            'company_id' => $company->id,
            'ranking_period' => '1w',
            'rank_position' => 1,
            'total_score' => 200.0,
        ]);
    }

    #[Test]
    public function save_rankings_既存ランキングを更新する()
    {
        // Arrange
        $company = Company::factory()->create();

        // 既存のランキング
        CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'ranking_period' => '1w',
            'rank_position' => 2,
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-07',
        ]);

        $newRankings = [
            [
                'company_id' => $company->id,
                'ranking_period' => '1w',
                'rank_position' => 1, // 順位変更
                'total_score' => 200.0,
                'article_count' => 10,
                'total_bookmarks' => 500,
                'period_start' => '2024-01-01',
                'period_end' => '2024-01-07',
                'calculated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Act
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('saveRankings');
        $method->setAccessible(true);
        $method->invoke($this->service, $newRankings);

        // Assert
        $this->assertDatabaseHas('company_rankings', [
            'company_id' => $company->id,
            'ranking_period' => '1w',
            'rank_position' => 1, // 更新された順位
        ]);

        // 古いランキングは削除されている
        $this->assertDatabaseMissing('company_rankings', [
            'company_id' => $company->id,
            'ranking_period' => '1w',
            'rank_position' => 2,
        ]);
    }

    #[Test]
    public function generate_rankings_concurrently_並行処理でランキングを生成する()
    {
        // Arrange
        $company = Company::factory()->create();

        $this->scoreService
            ->shouldReceive('calculateAllCompaniesScore')
            ->andReturn([]);

        Log::shouldReceive('info')->atLeast(1);

        // Act
        $results = $this->service->generateRankingsConcurrently();

        // Assert
        $this->assertIsArray($results);

        foreach (RankingPeriod::getValidPeriods() as $periodType) {
            $this->assertArrayHasKey($periodType, $results);
        }
    }

    #[Test]
    public function generate_ranking_for_period_空のスコアでも正常に処理される()
    {
        // Arrange
        $this->scoreService
            ->shouldReceive('calculateAllCompaniesScore')
            ->once()
            ->andReturn([]);

        Log::shouldReceive('info')->twice();

        // Act
        $rankings = $this->service->generateRankingForPeriod('1w');

        // Assert
        $this->assertIsArray($rankings);
        $this->assertEmpty($rankings);
    }

    #[Test]
    public function create_rankings_スコア順でランキングを作成する()
    {
        // Arrange
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $company3 = Company::factory()->create();

        // スコアは意図的に順序を逆に
        $scores = [
            (object) [
                'company_id' => $company2->id,
                'total_score' => 100.0,
                'article_count' => 5,
                'total_bookmarks' => 250,
            ],
            (object) [
                'company_id' => $company1->id,
                'total_score' => 300.0,
                'article_count' => 15,
                'total_bookmarks' => 750,
            ],
            (object) [
                'company_id' => $company3->id,
                'total_score' => 200.0,
                'article_count' => 10,
                'total_bookmarks' => 500,
            ],
        ];

        $periods = [
            'start' => Carbon::create(2024, 1, 1),
            'end' => Carbon::create(2024, 1, 7),
        ];

        // Act
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('createRankings');
        $method->setAccessible(true);
        $rankings = $method->invoke($this->service, $scores, '1w', $periods);

        // Assert
        $this->assertCount(3, $rankings);

        // スコア順にソートされている
        $this->assertEquals($company1->id, $rankings[0]['company_id']); // 300.0
        $this->assertEquals(1, $rankings[0]['rank_position']);

        $this->assertEquals($company3->id, $rankings[1]['company_id']); // 200.0
        $this->assertEquals(2, $rankings[1]['rank_position']);

        $this->assertEquals($company2->id, $rankings[2]['company_id']); // 100.0
        $this->assertEquals(3, $rankings[2]['rank_position']);
    }
}
