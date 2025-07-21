<?php

namespace App\Constants;

class CacheTime
{
    /**
     * デフォルトキャッシュ時間（5分）
     */
    public const DEFAULT = 300;

    /**
     * 短期キャッシュ時間（1分）
     */
    public const SHORT = 60;

    /**
     * 長期キャッシュ時間（10分）
     */
    public const LONG = 600;

    /**
     * 統計情報キャッシュ時間（10分）
     */
    public const STATISTICS = 600;

    /**
     * ランキングキャッシュ時間（5分）
     */
    public const RANKING = 300;

    /**
     * 企業詳細キャッシュ時間（5分）
     */
    public const COMPANY_DETAIL = 300;

    /**
     * 記事一覧キャッシュ時間（10分）
     */
    public const ARTICLE_LIST = 600;

    /**
     * 記事詳細キャッシュ時間（10分）
     */
    public const ARTICLE_DETAIL = 600;
}
