<?php

namespace Tests\Unit\Constants;

use App\Constants\ArticleEngagement;
use PHPUnit\Framework\TestCase;

class ArticleEngagementTest extends TestCase
{
    /**
     * 通常記事のブックマーク範囲取得テスト
     */
    public function test_get_normal_bookmark_range_正常に範囲を取得できること(): void
    {
        $result = ArticleEngagement::getNormalBookmarkRange();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(ArticleEngagement::NORMAL_BOOKMARK_MIN, $result[0]);
        $this->assertEquals(ArticleEngagement::NORMAL_BOOKMARK_MAX, $result[1]);
    }

    /**
     * 通常記事のいいね範囲取得テスト
     */
    public function test_get_normal_likes_range_正常に範囲を取得できること(): void
    {
        $result = ArticleEngagement::getNormalLikesRange();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(ArticleEngagement::NORMAL_LIKES_MIN, $result[0]);
        $this->assertEquals(ArticleEngagement::NORMAL_LIKES_MAX, $result[1]);
    }

    /**
     * 人気記事のブックマーク範囲取得テスト
     */
    public function test_get_popular_bookmark_range_正常に範囲を取得できること(): void
    {
        $result = ArticleEngagement::getPopularBookmarkRange();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(ArticleEngagement::POPULAR_BOOKMARK_MIN, $result[0]);
        $this->assertEquals(ArticleEngagement::POPULAR_BOOKMARK_MAX, $result[1]);
    }

    /**
     * 人気記事のいいね範囲取得テスト
     */
    public function test_get_popular_likes_range_正常に範囲を取得できること(): void
    {
        $result = ArticleEngagement::getPopularLikesRange();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(ArticleEngagement::POPULAR_LIKES_MIN, $result[0]);
        $this->assertEquals(ArticleEngagement::POPULAR_LIKES_MAX, $result[1]);
    }

    /**
     * 低エンゲージメント記事のブックマーク範囲取得テスト
     */
    public function test_get_low_bookmark_range_正常に範囲を取得できること(): void
    {
        $result = ArticleEngagement::getLowBookmarkRange();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(ArticleEngagement::LOW_BOOKMARK_MIN, $result[0]);
        $this->assertEquals(ArticleEngagement::LOW_BOOKMARK_MAX, $result[1]);
    }

    /**
     * 低エンゲージメント記事のいいね範囲取得テスト
     */
    public function test_get_low_likes_range_正常に範囲を取得できること(): void
    {
        $result = ArticleEngagement::getLowLikesRange();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(ArticleEngagement::LOW_LIKES_MIN, $result[0]);
        $this->assertEquals(ArticleEngagement::LOW_LIKES_MAX, $result[1]);
    }

    /**
     * 高エンゲージメント記事のブックマーク範囲取得テスト
     */
    public function test_get_high_bookmark_range_正常に範囲を取得できること(): void
    {
        $result = ArticleEngagement::getHighBookmarkRange();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(ArticleEngagement::HIGH_BOOKMARK_MIN, $result[0]);
        $this->assertEquals(ArticleEngagement::HIGH_BOOKMARK_MAX, $result[1]);
    }

    /**
     * 高エンゲージメント記事のいいね範囲取得テスト
     */
    public function test_get_high_likes_range_正常に範囲を取得できること(): void
    {
        $result = ArticleEngagement::getHighLikesRange();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(ArticleEngagement::HIGH_LIKES_MIN, $result[0]);
        $this->assertEquals(ArticleEngagement::HIGH_LIKES_MAX, $result[1]);
    }
}
