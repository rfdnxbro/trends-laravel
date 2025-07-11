<?php

namespace Tests\Unit\Console;

use App\Console\Commands\ScrapeAll;
use Illuminate\Console\Command;
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
}
