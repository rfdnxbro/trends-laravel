<?php

namespace App\Constants;

class Platform
{
    public const QIITA = 'Qiita';

    public const ZENN = 'Zenn';

    public const HATENA_BOOKMARK = 'はてなブックマーク';

    public const ALL = [
        self::QIITA,
        self::ZENN,
        self::HATENA_BOOKMARK,
    ];

    public const URLS = [
        self::QIITA => 'https://qiita.com',
        self::ZENN => 'https://zenn.dev',
        self::HATENA_BOOKMARK => 'https://b.hatena.ne.jp',
    ];

    public const RATE_LIMITS = [
        self::QIITA => 60,           // Qiita API の制限に合わせる
        self::ZENN => 30,            // Zenn の制限に合わせる
        self::HATENA_BOOKMARK => 20, // はてなブックマークの制限
    ];

    /**
     * 有効なプラットフォーム名の配列を取得
     */
    public static function getValidPlatforms(): array
    {
        return self::ALL;
    }

    /**
     * プラットフォーム名が有効かチェック
     */
    public static function isValid(string $platform): bool
    {
        return in_array($platform, self::ALL, true);
    }

    /**
     * プラットフォームのURLを取得
     */
    public static function getUrl(string $platform): ?string
    {
        return self::URLS[$platform] ?? null;
    }

    /**
     * プラットフォームのレート制限を取得
     */
    public static function getRateLimit(string $platform): ?int
    {
        return self::RATE_LIMITS[$platform] ?? null;
    }
}
