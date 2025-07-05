<?php

namespace Tests\Unit\Services;

use App\Services\BaseScraper;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BaseScraperTest extends TestCase
{
    private TestScraper $scraper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scraper = new TestScraper;
    }

    public function test_基本的なスクレイピングが成功する()
    {
        Http::fake([
            'https://example.com' => Http::response(['data' => 'test'], 200),
        ]);

        $result = $this->scraper->scrape('https://example.com');

        $this->assertEquals(['data' => 'test'], $result);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com';
        });
    }

    public function test_レート制限の設定ができる()
    {
        $this->scraper->setRateLimit(60);

        $reflection = new \ReflectionClass($this->scraper);
        $property = $reflection->getProperty('requestsPerMinute');
        $property->setAccessible(true);

        $this->assertEquals(60, $property->getValue($this->scraper));
    }

    public function test_リトライ設定ができる()
    {
        $this->scraper->setRetryOptions(5, 2);

        $reflection = new \ReflectionClass($this->scraper);

        $maxRetriesProperty = $reflection->getProperty('maxRetries');
        $maxRetriesProperty->setAccessible(true);

        $delayProperty = $reflection->getProperty('delaySeconds');
        $delayProperty->setAccessible(true);

        $this->assertEquals(5, $maxRetriesProperty->getValue($this->scraper));
        $this->assertEquals(2, $delayProperty->getValue($this->scraper));
    }

    public function test_ヘッダーの設定ができる()
    {
        $customHeaders = ['X-Custom-Header' => 'test-value'];
        $this->scraper->setHeaders($customHeaders);

        $reflection = new \ReflectionClass($this->scraper);
        $property = $reflection->getProperty('headers');
        $property->setAccessible(true);
        $headers = $property->getValue($this->scraper);

        $this->assertArrayHasKey('X-Custom-Header', $headers);
        $this->assertEquals('test-value', $headers['X-Custom-Header']);
    }

    public function test_タイムアウト設定ができる()
    {
        $this->scraper->setTimeout(60);

        $reflection = new \ReflectionClass($this->scraper);
        $property = $reflection->getProperty('timeout');
        $property->setAccessible(true);

        $this->assertEquals(60, $property->getValue($this->scraper));
    }

    public function test_最後のレスポンス情報を取得できる()
    {
        Http::fake([
            'https://example.com' => Http::response(['data' => 'test'], 200, ['X-Response-Header' => 'value']),
        ]);

        $this->scraper->scrape('https://example.com');
        $lastResponse = $this->scraper->getLastResponse();

        $this->assertNotNull($lastResponse);
        $this->assertEquals(200, $lastResponse['status']);
        $this->assertArrayHasKey('headers', $lastResponse);
        $this->assertArrayHasKey('body', $lastResponse);
    }

    public function test_httpエラー時にリトライされる()
    {
        // delaySecondsを0にしてsleepを回避
        $this->scraper->setRetryOptions(3, 0);

        Http::fake([
            'https://example.com' => Http::sequence()
                ->push(null, 500)
                ->push(null, 500)
                ->push(['data' => 'success'], 200),
        ]);

        Log::shouldReceive('error')->twice();
        Log::shouldReceive('info')->once();

        $result = $this->scraper->scrape('https://example.com');

        $this->assertEquals(['data' => 'success'], $result);
        $errorLog = $this->scraper->getErrorLog();
        $this->assertCount(2, $errorLog);
    }

    public function test_最大リトライ回数に達すると例外が発生する()
    {
        $this->scraper->setRetryOptions(2, 0);

        Http::fake([
            'https://example.com' => Http::response(null, 500),
        ]);

        Log::shouldReceive('error')->times(2);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('HTTP Error: 500');

        $this->scraper->scrape('https://example.com');
    }

    public function test_レート制限機能の設定テスト()
    {
        // レート制限の設定テストのみ（実際のsleep処理は除外）
        $this->scraper->setRateLimit(60);

        $reflection = new \ReflectionClass($this->scraper);
        $property = $reflection->getProperty('requestsPerMinute');
        $property->setAccessible(true);

        $this->assertEquals(60, $property->getValue($this->scraper));

        // 通常のスクレイピングが動作することを確認
        Http::fake([
            'https://example.com' => Http::response(['data' => 'test'], 200),
        ]);

        Log::shouldReceive('info')
            ->with('Scraping successful', \Mockery::type('array'));

        $result = $this->scraper->scrape('https://example.com');
        $this->assertEquals(['data' => 'test'], $result);
    }

    public function test_成功時にログが記録される()
    {
        Http::fake([
            'https://example.com' => Http::response(['data' => 'test'], 200),
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Scraping successful', [
                'scraper' => 'TestScraper',
                'url' => 'https://example.com',
                'data_count' => 1,
            ]);

        $this->scraper->scrape('https://example.com');
    }

    public function test_エラー時にログが記録される()
    {
        // delaySecondsを0にしてsleepを回避
        $this->scraper->setRetryOptions(3, 0);

        Http::fake([
            'https://example.com' => Http::response(null, 500),
        ]);

        Log::shouldReceive('error')
            ->times(3)
            ->with('Scraping failed', \Mockery::type('array'));

        $this->expectException(Exception::class);
        $this->scraper->scrape('https://example.com');
    }

    public function test_デフォルトヘッダーが設定される()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $property = $reflection->getProperty('headers');
        $property->setAccessible(true);
        $headers = $property->getValue($this->scraper);

        $this->assertArrayHasKey('User-Agent', $headers);
        $this->assertEquals('DevCorpTrends/1.0', $headers['User-Agent']);
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertArrayHasKey('Accept-Language', $headers);
    }

    public function test_エラーログが正しく記録される()
    {
        // delaySecondsを0にしてsleepを回避
        $this->scraper->setRetryOptions(3, 0);

        Http::fake([
            'https://example.com' => Http::response(null, 500),
        ]);

        Log::shouldReceive('error')->times(3);

        try {
            $this->scraper->scrape('https://example.com');
        } catch (Exception $e) {
            // 例外は期待される
        }

        $errorLog = $this->scraper->getErrorLog();
        $this->assertCount(3, $errorLog);

        $firstError = $errorLog[0];
        $this->assertEquals('TestScraper', $firstError['scraper']);
        $this->assertEquals('https://example.com', $firstError['url']);
        $this->assertEquals('HTTP Error: 500', $firstError['error']);
        $this->assertEquals(1, $firstError['attempt']);
        $this->assertArrayHasKey('timestamp', $firstError);
    }
}

// テスト用の具象クラス
class TestScraper extends BaseScraper
{
    protected function parseResponse(Response $response): array
    {
        return $response->json() ?? [];
    }
}
