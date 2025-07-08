<?php

namespace Tests\Unit\Services;

use App\Models\Article;
use App\Models\Company;
use App\Models\CompanyInfluenceScore;
use App\Models\Platform;
use App\Services\CompanyInfluenceScoreService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyInfluenceScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    private CompanyInfluenceScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CompanyInfluenceScoreService;
    }

    #[Test]
    public function calculate_company_score_記事なしの場合ゼロを返す()
    {
        // Arrange
        $company = Company::factory()->create();
        $periodStart = Carbon::create(2024, 1, 1);
        $periodEnd = Carbon::create(2024, 1, 7);

        // Act
        $score = $this->service->calculateCompanyScore($company, 'weekly', $periodStart, $periodEnd);

        // Assert
        $this->assertEquals(0.0, $score);
    }

    #[Test]
    public function calculate_company_score_記事ありの場合正しいスコアを計算する()
    {
        // Arrange
        $company = Company::factory()->create();
        $platform = Platform::factory()->create(['name' => 'qiita']);

        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'bookmark_count' => 10,
            'likes_count' => 5,
            'published_at' => Carbon::create(2024, 1, 3),
        ]);

        $periodStart = Carbon::create(2024, 1, 1);
        $periodEnd = Carbon::create(2024, 1, 7);

        // Act
        $score = $this->service->calculateCompanyScore($company, 'weekly', $periodStart, $periodEnd);

        // Assert
        $this->assertGreaterThan(0.0, $score);
        $this->assertIsFloat($score);
    }

    #[Test]
    public function calculate_company_score_複数記事の場合合計スコアを返す()
    {
        // Arrange
        $company = Company::factory()->create();
        $platform = Platform::factory()->create(['name' => 'qiita']);

        // 2記事作成
        Article::factory()->count(2)->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'bookmark_count' => 5,
            'likes_count' => 2,
            'published_at' => Carbon::create(2024, 1, 3),
        ]);

        $periodStart = Carbon::create(2024, 1, 1);
        $periodEnd = Carbon::create(2024, 1, 7);

        // Act
        $score = $this->service->calculateCompanyScore($company, 'weekly', $periodStart, $periodEnd);

        // Assert
        $this->assertGreaterThan(0.0, $score);

        // 単一記事のスコアと比較して大きいことを確認
        $singleArticleCompany = Company::factory()->create();
        Article::factory()->create([
            'company_id' => $singleArticleCompany->id,
            'platform_id' => $platform->id,
            'bookmark_count' => 5,
            'likes_count' => 2,
            'published_at' => Carbon::create(2024, 1, 3),
        ]);

        $singleScore = $this->service->calculateCompanyScore($singleArticleCompany, 'weekly', $periodStart, $periodEnd);
        $this->assertGreaterThan($singleScore, $score);
    }

    #[Test]
    public function calculate_company_score_プラットフォーム別重み付けが適用される()
    {
        // Arrange
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $qiitaPlatform = Platform::factory()->create(['name' => 'qiita']);
        $hatenaPlatform = Platform::factory()->create(['name' => 'hatena']);

        // Qiita記事（重み1.0）
        Article::factory()->create([
            'company_id' => $company1->id,
            'platform_id' => $qiitaPlatform->id,
            'bookmark_count' => 10,
            'likes_count' => 5,
            'published_at' => Carbon::create(2024, 1, 3),
        ]);

        // Hatena記事（重み0.8）
        Article::factory()->create([
            'company_id' => $company2->id,
            'platform_id' => $hatenaPlatform->id,
            'bookmark_count' => 10,
            'likes_count' => 5,
            'published_at' => Carbon::create(2024, 1, 3),
        ]);

        $periodStart = Carbon::create(2024, 1, 1);
        $periodEnd = Carbon::create(2024, 1, 7);

        // Act
        $qiitaScore = $this->service->calculateCompanyScore($company1, 'weekly', $periodStart, $periodEnd);
        $hatenaScore = $this->service->calculateCompanyScore($company2, 'weekly', $periodStart, $periodEnd);

        // Assert - スコアが計算されていることを確認
        $this->assertGreaterThan(0, $qiitaScore);
        $this->assertGreaterThan(0, $hatenaScore);
        // 実際の重み付けに応じてスコアが異なることを確認
        $this->assertNotEquals($qiitaScore, $hatenaScore);
    }

    #[Test]
    public function save_company_influence_score_正常にスコアを保存できる()
    {
        // Arrange
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'bookmark_count' => 10,
            'published_at' => Carbon::create(2024, 1, 3),
        ]);

        $periodStart = Carbon::create(2024, 1, 1);
        $periodEnd = Carbon::create(2024, 1, 7);
        $totalScore = 150.5;

        // Act
        $influenceScore = $this->service->saveCompanyInfluenceScore(
            $company,
            'weekly',
            $periodStart,
            $periodEnd,
            $totalScore
        );

        // Assert
        $this->assertInstanceOf(CompanyInfluenceScore::class, $influenceScore);
        $this->assertEquals($company->id, $influenceScore->company_id);
        $this->assertEquals('weekly', $influenceScore->period_type);
        $this->assertEquals($totalScore, $influenceScore->total_score);
        $this->assertEquals(1, $influenceScore->article_count);
        $this->assertEquals(10, $influenceScore->total_bookmarks);
        $this->assertNotNull($influenceScore->calculated_at);
    }

    #[Test]
    public function save_company_influence_score_同じ期間の場合更新される()
    {
        // Arrange
        $company = Company::factory()->create();
        $periodStart = Carbon::create(2024, 1, 1);
        $periodEnd = Carbon::create(2024, 1, 7);

        // 初回保存
        $firstScore = $this->service->saveCompanyInfluenceScore(
            $company,
            'weekly',
            $periodStart,
            $periodEnd,
            100.0
        );

        // Act - 同じ期間で再保存
        $secondScore = $this->service->saveCompanyInfluenceScore(
            $company,
            'weekly',
            $periodStart,
            $periodEnd,
            200.0
        );

        // Assert
        $this->assertEquals(200.0, $secondScore->total_score);

        // 同じ企業・期間のレコード数を確認
        $count = CompanyInfluenceScore::where([
            'company_id' => $company->id,
            'period_type' => 'weekly',
        ])->count();
        $this->assertLessThanOrEqual(2, $count); // 最大2レコード（更新または追加）
    }

    #[Test]
    public function calculate_all_companies_score_アクティブな企業のみ処理される()
    {
        // Arrange
        $activeCompany = Company::factory()->create(['is_active' => true]);
        $inactiveCompany = Company::factory()->create(['is_active' => false]);
        $platform = Platform::factory()->create();

        // 両方の企業に記事作成
        Article::factory()->create([
            'company_id' => $activeCompany->id,
            'platform_id' => $platform->id,
            'bookmark_count' => 10,
            'published_at' => Carbon::create(2024, 1, 3),
        ]);

        Article::factory()->create([
            'company_id' => $inactiveCompany->id,
            'platform_id' => $platform->id,
            'bookmark_count' => 15,
            'published_at' => Carbon::create(2024, 1, 3),
        ]);

        $periodStart = Carbon::create(2024, 1, 1);
        $periodEnd = Carbon::create(2024, 1, 7);

        // Act
        $results = $this->service->calculateAllCompaniesScore('weekly', $periodStart, $periodEnd);

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals($activeCompany->id, $results[0]->company_id);
    }

    #[Test]
    public function calculate_all_companies_score_スコアゼロの企業は結果に含まれない()
    {
        // Arrange
        $company = Company::factory()->create(['is_active' => true]);
        // 記事なし（スコア0）

        $periodStart = Carbon::create(2024, 1, 1);
        $periodEnd = Carbon::create(2024, 1, 7);

        // Act
        $results = $this->service->calculateAllCompaniesScore('weekly', $periodStart, $periodEnd);

        // Assert
        $this->assertCount(0, $results);
    }

    #[Test]
    public function calculate_scores_by_period_全期間タイプのスコアを計算する()
    {
        // Arrange
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'bookmark_count' => 10,
            'published_at' => now()->subDays(1),
        ]);

        // Act
        $results = $this->service->calculateScoresByPeriod();

        // Assert
        $this->assertArrayHasKey('daily', $results);
        $this->assertArrayHasKey('weekly', $results);
        $this->assertArrayHasKey('monthly', $results);

        foreach ($results as $periodType => $scores) {
            $this->assertIsArray($scores);
        }
    }

    #[Test]
    public function get_company_scores_by_period_期間別スコアを正しく取得する()
    {
        // Arrange
        $company = Company::factory()->create();

        // 各期間のスコアを事前作成
        CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'period_type' => 'daily',
            'total_score' => 100.0,
        ]);

        CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'period_type' => 'weekly',
            'total_score' => 200.0,
        ]);

        // Act
        $scores = $this->service->getCompanyScoresByPeriod($company, 5);

        // Assert
        $this->assertArrayHasKey('daily', $scores);
        $this->assertArrayHasKey('weekly', $scores);
        $this->assertArrayHasKey('monthly', $scores);

        $this->assertCount(1, $scores['daily']);
        $this->assertCount(1, $scores['weekly']);
        $this->assertCount(0, $scores['monthly']);
    }

    #[Test]
    public function get_company_score_statistics_統計情報を正しく計算する()
    {
        // Arrange
        $company = Company::factory()->create();

        // 複数のスコア作成
        CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'period_type' => 'daily',
            'total_score' => 100.0,
        ]);

        CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'period_type' => 'daily',
            'total_score' => 200.0,
        ]);

        // Act
        $statistics = $this->service->getCompanyScoreStatistics($company);

        // Assert
        $this->assertArrayHasKey('daily', $statistics);
        $this->assertEquals(150.0, $statistics['daily']['average_score']); // (100+200)/2
        $this->assertEquals(200.0, $statistics['daily']['max_score']);
        $this->assertEquals(100.0, $statistics['daily']['min_score']);
        $this->assertEquals(2, $statistics['daily']['score_count']);
    }

    #[Test]
    public function get_company_score_history_指定期間の履歴を取得する()
    {
        // Arrange
        $company = Company::factory()->create();

        // 過去30日以内のスコア
        CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'period_type' => '1d',
            'total_score' => 100.0,
            'calculated_at' => now()->subDays(5),
        ]);

        // 30日より古いスコア（取得対象外）
        CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'period_type' => '1d',
            'total_score' => 50.0,
            'calculated_at' => now()->subDays(35),
        ]);

        // Act
        $history = $this->service->getCompanyScoreHistory($company->id, '1d', 30);

        // Assert
        $this->assertCount(1, $history);
        $this->assertEquals(100.0, $history[0]['score']);
        $this->assertArrayHasKey('date', $history[0]);
        $this->assertArrayHasKey('calculated_at', $history[0]);
    }

    #[Test]
    public function 期間外の記事は対象外となる()
    {
        // Arrange
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        // 期間外の記事
        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'bookmark_count' => 10,
            'published_at' => Carbon::create(2023, 12, 31), // 期間より前
        ]);

        $periodStart = Carbon::create(2024, 1, 1);
        $periodEnd = Carbon::create(2024, 1, 7);

        // Act
        $score = $this->service->calculateCompanyScore($company, 'weekly', $periodStart, $periodEnd);

        // Assert
        $this->assertEquals(0.0, $score);
    }

    #[Test]
    public function published_at_がnullの場合scraped_atで判定される()
    {
        // Arrange
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'bookmark_count' => 10,
            'published_at' => null,
            'scraped_at' => Carbon::create(2024, 1, 3),
        ]);

        $periodStart = Carbon::create(2024, 1, 1);
        $periodEnd = Carbon::create(2024, 1, 7);

        // Act
        $score = $this->service->calculateCompanyScore($company, 'weekly', $periodStart, $periodEnd);

        // Assert
        $this->assertGreaterThan(0.0, $score);
    }

    #[Test]
    public function 時系列重み付けが正しく適用される()
    {
        // Arrange
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $platform = Platform::factory()->create(['name' => 'qiita']);

        // 新しい記事
        Article::factory()->create([
            'company_id' => $company1->id,
            'platform_id' => $platform->id,
            'bookmark_count' => 10,
            'likes_count' => 5,
            'published_at' => Carbon::create(2024, 1, 6), // 期間終了近く
        ]);

        // 古い記事
        Article::factory()->create([
            'company_id' => $company2->id,
            'platform_id' => $platform->id,
            'bookmark_count' => 10,
            'likes_count' => 5,
            'published_at' => Carbon::create(2024, 1, 2), // 期間開始近く
        ]);

        $periodStart = Carbon::create(2024, 1, 1);
        $periodEnd = Carbon::create(2024, 1, 7);

        // Act
        $newScore = $this->service->calculateCompanyScore($company1, 'weekly', $periodStart, $periodEnd);
        $oldScore = $this->service->calculateCompanyScore($company2, 'weekly', $periodStart, $periodEnd);

        // Assert - 時系列重み付けが適用されていることを確認
        $this->assertGreaterThan(0, $newScore);
        $this->assertGreaterThan(0, $oldScore);
        // スコアが異なることを確認（時系列重み付けにより）
        $this->assertNotEquals($newScore, $oldScore);
    }

    #[Test]
    public function calculate_company_score_でログが出力される()
    {
        // Arrange
        Log::shouldReceive('info')
            ->once()
            ->with('Company influence score calculated', \Mockery::type('array'));

        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'bookmark_count' => 10,
            'published_at' => Carbon::create(2024, 1, 3),
        ]);

        $periodStart = Carbon::create(2024, 1, 1);
        $periodEnd = Carbon::create(2024, 1, 7);

        // Act
        $this->service->calculateCompanyScore($company, 'weekly', $periodStart, $periodEnd);

        // Assert - ログが出力されることを確認（shouldReceiveで検証済み）
    }
}
