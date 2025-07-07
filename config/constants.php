<?php

return [
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 100,
    ],

    'api' => [
        'default_article_days' => 30,
        'max_article_days' => 365,
        'default_search_limit' => 20,
        'max_search_limit' => 100,
        'default_article_limit' => 5,
        'timeout_seconds' => 30,
        'max_retry_count' => 3,
        'retry_delay_seconds' => 1,
        'rate_limit_per_minute' => 30,
        'rate_limit_window_seconds' => 60,
    ],

    'ranking' => [
        'top_companies_count' => 10,
        'top_companies_max' => 50,
        'history_days' => 30,
        'default_limit' => 50,
        'calculation_multiplier' => 10,
        'all_time_start_year' => 2020,
    ],

    'search' => [
        'max_query_length' => 255,
        'min_ranking_display' => 1,
    ],

    'hatena' => [
        'rate_limit_per_minute' => 20,
    ],

    'qiita' => [
        'rate_limit_per_minute' => 20,
    ],

    'zenn' => [
        'rate_limit_per_minute' => 20,
    ],

    'scoring' => [
        'company' => [
            'exact_match_weight' => 1.0,
            'partial_match_weight' => 0.8,
            'domain_match_weight' => 0.6,
            'description_match_weight' => 0.4,
            'ranking_bonus_weight' => 0.2,
        ],
        'article' => [
            'title_match_weight' => 1.0,
            'author_match_weight' => 0.5,
            'high_bookmark_weight' => 0.3,
            'medium_bookmark_weight' => 0.2,
            'low_bookmark_weight' => 0.1,
            'recent_bonus_weight' => 0.2,
            'somewhat_recent_bonus_weight' => 0.1,
            'old_penalty_weight' => -0.1,
        ],
        'thresholds' => [
            'high_bookmarks' => 100,
            'medium_bookmarks' => 50,
            'low_bookmarks' => 10,
            'recent_days' => 7,
            'somewhat_recent_days' => 30,
            'old_days' => 100,
        ],
    ],
];
