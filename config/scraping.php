<?php

return [
    /*
    |--------------------------------------------------------------------------
    | スクレイピング設定
    |--------------------------------------------------------------------------
    |
    | スクレイピング機能の共通設定を定義します。
    |
    */

    'default' => [
        /*
        |--------------------------------------------------------------------------
        | タイムアウト設定（秒）
        |--------------------------------------------------------------------------
        */
        'timeout' => env('SCRAPING_TIMEOUT', 30),

        /*
        |--------------------------------------------------------------------------
        | レート制限設定（1分あたりのリクエスト数）
        |--------------------------------------------------------------------------
        */
        'rate_limit' => env('SCRAPING_RATE_LIMIT', 30),

        /*
        |--------------------------------------------------------------------------
        | リトライ設定
        |--------------------------------------------------------------------------
        */
        'retry' => [
            'max_attempts' => env('SCRAPING_MAX_RETRIES', 3),
            'delay_seconds' => env('SCRAPING_RETRY_DELAY', 1),
        ],

        /*
        |--------------------------------------------------------------------------
        | ユーザーエージェント
        |--------------------------------------------------------------------------
        */
        'user_agent' => env('SCRAPING_USER_AGENT', 'DevCorpTrends/1.0'),

        /*
        |--------------------------------------------------------------------------
        | デフォルトヘッダー
        |--------------------------------------------------------------------------
        */
        'headers' => [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'ja,en-US;q=0.7,en;q=0.3',
            'Accept-Encoding' => 'gzip, deflate',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | プラットフォーム別設定
    |--------------------------------------------------------------------------
    |
    | 各プラットフォームごとの個別設定を定義します。
    |
    */

    'platforms' => [
        'hatena_bookmark' => [
            'rate_limit' => env('HATENA_SCRAPING_RATE_LIMIT', \App\Constants\Platform::getRateLimit(\App\Constants\Platform::HATENA_BOOKMARK)),
            'timeout' => env('HATENA_SCRAPING_TIMEOUT', 30),
            'base_url' => \App\Constants\Platform::getUrl(\App\Constants\Platform::HATENA_BOOKMARK),
            'api_url' => 'https://bookmark.hatenaapis.com',
        ],

        'qiita' => [
            'rate_limit' => env('QIITA_SCRAPING_RATE_LIMIT', \App\Constants\Platform::getRateLimit(\App\Constants\Platform::QIITA)),
            'timeout' => env('QIITA_SCRAPING_TIMEOUT', 30),
            'api_token' => env('QIITA_TOKEN'),
            'api_url' => 'https://qiita.com/api/v2',
        ],

        'zenn' => [
            'rate_limit' => env('ZENN_SCRAPING_RATE_LIMIT', \App\Constants\Platform::getRateLimit(\App\Constants\Platform::ZENN)),
            'timeout' => env('ZENN_SCRAPING_TIMEOUT', 30),
            'base_url' => \App\Constants\Platform::getUrl(\App\Constants\Platform::ZENN),
            'api_url' => 'https://zenn.dev/api',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | キャッシュ設定
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('SCRAPING_CACHE_ENABLED', true),
        'ttl' => env('SCRAPING_CACHE_TTL', 3600), // 1時間
        'prefix' => 'scraping_',
    ],

    /*
    |--------------------------------------------------------------------------
    | ログ設定
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('SCRAPING_LOGGING_ENABLED', true),
        'level' => env('SCRAPING_LOG_LEVEL', 'info'),
        'channel' => env('SCRAPING_LOG_CHANNEL', 'single'),
    ],
];
