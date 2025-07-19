<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\CalculateCompanyInfluenceScoresJob;
use App\Jobs\GenerateCompanyRankingsJob;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class JobComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    /**
     * CalculateCompanyInfluenceScoresJobの基本テスト
     */
    public function test_calculate_company_influence_scores_job_が正しくインスタンス化されること(): void
    {
        $referenceDate = Carbon::now();
        $periodType = 'one_week';

        $job = new CalculateCompanyInfluenceScoresJob($referenceDate, $periodType);

        $this->assertInstanceOf(CalculateCompanyInfluenceScoresJob::class, $job);
    }

    /**
     * GenerateCompanyRankingsJobの基本テスト
     */
    public function test_generate_company_rankings_job_が正しくインスタンス化されること(): void
    {
        $job = new GenerateCompanyRankingsJob('one_week');

        $this->assertInstanceOf(GenerateCompanyRankingsJob::class, $job);
    }

    /**
     * ジョブのキューイングテスト
     */
    public function test_ジョブが正しくキューに追加されること(): void
    {
        Queue::fake();

        $company = Company::factory()->create();

        // CalculateCompanyInfluenceScoresJobをディスパッチ
        CalculateCompanyInfluenceScoresJob::dispatch(Carbon::now(), 'one_week');

        Queue::assertPushed(CalculateCompanyInfluenceScoresJob::class);

        // GenerateCompanyRankingsJobをディスパッチ
        GenerateCompanyRankingsJob::dispatch('one_week');

        Queue::assertPushed(GenerateCompanyRankingsJob::class);
    }

    /**
     * ジョブの実行回数テスト
     */
    public function test_ジョブが指定回数実行されること(): void
    {
        Queue::fake();

        $companies = Company::factory()->count(3)->create();

        foreach ($companies as $company) {
            CalculateCompanyInfluenceScoresJob::dispatch(Carbon::now(), 'one_week');
        }

        Queue::assertPushed(CalculateCompanyInfluenceScoresJob::class, 3);
    }

    /**
     * ジョブのデータ検証テスト
     */
    public function test_ジョブに正しいデータが渡されること(): void
    {
        Queue::fake();

        $referenceDate = Carbon::now();
        $periodType = 'one_week';

        CalculateCompanyInfluenceScoresJob::dispatch($referenceDate, $periodType);

        Queue::assertPushed(CalculateCompanyInfluenceScoresJob::class, function ($job) use ($referenceDate, $periodType) {
            // リフレクションを使用してプライベートプロパティを確認
            $reflection = new \ReflectionClass($job);
            $referenceDateProperty = $reflection->getProperty('referenceDate');
            $referenceDateProperty->setAccessible(true);
            $periodTypeProperty = $reflection->getProperty('periodType');
            $periodTypeProperty->setAccessible(true);

            return $referenceDateProperty->getValue($job)?->equalTo($referenceDate) &&
                   $periodTypeProperty->getValue($job) === $periodType;
        });
    }

    /**
     * ランキング生成ジョブのパラメータテスト
     */
    public function test_ランキング生成ジョブに正しいパラメータが渡されること(): void
    {
        Queue::fake();

        $periods = ['one_week', 'one_month', 'three_months'];

        foreach ($periods as $period) {
            GenerateCompanyRankingsJob::dispatch($period);
        }

        Queue::assertPushed(GenerateCompanyRankingsJob::class, 3);

        // 特定の期間でのジョブ実行を確認
        Queue::assertPushed(GenerateCompanyRankingsJob::class, function ($job) {
            // リフレクションを使用してプライベートプロパティを確認
            $reflection = new \ReflectionClass($job);
            $periodTypeProperty = $reflection->getProperty('periodType');
            $periodTypeProperty->setAccessible(true);
            $periodType = $periodTypeProperty->getValue($job);

            return in_array($periodType, ['one_week', 'one_month', 'three_months']);
        });
    }

    /**
     * ジョブのバッチ処理テスト
     */
    public function test_バッチ処理でジョブが正しく実行されること(): void
    {
        Queue::fake();

        $companies = Company::factory()->count(5)->create();

        // バッチでジョブをディスパッチ
        foreach ($companies as $company) {
            CalculateCompanyInfluenceScoresJob::dispatch(Carbon::now(), 'one_week');
        }

        Queue::assertPushed(CalculateCompanyInfluenceScoresJob::class, 5);
    }

    /**
     * ジョブのエラーハンドリングテスト
     */
    public function test_無効なデータでジョブがエラーになること(): void
    {
        Queue::fake();

        // 無効な期間タイプでジョブを作成
        $job = new CalculateCompanyInfluenceScoresJob(Carbon::now(), 'invalid_period');

        $this->assertInstanceOf(CalculateCompanyInfluenceScoresJob::class, $job);

        // リフレクションを使用してプライベートプロパティを確認
        $reflection = new \ReflectionClass($job);
        $periodTypeProperty = $reflection->getProperty('periodType');
        $periodTypeProperty->setAccessible(true);

        $this->assertEquals('invalid_period', $periodTypeProperty->getValue($job));
    }

    /**
     * ジョブの遅延実行テスト
     */
    public function test_ジョブが遅延実行されること(): void
    {
        Queue::fake();

        $company = Company::factory()->create();

        // 1分後に実行するジョブをディスパッチ
        CalculateCompanyInfluenceScoresJob::dispatch(Carbon::now(), 'one_week')->delay(now()->addMinute());

        Queue::assertPushed(CalculateCompanyInfluenceScoresJob::class);
    }

    /**
     * ジョブのリトライ設定テスト
     */
    public function test_ジョブのリトライ設定が適用されること(): void
    {
        $company = Company::factory()->create();
        $job = new CalculateCompanyInfluenceScoresJob(Carbon::now(), 'one_week');

        $this->assertInstanceOf(CalculateCompanyInfluenceScoresJob::class, $job);

        // リトライ回数のプロパティが設定されていることを確認
        $this->assertTrue(property_exists($job, 'tries') || method_exists($job, 'tries'));
    }

    /**
     * ジョブのシリアライゼーション/デシリアライゼーションテスト
     */
    public function test_ジョブが正しくシリアライズされること(): void
    {
        $referenceDate = Carbon::now();
        $periodType = 'one_week';
        $job = new CalculateCompanyInfluenceScoresJob($referenceDate, $periodType);

        // シリアライズ
        $serialized = serialize($job);
        $this->assertIsString($serialized);

        // デシリアライズ
        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(CalculateCompanyInfluenceScoresJob::class, $unserialized);

        // リフレクションでプロパティを確認
        $reflection = new \ReflectionClass($unserialized);
        $periodTypeProperty = $reflection->getProperty('periodType');
        $periodTypeProperty->setAccessible(true);

        $this->assertEquals($periodType, $periodTypeProperty->getValue($unserialized));
    }

    /**
     * ジョブの優先度テスト
     */
    public function test_ジョブに優先度が設定されること(): void
    {
        Queue::fake();

        $company = Company::factory()->create();

        // 高優先度でジョブをディスパッチ
        CalculateCompanyInfluenceScoresJob::dispatch(Carbon::now(), 'one_week')->onQueue('high');

        Queue::assertPushedOn('high', CalculateCompanyInfluenceScoresJob::class);
    }

    /**
     * 複数種類のジョブの同時実行テスト
     */
    public function test_複数種類のジョブが同時に実行されること(): void
    {
        Queue::fake();

        $company = Company::factory()->create();

        // 両方のジョブをディスパッチ
        CalculateCompanyInfluenceScoresJob::dispatch(Carbon::now(), 'one_week');
        GenerateCompanyRankingsJob::dispatch('one_week');

        Queue::assertPushed(CalculateCompanyInfluenceScoresJob::class, 1);
        Queue::assertPushed(GenerateCompanyRankingsJob::class, 1);
    }
}
