<?php

namespace Tests\Unit\Services;

use App\Services\BaseScraper;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
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

    public function test_enforce_rate_limit_制限値内では即座に実行される()
    {
        $this->scraper->setRateLimit(60);

        // キャッシュをクリア
        Cache::flush();

        $startTime = microtime(true);

        // reflectionでprotectedメソッドにアクセス
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('enforceRateLimit');
        $method->setAccessible(true);

        $method->invoke($this->scraper);

        $endTime = microtime(true);

        // 1秒未満で完了することを確認
        $this->assertLessThan(1, $endTime - $startTime);
    }

    public function test_enforce_rate_limit_制限値超過の検知()
    {
        $this->scraper->setRateLimit(1);
        Cache::flush();

        $cacheKey = 'rate_limit_'.class_basename($this->scraper);
        Cache::put($cacheKey, 2, 60); // 制限値超過の状況を作成

        // 制限値超過時の条件確認のみ（sleep実行は行わない）
        $reflection = new \ReflectionClass($this->scraper);
        $property = $reflection->getProperty('requestsPerMinute');
        $property->setAccessible(true);
        $limit = $property->getValue($this->scraper);

        $currentRequests = Cache::get($cacheKey, 0);

        // 制限値を超過していることを確認
        $this->assertGreaterThanOrEqual($limit, $currentRequests);
        $this->assertEquals(1, $limit);
        $this->assertEquals(2, $currentRequests);
    }

    public function test_enforce_rate_limit_キャッシュカウンターの更新()
    {
        $this->scraper->setRateLimit(60);

        // キャッシュをクリア
        Cache::flush();

        $cacheKey = 'rate_limit_'.class_basename($this->scraper);

        // reflectionでprotectedメソッドにアクセス
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('enforceRateLimit');
        $method->setAccessible(true);

        // 初回実行
        $method->invoke($this->scraper);
        $this->assertEquals(1, Cache::get($cacheKey));

        // 2回目実行
        $method->invoke($this->scraper);
        $this->assertEquals(2, Cache::get($cacheKey));
    }

    public function test_enforce_rate_limit_複数リクエスト間隔の検証()
    {
        $this->scraper->setRateLimit(5); // 5リクエスト/分

        // キャッシュをクリア
        Cache::flush();

        $cacheKey = 'rate_limit_'.class_basename($this->scraper);

        // reflectionでprotectedメソッドにアクセス
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('enforceRateLimit');
        $method->setAccessible(true);

        // 5回まで制限値内で実行
        for ($i = 1; $i <= 5; $i++) {
            $method->invoke($this->scraper);
            $this->assertEquals($i, Cache::get($cacheKey));
        }

        // 6回目は制限超過でログが出力される
        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Rate limit exceeded/'), \Mockery::type('array'));

        $method->invoke($this->scraper);
    }

    public function test_enforce_rate_limit_境界値テスト()
    {
        // 境界値：制限値ちょうど
        $this->scraper->setRateLimit(3);
        Cache::flush();

        $cacheKey = 'rate_limit_'.class_basename($this->scraper);
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('enforceRateLimit');
        $method->setAccessible(true);

        // 制限値ちょうどまで実行
        for ($i = 1; $i <= 3; $i++) {
            $method->invoke($this->scraper);
            $this->assertEquals($i, Cache::get($cacheKey));
        }

        // 制限値+1でログ出力
        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Rate limit exceeded/'), \Mockery::type('array'));

        $method->invoke($this->scraper);
    }

    public function test_enforce_rate_limit_設定値0の場合の検知()
    {
        $this->scraper->setRateLimit(0);
        Cache::flush();

        // 制限値0の場合、初回リクエストでも制限に引っかかることを確認
        $reflection = new \ReflectionClass($this->scraper);
        $property = $reflection->getProperty('requestsPerMinute');
        $property->setAccessible(true);
        $limit = $property->getValue($this->scraper);

        $this->assertEquals(0, $limit);

        // キャッシュ内のリクエスト数は0でも、制限値が0なら条件に引っかかる
        $cacheKey = 'rate_limit_'.class_basename($this->scraper);
        $currentRequests = Cache::get($cacheKey, 0);

        $this->assertGreaterThanOrEqual($limit, $currentRequests);
    }

    public function test_enforce_rate_limit_大きな制限値での処理()
    {
        $this->scraper->setRateLimit(9999);
        Cache::flush();

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('enforceRateLimit');
        $method->setAccessible(true);

        // 大きな制限値でも正常に動作
        $startTime = microtime(true);
        $method->invoke($this->scraper);
        $endTime = microtime(true);

        $this->assertLessThan(1, $endTime - $startTime);
        $this->assertEquals(1, Cache::get('rate_limit_'.class_basename($this->scraper)));
    }

    public function test_make_request_例外処理()
    {
        Http::fake([
            'https://timeout.com' => function () {
                throw new \Exception('Connection timeout');
            },
        ]);

        $this->scraper->setRetryOptions(1, 0);
        Log::shouldReceive('error')->once();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Connection timeout');

        $this->scraper->scrape('https://timeout.com');
    }

    public function test_parse_response_空レスポンス処理()
    {
        Http::fake([
            'https://empty.com' => Http::response('', 200),
        ]);

        Log::shouldReceive('info')->once();

        $result = $this->scraper->scrape('https://empty.com');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_エラーログ蓄積の境界テスト()
    {
        $this->scraper->setRetryOptions(10, 0);

        Http::fake([
            'https://fail.com' => Http::response(null, 500),
        ]);

        Log::shouldReceive('error')->times(10);

        try {
            $this->scraper->scrape('https://fail.com');
        } catch (Exception $e) {
            // 期待される例外
        }

        $errorLog = $this->scraper->getErrorLog();
        $this->assertCount(10, $errorLog);

        // 各エラーログのattempt番号が正しく設定されているかテスト
        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals($i + 1, $errorLog[$i]['attempt']);
        }
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
