<?php

namespace Tests\Unit\Console;

use App\Services\HatenaBookmarkScraper;
use App\Services\QiitaScraper;
use App\Services\ZennScraper;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ScrapePlatformCommandHandleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle実行_qiita正常系()
    {
        $this->app->bind(QiitaScraper::class, function () {
            $mock = Mockery::mock(QiitaScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'engagement_count' => 10],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([['id' => 1]]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $result = $this->artisan('scrape:platform', ['platform' => 'qiita'])
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_handle実行_zenn正常系()
    {
        $this->app->bind(ZennScraper::class, function () {
            $mock = Mockery::mock(ZennScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'engagement_count' => 15],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([['id' => 1]]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $result = $this->artisan('scrape:platform', ['platform' => 'zenn'])
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_handle実行_hatena正常系()
    {
        $this->app->bind(HatenaBookmarkScraper::class, function () {
            $mock = Mockery::mock(HatenaBookmarkScraper::class);
            $mock->shouldReceive('scrapePopularItEntries')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'engagement_count' => 100],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([['id' => 1]]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $result = $this->artisan('scrape:platform', ['platform' => 'hatena'])
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_handle実行_qiita_dry_runモード()
    {
        $this->app->bind(QiitaScraper::class, function () {
            $mock = Mockery::mock(QiitaScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'engagement_count' => 10],
            ]);
            // dry-runでは保存しない
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $result = $this->artisan('scrape:platform', ['platform' => 'qiita', '--dry-run' => true])
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_handle実行_zenn_dry_runモード()
    {
        $this->app->bind(ZennScraper::class, function () {
            $mock = Mockery::mock(ZennScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'engagement_count' => 15],
            ]);
            // dry-runでは保存しない
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $result = $this->artisan('scrape:platform', ['platform' => 'zenn', '--dry-run' => true])
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_handle実行_hatena_dry_runモード()
    {
        $this->app->bind(HatenaBookmarkScraper::class, function () {
            $mock = Mockery::mock(HatenaBookmarkScraper::class);
            $mock->shouldReceive('scrapePopularItEntries')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'engagement_count' => 100],
            ]);
            // dry-runでは保存しない
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $result = $this->artisan('scrape:platform', ['platform' => 'hatena', '--dry-run' => true])
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_handle実行_無効なプラットフォーム()
    {
        $result = $this->artisan('scrape:platform', ['platform' => 'invalid'])
            ->assertExitCode(Command::FAILURE);
    }

    public function test_handle実行_qiitaエラー時()
    {
        $this->app->bind(QiitaScraper::class, function () {
            $mock = Mockery::mock(QiitaScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andThrow(new \Exception('テストエラー'));
            $mock->shouldReceive('getErrorLog')->andReturn([
                ['error' => 'Test error', 'attempt' => 1],
            ]);

            return $mock;
        });

        $result = $this->artisan('scrape:platform', ['platform' => 'qiita'])
            ->assertExitCode(Command::FAILURE);
    }

    public function test_handle実行_zennエラー時()
    {
        $this->app->bind(ZennScraper::class, function () {
            $mock = Mockery::mock(ZennScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andThrow(new \Exception('テストエラー'));
            $mock->shouldReceive('getErrorLog')->andReturn([
                ['error' => 'Test error', 'attempt' => 1],
            ]);

            return $mock;
        });

        $result = $this->artisan('scrape:platform', ['platform' => 'zenn'])
            ->assertExitCode(Command::FAILURE);
    }

    public function test_handle実行_hatenaエラー時()
    {
        $this->app->bind(HatenaBookmarkScraper::class, function () {
            $mock = Mockery::mock(HatenaBookmarkScraper::class);
            $mock->shouldReceive('scrapePopularItEntries')->andThrow(new \Exception('テストエラー'));
            $mock->shouldReceive('getErrorLog')->andReturn([
                ['error' => 'Test error', 'attempt' => 1],
            ]);

            return $mock;
        });

        $result = $this->artisan('scrape:platform', ['platform' => 'hatena'])
            ->assertExitCode(Command::FAILURE);
    }

    public function test_handle実行_プラットフォーム名大文字小文字()
    {
        $this->app->bind(QiitaScraper::class, function () {
            $mock = Mockery::mock(QiitaScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'engagement_count' => 10],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([['id' => 1]]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $result = $this->artisan('scrape:platform', ['platform' => 'QIITA'])
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_handle実行_統計情報表示()
    {
        $this->app->bind(QiitaScraper::class, function () {
            $mock = Mockery::mock(QiitaScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Article 1', 'url' => 'https://test1.com', 'engagement_count' => 10],
                ['title' => 'Article 2', 'url' => 'https://test2.com', 'engagement_count' => 20],
                ['title' => 'Article 3', 'url' => 'https://test3.com', 'engagement_count' => 30],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([
                ['id' => 1], ['id' => 2], ['id' => 3],
            ]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $result = $this->artisan('scrape:platform', ['platform' => 'qiita'])
            ->assertExitCode(Command::SUCCESS);
    }
}
