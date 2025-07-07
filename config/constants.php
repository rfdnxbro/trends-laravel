<?php

return [
    'pagination' => [
        'default_per_page' => env('PAGINATION_DEFAULT_PER_PAGE', 20),
        'max_per_page' => env('PAGINATION_MAX_PER_PAGE', 100),
    ],

    'api' => [
        'default_article_days' => env('API_DEFAULT_ARTICLE_DAYS', 30),
        'max_article_days' => env('API_MAX_ARTICLE_DAYS', 365),
        'default_article_limit' => env('API_DEFAULT_ARTICLE_LIMIT', 5),
        'timeout_seconds' => env('API_TIMEOUT_SECONDS', 30),
        'max_retry_count' => env('API_MAX_RETRY_COUNT', 3),
        'retry_delay_seconds' => env('API_RETRY_DELAY_SECONDS', 1),
        'rate_limit_per_minute' => env('API_RATE_LIMIT_PER_MINUTE', 30),
        'rate_limit_window_seconds' => env('API_RATE_LIMIT_WINDOW_SECONDS', 60),
    ],

    'ranking' => [
        'top_companies_count' => env('RANKING_TOP_COMPANIES_COUNT', 10),
        'top_companies_max' => env('RANKING_TOP_COMPANIES_MAX', 50),
        'history_days' => env('RANKING_HISTORY_DAYS', 30),
        'default_limit' => env('RANKING_DEFAULT_LIMIT', 50),
    ],

    'hatena' => [
        'rate_limit_per_minute' => env('HATENA_RATE_LIMIT_PER_MINUTE', 20),
    ],

    'qiita' => [
        'rate_limit_per_minute' => env('QIITA_RATE_LIMIT_PER_MINUTE', 20),
    ],

    'zenn' => [
        'rate_limit_per_minute' => env('ZENN_RATE_LIMIT_PER_MINUTE', 20),
    ],
];
