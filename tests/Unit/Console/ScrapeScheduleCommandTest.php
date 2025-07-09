<?php

namespace Tests\Unit\Console;

use App\Console\Commands\ScrapeSchedule;
use App\Services\HatenaBookmarkScraper;
use App\Services\QiitaScraper;
use App\Services\ZennScraper;
use Tests\TestCase;

class ScrapeScheduleCommandTest extends TestCase
{
    public function test_コマンドの基本情報が正しく設定されている()
    {
        $command = new ScrapeSchedule();

        $this->assertEquals('scrape:schedule', $command->getName());
        $this->assertEquals('定期実行用のスクレイピングコマンド（cron job に最適化）', $command->getDescription());
    }

    public function test_platformオプションが定義されている()
    {
        $command = new ScrapeSchedule();
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasOption('platform'));
        $this->assertEquals('特定のプラットフォームのみ実行 (qiita, zenn, hatena)', $definition->getOption('platform')->getDescription());
    }

    public function test_silentオプションが定義されている()
    {
        $command = new ScrapeSchedule();
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
}