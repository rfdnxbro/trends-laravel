<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScrapeCommandsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * scrape:allコマンドのdry-runオプションテスト
     */
    public function test_scrape_all_command_with_dry_run(): void
    {
        $this->artisan('scrape:all --dry-run')
            ->assertExitCode(0);
    }

    /**
     * 有効なプラットフォームでのscrape:platformコマンドテスト
     */
    public function test_scrape_platform_command_with_valid_platform(): void
    {
        $this->artisan('scrape:platform qiita --dry-run')
            ->assertExitCode(0);
    }

    /**
     * 無効なプラットフォームでのscrape:platformコマンドテスト
     */
    public function test_scrape_platform_command_with_invalid_platform(): void
    {
        $this->artisan('scrape:platform invalid')
            ->assertExitCode(1);
    }

    /**
     * プラットフォームオプション付きscrape:scheduleコマンドテスト
     */
    public function test_scrape_schedule_command_with_platform(): void
    {
        $this->artisan('scrape:schedule --platform=qiita')
            ->assertExitCode(0);
    }

    /**
     * 無効なプラットフォームでのscrape:scheduleコマンドテスト
     */
    public function test_scrape_schedule_command_with_invalid_platform(): void
    {
        $this->artisan('scrape:schedule --platform=invalid')
            ->assertExitCode(1);
    }

    /**
     * サイレントモードでのscrape:scheduleコマンドテスト
     */
    public function test_scrape_schedule_command_silent_mode(): void
    {
        $this->artisan('scrape:schedule --platform=qiita --silent')
            ->assertExitCode(0);
    }

    /**
     * scrape:platformコマンドで全プラットフォームが利用可能かテスト
     */
    public function test_all_platforms_are_available(): void
    {
        $platforms = ['qiita', 'zenn', 'hatena'];

        foreach ($platforms as $platform) {
            $this->artisan("scrape:platform {$platform} --dry-run")
                ->assertExitCode(0);
        }
    }
}
