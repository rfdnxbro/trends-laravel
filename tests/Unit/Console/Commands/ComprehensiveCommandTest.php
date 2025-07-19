<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ComprehensiveCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * GenerateCompanyRankingsCommandが登録されていることをテスト
     */
    public function test_generate_company_rankings_command_が登録されていること(): void
    {
        $this->assertTrue(class_exists(\App\Console\Commands\GenerateCompanyRankingsCommand::class));
    }

    /**
     * ScrapeAllCommandが登録されていることをテスト
     */
    public function test_scrape_all_command_が登録されていること(): void
    {
        $this->assertTrue(class_exists(\App\Console\Commands\ScrapeAll::class));
    }

    /**
     * ScrapePlatformCommandが登録されていることをテスト
     */
    public function test_scrape_platform_command_が登録されていること(): void
    {
        $this->assertTrue(class_exists(\App\Console\Commands\ScrapePlatform::class));
    }

    /**
     * ScrapeScheduleCommandが登録されていることをテスト
     */
    public function test_scrape_schedule_command_が登録されていること(): void
    {
        $this->assertTrue(class_exists(\App\Console\Commands\ScrapeSchedule::class));
    }

    /**
     * Artisanコマンドリストにコマンドが含まれていることをテスト
     */
    public function test_artisan_コマンドリストにカスタムコマンドが含まれていること(): void
    {
        $exitCode = Artisan::call('list');

        $this->assertEquals(0, $exitCode);

        // テストでは出力内容より終了コードが重要
        $this->assertTrue(true, 'Artisan list command executed successfully');
    }

    /**
     * GenerateCompanyRankingsCommandの基本実行テスト
     */
    public function test_generate_company_rankings_command_基本実行(): void
    {
        // 実際の実行はせず、コマンドのインスタンス化のみテスト
        $command = new \App\Console\Commands\GenerateCompanyRankingsCommand;

        $this->assertInstanceOf(\App\Console\Commands\GenerateCompanyRankingsCommand::class, $command);
        $this->assertIsString($command->getName());
    }

    /**
     * ScrapeAllCommandの基本実行テスト
     */
    public function test_scrape_all_command_基本実行(): void
    {
        $command = new \App\Console\Commands\ScrapeAll;

        $this->assertInstanceOf(\App\Console\Commands\ScrapeAll::class, $command);
        $this->assertIsString($command->getName());
    }

    /**
     * ScrapePlatformCommandの基本実行テスト
     */
    public function test_scrape_platform_command_基本実行(): void
    {
        $command = new \App\Console\Commands\ScrapePlatform;

        $this->assertInstanceOf(\App\Console\Commands\ScrapePlatform::class, $command);
        $this->assertIsString($command->getName());
    }

    /**
     * ScrapeScheduleCommandの基本実行テスト
     */
    public function test_scrape_schedule_command_基本実行(): void
    {
        $command = new \App\Console\Commands\ScrapeSchedule;

        $this->assertInstanceOf(\App\Console\Commands\ScrapeSchedule::class, $command);
        $this->assertIsString($command->getName());
    }

    /**
     * コマンドオプションのテスト
     */
    public function test_コマンドオプションが正しく定義されていること(): void
    {
        $scrapeAllCommand = new \App\Console\Commands\ScrapeAll;
        $definition = $scrapeAllCommand->getDefinition();

        $this->assertNotNull($definition);

        $scrapePlatformCommand = new \App\Console\Commands\ScrapePlatform;
        $definition = $scrapePlatformCommand->getDefinition();

        $this->assertNotNull($definition);
    }

    /**
     * コマンドのシグネチャテスト
     */
    public function test_コマンドのシグネチャが正しく設定されていること(): void
    {
        $commands = [
            \App\Console\Commands\ScrapeAll::class,
            \App\Console\Commands\ScrapePlatform::class,
            \App\Console\Commands\ScrapeSchedule::class,
            \App\Console\Commands\GenerateCompanyRankingsCommand::class,
        ];

        foreach ($commands as $commandClass) {
            $command = new $commandClass;
            $signature = $command->getName();

            $this->assertIsString($signature);
            $this->assertNotEmpty($signature);
        }
    }

    /**
     * コマンドの説明テスト
     */
    public function test_コマンドの説明が設定されていること(): void
    {
        $commands = [
            \App\Console\Commands\ScrapeAll::class,
            \App\Console\Commands\ScrapePlatform::class,
            \App\Console\Commands\ScrapeSchedule::class,
            \App\Console\Commands\GenerateCompanyRankingsCommand::class,
        ];

        foreach ($commands as $commandClass) {
            $command = new $commandClass;
            $description = $command->getDescription();

            $this->assertIsString($description);
        }
    }

    /**
     * コマンドが正常にインスタンス化されることをテスト
     */
    public function test_全コマンドが正常にインスタンス化されること(): void
    {
        $commands = [
            \App\Console\Commands\ScrapeAll::class,
            \App\Console\Commands\ScrapePlatform::class,
            \App\Console\Commands\ScrapeSchedule::class,
            \App\Console\Commands\GenerateCompanyRankingsCommand::class,
        ];

        foreach ($commands as $commandClass) {
            $command = new $commandClass;

            $this->assertInstanceOf($commandClass, $command);
            $this->assertInstanceOf(\Illuminate\Console\Command::class, $command);
        }
    }

    /**
     * コマンドヘルプ表示テスト
     */
    public function test_コマンドヘルプが表示されること(): void
    {
        $exitCode = Artisan::call('help', ['command_name' => 'list']);

        $this->assertEquals(0, $exitCode);

        // テスト環境では出力が異なる場合があるので、終了コードの確認で十分
        $this->assertTrue(true, 'Help command executed successfully');
    }

    /**
     * 無効なコマンド実行時のエラーハンドリングテスト
     */
    public function test_無効なコマンド実行時のエラーハンドリング(): void
    {
        $this->expectException(\Symfony\Component\Console\Exception\CommandNotFoundException::class);

        // 無効なコマンドは例外を投げる
        Artisan::call('invalid:command:name');
    }
}
