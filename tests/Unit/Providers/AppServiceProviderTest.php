<?php

namespace Tests\Unit\Providers;

use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    private AppServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new AppServiceProvider($this->app);
    }

    public function test_プロバイダーが正しくインスタンス化される()
    {
        $this->assertInstanceOf(AppServiceProvider::class, $this->provider);
    }

    public function test_registerメソッドが実装されている()
    {
        $this->assertTrue(method_exists($this->provider, 'register'));

        // registerメソッドを呼び出してもエラーが発生しない
        $this->provider->register();
        $this->assertTrue(true);
    }

    public function test_bootメソッドが実装されている()
    {
        $this->assertTrue(method_exists($this->provider, 'boot'));
    }

    public function test_bootメソッドが正常に実行される()
    {
        // bootメソッドを呼び出してもエラーが発生しない
        $this->provider->boot();
        $this->assertTrue(true);
    }

    public function test_レート制限設定の基本テスト()
    {
        $this->provider->boot();

        // RateLimiterの実際のインスタンスを取得
        $rateLimiter = app('Illuminate\Cache\RateLimiter');

        // リフレクションを使って内部状態を確認
        $reflection = new \ReflectionObject($rateLimiter);
        $limitersProperty = $reflection->getProperty('limiters');
        $limitersProperty->setAccessible(true);
        $limiters = $limitersProperty->getValue($rateLimiter);

        $this->assertArrayHasKey('api', $limiters);
        $this->assertIsCallable($limiters['api']);
    }

    public function test_レート制限コールバックの動作確認()
    {
        $this->provider->boot();

        $rateLimiter = app('Illuminate\Cache\RateLimiter');
        $reflection = new \ReflectionObject($rateLimiter);
        $limitersProperty = $reflection->getProperty('limiters');
        $limitersProperty->setAccessible(true);
        $limiters = $limitersProperty->getValue($rateLimiter);

        // 認証済みユーザーのテスト
        $user = $this->createMock(\App\Models\User::class);
        $user->method('getKey')->willReturn(1);
        $user->id = 1;

        $request = Request::create('/api/test');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $limiter = $limiters['api'];
        $limit = $limiter($request);

        $this->assertEquals(60, $limit->maxAttempts);
        $this->assertInstanceOf(\Illuminate\Cache\RateLimiting\Limit::class, $limit);

        // 未認証の場合はIPベースになることも確認
        $requestGuest = Request::create('/api/test');
        $requestGuest->server->set('REMOTE_ADDR', '192.168.1.1');
        $limitGuest = $limiter($requestGuest);
        $this->assertEquals('192.168.1.1', $limitGuest->key);
    }
}
