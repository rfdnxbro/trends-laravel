<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GenerateCompanyRankingsJob;
use App\Services\CompanyRankingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerateCompanyRankingsJobTest extends TestCase
{
    // Removed RefreshDatabase trait to avoid transaction issues

    private CompanyRankingService $rankingService;

    protected function setUp(): void
    {
        parent::setUp();

        // モックサービスを作成
        $this->rankingService = Mockery::mock(CompanyRankingService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function test_handle_特定期間のランキング生成が正常に実行される()
    {
        // Arrange
        $referenceDate = Carbon::create(2024, 1, 7);
        $periodType = '1w';

        $mockResults = [
            ['company_id' => 1, 'rank_position' => 1, 'total_score' => 200.0],
            ['company_id' => 2, 'rank_position' => 2, 'total_score' => 150.0],
            ['company_id' => 3, 'rank_position' => 3, 'total_score' => 100.0],
        ];

        $this->rankingService
            ->shouldReceive('generateRankingForPeriod')
            ->once()
            ->with($periodType, $referenceDate)
            ->andReturn($mockResults);

        Log::shouldReceive('info')
            ->with('Starting company ranking generation for period', Mockery::on(function ($data) use ($periodType, $referenceDate) {
                return $data['period_type'] === $periodType &&
                       $data['reference_date'] === $referenceDate->toDateString();
            }))
            ->once();

        Log::shouldReceive('info')
            ->with('Company ranking generation completed for period', Mockery::on(function ($data) use ($periodType) {
                return $data['period_type'] === $periodType &&
                       $data['companies_ranked'] === 3;
            }))
            ->once();

        $job = new GenerateCompanyRankingsJob($periodType, $referenceDate);

        // Act
        $job->handle($this->rankingService);

        // Assert - ジョブが正常に完了したことを確認
        $this->assertTrue(true); // Mockeryの期待値が満たされれば成功
    }

    #[Test]
    public function test_handle_全期間のランキング生成が正常に実行される()
    {
        // Arrange
        $referenceDate = Carbon::create(2024, 1, 7);

        $mockResults = [
            '1w' => [
                ['company_id' => 1, 'rank_position' => 1],
                ['company_id' => 2, 'rank_position' => 2],
            ],
            '1m' => [
                ['company_id' => 1, 'rank_position' => 1],
                ['company_id' => 3, 'rank_position' => 2],
                ['company_id' => 2, 'rank_position' => 3],
            ],
            '3m' => [],
        ];

        $this->rankingService
            ->shouldReceive('generateAllRankings')
            ->once()
            ->with($referenceDate)
            ->andReturn($mockResults);

        Log::shouldReceive('info')
            ->with('Starting company ranking generation for all periods', Mockery::on(function ($data) use ($referenceDate) {
                return $data['reference_date'] === $referenceDate->toDateString();
            }))
            ->once();

        Log::shouldReceive('info')
            ->with('Company ranking generation completed for all periods', Mockery::on(function ($data) {
                return $data['total_companies_ranked'] === 5 && // 2 + 3 + 0
                       $data['periods_processed'] === 3;
            }))
            ->once();

        $job = new GenerateCompanyRankingsJob(null, $referenceDate);

        // Act
        $job->handle($this->rankingService);

        // Assert - ジョブが正常に完了したことを確認
        $this->assertTrue(true); // Mockeryの期待値が満たされれば成功
    }

    #[Test]
    public function test_handle_基準日未指定の場合現在日時を使用する()
    {
        // Arrange
        $periodType = '1w';

        $this->rankingService
            ->shouldReceive('generateRankingForPeriod')
            ->once()
            ->with($periodType, Mockery::type('Carbon\Carbon'))
            ->andReturn([]);

        Log::shouldReceive('info')->twice();

        $job = new GenerateCompanyRankingsJob($periodType);

        // Act
        $job->handle($this->rankingService);

        // Assert - 基準日がnullでも正常に動作することを確認
        $this->assertTrue(true); // Mockeryの期待値が満たされれば成功
    }

    #[Test]
    public function test_handle_特定期間処理で例外発生時にログを出力して再スローする()
    {
        // Arrange
        $referenceDate = Carbon::create(2024, 1, 7);
        $periodType = '1w';
        $exception = new \Exception('Test exception');

        $this->rankingService
            ->shouldReceive('generateRankingForPeriod')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('info')
            ->with('Starting company ranking generation for period', Mockery::type('array'))
            ->once();

        Log::shouldReceive('error')
            ->with('Company ranking generation failed', Mockery::on(function ($data) use ($periodType, $referenceDate, $exception) {
                return $data['period_type'] === $periodType &&
                       $data['reference_date'] === $referenceDate->toDateString() &&
                       $data['error'] === $exception->getMessage() &&
                       isset($data['trace']);
            }))
            ->once();

        $job = new GenerateCompanyRankingsJob($periodType, $referenceDate);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');

        $job->handle($this->rankingService);
    }

    #[Test]
    public function test_handle_全期間処理で例外発生時にログを出力して再スローする()
    {
        // Arrange
        $referenceDate = Carbon::create(2024, 1, 7);
        $exception = new \Exception('All periods test exception');

        $this->rankingService
            ->shouldReceive('generateAllRankings')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('info')
            ->with('Starting company ranking generation for all periods', Mockery::type('array'))
            ->once();

        Log::shouldReceive('error')
            ->with('Company ranking generation failed', Mockery::on(function ($data) use ($referenceDate, $exception) {
                return $data['period_type'] === null &&
                       $data['reference_date'] === $referenceDate->toDateString() &&
                       $data['error'] === $exception->getMessage() &&
                       isset($data['trace']);
            }))
            ->once();

        $job = new GenerateCompanyRankingsJob(null, $referenceDate);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('All periods test exception');

        $job->handle($this->rankingService);
    }

    #[Test]
    public function test_failed_失敗時のログを出力する()
    {
        // Arrange
        $exception = new \Exception('Test failure');
        $referenceDate = Carbon::create(2024, 1, 7);
        $periodType = '1w';

        Log::shouldReceive('error')
            ->with('Company ranking generation job failed', Mockery::on(function ($data) use ($exception, $referenceDate, $periodType) {
                return $data['period_type'] === $periodType &&
                       $data['reference_date'] === $referenceDate->toDateString() &&
                       $data['error'] === $exception->getMessage();
            }))
            ->once();

        $job = new GenerateCompanyRankingsJob($periodType, $referenceDate);

        // Act
        $job->failed($exception);

        // Assert - 失敗ログが出力されることを確認
        $this->assertTrue(true); // Mockeryの期待値が満たされれば成功
    }

    #[Test]
    public function test_failed_参照日がnullの場合も正常に処理される()
    {
        // Arrange
        $exception = new \Exception('Test failure');
        $periodType = '1w';

        Log::shouldReceive('error')
            ->with('Company ranking generation job failed', Mockery::on(function ($data) use ($exception, $periodType) {
                return $data['period_type'] === $periodType &&
                       $data['reference_date'] === null &&
                       $data['error'] === $exception->getMessage();
            }))
            ->once();

        $job = new GenerateCompanyRankingsJob($periodType, null);

        // Act
        $job->failed($exception);

        // Assert - 参照日がnullでも正常に動作することを確認
        $this->assertTrue(true); // Mockeryの期待値が満たされれば成功
    }

    #[Test]
    public function test_constructor_パラメータが正しく設定される()
    {
        // Arrange
        $referenceDate = Carbon::create(2024, 1, 7);
        $periodType = '1w';

        // Act
        $job = new GenerateCompanyRankingsJob($periodType, $referenceDate);

        // Assert - リフレクションでプライベートプロパティにアクセス
        $reflection = new \ReflectionClass($job);

        $periodTypeProperty = $reflection->getProperty('periodType');
        $periodTypeProperty->setAccessible(true);
        $this->assertEquals($periodType, $periodTypeProperty->getValue($job));

        $referenceDateProperty = $reflection->getProperty('referenceDate');
        $referenceDateProperty->setAccessible(true);
        $this->assertEquals($referenceDate, $referenceDateProperty->getValue($job));
    }

    #[Test]
    public function test_constructor_パラメータがnullでも正常に設定される()
    {
        // Act
        $job = new GenerateCompanyRankingsJob;

        // Assert - リフレクションでプライベートプロパティにアクセス
        $reflection = new \ReflectionClass($job);

        $periodTypeProperty = $reflection->getProperty('periodType');
        $periodTypeProperty->setAccessible(true);
        $this->assertNull($periodTypeProperty->getValue($job));

        $referenceDateProperty = $reflection->getProperty('referenceDate');
        $referenceDateProperty->setAccessible(true);
        $this->assertNull($referenceDateProperty->getValue($job));
    }

    #[Test]
    public function test_handle_空の結果でも正常に処理される()
    {
        // Arrange
        $periodType = '1w';
        $referenceDate = Carbon::create(2024, 1, 7);

        $this->rankingService
            ->shouldReceive('generateRankingForPeriod')
            ->once()
            ->with($periodType, $referenceDate)
            ->andReturn([]);

        Log::shouldReceive('info')
            ->with('Starting company ranking generation for period', Mockery::type('array'))
            ->once();

        Log::shouldReceive('info')
            ->with('Company ranking generation completed for period', Mockery::on(function ($data) {
                return $data['companies_ranked'] === 0;
            }))
            ->once();

        $job = new GenerateCompanyRankingsJob($periodType, $referenceDate);

        // Act
        $job->handle($this->rankingService);

        // Assert - 空の結果でも正常に処理されることを確認
        $this->assertTrue(true); // Mockeryの期待値が満たされれば成功
    }

    #[Test]
    public function test_handle_全期間で空の結果でも正常に処理される()
    {
        // Arrange
        $referenceDate = Carbon::create(2024, 1, 7);

        $this->rankingService
            ->shouldReceive('generateAllRankings')
            ->once()
            ->with($referenceDate)
            ->andReturn([
                '1w' => [],
                '1m' => [],
                '3m' => [],
            ]);

        Log::shouldReceive('info')
            ->with('Starting company ranking generation for all periods', Mockery::type('array'))
            ->once();

        Log::shouldReceive('info')
            ->with('Company ranking generation completed for all periods', Mockery::on(function ($data) {
                return $data['total_companies_ranked'] === 0 &&
                       $data['periods_processed'] === 3;
            }))
            ->once();

        $job = new GenerateCompanyRankingsJob(null, $referenceDate);

        // Act
        $job->handle($this->rankingService);

        // Assert - 全期間で空の結果でも正常に処理されることを確認
        $this->assertTrue(true); // Mockeryの期待値が満たされれば成功
    }

    #[Test]
    public function test_handle_企業数の計算が正しく行われる()
    {
        // Arrange
        $referenceDate = Carbon::create(2024, 1, 7);

        $mockResults = [
            '1w' => [
                ['company_id' => 1],
                ['company_id' => 2],
                ['company_id' => 3],
            ],
            '1m' => [
                ['company_id' => 1],
                ['company_id' => 2],
            ],
            '3m' => [
                ['company_id' => 1],
            ],
        ];

        $this->rankingService
            ->shouldReceive('generateAllRankings')
            ->once()
            ->andReturn($mockResults);

        Log::shouldReceive('info')
            ->with('Starting company ranking generation for all periods', Mockery::type('array'))
            ->once();

        Log::shouldReceive('info')
            ->with('Company ranking generation completed for all periods', Mockery::on(function ($data) {
                return $data['total_companies_ranked'] === 6 && // 3 + 2 + 1
                       $data['periods_processed'] === 3;
            }))
            ->once();

        $job = new GenerateCompanyRankingsJob(null, $referenceDate);

        // Act
        $job->handle($this->rankingService);

        // Assert - 企業数の計算が正しく行われることを確認
        $this->assertTrue(true); // Mockeryの期待値が満たされれば成功
    }

    #[Test]
    public function test_job_should_queueインターフェースを実装している()
    {
        // Assert
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, new GenerateCompanyRankingsJob);
    }

    #[Test]
    public function test_job_queueableトレイトを使用している()
    {
        // Arrange
        $job = new GenerateCompanyRankingsJob;
        $reflection = new \ReflectionClass($job);

        // Assert
        $this->assertContains('Illuminate\Foundation\Queue\Queueable', $reflection->getTraitNames());
    }
}
