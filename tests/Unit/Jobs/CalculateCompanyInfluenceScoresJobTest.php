<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CalculateCompanyInfluenceScoresJob;
use App\Services\CompanyInfluenceScoreService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CalculateCompanyInfluenceScoresJobTest extends TestCase
{
    // Removed RefreshDatabase trait to avoid transaction issues

    private CompanyInfluenceScoreService $scoreService;

    protected function setUp(): void
    {
        parent::setUp();

        // モックサービスを作成
        $this->scoreService = Mockery::mock(CompanyInfluenceScoreService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function test_handle_全期間タイプの計算が正常に実行される()
    {
        // Arrange
        $mockResults = [
            'daily' => [
                (object) ['total_score' => 100.0, 'company_id' => 1],
                (object) ['total_score' => 200.0, 'company_id' => 2],
            ],
            'weekly' => [
                (object) ['total_score' => 150.0, 'company_id' => 1],
            ],
            'monthly' => [],
        ];

        $this->scoreService
            ->shouldReceive('calculateScoresByPeriod')
            ->once()
            ->with(null)
            ->andReturn($mockResults);

        Log::shouldReceive('info')
            ->with('Company influence scores calculation job started', Mockery::type('array'))
            ->once();

        Log::shouldReceive('info')
            ->with('All periods calculation completed', Mockery::type('array'))
            ->once();

        Log::shouldReceive('info')
            ->with('Company influence scores calculation job completed', Mockery::type('array'))
            ->once();

        $job = new CalculateCompanyInfluenceScoresJob;

        // Act
        $job->handle($this->scoreService);

        // Assert - ジョブが正常に完了したことを確認
        $this->assertTrue(true); // Mockeryの期待値が満たされれば成功
    }

    #[Test]
    public function test_handle_特定期間タイプの計算が正常に実行される()
    {
        // Arrange
        $referenceDate = Carbon::create(2024, 1, 7);
        $periodType = 'weekly';

        $mockScores = [
            (object) ['total_score' => 100.0, 'company_id' => 1],
            (object) ['total_score' => 200.0, 'company_id' => 2],
        ];

        $this->scoreService
            ->shouldReceive('calculateAllCompaniesScore')
            ->once()
            ->with(
                $periodType,
                Mockery::type('Carbon\Carbon'),
                Mockery::type('Carbon\Carbon')
            )
            ->andReturn($mockScores);

        Log::shouldReceive('info')
            ->with('Company influence scores calculation job started', Mockery::type('array'))
            ->once();

        Log::shouldReceive('info')
            ->with('Specific period calculation completed', Mockery::type('array'))
            ->once();

        Log::shouldReceive('info')
            ->with('Company influence scores calculation job completed', Mockery::type('array'))
            ->once();

        $job = new CalculateCompanyInfluenceScoresJob($referenceDate, $periodType);

        // Act
        $job->handle($this->scoreService);

        // Assert - ジョブが正常に完了したことを確認
        $this->assertTrue(true); // Mockeryの期待値が満たされれば成功
    }

    #[Test]
    public function test_handle_基準日未指定の場合現在日時を使用する()
    {
        // Arrange
        $periodType = 'daily';

        $this->scoreService
            ->shouldReceive('calculateAllCompaniesScore')
            ->once()
            ->with(
                $periodType,
                Mockery::type('Carbon\Carbon'),
                Mockery::type('Carbon\Carbon')
            )
            ->andReturn([]);

        Log::shouldReceive('info')->atLeast(1);
        Log::shouldReceive('error')->atMost(1); // エラーログも許可

        $job = new CalculateCompanyInfluenceScoresJob(null, $periodType);

        // Act
        $job->handle($this->scoreService);

        // Assert - 基準日がnullでも正常に動作することを確認
        $this->assertTrue(true); // Mockeryの期待値が満たされれば成功
    }

    #[Test]
    public function test_handle_例外発生時にログを出力して再スローする()
    {
        // Arrange
        $exception = new \Exception('Test exception');

        $this->scoreService
            ->shouldReceive('calculateScoresByPeriod')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('info')
            ->with('Company influence scores calculation job started', Mockery::type('array'))
            ->once();

        Log::shouldReceive('error')
            ->with('Company influence scores calculation job failed', Mockery::type('array'))
            ->once();

        $job = new CalculateCompanyInfluenceScoresJob;

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');

        $job->handle($this->scoreService);
    }

    #[Test]
    public function test_handle_実行時間がログに記録される()
    {
        // Arrange
        $this->scoreService
            ->shouldReceive('calculateScoresByPeriod')
            ->once()
            ->andReturn([]);

        Log::shouldReceive('info')
            ->with('Company influence scores calculation job started', Mockery::type('array'))
            ->once();

        Log::shouldReceive('info')
            ->with('All periods calculation completed', Mockery::type('array'))
            ->once();

        Log::shouldReceive('info')
            ->with('Company influence scores calculation job completed', Mockery::on(function ($data) {
                return isset($data['execution_time']) && is_numeric($data['execution_time']);
            }))
            ->once();

        $job = new CalculateCompanyInfluenceScoresJob;

        // Act
        $job->handle($this->scoreService);

        // Assert - ログに実行時間が含まれることを確認
        $this->assertTrue(true); // Mockeryの期待値が満たされれば成功
    }

    #[Test]
    public function test_get_period_days_正しい日数を返す()
    {
        // Arrange
        $job = new CalculateCompanyInfluenceScoresJob;
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('getPeriodDays');
        $method->setAccessible(true);

        // Act & Assert
        $this->assertEquals(1, $method->invoke($job, 'daily'));
        $this->assertEquals(7, $method->invoke($job, 'weekly'));
        $this->assertEquals(30, $method->invoke($job, 'monthly'));
    }

    #[Test]
    public function test_get_period_days_無効な期間タイプで例外を発生する()
    {
        // Arrange
        $job = new CalculateCompanyInfluenceScoresJob;
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('getPeriodDays');
        $method->setAccessible(true);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid period type: invalid');

        $method->invoke($job, 'invalid');
    }

    #[Test]
    public function test_failed_失敗時のログを出力する()
    {
        // Arrange
        $exception = new \Exception('Test failure');
        $referenceDate = Carbon::create(2024, 1, 7);
        $periodType = 'weekly';

        Log::shouldReceive('error')
            ->with('Company influence scores calculation job failed permanently', Mockery::on(function ($data) use ($exception, $referenceDate, $periodType) {
                return $data['exception'] === $exception->getMessage() &&
                       $data['reference_date'] === $referenceDate->toDateString() &&
                       $data['period_type'] === $periodType &&
                       isset($data['trace']);
            }))
            ->once();

        $job = new CalculateCompanyInfluenceScoresJob($referenceDate, $periodType);

        // Act
        $job->failed($exception);

        // Assert - 失敗ログが出力されることを確認
        $this->assertTrue(true); // Mockeryの期待値が満たされれば成功
    }

    #[Test]
    public function test_unique_id_正しい識別子を生成する()
    {
        // Arrange & Act
        $referenceDate = Carbon::create(2024, 1, 7);
        $periodType = 'weekly';

        $job1 = new CalculateCompanyInfluenceScoresJob($referenceDate, $periodType);
        $job2 = new CalculateCompanyInfluenceScoresJob(null, null);
        $job3 = new CalculateCompanyInfluenceScoresJob($referenceDate, null);

        // Assert
        $this->assertEquals('company_influence_scores_2024-01-07_weekly', $job1->uniqueId());
        $this->assertEquals('company_influence_scores_now_all', $job2->uniqueId());
        $this->assertEquals('company_influence_scores_2024-01-07_all', $job3->uniqueId());
    }

    #[Test]
    public function test_job_プロパティが正しく設定されている()
    {
        // Arrange
        $job = new CalculateCompanyInfluenceScoresJob;

        // Assert
        $this->assertEquals(300, $job->timeout);
        $this->assertEquals(3, $job->tries);
    }

    #[Test]
    public function test_constructor_パラメータが正しく設定される()
    {
        // Arrange
        $referenceDate = Carbon::create(2024, 1, 7);
        $periodType = 'weekly';

        // Act
        $job = new CalculateCompanyInfluenceScoresJob($referenceDate, $periodType);

        // Assert - リフレクションでプライベートプロパティにアクセス
        $reflection = new \ReflectionClass($job);

        $referenceDateProperty = $reflection->getProperty('referenceDate');
        $referenceDateProperty->setAccessible(true);
        $this->assertEquals($referenceDate, $referenceDateProperty->getValue($job));

        $periodTypeProperty = $reflection->getProperty('periodType');
        $periodTypeProperty->setAccessible(true);
        $this->assertEquals($periodType, $periodTypeProperty->getValue($job));
    }

    #[Test]
    public function test_calculate_specific_period_正しいパラメータでサービスを呼び出す()
    {
        // Arrange
        $referenceDate = Carbon::create(2024, 1, 10);
        $periodType = 'monthly';

        $this->scoreService
            ->shouldReceive('calculateAllCompaniesScore')
            ->once()
            ->with(
                $periodType,
                Mockery::type('Carbon\Carbon'),
                Mockery::type('Carbon\Carbon')
            )
            ->andReturn([]);

        Log::shouldReceive('info')->atLeast(1);

        $job = new CalculateCompanyInfluenceScoresJob($referenceDate, $periodType);

        // Act
        $job->handle($this->scoreService);

        // Assert - サービスが正しいパラメータで呼ばれることを確認
        $this->assertTrue(true); // Mockeryの期待値が満たされれば成功
    }

    #[Test]
    public function test_calculate_all_periods_正しいパラメータでサービスを呼び出す()
    {
        // Arrange
        $referenceDate = Carbon::create(2024, 1, 7);

        $this->scoreService
            ->shouldReceive('calculateScoresByPeriod')
            ->once()
            ->with($referenceDate)
            ->andReturn([
                'daily' => [],
                'weekly' => [],
                'monthly' => [],
            ]);

        Log::shouldReceive('info')->atLeast(1);

        $job = new CalculateCompanyInfluenceScoresJob($referenceDate);

        // Act
        $job->handle($this->scoreService);

        // Assert - サービスが正しいパラメータで呼ばれることを確認
        $this->assertTrue(true); // Mockeryの期待値が満たされれば成功
    }

    #[Test]
    public function test_calculate_all_periods_処理企業数が正しくログに記録される()
    {
        // Arrange
        $mockResults = [
            'daily' => [
                (object) ['total_score' => 100.0, 'company_id' => 1],
                (object) ['total_score' => 200.0, 'company_id' => 2],
            ],
            'weekly' => [
                (object) ['total_score' => 150.0, 'company_id' => 1],
            ],
            'monthly' => [],
        ];

        $this->scoreService
            ->shouldReceive('calculateScoresByPeriod')
            ->once()
            ->andReturn($mockResults);

        Log::shouldReceive('info')
            ->with('Company influence scores calculation job started', Mockery::type('array'))
            ->once();

        Log::shouldReceive('info')
            ->with('All periods calculation completed', Mockery::on(function ($data) {
                return $data['periods_calculated'] === 3 &&
                       $data['total_companies_processed'] === 3; // 2 + 1 + 0
            }))
            ->once();

        Log::shouldReceive('info')
            ->with('Company influence scores calculation job completed', Mockery::type('array'))
            ->once();

        $job = new CalculateCompanyInfluenceScoresJob;

        // Act
        $job->handle($this->scoreService);

        // Assert - 処理企業数が正しくカウントされることを確認
        $this->assertTrue(true); // Mockeryの期待値が満たされれば成功
    }
}
