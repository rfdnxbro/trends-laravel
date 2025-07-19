<?php

namespace Tests\Unit\Console;

use App\Services\HatenaBookmarkScraper;
use App\Services\QiitaScraper;
use App\Services\ZennScraper;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ScrapeScheduleCommandHandleTest extends TestCase
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
        $this->mockAllScrapers();

        $result = $this->artisan('scrape:schedule')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_handle実行_silentモード()
    {
        // スクレイパーのモック作成（silentでは出力を抑制）
        $this->app->bind(QiitaScraper::class, function () {
            $mock = Mockery::mock(QiitaScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'likes_count' => 10],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([['id' => 1]]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $this->app->bind(ZennScraper::class, function () {
            $mock = Mockery::mock(ZennScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'likes_count' => 10],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([['id' => 1]]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $this->app->bind(HatenaBookmarkScraper::class, function () {
            $mock = Mockery::mock(HatenaBookmarkScraper::class);
            $mock->shouldReceive('scrapePopularItEntries')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'bookmark_count' => 100],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([['id' => 1]]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $result = $this->artisan('scrape:schedule', ['--silent' => true])
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

        $result = $this->artisan('scrape:schedule')
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

        $result = $this->artisan('scrape:schedule')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_handle実行_部分的成功()
    {
        // 一部のスクレイパーは成功、一部は失敗
        $this->app->bind(QiitaScraper::class, function () {
            $mock = Mockery::mock(QiitaScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Success Article', 'url' => 'https://success.com', 'likes_count' => 10],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([['id' => 1]]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $this->app->bind(ZennScraper::class, function () {
            $mock = Mockery::mock(ZennScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andThrow(new \Exception('Zennエラー'));
            $mock->shouldReceive('getErrorLog')->andReturn([
                ['error' => 'Network error', 'attempt' => 2],
            ]);

            return $mock;
        });

        $this->app->bind(HatenaBookmarkScraper::class, function () {
            $mock = Mockery::mock(HatenaBookmarkScraper::class);
            $mock->shouldReceive('scrapePopularItEntries')->andReturn([]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $result = $this->artisan('scrape:schedule')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_handle実行_統計情報表示()
    {
        // より詳細な統計情報をテスト
        $this->app->bind(QiitaScraper::class, function () {
            $mock = Mockery::mock(QiitaScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Article 1', 'url' => 'https://test1.com', 'likes_count' => 10],
                ['title' => 'Article 2', 'url' => 'https://test2.com', 'likes_count' => 20],
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
                ['title' => 'Article 3', 'url' => 'https://test3.com', 'likes_count' => 15],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([
                ['id' => 3],
            ]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $this->app->bind(HatenaBookmarkScraper::class, function () {
            $mock = Mockery::mock(HatenaBookmarkScraper::class);
            $mock->shouldReceive('scrapePopularItEntries')->andReturn([
                ['title' => 'Article 4', 'url' => 'https://test4.com', 'bookmark_count' => 100],
                ['title' => 'Article 5', 'url' => 'https://test5.com', 'bookmark_count' => 200],
                ['title' => 'Article 6', 'url' => 'https://test6.com', 'bookmark_count' => 300],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([
                ['id' => 4], ['id' => 5], ['id' => 6],
            ]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $result = $this->artisan('scrape:schedule')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_handle実行_特定プラットフォーム()
    {
        // Qiitaのみ実行するテスト
        $this->app->bind(QiitaScraper::class, function () {
            $mock = Mockery::mock(QiitaScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'likes_count' => 10],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([['id' => 1]]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $result = $this->artisan('scrape:schedule', ['--platform' => 'qiita'])
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_handle実行_無効なプラットフォーム()
    {
        $result = $this->artisan('scrape:schedule', ['--platform' => 'invalid'])
            ->assertExitCode(Command::FAILURE);
    }

    private function mockAllScrapers()
    {
        $this->app->bind(QiitaScraper::class, function () {
            $mock = Mockery::mock(QiitaScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'likes_count' => 10],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([['id' => 1]]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $this->app->bind(ZennScraper::class, function () {
            $mock = Mockery::mock(ZennScraper::class);
            $mock->shouldReceive('scrapeTrendingArticles')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'likes_count' => 10],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([['id' => 1]]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });

        $this->app->bind(HatenaBookmarkScraper::class, function () {
            $mock = Mockery::mock(HatenaBookmarkScraper::class);
            $mock->shouldReceive('scrapePopularItEntries')->andReturn([
                ['title' => 'Test Article', 'url' => 'https://test.com', 'bookmark_count' => 100],
            ]);
            $mock->shouldReceive('normalizeAndSaveData')->andReturn([['id' => 1]]);
            $mock->shouldReceive('getErrorLog')->andReturn([]);

            return $mock;
        });
    }
}
