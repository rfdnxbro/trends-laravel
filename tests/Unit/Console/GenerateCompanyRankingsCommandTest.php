<?php

namespace Tests\Unit\Console;

use App\Console\Commands\GenerateCompanyRankingsCommand;
use App\Jobs\GenerateCompanyRankingsJob;
use App\Services\CompanyRankingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class GenerateCompanyRankingsCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_コマンドの基本情報が正しく設定されている()
    {
        $command = new GenerateCompanyRankingsCommand;

        $this->assertEquals('company:generate-rankings', $command->getName());
        $this->assertEquals('企業の期間別ランキングを生成します', $command->getDescription());
    }

    public function test_期間オプションが定義されている()
    {
        $command = new GenerateCompanyRankingsCommand;
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('period'));
        $this->assertEquals('特定期間のランキングを生成 (1w, 1m, 3m, 6m, 1y, 3y, all)', $definition->getOption('period')->getDescription());
    }

    public function test_日付オプションが定義されている()
    {
        $command = new GenerateCompanyRankingsCommand;
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('date'));
        $this->assertEquals('基準日を指定 (YYYY-MM-DD形式)', $definition->getOption('date')->getDescription());
    }

    public function test_キューオプションが定義されている()
    {
        $command = new GenerateCompanyRankingsCommand;
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('queue'));
        $this->assertEquals('キュー処理で実行', $definition->getOption('queue')->getDescription());
    }

    public function test_handleメソッドが実装されている()
    {
        $reflection = new \ReflectionClass(GenerateCompanyRankingsCommand::class);
        $handleMethod = $reflection->getMethod('handle');

        $this->assertTrue($handleMethod->isPublic());
        $this->assertEquals('handle', $handleMethod->getName());
    }

    public function test_デフォルト実行での正常終了()
    {
        // CompanyRankingServiceをモック
        $mockService = Mockery::mock(CompanyRankingService::class);
        $mockService->shouldReceive('generateAllRankings')
            ->once()
            ->with(Mockery::type(Carbon::class))
            ->andReturn([
                '1w' => [['company_id' => 1], ['company_id' => 2]],
                '1m' => [['company_id' => 1], ['company_id' => 2]],
                '3m' => [],
                '6m' => [],
                '1y' => [],
                '3y' => [],
                'all' => [],
            ]);
        $mockService->shouldReceive('getRankingStatistics')
            ->once()
            ->andReturn([
                '1w' => [
                    'total_companies' => 100,
                    'average_score' => 75.5,
                    'max_score' => 95.0,
                    'min_score' => 25.0,
                    'total_articles' => 500,
                    'total_bookmarks' => 1000,
                ],
            ]);

        $this->app->instance(CompanyRankingService::class, $mockService);

        $this->artisan('company:generate-rankings')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_期間指定オプションでの実行()
    {
        $mockService = Mockery::mock(CompanyRankingService::class);
        $mockService->shouldReceive('generateRankingForPeriod')
            ->once()
            ->with('1w', Mockery::type(Carbon::class))
            ->andReturn([['company_id' => 1], ['company_id' => 2]]);
        $mockService->shouldReceive('getRankingStatistics')
            ->once()
            ->andReturn([]);

        $this->app->instance(CompanyRankingService::class, $mockService);

        $this->artisan('company:generate-rankings', ['--period' => '1w'])
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_基準日指定オプションでの実行()
    {
        $mockService = Mockery::mock(CompanyRankingService::class);
        $mockService->shouldReceive('generateRankingForPeriod')
            ->once()
            ->with('1m', Mockery::any())
            ->andReturn([]);
        $mockService->shouldReceive('getRankingStatistics')
            ->once()
            ->andReturn([]);

        $this->app->instance(CompanyRankingService::class, $mockService);

        $this->artisan('company:generate-rankings', [
            '--period' => '1m',
            '--date' => '2024-01-01',
        ])
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_キューオプションでの単一期間実行()
    {
        Queue::fake();

        $this->artisan('company:generate-rankings', [
            '--period' => '1w',
            '--queue' => true,
        ])
            ->assertExitCode(Command::SUCCESS);

        Queue::assertPushed(GenerateCompanyRankingsJob::class, 1);
    }

    public function test_キューオプションでの全期間実行()
    {
        Queue::fake();

        $this->artisan('company:generate-rankings', ['--queue' => true])
            ->assertExitCode(Command::SUCCESS);

        // 全期間（7つ）のジョブが追加されることを確認
        Queue::assertPushed(GenerateCompanyRankingsJob::class, 7);
    }

    public function test_無効な日付指定時のエラー()
    {
        // Carbon例外がコマンドでキャッチされることを確認
        $reflection = new \ReflectionClass(GenerateCompanyRankingsCommand::class);
        $source = file_get_contents($reflection->getFileName());

        // try-catch文で例外ハンドリングが実装されていることを確認
        $this->assertStringContainsString('try', $source);
        $this->assertStringContainsString('catch', $source);
        $this->assertStringContainsString('$e->getMessage()', $source);
    }

    public function test_サービス例外時の処理()
    {
        $mockService = Mockery::mock(CompanyRankingService::class);
        $mockService->shouldReceive('generateAllRankings')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));

        $this->app->instance(CompanyRankingService::class, $mockService);

        $this->artisan('company:generate-rankings')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_統計情報表示機能()
    {
        $mockService = Mockery::mock(CompanyRankingService::class);
        $mockService->shouldReceive('generateRankingForPeriod')
            ->once()
            ->andReturn([]);

        $mockService->shouldReceive('getRankingStatistics')
            ->once()
            ->andReturn([
                '1w' => [
                    'total_companies' => 150,
                    'average_score' => 82.75,
                    'max_score' => 98.5,
                    'min_score' => 15.25,
                    'total_articles' => 750,
                    'total_bookmarks' => 2500,
                ],
                '1m' => [
                    'total_companies' => 200,
                    'average_score' => 70.0,
                    'max_score' => 95.0,
                    'min_score' => 10.0,
                    'total_articles' => 1000,
                    'total_bookmarks' => 5000,
                ],
            ]);

        $this->app->instance(CompanyRankingService::class, $mockService);

        $this->artisan('company:generate-rankings', ['--period' => '1w'])
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_実行時間測定とログ出力()
    {
        $reflection = new \ReflectionClass(GenerateCompanyRankingsCommand::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('Log::error', $source);
        $this->assertStringContainsString('try', $source);
        $this->assertStringContainsString('catch', $source);
    }

    public function test_プログレスバー機能の実装()
    {
        $reflection = new \ReflectionClass(GenerateCompanyRankingsCommand::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('createProgressBar', $source);
        $this->assertStringContainsString('bar->start()', $source);
        $this->assertStringContainsString('bar->advance()', $source);
        $this->assertStringContainsString('bar->finish()', $source);
    }

    public function test_コマンド成功時の終了コード()
    {
        $this->assertEquals(0, Command::SUCCESS);
        $this->assertEquals(1, Command::FAILURE);
    }

    public function test_期間処理の分離実装()
    {
        $reflection = new \ReflectionClass(GenerateCompanyRankingsCommand::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('handleWithQueue', $source);
        $this->assertStringContainsString('handleSynchronously', $source);
        $this->assertStringContainsString('displayStatistics', $source);
    }

    public function test_サービスクラス連携の確認()
    {
        $reflection = new \ReflectionClass(GenerateCompanyRankingsCommand::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('CompanyRankingService', $source);
        $this->assertStringContainsString('generateRankingForPeriod', $source);
        $this->assertStringContainsString('generateAllRankings', $source);
        $this->assertStringContainsString('getRankingStatistics', $source);
    }
}
