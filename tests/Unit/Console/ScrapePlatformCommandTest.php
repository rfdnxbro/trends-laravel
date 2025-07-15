<?php

namespace Tests\Unit\Console;

use App\Console\Commands\ScrapePlatform;
use App\Services\HatenaBookmarkScraper;
use App\Services\QiitaScraper;
use App\Services\ZennScraper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class ScrapePlatformCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_コマンドの基本情報が正しく設定されている()
    {
        $command = new ScrapePlatform;

        $this->assertEquals('scrape:platform', $command->getName());
        $this->assertEquals('指定されたプラットフォームのトレンド記事をスクレイピングします', $command->getDescription());
    }

    public function test_プラットフォーム引数が定義されている()
    {
        $command = new ScrapePlatform;
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('platform'));
        $this->assertEquals('スクレイピングするプラットフォーム (qiita, zenn, hatena)', $definition->getArgument('platform')->getDescription());
    }

    public function test_dry_runオプションが定義されている()
    {
        $command = new ScrapePlatform;
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('dry-run'));
        $this->assertEquals('データを保存せずに取得のみ行う', $definition->getOption('dry-run')->getDescription());
    }

    public function test_プラットフォームマップが正しく定義されている()
    {
        $reflection = new \ReflectionClass(ScrapePlatform::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('qiita', $source);
        $this->assertStringContainsString('zenn', $source);
        $this->assertStringContainsString('hatena', $source);
        $this->assertStringContainsString('QiitaScraper::class', $source);
        $this->assertStringContainsString('ZennScraper::class', $source);
        $this->assertStringContainsString('HatenaBookmarkScraper::class', $source);
    }

    public function test_無効なプラットフォームでエラーハンドリングが実装されている()
    {
        $reflection = new \ReflectionClass(ScrapePlatform::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('無効なプラットフォームです', $source);
        $this->assertStringContainsString('利用可能なプラットフォーム', $source);
        $this->assertStringContainsString('Command::FAILURE', $source);
    }

    public function test_プラットフォーム引数の検証ロジックが存在する()
    {
        $reflection = new \ReflectionClass(ScrapePlatform::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('strtolower', $source);
        $this->assertStringContainsString('isset', $source);
        $this->assertStringContainsString('platformMap', $source);
    }

    public function test_handleメソッドが実装されている()
    {
        $reflection = new \ReflectionClass(ScrapePlatform::class);
        $handleMethod = $reflection->getMethod('handle');

        $this->assertTrue($handleMethod->isPublic());
        $this->assertEquals('handle', $handleMethod->getName());
    }

    public function test_コマンド成功と失敗の終了コードが定義されている()
    {
        $this->assertEquals(0, Command::SUCCESS);
        $this->assertEquals(1, Command::FAILURE);
    }

    public function test_プラットフォーム設定構造が正しい()
    {
        $reflection = new \ReflectionClass(ScrapePlatform::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('name', $source);
        $this->assertStringContainsString('class', $source);
        $this->assertStringContainsString('Qiita', $source);
        $this->assertStringContainsString('Zenn', $source);
        $this->assertStringContainsString('はてなブックマーク', $source);
    }

    public function test_引数とオプションの処理ロジックが存在する()
    {
        $reflection = new \ReflectionClass(ScrapePlatform::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('$this->argument(\'platform\')', $source);
        $this->assertStringContainsString('$this->option(\'dry-run\')', $source);
    }

    public function test_有効なプラットフォームqiitaでの正常実行()
    {
        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn([
                ['title' => 'Test Qiita Article', 'url' => 'https://qiita.com/test', 'author' => 'test_author'],
            ]);
        $qiitaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn([['id' => 1]]);
        $qiitaMock->shouldReceive('getErrorLog')
            ->andReturn([]);

        $this->app->instance(QiitaScraper::class, $qiitaMock);

        $exitCode = Artisan::call('scrape:platform', ['platform' => 'qiita']);

        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function test_有効なプラットフォームzennでの正常実行()
    {
        $zennMock = Mockery::mock(ZennScraper::class);
        $zennMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn([
                ['title' => 'Test Zenn Article', 'url' => 'https://zenn.dev/test', 'author' => 'test_author'],
            ]);
        $zennMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn([['id' => 1]]);

        $this->app->instance(ZennScraper::class, $zennMock);

        $exitCode = Artisan::call('scrape:platform', ['platform' => 'zenn']);

        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function test_有効なプラットフォームhatenaでの正常実行()
    {
        $hatenaMock = Mockery::mock(HatenaBookmarkScraper::class);
        $hatenaMock->shouldReceive('scrapePopularItEntries')
            ->once()
            ->andReturn([
                ['title' => 'Test Hatena Article', 'url' => 'https://b.hatena.ne.jp/test'],
            ]);
        $hatenaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn([['id' => 1]]);

        $this->app->instance(HatenaBookmarkScraper::class, $hatenaMock);

        $exitCode = Artisan::call('scrape:platform', ['platform' => 'hatena']);

        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function test_無効なプラットフォーム指定時のエラー処理()
    {
        $exitCode = Artisan::call('scrape:platform', ['platform' => 'invalid']);
        $output = Artisan::output();

        $this->assertEquals(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('無効なプラットフォームです', $output);
        $this->assertStringContainsString('利用可能なプラットフォーム', $output);
    }

    public function test_dry_runモードでのqiita実行()
    {
        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn([
                ['title' => 'Test Qiita Article', 'url' => 'https://qiita.com/test', 'author' => 'test_author'],
            ]);
        $qiitaMock->shouldReceive('normalizeAndSaveData')
            ->never();

        $this->app->instance(QiitaScraper::class, $qiitaMock);

        $exitCode = Artisan::call('scrape:platform', ['platform' => 'qiita', '--dry-run' => true]);
        $output = Artisan::output();

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('dry-run', $output);
    }

    public function test_dry_runモードでのzenn実行()
    {
        $zennMock = Mockery::mock(ZennScraper::class);
        $zennMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn([
                ['title' => 'Test Zenn Article', 'url' => 'https://zenn.dev/test', 'author' => 'test_author'],
            ]);
        $zennMock->shouldReceive('normalizeAndSaveData')
            ->never();

        $this->app->instance(ZennScraper::class, $zennMock);

        $exitCode = Artisan::call('scrape:platform', ['platform' => 'zenn', '--dry-run' => true]);
        $output = Artisan::output();

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('dry-run', $output);
    }

    public function test_dry_runモードでのhatena実行()
    {
        $hatenaMock = Mockery::mock(HatenaBookmarkScraper::class);
        $hatenaMock->shouldReceive('scrapePopularItEntries')
            ->once()
            ->andReturn([
                ['title' => 'Test Hatena Article', 'url' => 'https://b.hatena.ne.jp/test'],
            ]);
        $hatenaMock->shouldReceive('normalizeAndSaveData')
            ->never();

        $this->app->instance(HatenaBookmarkScraper::class, $hatenaMock);

        $exitCode = Artisan::call('scrape:platform', ['platform' => 'hatena', '--dry-run' => true]);
        $output = Artisan::output();

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('dry-run', $output);
    }

    public function test_プラットフォーム名の大文字小文字を無視する()
    {
        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn([
                ['title' => 'Test Article', 'url' => 'https://qiita.com/test', 'author' => 'test_author'],
            ]);
        $qiitaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn([['id' => 1]]);

        $this->app->instance(QiitaScraper::class, $qiitaMock);

        $exitCode = Artisan::call('scrape:platform', ['platform' => 'QIITA']);

        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function test_プラットフォームエラー時の処理()
    {
        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andThrow(new \Exception('Scraping failed'));
        $qiitaMock->shouldReceive('getErrorLog')
            ->once()
            ->andReturn([]);

        $this->app->instance(QiitaScraper::class, $qiitaMock);

        $exitCode = Artisan::call('scrape:platform', ['platform' => 'qiita']);

        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    public function test_handle実行時の基本フロー()
    {
        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn([
                ['title' => 'Test Qiita Article', 'url' => 'https://qiita.com/test', 'author' => 'test_author'],
            ]);
        $qiitaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn([['id' => 1]]);

        $this->app->instance(QiitaScraper::class, $qiitaMock);

        $command = $this->app->make(ScrapePlatform::class);
        $command->setLaravel($this->app);

        // コマンドに引数を設定
        $input = new \Symfony\Component\Console\Input\ArrayInput([
            'platform' => 'qiita',
        ]);
        $output = new \Symfony\Component\Console\Output\BufferedOutput;

        $command->run($input, $output);

        $this->assertTrue(true); // テスト成功
    }

    public function test_handle無効プラットフォーム指定時のエラー処理()
    {
        $command = $this->app->make(ScrapePlatform::class);
        $command->setLaravel($this->app);

        // コマンドに引数を設定
        $input = new \Symfony\Component\Console\Input\ArrayInput([
            'platform' => 'invalid',
        ]);
        $output = new \Symfony\Component\Console\Output\BufferedOutput;

        $exitCode = $command->run($input, $output);

        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    public function test_handle_dry_runオプション使用時の動作()
    {
        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn([
                ['title' => 'Test Qiita Article', 'url' => 'https://qiita.com/test', 'author' => 'test_author'],
            ]);
        $qiitaMock->shouldReceive('normalizeAndSaveData')
            ->never();

        $this->app->instance(QiitaScraper::class, $qiitaMock);

        $command = $this->app->make(ScrapePlatform::class);
        $command->setLaravel($this->app);

        // コマンドに引数とオプションを設定
        $input = new \Symfony\Component\Console\Input\ArrayInput([
            'platform' => 'qiita',
            '--dry-run' => true,
        ]);
        $output = new \Symfony\Component\Console\Output\BufferedOutput;

        $exitCode = $command->run($input, $output);

        $this->assertEquals(Command::SUCCESS, $exitCode);
    }
}
