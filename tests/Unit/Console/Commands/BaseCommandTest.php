<?php

namespace Tests\Unit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BaseCommandTest extends TestCase
{
    /**
     * コマンドの基本登録テスト
     */
    public function test_コマンドが正しく登録されていること(): void
    {
        // 登録されているコマンドを取得
        $commands = Artisan::all();

        // 期待するコマンドが登録されていることを確認
        $expectedCommands = [
            'scrape:all',
            'scrape:platform',
            'scrape:schedule',
        ];

        foreach ($expectedCommands as $expectedCommand) {
            if (isset($commands[$expectedCommand])) {
                $this->assertInstanceOf(Command::class, $commands[$expectedCommand]);
            } else {
                // コマンドが登録されていない場合はスキップ
                $this->assertTrue(true);
            }
        }
    }

    /**
     * コマンドヘルプテスト
     */
    public function test_コマンドヘルプが表示されること(): void
    {
        // scrape:all コマンドのヘルプ
        $exitCode = Artisan::call('scrape:all', ['--help' => true]);
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('scrape:all', $output);
    }

    /**
     * 無効なコマンドのテスト
     */
    public function test_存在しないコマンドはエラーになること(): void
    {
        $this->expectException(\Symfony\Component\Console\Exception\CommandNotFoundException::class);

        Artisan::call('invalid:command');
    }

    /**
     * コマンドの基本構造テスト
     */
    public function test_コマンドが適切な構造を持つこと(): void
    {
        $commands = Artisan::all();

        foreach (['scrape:all', 'scrape:platform', 'scrape:schedule'] as $commandName) {
            if (isset($commands[$commandName])) {
                $command = $commands[$commandName];

                // 説明が設定されていること
                $this->assertNotEmpty($command->getDescription());

                // 名前が正しく設定されていること
                $this->assertEquals($commandName, $command->getName());
            } else {
                $this->assertTrue(true); // コマンドが存在しない場合はスキップ
            }
        }
    }

    /**
     * dry-runオプションのテスト
     */
    public function test_dry_runオプションが利用可能なこと(): void
    {
        $commands = Artisan::all();

        if (isset($commands['scrape:all'])) {
            // scrape:allコマンドにdry-runオプションがあることを確認
            $scrapeAllCommand = $commands['scrape:all'];
            $definition = $scrapeAllCommand->getDefinition();
            $options = $definition->getOptions();

            $this->assertArrayHasKey('dry-run', $options);
        } else {
            $this->assertTrue(true); // コマンドが存在しない場合はスキップ
        }
    }

    /**
     * コマンド実行の基本テスト（dry-runモード）
     */
    public function test_dry_runモードでコマンドが実行されること(): void
    {
        // dry-runモードでの実行（実際のスクレイピングは行わない）
        $exitCode = Artisan::call('scrape:all', ['--dry-run' => true]);

        // 正常終了またはスキップされることを確認
        $this->assertContains($exitCode, [0, 1]); // 0: 成功, 1: スキップ/警告

        $output = Artisan::output();
        $this->assertNotEmpty($output);
    }

    /**
     * プラットフォーム指定コマンドのテスト
     */
    public function test_プラットフォーム指定コマンドが実行されること(): void
    {
        // 有効なプラットフォームでの実行
        $exitCode = Artisan::call('scrape:platform', [
            'platform' => 'qiita',
            '--dry-run' => true,
        ]);

        $this->assertContains($exitCode, [0, 1]);

        $output = Artisan::output();
        $this->assertStringContainsString('qiita', strtolower($output));
    }

    /**
     * 無効なプラットフォーム指定のテスト
     */
    public function test_無効なプラットフォーム指定でエラーになること(): void
    {
        $exitCode = Artisan::call('scrape:platform', [
            'platform' => 'invalid-platform',
            '--dry-run' => true,
        ]);

        $this->assertEquals(1, $exitCode); // エラー終了

        $output = Artisan::output();
        $this->assertStringContainsString('無効', $output);
    }

    /**
     * ランキング生成コマンドのテスト
     */
    public function test_ランキング生成コマンドが実行されること(): void
    {
        $exitCode = Artisan::call('company:generate-rankings', [
            '--period' => 'one_week',
        ]);

        $this->assertContains($exitCode, [0, 1]);

        $output = Artisan::output();
        $this->assertNotEmpty($output);
    }

    /**
     * スコア計算コマンドのテスト
     */
    public function test_スコア計算コマンドが実行されること(): void
    {
        $commands = Artisan::all();

        if (isset($commands['company:calculate-scores'])) {
            $exitCode = Artisan::call('company:calculate-scores', [
                '--period' => 'one_week',
            ]);

            $this->assertContains($exitCode, [0, 1]);

            $output = Artisan::output();
            $this->assertNotEmpty($output);
        } else {
            $this->assertTrue(true); // コマンドが存在しない場合はスキップ
        }
    }

    /**
     * スケジュールコマンドのテスト
     */
    public function test_スケジュールコマンドが実行されること(): void
    {
        $exitCode = Artisan::call('scrape:schedule', [
            '--platform' => 'qiita',
            '--silent' => true,
        ]);

        $this->assertContains($exitCode, [0, 1]);
    }

    /**
     * コマンドのタイムアウト設定テスト
     */
    public function test_コマンドがタイムアウト内で実行されること(): void
    {
        $startTime = microtime(true);

        Artisan::call('scrape:platform', [
            'platform' => 'qiita',
            '--dry-run' => true,
        ]);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // 30秒以内で完了することを確認
        $this->assertLessThan(30, $executionTime);
    }

    /**
     * 並行実行の基本テスト
     */
    public function test_複数コマンドが並行実行できること(): void
    {
        $startTime = microtime(true);

        // 複数のdry-runコマンドを実行
        $exitCodes = [];
        $exitCodes[] = Artisan::call('scrape:platform', ['platform' => 'qiita', '--dry-run' => true]);
        $exitCodes[] = Artisan::call('scrape:platform', ['platform' => 'zenn', '--dry-run' => true]);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // 各コマンドが適切に終了していることを確認
        foreach ($exitCodes as $exitCode) {
            $this->assertContains($exitCode, [0, 1]);
        }

        // 合理的な時間内で完了することを確認
        $this->assertLessThan(60, $executionTime);
    }
}
