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
    | セレクタ戦略設定
    |--------------------------------------------------------------------------
    |
    | 各データ要素に対する優先順位付きセレクタパターンを定義
    | より安定性の高いセレクタを上位に配置
    |
    */
    'selectors' => [
        /*
        |--------------------------------------------------------------------------
        | 記事要素セレクタ（記事全体のコンテナ）
        |--------------------------------------------------------------------------
        */
        'article_container' => [
            'semantic' => [
                'article',
                '[role="article"]',
                'main article',
                'section[itemtype*="Article"]',
            ],
            'accessibility' => [
                '[data-testid*="article"]',
                '[data-testid*="item"]',
                '[aria-labelledby]',
            ],
            'structural' => [
                '[class*="article"]',
                '[class*="item"]',
                '[class*="card"]',
                '[class*="entry"]',
                'div[class*="style-"]', // CSS Modules対応
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | タイトルセレクタ
        |--------------------------------------------------------------------------
        */
        'title' => [
            'semantic' => [
                'h1',
                'h2',
                'h3',
                '[role="heading"]',
            ],
            'accessibility' => [
                '[data-testid*="title"]',
                '[aria-label*="title"]',
                '[aria-labelledby*="title"]',
            ],
            'structural' => [
                'a[href*="/articles/"]',
                'a[href*="/items/"]',
                '[class*="title"]',
                '[class*="Title"]',
                '[class*="heading"]',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | URLセレクタ（記事リンク）
        |--------------------------------------------------------------------------
        */
        'url' => [
            'semantic' => [
                'h1 a',
                'h2 a',
                'h3 a',
                '[role="heading"] a',
            ],
            'specific' => [
                'a[href*="/articles/"]',
                'a[href*="/items/"]',
            ],
            'structural' => [
                '[class*="title"] a',
                '[class*="Title"] a',
                'a',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 著者セレクタ
        |--------------------------------------------------------------------------
        */
        'author' => [
            'accessibility' => [
                '[data-testid*="author"]',
                '[aria-label*="author"]',
                '[aria-label*="user"]',
            ],
            'structural' => [
                '[class*="author"]',
                '[class*="Author"]',
                '[class*="user"]',
                '[class*="User"]',
                '[class*="userName"]',
                '[class*="profile"]',
                '[class*="Profile"]',
                'a[href*="/@"]',
                'a[href^="/"]',
                'img[alt]', // アバター画像のalt属性
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | 日時セレクタ
        |--------------------------------------------------------------------------
        */
        'datetime' => [
            'semantic' => [
                'time[datetime]',
                'time',
                '[datetime]',
            ],
            'accessibility' => [
                '[data-testid*="date"]',
                '[data-testid*="time"]',
                '[aria-label*="date"]',
                '[aria-label*="time"]',
            ],
            'structural' => [
                '[class*="date"]',
                '[class*="Date"]',
                '[class*="time"]',
                '[class*="Time"]',
                '[class*="published"]',
                '[class*="Published"]',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | エンゲージメント数セレクタ（いいね、ブックマーク等）
        |--------------------------------------------------------------------------
        */
        'engagement' => [
            'accessibility' => [
                '[data-testid*="like"]',
                '[data-testid*="bookmark"]',
                '[aria-label*="いいね"]',
                '[aria-label*="like"]',
                '[aria-label*="LGTM"]',
                '[aria-label*="bookmark"]',
                '[aria-label*="ブックマーク"]',
            ],
            'semantic' => [
                'button[aria-label*="いいね"]',
                'button[aria-label*="like"]',
                'button[aria-label*="LGTM"]',
            ],
            'structural' => [
                '[class*="like"]',
                '[class*="Like"]',
                '[class*="lgtm"]',
                '[class*="LGTM"]',
                '[class*="bookmark"]',
                '[class*="Bookmark"]',
                '[class*="users"]', // はてなブックマーク用
                'span[aria-label]',
            ],
        ],
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
