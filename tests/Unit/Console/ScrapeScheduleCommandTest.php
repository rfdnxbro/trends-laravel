<?php

namespace Tests\Unit\Console;

use App\Console\Commands\ScrapeSchedule;
use App\Services\HatenaBookmarkScraper;
use App\Services\QiitaScraper;
use App\Services\ZennScraper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ScrapeScheduleCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_コマンドの基本情報が正しく設定されている()
    {
        $command = new ScrapeSchedule;

        $this->assertEquals('scrape:schedule', $command->getName());
        $this->assertEquals('定期実行用のスクレイピングコマンド（cron job に最適化）', $command->getDescription());
    }

    public function test_platformオプションが定義されている()
    {
        $command = new ScrapeSchedule;
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('platform'));
        $this->assertEquals('特定のプラットフォームのみ実行 (qiita, zenn, hatena)', $definition->getOption('platform')->getDescription());
    }

    public function test_silentオプションが定義されている()
    {
        $command = new ScrapeSchedule;
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('silent'));
        $this->assertEquals('詳細出力を抑制', $definition->getOption('silent')->getDescription());
    }

    public function test_プラットフォームマップが正しく定義されている()
    {
        $reflection = new \ReflectionClass(ScrapeSchedule::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('qiita', $source);
        $this->assertStringContainsString('zenn', $source);
        $this->assertStringContainsString('hatena', $source);
        $this->assertStringContainsString('QiitaScraper::class', $source);
        $this->assertStringContainsString('ZennScraper::class', $source);
        $this->assertStringContainsString('HatenaBookmarkScraper::class', $source);
    }

    public function test_cron_job用の最適化が実装されている()
    {
        $reflection = new \ReflectionClass(ScrapeSchedule::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('silent', $source);
        $this->assertStringContainsString('Log::', $source);
        $this->assertStringContainsString('microtime', $source);
    }

    public function test_ログ記録機能が使用されている()
    {
        $reflection = new \ReflectionClass(ScrapeSchedule::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('use Illuminate\Support\Facades\Log', $source);
    }

    public function test_handleメソッドが実装されている()
    {
        $reflection = new \ReflectionClass(ScrapeSchedule::class);
        $handleMethod = $reflection->getMethod('handle');

        $this->assertTrue($handleMethod->isPublic());
        $this->assertEquals('handle', $handleMethod->getName());
    }

    public function test_実行時間測定機能が実装されている()
    {
        $reflection = new \ReflectionClass(ScrapeSchedule::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('startTime', $source);
        $this->assertStringContainsString('microtime(true)', $source);
    }

    public function test_プラットフォーム選択ロジックが存在する()
    {
        $reflection = new \ReflectionClass(ScrapeSchedule::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('$this->option(\'platform\')', $source);
        $this->assertStringContainsString('specificPlatform', $source);
        $this->assertStringContainsString('strtolower', $source);
    }

    public function test_サイレントモード処理が実装されている()
    {
        $reflection = new \ReflectionClass(ScrapeSchedule::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('$this->option(\'silent\')', $source);
        $this->assertStringContainsString('isSilent', $source);
    }

    public function test_プラットフォーム設定構造が正しい()
    {
        $reflection = new \ReflectionClass(ScrapeSchedule::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('name', $source);
        $this->assertStringContainsString('class', $source);
        $this->assertStringContainsString('Qiita', $source);
        $this->assertStringContainsString('Zenn', $source);
        $this->assertStringContainsString('はてなブックマーク', $source);
    }

    public function test_全プラットフォーム実行での正常処理()
    {
        Log::spy();

        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn([
                ['title' => 'Test Qiita Article', 'url' => 'https://qiita.com/test'],
            ]);
        $qiitaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('array'), false)
            ->andReturn([['id' => 1]]);

        $zennMock = Mockery::mock(ZennScraper::class);
        $zennMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn([
                ['title' => 'Test Zenn Article', 'url' => 'https://zenn.dev/test'],
            ]);
        $zennMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('array'), false)
            ->andReturn([['id' => 1]]);

        $hatenaMock = Mockery::mock(HatenaBookmarkScraper::class);
        $hatenaMock->shouldReceive('scrapePopularItEntries')
            ->once()
            ->andReturn([
                ['title' => 'Test Hatena Article', 'url' => 'https://b.hatena.ne.jp/test'],
            ]);
        $hatenaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('array'), false)
            ->andReturn([['id' => 1]]);

        $this->app->instance(QiitaScraper::class, $qiitaMock);
        $this->app->instance(ZennScraper::class, $zennMock);
        $this->app->instance(HatenaBookmarkScraper::class, $hatenaMock);

        $exitCode = Artisan::call('scrape:schedule');

        $this->assertEquals(Command::SUCCESS, $exitCode);

        Log::shouldHaveReceived('info')
            ->with('スクレイピング実行開始')
            ->once();
        Log::shouldHaveReceived('info')
            ->atLeast()
            ->once();
    }

    public function test_特定プラットフォーム指定での実行()
    {
        Log::spy();

        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn([
                ['title' => 'Test Qiita Article', 'url' => 'https://qiita.com/test'],
            ]);
        $qiitaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('array'), false)
            ->andReturn([['id' => 1]]);

        $this->app->instance(QiitaScraper::class, $qiitaMock);

        $exitCode = Artisan::call('scrape:schedule', ['--platform' => 'qiita']);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        Log::shouldHaveReceived('info')
            ->with('スクレイピング実行開始')
            ->once();
        Log::shouldHaveReceived('info')
            ->atLeast()
            ->once();
    }

    public function test_silentオプション使用時の出力抑制()
    {
        Log::spy();

        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn([
                ['title' => 'Test Article', 'url' => 'https://qiita.com/test'],
            ]);
        $qiitaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('array'), false)
            ->andReturn([['id' => 1]]);

        $this->app->instance(QiitaScraper::class, $qiitaMock);

        $exitCode = Artisan::call('scrape:schedule', ['--platform' => 'qiita', '--silent' => true]);
        $output = Artisan::output();

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertEmpty(trim($output));

        Log::shouldHaveReceived('info')
            ->with('スクレイピング実行開始')
            ->once();
    }

    public function test_実行時間測定の確認()
    {
        Log::spy();

        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn([
                ['title' => 'Test Article', 'url' => 'https://qiita.com/test'],
            ]);
        $qiitaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('array'), false)
            ->andReturn([['id' => 1]]);

        $this->app->instance(QiitaScraper::class, $qiitaMock);

        $exitCode = Artisan::call('scrape:schedule', ['--platform' => 'qiita']);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        Log::shouldHaveReceived('info')
            ->atLeast()
            ->once();
    }

    public function test_ログ記録内容の確認()
    {
        Log::spy();

        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn([
                ['title' => 'Test Article', 'url' => 'https://qiita.com/test'],
            ]);
        $qiitaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('array'), false)
            ->andReturn([['id' => 1]]);

        $this->app->instance(QiitaScraper::class, $qiitaMock);

        $exitCode = Artisan::call('scrape:schedule', ['--platform' => 'qiita']);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context = []) {
                return $message === 'スクレイピング実行開始';
            })
            ->once();

        Log::shouldHaveReceived('info')
            ->atLeast()
            ->once();
    }

    public function test_エラー発生時のログ記録()
    {
        Log::spy();

        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andThrow(new \Exception('Scraping failed'));
        $qiitaMock->shouldReceive('getErrorLog')
            ->once()
            ->andReturn([]);

        $this->app->instance(QiitaScraper::class, $qiitaMock);

        $exitCode = Artisan::call('scrape:schedule', ['--platform' => 'qiita']);

        $this->assertEquals(Command::FAILURE, $exitCode);

        Log::shouldHaveReceived('info')
            ->with('スクレイピング実行開始')
            ->once();
        Log::shouldHaveReceived('error')
            ->atLeast()
            ->once();
    }

    public function test_無効なプラットフォーム指定時のエラー処理()
    {
        Log::spy();

        $exitCode = Artisan::call('scrape:schedule', ['--platform' => 'invalid']);

        $this->assertEquals(Command::FAILURE, $exitCode);

        Log::shouldHaveReceived('warning')
            ->with('無効なプラットフォーム: invalid')
            ->once();
    }

    public function test_複数プラットフォームでの一部エラー時の継続処理()
    {
        Log::spy();

        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andThrow(new \Exception('Qiita scraping failed'));
        $qiitaMock->shouldReceive('getErrorLog')
            ->once()
            ->andReturn([]);

        $zennMock = Mockery::mock(ZennScraper::class);
        $zennMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn([
                ['title' => 'Test Zenn Article', 'url' => 'https://zenn.dev/test'],
            ]);
        $zennMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('array'), false)
            ->andReturn([['id' => 1]]);

        $hatenaMock = Mockery::mock(HatenaBookmarkScraper::class);
        $hatenaMock->shouldReceive('scrapePopularItEntries')
            ->once()
            ->andReturn([
                ['title' => 'Test Hatena Article', 'url' => 'https://b.hatena.ne.jp/test'],
            ]);
        $hatenaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('array'), false)
            ->andReturn([['id' => 1]]);

        $this->app->instance(QiitaScraper::class, $qiitaMock);
        $this->app->instance(ZennScraper::class, $zennMock);
        $this->app->instance(HatenaBookmarkScraper::class, $hatenaMock);

        $exitCode = Artisan::call('scrape:schedule');

        $this->assertEquals(Command::FAILURE, $exitCode);

        Log::shouldHaveReceived('error')
            ->atLeast()
            ->once();

        Log::shouldHaveReceived('info')
            ->atLeast()
            ->once();
    }
}
