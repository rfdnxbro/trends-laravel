<?php

namespace Tests\Unit\Constants;

use App\Constants\Platform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PlatformTest extends TestCase
{
    #[Test]
    public function qiita定数が正しい値を持つ(): void
    {
        $this->assertSame('Qiita', Platform::QIITA);
    }

    #[Test]
    public function zenn定数が正しい値を持つ(): void
    {
        $this->assertSame('Zenn', Platform::ZENN);
    }

    #[Test]
    public function はてなブックマーク定数が正しい値を持つ(): void
    {
        $this->assertSame('はてなブックマーク', Platform::HATENA_BOOKMARK);
    }

    #[Test]
    public function all定数が全プラットフォームを含む(): void
    {
        $expected = [
            'Qiita',
            'Zenn',
            'はてなブックマーク',
        ];

        $this->assertSame($expected, Platform::ALL);
        $this->assertCount(3, Platform::ALL);
    }

    #[Test]
    public function urls定数が正しいurlを含む(): void
    {
        $expected = [
            'Qiita' => 'https://qiita.com',
            'Zenn' => 'https://zenn.dev',
            'はてなブックマーク' => 'https://b.hatena.ne.jp',
        ];

        $this->assertSame($expected, Platform::URLS);
    }

    #[Test]
    public function rate_limits定数が正しい制限値を含む(): void
    {
        $expected = [
            'Qiita' => 60,
            'Zenn' => 30,
            'はてなブックマーク' => 20,
        ];

        $this->assertSame($expected, Platform::RATE_LIMITS);
    }

    #[Test]
    public function get_valid_platformsが全プラットフォームを返す(): void
    {
        $result = Platform::getValidPlatforms();

        $this->assertSame(Platform::ALL, $result);
        $this->assertContains('Qiita', $result);
        $this->assertContains('Zenn', $result);
        $this->assertContains('はてなブックマーク', $result);
    }

    #[Test]
    #[DataProvider('有効なプラットフォーム名のデータプロバイダー')]
    public function is_validが有効なプラットフォーム名でtrueを返す(string $platform): void
    {
        $this->assertTrue(Platform::isValid($platform));
    }

    public static function 有効なプラットフォーム名のデータプロバイダー(): array
    {
        return [
            'Qiita' => ['Qiita'],
            'Zenn' => ['Zenn'],
            'はてなブックマーク' => ['はてなブックマーク'],
        ];
    }

    #[Test]
    #[DataProvider('無効なプラットフォーム名のデータプロバイダー')]
    public function is_validが無効なプラットフォーム名でfalseを返す(string $platform): void
    {
        $this->assertFalse(Platform::isValid($platform));
    }

    public static function 無効なプラットフォーム名のデータプロバイダー(): array
    {
        return [
            '存在しないプラットフォーム' => ['Twitter'],
            '空文字列' => [''],
            '大文字小文字違い' => ['qiita'],
            'ZENN大文字' => ['ZENN'],
            'スペース含み' => [' Qiita'],
            'null文字列' => ['null'],
            'GitHub' => ['GitHub'],
            'はてな' => ['はてな'],
        ];
    }

    #[Test]
    #[DataProvider('プラットフォームURLのデータプロバイダー')]
    public function get_urlが有効なプラットフォームで正しいurlを返す(string $platform, string $expectedUrl): void
    {
        $this->assertSame($expectedUrl, Platform::getUrl($platform));
    }

    public static function プラットフォームURLのデータプロバイダー(): array
    {
        return [
            'Qiita' => ['Qiita', 'https://qiita.com'],
            'Zenn' => ['Zenn', 'https://zenn.dev'],
            'はてなブックマーク' => ['はてなブックマーク', 'https://b.hatena.ne.jp'],
        ];
    }

    #[Test]
    #[DataProvider('無効なプラットフォーム名のデータプロバイダー')]
    public function get_urlが無効なプラットフォーム名でnullを返す(string $platform): void
    {
        $this->assertNull(Platform::getUrl($platform));
    }

    #[Test]
    #[DataProvider('プラットフォームレート制限のデータプロバイダー')]
    public function get_rate_limitが有効なプラットフォームで正しい制限値を返す(string $platform, int $expectedRateLimit): void
    {
        $this->assertSame($expectedRateLimit, Platform::getRateLimit($platform));
    }

    public static function プラットフォームレート制限のデータプロバイダー(): array
    {
        return [
            'Qiita' => ['Qiita', 60],
            'Zenn' => ['Zenn', 30],
            'はてなブックマーク' => ['はてなブックマーク', 20],
        ];
    }

    #[Test]
    #[DataProvider('無効なプラットフォーム名のデータプロバイダー')]
    public function get_rate_limitが無効なプラットフォーム名でnullを返す(string $platform): void
    {
        $this->assertNull(Platform::getRateLimit($platform));
    }

    #[Test]
    public function all配列とurls配列のキーが一致する(): void
    {
        $allPlatforms = Platform::ALL;
        $urlKeys = array_keys(Platform::URLS);

        sort($allPlatforms);
        sort($urlKeys);

        $this->assertSame($allPlatforms, $urlKeys);
    }

    #[Test]
    public function all配列とrate_limits配列のキーが一致する(): void
    {
        $allPlatforms = Platform::ALL;
        $rateLimitKeys = array_keys(Platform::RATE_LIMITS);

        sort($allPlatforms);
        sort($rateLimitKeys);

        $this->assertSame($allPlatforms, $rateLimitKeys);
    }

    #[Test]
    public function urls配列とrate_limits配列のキーが一致する(): void
    {
        $urlKeys = array_keys(Platform::URLS);
        $rateLimitKeys = array_keys(Platform::RATE_LIMITS);

        sort($urlKeys);
        sort($rateLimitKeys);

        $this->assertSame($urlKeys, $rateLimitKeys);
    }

    #[Test]
    public function プラットフォーム定数値に重複がない(): void
    {
        $platforms = [
            Platform::QIITA,
            Platform::ZENN,
            Platform::HATENA_BOOKMARK,
        ];

        $this->assertSame(count($platforms), count(array_unique($platforms)));
    }

    #[Test]
    public function urlが有効な形式である(): void
    {
        foreach (Platform::URLS as $platform => $url) {
            $this->assertStringStartsWith('https://', $url, "プラットフォーム {$platform} のURLがHTTPSで始まらない");
            $this->assertTrue(filter_var($url, FILTER_VALIDATE_URL) !== false, "プラットフォーム {$platform} のURLが無効");
        }
    }

    #[Test]
    public function レート制限が正の整数である(): void
    {
        foreach (Platform::RATE_LIMITS as $platform => $rateLimit) {
            $this->assertIsInt($rateLimit, "プラットフォーム {$platform} のレート制限が整数でない");
            $this->assertGreaterThan(0, $rateLimit, "プラットフォーム {$platform} のレート制限が正の数でない");
        }
    }

    #[Test]
    public function レート制限が現実的な範囲内である(): void
    {
        foreach (Platform::RATE_LIMITS as $platform => $rateLimit) {
            $this->assertLessThanOrEqual(1000, $rateLimit, "プラットフォーム {$platform} のレート制限が高すぎる");
            $this->assertGreaterThanOrEqual(1, $rateLimit, "プラットフォーム {$platform} のレート制限が低すぎる");
        }
    }

    #[Test]
    public function 静的メソッドが正しく動作する(): void
    {
        // getValidPlatforms()が静的メソッドとして呼び出せる
        $this->assertIsArray(Platform::getValidPlatforms());

        // isValid()が静的メソッドとして呼び出せる
        $this->assertIsBool(Platform::isValid('Qiita'));

        // getUrl()が静的メソッドとして呼び出せる
        $this->assertIsString(Platform::getUrl('Qiita'));

        // getRateLimit()が静的メソッドとして呼び出せる
        $this->assertIsInt(Platform::getRateLimit('Qiita'));
    }

    #[Test]
    public function エッジケース_空文字列での各メソッド動作(): void
    {
        $emptyString = '';

        $this->assertFalse(Platform::isValid($emptyString));
        $this->assertNull(Platform::getUrl($emptyString));
        $this->assertNull(Platform::getRateLimit($emptyString));
    }

    #[Test]
    public function エッジケース_null文字列での各メソッド動作(): void
    {
        $nullString = 'null';

        $this->assertFalse(Platform::isValid($nullString));
        $this->assertNull(Platform::getUrl($nullString));
        $this->assertNull(Platform::getRateLimit($nullString));
    }

    #[Test]
    public function エッジケース_大文字小文字の違いは区別される(): void
    {
        $this->assertFalse(Platform::isValid('qiita'));
        $this->assertFalse(Platform::isValid('QIITA'));
        $this->assertFalse(Platform::isValid('zenn'));
        $this->assertFalse(Platform::isValid('ZENN'));
    }

    #[Test]
    public function 定数の不変性確認(): void
    {
        $originalAll = Platform::ALL;
        $originalUrls = Platform::URLS;
        $originalRateLimits = Platform::RATE_LIMITS;

        // 定数は変更されないことを確認
        $this->assertSame($originalAll, Platform::ALL);
        $this->assertSame($originalUrls, Platform::URLS);
        $this->assertSame($originalRateLimits, Platform::RATE_LIMITS);
    }

    #[Test]
    public function パフォーマンステスト_大量呼び出し(): void
    {
        $startTime = microtime(true);

        // 各メソッドを1000回呼び出し
        for ($i = 0; $i < 1000; $i++) {
            Platform::getValidPlatforms();
            Platform::isValid('Qiita');
            Platform::getUrl('Qiita');
            Platform::getRateLimit('Qiita');
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // 1秒以内に完了することを確認（パフォーマンス要件）
        $this->assertLessThan(1.0, $executionTime, 'メソッドの大量呼び出しが1秒を超えました');
    }
}
