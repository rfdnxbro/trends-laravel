<?php

namespace Tests\Unit\Config;

use Tests\TestCase;

class ScrapingConfigTest extends TestCase
{
    public function test_デフォルト設定が正しく読み込まれる()
    {
        $config = config('scraping.default');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('timeout', $config);
        $this->assertArrayHasKey('rate_limit', $config);
        $this->assertArrayHasKey('retry', $config);
        $this->assertArrayHasKey('user_agent', $config);
        $this->assertArrayHasKey('headers', $config);
    }

    public function test_デフォルトタイムアウト値が設定されている()
    {
        $timeout = config('scraping.default.timeout');

        $this->assertEquals(30, $timeout);
    }

    public function test_デフォルトレート制限値が設定されている()
    {
        $rateLimit = config('scraping.default.rate_limit');

        $this->assertEquals(30, $rateLimit);
    }

    public function test_リトライ設定が正しく設定されている()
    {
        $retry = config('scraping.default.retry');

        $this->assertIsArray($retry);
        $this->assertArrayHasKey('max_attempts', $retry);
        $this->assertArrayHasKey('delay_seconds', $retry);
        $this->assertEquals(3, $retry['max_attempts']);
        $this->assertEquals(1, $retry['delay_seconds']);
    }

    public function test_ユーザーエージェントが設定されている()
    {
        $userAgent = config('scraping.default.user_agent');

        $this->assertEquals('DevCorpTrends/1.0', $userAgent);
    }

    public function test_デフォルトヘッダーが設定されている()
    {
        $headers = config('scraping.default.headers');

        $this->assertIsArray($headers);
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertArrayHasKey('Accept-Language', $headers);
        $this->assertArrayHasKey('Accept-Encoding', $headers);
        $this->assertArrayHasKey('Connection', $headers);
        $this->assertArrayHasKey('Upgrade-Insecure-Requests', $headers);
    }

    public function test_プラットフォーム別設定が正しく読み込まれる()
    {
        $platforms = config('scraping.platforms');

        $this->assertIsArray($platforms);
        $this->assertArrayHasKey('github', $platforms);
        $this->assertArrayHasKey('qiita', $platforms);
        $this->assertArrayHasKey('zenn', $platforms);
    }

    public function test_git_hub設定が正しく設定されている()
    {
        $github = config('scraping.platforms.github');

        $this->assertIsArray($github);
        $this->assertArrayHasKey('rate_limit', $github);
        $this->assertArrayHasKey('timeout', $github);
        $this->assertArrayHasKey('api_token', $github);
        $this->assertEquals(20, $github['rate_limit']);
        $this->assertEquals(60, $github['timeout']);
    }

    public function test_qiita設定が正しく設定されている()
    {
        $qiita = config('scraping.platforms.qiita');

        $this->assertIsArray($qiita);
        $this->assertArrayHasKey('rate_limit', $qiita);
        $this->assertArrayHasKey('timeout', $qiita);
        $this->assertArrayHasKey('api_token', $qiita);
        $this->assertEquals(30, $qiita['rate_limit']);
        $this->assertEquals(30, $qiita['timeout']);
    }

    public function test_zenn設定が正しく設定されている()
    {
        $zenn = config('scraping.platforms.zenn');

        $this->assertIsArray($zenn);
        $this->assertArrayHasKey('rate_limit', $zenn);
        $this->assertArrayHasKey('timeout', $zenn);
        $this->assertEquals(30, $zenn['rate_limit']);
        $this->assertEquals(30, $zenn['timeout']);
    }

    public function test_キャッシュ設定が正しく設定されている()
    {
        $cache = config('scraping.cache');

        $this->assertIsArray($cache);
        $this->assertArrayHasKey('enabled', $cache);
        $this->assertArrayHasKey('ttl', $cache);
        $this->assertArrayHasKey('prefix', $cache);
        $this->assertTrue($cache['enabled']);
        $this->assertEquals(3600, $cache['ttl']);
        $this->assertEquals('scraping_', $cache['prefix']);
    }

    public function test_ログ設定が正しく設定されている()
    {
        $logging = config('scraping.logging');

        $this->assertIsArray($logging);
        $this->assertArrayHasKey('enabled', $logging);
        $this->assertArrayHasKey('level', $logging);
        $this->assertArrayHasKey('channel', $logging);
        $this->assertTrue($logging['enabled']);
        $this->assertEquals('info', $logging['level']);
        $this->assertEquals('single', $logging['channel']);
    }

    public function test_環境変数での設定上書きが可能()
    {
        // 環境変数を一時的に設定
        config(['scraping.default.timeout' => 60]);

        $timeout = config('scraping.default.timeout');
        $this->assertEquals(60, $timeout);

        // 設定を元に戻す
        config(['scraping.default.timeout' => 30]);
    }
}
