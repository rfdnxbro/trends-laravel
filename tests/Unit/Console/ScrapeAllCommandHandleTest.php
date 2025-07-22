<?php

namespace Tests\Unit\Console;

use App\Services\HatenaBookmarkScraper;
use App\Services\QiitaScraper;
use App\Services\ZennScraper;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ScrapeAllCommandHandleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle実行_正常系()
    {
        // スクレイパーのモック作成
        $this->mockScrapers();

        $result = $this->artisan('scrape:all')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_handle実行_dry_runモード()
    {
        // スクレイパーのモック作成（dry-runでは保存しない）
        $this->app->bind(QiitaScraper::class, function () {
            $mock = Mockery::mock(QiitaScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'engagement_count' => 10],
            ]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $this->app->bind(ZennScraper::class, function () {
            $mock = Mockery::mock(ZennScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'engagement_count' => 10],
            ]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $this->app->bind(HatenaBookmarkScraper::class, function () {
            $mock = Mockery::mock(HatenaBookmarkScraper::class);
            $mock->shouldReceive('scrapePopularItEntries')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'engagement_count' => 100],
            ]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $result = $this->artisan('scrape:all', ['--dry-run' => true])
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_handle実行_エラー発生時()
    {
        // 最初のスクレイパーでエラーを発生させる
        $this->app->bind(QiitaScraper::class, function () {
            $mock = Mockery::mock(QiitaScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andThrow(new \Exception('テストエラー'));
            $mock->shouldReceive('getErrorLog')->andReturn([
                ['error' => 'Test error', 'attempt' => 1],
            ]);

            return $mock;
        });

        // 他のスクレイパーは正常
        $this->app->bind(ZennScraper::class, function () {
            $mock = Mockery::mock(ZennScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $this->app->bind(HatenaBookmarkScraper::class, function () {
            $mock = Mockery::mock(HatenaBookmarkScraper::class);
            $mock->shouldReceive('scrapePopularItEntries')->andReturn([]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $result = $this->artisan('scrape:all')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_handle実行_全スクレイパーエラー時()
    {
        // 全スクレイパーでエラーを発生させる
        $this->app->bind(QiitaScraper::class, function () {
            $mock = Mockery::mock(QiitaScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andThrow(new \Exception('Qiitaエラー'));
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $this->app->bind(ZennScraper::class, function () {
            $mock = Mockery::mock(ZennScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andThrow(new \Exception('Zennエラー'));
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $this->app->bind(HatenaBookmarkScraper::class, function () {
            $mock = Mockery::mock(HatenaBookmarkScraper::class);
            $mock->shouldReceive('scrapePopularItEntries')->andThrow(new \Exception('はてなエラー'));
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $result = $this->artisan('scrape:all')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_handle実行_統計情報表示()
    {
        // 正常なデータを返すモック
        $this->app->bind(QiitaScraper::class, function () {
            $mock = Mockery::mock(QiitaScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Article 1', 'url' => 'https://test1.com', 'engagement_count' => 10],
                ['title' => 'Article 2', 'url' => 'https://test2.com', 'engagement_count' => 20],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([
                ['id' => 1], ['id' => 2],
            ]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $this->app->bind(ZennScraper::class, function () {
            $mock = Mockery::mock(ZennScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Article 3', 'url' => 'https://test3.com', 'engagement_count' => 15],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([
                ['id' => 3],
            ]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $this->app->bind(HatenaBookmarkScraper::class, function () {
            $mock = Mockery::mock(HatenaBookmarkScraper::class);
            $mock->shouldReceive('scrapePopularItEntries')->andReturn([]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $result = $this->artisan('scrape:all')
            ->assertExitCode(Command::SUCCESS);
    }

    private function mockScrapers()
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

        $this->app->bind(ZennScraper::class, function () {
            $mock = Mockery::mock(ZennScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'engagement_count' => 10],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([['id' => 1]]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $this->app->bind(HatenaBookmarkScraper::class, function () {
            $mock = Mockery::mock(HatenaBookmarkScraper::class);
            $mock->shouldReceive('scrapePopularItEntries')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'engagement_count' => 100],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([['id' => 1]]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });
    }
}
