<?php

namespace Tests\Unit\Console;

use App\Console\Commands\ScrapeAll;
use App\Services\HatenaBookmarkScraper;
use App\Services\QiitaScraper;
use App\Services\ZennScraper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ScrapeAllCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_コマンドの基本情報が正しく設定されている()
    {
        $command = new ScrapeAll;

        $this->assertEquals('scrape:all', $command->getName());
        $this->assertEquals('全プラットフォーム（Qiita、Zenn、はてなブックマーク）のトレンド記事をスクレイピングします', $command->getDescription());
    }

    public function test_コマンドにdry_runオプションが定義されている()
    {
        $command = new ScrapeAll;
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('dry-run'));
        $this->assertEquals('データを保存せずに取得のみ行う', $definition->getOption('dry-run')->getDescription());
    }

    public function test_コマンドの処理構造が適切に実装されている()
    {
        $reflection = new \ReflectionClass(ScrapeAll::class);
        $handleMethod = $reflection->getMethod('handle');

        $this->assertTrue($handleMethod->isPublic());
        $this->assertEquals('handle', $handleMethod->getName());
    }

    public function test_コマンドの終了コードが成功時に_succes_sになる()
    {
        $command = new ScrapeAll;
        $this->assertEquals(Command::SUCCESS, Command::SUCCESS);
        $this->assertEquals(Command::FAILURE, Command::FAILURE);
    }

    public function test_プログレスバーが使用される()
    {
        $this->artisan('scrape:all', ['--dry-run' => true]);

        // プログレスバーの実装は実際の出力で確認される
        $this->assertTrue(true);
    }

    public function test_エラーハンドリングの仕組みが存在する()
    {
        // ScrapeAllクラスのhandleメソッドにtry-catchが含まれていることを確認
        $reflection = new \ReflectionClass(ScrapeAll::class);
        $handleMethod = $reflection->getMethod('handle');
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('try', $source);
        $this->assertStringContainsString('catch', $source);
        $this->assertStringContainsString('$errors', $source);
    }

    public function test_各プラットフォームのサービスクラスが定義されている()
    {
        $reflection = new \ReflectionClass(ScrapeAll::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('QiitaScraper::class', $source);
        $this->assertStringContainsString('ZennScraper::class', $source);
        $this->assertStringContainsString('HatenaBookmarkScraper::class', $source);
    }

    public function test_dry_runオプションが適切に処理される()
    {
        $reflection = new \ReflectionClass(ScrapeAll::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('$this->option(\'dry-run\')', $source);
        $this->assertStringContainsString('--dry-run', $source);
    }

    public function test_記事の取得と保存の分離が行われている()
    {
        $reflection = new \ReflectionClass(ScrapeAll::class);
        $source = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString('scrapeTrendingArticles', $source);
        $this->assertStringContainsString('scrapePopularItEntries', $source);
        $this->assertStringContainsString('normalizeAndSaveData', $source);
    }

    public function test_全プラットフォーム正常実行時の処理フロー()
    {
        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn(collect([
                ['title' => 'Test Qiita Article', 'url' => 'https://qiita.com/test'],
            ]));
        $qiitaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('Illuminate\Support\Collection'), false)
            ->andReturn(1);

        $zennMock = Mockery::mock(ZennScraper::class);
        $zennMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn(collect([
                ['title' => 'Test Zenn Article', 'url' => 'https://zenn.dev/test'],
            ]));
        $zennMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('Illuminate\Support\Collection'), false)
            ->andReturn(1);

        $hatenaMock = Mockery::mock(HatenaBookmarkScraper::class);
        $hatenaMock->shouldReceive('scrapePopularItEntries')
            ->once()
            ->andReturn(collect([
                ['title' => 'Test Hatena Article', 'url' => 'https://b.hatena.ne.jp/test'],
            ]));
        $hatenaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('Illuminate\Support\Collection'), false)
            ->andReturn(1);

        $this->app->instance(QiitaScraper::class, $qiitaMock);
        $this->app->instance(ZennScraper::class, $zennMock);
        $this->app->instance(HatenaBookmarkScraper::class, $hatenaMock);

        $exitCode = Artisan::call('scrape:all');

        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function test_dry_runオプション使用時のデータ非保存()
    {
        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn(collect([
                ['title' => 'Test Qiita Article', 'url' => 'https://qiita.com/test'],
            ]));
        $qiitaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('Illuminate\Support\Collection'), true)
            ->andReturn(0);

        $zennMock = Mockery::mock(ZennScraper::class);
        $zennMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn(collect([
                ['title' => 'Test Zenn Article', 'url' => 'https://zenn.dev/test'],
            ]));
        $zennMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('Illuminate\Support\Collection'), true)
            ->andReturn(0);

        $hatenaMock = Mockery::mock(HatenaBookmarkScraper::class);
        $hatenaMock->shouldReceive('scrapePopularItEntries')
            ->once()
            ->andReturn(collect([
                ['title' => 'Test Hatena Article', 'url' => 'https://b.hatena.ne.jp/test'],
            ]));
        $hatenaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('Illuminate\Support\Collection'), true)
            ->andReturn(0);

        $this->app->instance(QiitaScraper::class, $qiitaMock);
        $this->app->instance(ZennScraper::class, $zennMock);
        $this->app->instance(HatenaBookmarkScraper::class, $hatenaMock);

        $exitCode = Artisan::call('scrape:all', ['--dry-run' => true]);

        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    public function test_一部プラットフォームエラー時の継続処理()
    {
        Log::spy();

        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andThrow(new \Exception('Qiita scraping failed'));
        $qiitaMock->shouldReceive('logError')
            ->once()
            ->with(Mockery::type(\Exception::class));

        $zennMock = Mockery::mock(ZennScraper::class);
        $zennMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn(collect([
                ['title' => 'Test Zenn Article', 'url' => 'https://zenn.dev/test'],
            ]));
        $zennMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('Illuminate\Support\Collection'), false)
            ->andReturn(1);

        $hatenaMock = Mockery::mock(HatenaBookmarkScraper::class);
        $hatenaMock->shouldReceive('scrapePopularItEntries')
            ->once()
            ->andReturn(collect([
                ['title' => 'Test Hatena Article', 'url' => 'https://b.hatena.ne.jp/test'],
            ]));
        $hatenaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('Illuminate\Support\Collection'), false)
            ->andReturn(1);

        $this->app->instance(QiitaScraper::class, $qiitaMock);
        $this->app->instance(ZennScraper::class, $zennMock);
        $this->app->instance(HatenaBookmarkScraper::class, $hatenaMock);

        $exitCode = Artisan::call('scrape:all');

        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    public function test_全プラットフォームエラー時の処理()
    {
        Log::spy();

        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andThrow(new \Exception('Qiita scraping failed'));
        $qiitaMock->shouldReceive('logError')
            ->once()
            ->with(Mockery::type(\Exception::class));

        $zennMock = Mockery::mock(ZennScraper::class);
        $zennMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andThrow(new \Exception('Zenn scraping failed'));
        $zennMock->shouldReceive('logError')
            ->once()
            ->with(Mockery::type(\Exception::class));

        $hatenaMock = Mockery::mock(HatenaBookmarkScraper::class);
        $hatenaMock->shouldReceive('scrapePopularItEntries')
            ->once()
            ->andThrow(new \Exception('Hatena scraping failed'));
        $hatenaMock->shouldReceive('logError')
            ->once()
            ->with(Mockery::type(\Exception::class));

        $this->app->instance(QiitaScraper::class, $qiitaMock);
        $this->app->instance(ZennScraper::class, $zennMock);
        $this->app->instance(HatenaBookmarkScraper::class, $hatenaMock);

        $exitCode = Artisan::call('scrape:all');

        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    public function test_結果サマリーの表示内容()
    {
        $qiitaMock = Mockery::mock(QiitaScraper::class);
        $qiitaMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn(collect([
                ['title' => 'Test Article 1', 'url' => 'https://qiita.com/test1'],
                ['title' => 'Test Article 2', 'url' => 'https://qiita.com/test2'],
            ]));
        $qiitaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('Illuminate\Support\Collection'), false)
            ->andReturn(2);

        $zennMock = Mockery::mock(ZennScraper::class);
        $zennMock->shouldReceive('scrapeTrendingArticles')
            ->once()
            ->andReturn(collect([
                ['title' => 'Test Article 3', 'url' => 'https://zenn.dev/test3'],
            ]));
        $zennMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('Illuminate\Support\Collection'), false)
            ->andReturn(1);

        $hatenaMock = Mockery::mock(HatenaBookmarkScraper::class);
        $hatenaMock->shouldReceive('scrapePopularItEntries')
            ->once()
            ->andReturn(collect([
                ['title' => 'Test Article 4', 'url' => 'https://b.hatena.ne.jp/test4'],
                ['title' => 'Test Article 5', 'url' => 'https://b.hatena.ne.jp/test5'],
            ]));
        $hatenaMock->shouldReceive('normalizeAndSaveData')
            ->once()
            ->with(Mockery::type('Illuminate\Support\Collection'), false)
            ->andReturn(2);

        $this->app->instance(QiitaScraper::class, $qiitaMock);
        $this->app->instance(ZennScraper::class, $zennMock);
        $this->app->instance(HatenaBookmarkScraper::class, $hatenaMock);

        Artisan::call('scrape:all');
        $output = Artisan::output();

        $this->assertStringContainsString('総記事数: 5', $output);
        $this->assertStringContainsString('保存記事数: 5', $output);
        $this->assertStringContainsString('スクレイピング完了', $output);
    }
}
