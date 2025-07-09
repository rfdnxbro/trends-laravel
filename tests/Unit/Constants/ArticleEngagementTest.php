<?php

namespace Tests\Unit\Constants;

use App\Constants\ArticleEngagement;
use Tests\TestCase;

class ArticleEngagementTest extends TestCase
{
    public function test_通常記事のブックマーク範囲が正しく取得できる()
    {
        $range = ArticleEngagement::getNormalBookmarkRange();

        $this->assertEquals([0, 1000], $range);
        $this->assertEquals(ArticleEngagement::NORMAL_BOOKMARK_MIN, $range[0]);
        $this->assertEquals(ArticleEngagement::NORMAL_BOOKMARK_MAX, $range[1]);
    }

    public function test_通常記事のいいね範囲が正しく取得できる()
    {
        $range = ArticleEngagement::getNormalLikesRange();

        $this->assertEquals([0, 500], $range);
        $this->assertEquals(ArticleEngagement::NORMAL_LIKES_MIN, $range[0]);
        $this->assertEquals(ArticleEngagement::NORMAL_LIKES_MAX, $range[1]);
    }

    public function test_人気記事のブックマーク範囲が正しく取得できる()
    {
        $range = ArticleEngagement::getPopularBookmarkRange();

        $this->assertEquals([500, 2000], $range);
        $this->assertEquals(ArticleEngagement::POPULAR_BOOKMARK_MIN, $range[0]);
        $this->assertEquals(ArticleEngagement::POPULAR_BOOKMARK_MAX, $range[1]);
    }

    public function test_人気記事のいいね範囲が正しく取得できる()
    {
        $range = ArticleEngagement::getPopularLikesRange();

        $this->assertEquals([200, 1000], $range);
        $this->assertEquals(ArticleEngagement::POPULAR_LIKES_MIN, $range[0]);
        $this->assertEquals(ArticleEngagement::POPULAR_LIKES_MAX, $range[1]);
    }

    public function test_低エンゲージメント記事のブックマーク範囲が正しく取得できる()
    {
        $range = ArticleEngagement::getLowBookmarkRange();

        $this->assertEquals([0, 10], $range);
        $this->assertEquals(ArticleEngagement::LOW_BOOKMARK_MIN, $range[0]);
        $this->assertEquals(ArticleEngagement::LOW_BOOKMARK_MAX, $range[1]);
    }

    public function test_低エンゲージメント記事のいいね範囲が正しく取得できる()
    {
        $range = ArticleEngagement::getLowLikesRange();

        $this->assertEquals([0, 5], $range);
        $this->assertEquals(ArticleEngagement::LOW_LIKES_MIN, $range[0]);
        $this->assertEquals(ArticleEngagement::LOW_LIKES_MAX, $range[1]);
    }

    public function test_高エンゲージメント記事のブックマーク範囲が正しく取得できる()
    {
        $range = ArticleEngagement::getHighBookmarkRange();

        $this->assertEquals([1000, 5000], $range);
        $this->assertEquals(ArticleEngagement::HIGH_BOOKMARK_MIN, $range[0]);
        $this->assertEquals(ArticleEngagement::HIGH_BOOKMARK_MAX, $range[1]);
    }

    public function test_高エンゲージメント記事のいいね範囲が正しく取得できる()
    {
        $range = ArticleEngagement::getHighLikesRange();

        $this->assertEquals([500, 2000], $range);
        $this->assertEquals(ArticleEngagement::HIGH_LIKES_MIN, $range[0]);
        $this->assertEquals(ArticleEngagement::HIGH_LIKES_MAX, $range[1]);
    }

    public function test_定数値が期待通りに設定されている()
    {
        // 通常記事
        $this->assertEquals(0, ArticleEngagement::NORMAL_BOOKMARK_MIN);
        $this->assertEquals(1000, ArticleEngagement::NORMAL_BOOKMARK_MAX);
        $this->assertEquals(0, ArticleEngagement::NORMAL_LIKES_MIN);
        $this->assertEquals(500, ArticleEngagement::NORMAL_LIKES_MAX);

        // 人気記事
        $this->assertEquals(500, ArticleEngagement::POPULAR_BOOKMARK_MIN);
        $this->assertEquals(2000, ArticleEngagement::POPULAR_BOOKMARK_MAX);
        $this->assertEquals(200, ArticleEngagement::POPULAR_LIKES_MIN);
        $this->assertEquals(1000, ArticleEngagement::POPULAR_LIKES_MAX);

        // 低エンゲージメント記事
        $this->assertEquals(0, ArticleEngagement::LOW_BOOKMARK_MIN);
        $this->assertEquals(10, ArticleEngagement::LOW_BOOKMARK_MAX);
        $this->assertEquals(0, ArticleEngagement::LOW_LIKES_MIN);
        $this->assertEquals(5, ArticleEngagement::LOW_LIKES_MAX);

        // 高エンゲージメント記事
        $this->assertEquals(1000, ArticleEngagement::HIGH_BOOKMARK_MIN);
        $this->assertEquals(5000, ArticleEngagement::HIGH_BOOKMARK_MAX);
        $this->assertEquals(500, ArticleEngagement::HIGH_LIKES_MIN);
        $this->assertEquals(2000, ArticleEngagement::HIGH_LIKES_MAX);
    }
}
