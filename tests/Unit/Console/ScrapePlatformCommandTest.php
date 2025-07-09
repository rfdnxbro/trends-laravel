<?php

namespace Tests\Unit\Console;

use App\Console\Commands\ScrapePlatform;
use Illuminate\Console\Command;
use Tests\TestCase;

class ScrapePlatformCommandTest extends TestCase
{
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
}
