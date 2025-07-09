<?php

namespace App\Constants;

class ArticleEngagement
{
    // 通常の記事のエンゲージメント範囲
    public const NORMAL_BOOKMARK_MIN = 0;

    public const NORMAL_BOOKMARK_MAX = 1000;

    public const NORMAL_LIKES_MIN = 0;

    public const NORMAL_LIKES_MAX = 500;

    // 人気記事のエンゲージメント範囲
    public const POPULAR_BOOKMARK_MIN = 500;

    public const POPULAR_BOOKMARK_MAX = 2000;

    public const POPULAR_LIKES_MIN = 200;

    public const POPULAR_LIKES_MAX = 1000;

    // 低エンゲージメント記事の範囲
    public const LOW_BOOKMARK_MIN = 0;

    public const LOW_BOOKMARK_MAX = 10;

    public const LOW_LIKES_MIN = 0;

    public const LOW_LIKES_MAX = 5;

    // 高エンゲージメント記事の範囲
    public const HIGH_BOOKMARK_MIN = 1000;

    public const HIGH_BOOKMARK_MAX = 5000;

    public const HIGH_LIKES_MIN = 500;

    public const HIGH_LIKES_MAX = 2000;

    /**
     * 通常記事のブックマーク範囲を取得
     */
    public static function getNormalBookmarkRange(): array
    {
        return [self::NORMAL_BOOKMARK_MIN, self::NORMAL_BOOKMARK_MAX];
    }

    /**
     * 通常記事のいいね範囲を取得
     */
    public static function getNormalLikesRange(): array
    {
        return [self::NORMAL_LIKES_MIN, self::NORMAL_LIKES_MAX];
    }

    /**
     * 人気記事のブックマーク範囲を取得
     */
    public static function getPopularBookmarkRange(): array
    {
        return [self::POPULAR_BOOKMARK_MIN, self::POPULAR_BOOKMARK_MAX];
    }

    /**
     * 人気記事のいいね範囲を取得
     */
    public static function getPopularLikesRange(): array
    {
        return [self::POPULAR_LIKES_MIN, self::POPULAR_LIKES_MAX];
    }

    /**
     * 低エンゲージメント記事のブックマーク範囲を取得
     */
    public static function getLowBookmarkRange(): array
    {
        return [self::LOW_BOOKMARK_MIN, self::LOW_BOOKMARK_MAX];
    }

    /**
     * 低エンゲージメント記事のいいね範囲を取得
     */
    public static function getLowLikesRange(): array
    {
        return [self::LOW_LIKES_MIN, self::LOW_LIKES_MAX];
    }

    /**
     * 高エンゲージメント記事のブックマーク範囲を取得
     */
    public static function getHighBookmarkRange(): array
    {
        return [self::HIGH_BOOKMARK_MIN, self::HIGH_BOOKMARK_MAX];
    }

    /**
     * 高エンゲージメント記事のいいね範囲を取得
     */
    public static function getHighLikesRange(): array
    {
        return [self::HIGH_LIKES_MIN, self::HIGH_LIKES_MAX];
    }
}
