<?php

namespace App\Constants;

class ScoringConstants
{
    /**
     * 企業検索スコア重み
     */
    public const COMPANY_EXACT_MATCH_WEIGHT = 1.0;

    public const COMPANY_PARTIAL_MATCH_WEIGHT = 0.8;

    public const COMPANY_DOMAIN_MATCH_WEIGHT = 0.6;

    public const COMPANY_DESCRIPTION_MATCH_WEIGHT = 0.4;

    public const COMPANY_RANKING_BONUS_WEIGHT = 0.2;

    /**
     * 記事検索スコア重み
     */
    public const ARTICLE_TITLE_MATCH_WEIGHT = 1.0;

    public const ARTICLE_AUTHOR_MATCH_WEIGHT = 0.5;

    public const ARTICLE_HIGH_BOOKMARK_WEIGHT = 0.3;

    public const ARTICLE_MEDIUM_BOOKMARK_WEIGHT = 0.2;

    public const ARTICLE_LOW_BOOKMARK_WEIGHT = 0.1;

    public const ARTICLE_RECENT_BONUS_WEIGHT = 0.2;

    public const ARTICLE_SOMEWHAT_RECENT_BONUS_WEIGHT = 0.1;

    public const ARTICLE_OLD_PENALTY_WEIGHT = -0.1;

    /**
     * スコア計算閾値
     */
    public const HIGH_BOOKMARKS_THRESHOLD = 100;

    public const MEDIUM_BOOKMARKS_THRESHOLD = 50;

    public const LOW_BOOKMARKS_THRESHOLD = 10;

    public const RECENT_DAYS_THRESHOLD = 7;

    public const SOMEWHAT_RECENT_DAYS_THRESHOLD = 30;

    public const OLD_DAYS_THRESHOLD = 100;
}
