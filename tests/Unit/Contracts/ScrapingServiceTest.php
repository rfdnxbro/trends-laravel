<?php

namespace Tests\Unit\Contracts;

use App\Contracts\ScrapingService;
use App\Services\BaseScraper;
use Illuminate\Http\Client\Response;
use Tests\TestCase;

class ScrapingServiceTest extends TestCase
{
    private ScrapingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new class extends BaseScraper
        {
            protected function parseResponse(Response $response): array
            {
                return $response->json() ?? [];
            }
        };
    }

    public function test_scraping_serviceインターフェースが実装されている()
    {
        $this->assertInstanceOf(ScrapingService::class, $this->service);
    }

    public function test_scrapeメソッドが定義されている()
    {
        $this->assertTrue(method_exists($this->service, 'scrape'));
    }

    public function test_set_rate_limitメソッドが定義されている()
    {
        $this->assertTrue(method_exists($this->service, 'setRateLimit'));
    }

    public function test_set_retry_optionsメソッドが定義されている()
    {
        $this->assertTrue(method_exists($this->service, 'setRetryOptions'));
    }

    public function test_set_headersメソッドが定義されている()
    {
        $this->assertTrue(method_exists($this->service, 'setHeaders'));
    }

    public function test_set_timeoutメソッドが定義されている()
    {
        $this->assertTrue(method_exists($this->service, 'setTimeout'));
    }

    public function test_get_last_responseメソッドが定義されている()
    {
        $this->assertTrue(method_exists($this->service, 'getLastResponse'));
    }

    public function test_get_error_logメソッドが定義されている()
    {
        $this->assertTrue(method_exists($this->service, 'getErrorLog'));
    }

    public function test_インターフェースメソッドの戻り値の型が正しい()
    {
        $reflection = new \ReflectionClass($this->service);

        // scrapeメソッドの戻り値
        $scrapeMethod = $reflection->getMethod('scrape');
        $returnType = $scrapeMethod->getReturnType();
        $this->assertEquals('array', $returnType->getName());

        // getLastResponseメソッドの戻り値
        $getLastResponseMethod = $reflection->getMethod('getLastResponse');
        $returnType = $getLastResponseMethod->getReturnType();
        $this->assertEquals('array', $returnType->getName());
        $this->assertTrue($returnType->allowsNull());

        // getErrorLogメソッドの戻り値
        $getErrorLogMethod = $reflection->getMethod('getErrorLog');
        $returnType = $getErrorLogMethod->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    public function test_setメソッドの引数の型が正しい()
    {
        $reflection = new \ReflectionClass($this->service);

        // setRateLimitメソッドの引数
        $setRateLimitMethod = $reflection->getMethod('setRateLimit');
        $parameters = $setRateLimitMethod->getParameters();
        $this->assertEquals('int', $parameters[0]->getType()->getName());

        // setRetryOptionsメソッドの引数
        $setRetryOptionsMethod = $reflection->getMethod('setRetryOptions');
        $parameters = $setRetryOptionsMethod->getParameters();
        $this->assertEquals('int', $parameters[0]->getType()->getName());
        $this->assertEquals('int', $parameters[1]->getType()->getName());

        // setHeadersメソッドの引数
        $setHeadersMethod = $reflection->getMethod('setHeaders');
        $parameters = $setHeadersMethod->getParameters();
        $this->assertEquals('array', $parameters[0]->getType()->getName());

        // setTimeoutメソッドの引数
        $setTimeoutMethod = $reflection->getMethod('setTimeout');
        $parameters = $setTimeoutMethod->getParameters();
        $this->assertEquals('int', $parameters[0]->getType()->getName());
    }
}
