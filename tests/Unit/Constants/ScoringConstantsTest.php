<?php

namespace Tests\Unit\Constants;

use App\Constants\ScoringConstants;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ScoringConstantsTest extends TestCase
{
    #[Test]
    public function test_企業検索重み定数が正しい値を持つ(): void
    {
        $this->assertSame(1.0, ScoringConstants::COMPANY_EXACT_MATCH_WEIGHT);
        $this->assertSame(0.8, ScoringConstants::COMPANY_PARTIAL_MATCH_WEIGHT);
        $this->assertSame(0.6, ScoringConstants::COMPANY_DOMAIN_MATCH_WEIGHT);
        $this->assertSame(0.4, ScoringConstants::COMPANY_DESCRIPTION_MATCH_WEIGHT);
        $this->assertSame(0.2, ScoringConstants::COMPANY_RANKING_BONUS_WEIGHT);
    }

    #[Test]
    public function test_記事検索重み定数が正しい値を持つ(): void
    {
        $this->assertSame(1.0, ScoringConstants::ARTICLE_TITLE_MATCH_WEIGHT);
        $this->assertSame(0.5, ScoringConstants::ARTICLE_AUTHOR_MATCH_WEIGHT);
        $this->assertSame(0.3, ScoringConstants::ARTICLE_HIGH_BOOKMARK_WEIGHT);
        $this->assertSame(0.2, ScoringConstants::ARTICLE_MEDIUM_BOOKMARK_WEIGHT);
        $this->assertSame(0.1, ScoringConstants::ARTICLE_LOW_BOOKMARK_WEIGHT);
        $this->assertSame(0.2, ScoringConstants::ARTICLE_RECENT_BONUS_WEIGHT);
        $this->assertSame(0.1, ScoringConstants::ARTICLE_SOMEWHAT_RECENT_BONUS_WEIGHT);
        $this->assertSame(-0.1, ScoringConstants::ARTICLE_OLD_PENALTY_WEIGHT);
    }

    #[Test]
    public function test_閾値定数が正しい値を持つ(): void
    {
        $this->assertSame(100, ScoringConstants::HIGH_BOOKMARKS_THRESHOLD);
        $this->assertSame(50, ScoringConstants::MEDIUM_BOOKMARKS_THRESHOLD);
        $this->assertSame(10, ScoringConstants::LOW_BOOKMARKS_THRESHOLD);
        $this->assertSame(7, ScoringConstants::RECENT_DAYS_THRESHOLD);
        $this->assertSame(30, ScoringConstants::SOMEWHAT_RECENT_DAYS_THRESHOLD);
        $this->assertSame(100, ScoringConstants::OLD_DAYS_THRESHOLD);
    }

    #[Test]
    public function test_企業検索重みの優先順位が正しい(): void
    {
        $this->assertGreaterThan(
            ScoringConstants::COMPANY_PARTIAL_MATCH_WEIGHT,
            ScoringConstants::COMPANY_EXACT_MATCH_WEIGHT,
            '完全一致は部分一致より高い重みを持つべき'
        );

        $this->assertGreaterThan(
            ScoringConstants::COMPANY_DOMAIN_MATCH_WEIGHT,
            ScoringConstants::COMPANY_PARTIAL_MATCH_WEIGHT,
            '部分一致はドメイン一致より高い重みを持つべき'
        );

        $this->assertGreaterThan(
            ScoringConstants::COMPANY_DESCRIPTION_MATCH_WEIGHT,
            ScoringConstants::COMPANY_DOMAIN_MATCH_WEIGHT,
            'ドメイン一致は説明一致より高い重みを持つべき'
        );

        $this->assertGreaterThan(
            ScoringConstants::COMPANY_RANKING_BONUS_WEIGHT,
            ScoringConstants::COMPANY_DESCRIPTION_MATCH_WEIGHT,
            '説明一致はランキングボーナスより高い重みを持つべき'
        );
    }

    #[Test]
    public function test_記事検索重みの優先順位が正しい(): void
    {
        $this->assertGreaterThan(
            ScoringConstants::ARTICLE_AUTHOR_MATCH_WEIGHT,
            ScoringConstants::ARTICLE_TITLE_MATCH_WEIGHT,
            'タイトル一致は著者一致より高い重みを持つべき'
        );

        $this->assertGreaterThan(
            ScoringConstants::ARTICLE_HIGH_BOOKMARK_WEIGHT,
            ScoringConstants::ARTICLE_AUTHOR_MATCH_WEIGHT,
            '著者一致は高ブックマーク重みより高い重みを持つべき'
        );

        $this->assertGreaterThan(
            ScoringConstants::ARTICLE_MEDIUM_BOOKMARK_WEIGHT,
            ScoringConstants::ARTICLE_HIGH_BOOKMARK_WEIGHT,
            '高ブックマークは中ブックマークより高い重みを持つべき'
        );

        $this->assertGreaterThan(
            ScoringConstants::ARTICLE_LOW_BOOKMARK_WEIGHT,
            ScoringConstants::ARTICLE_MEDIUM_BOOKMARK_WEIGHT,
            '中ブックマークは低ブックマークより高い重みを持つべき'
        );
    }

    #[Test]
    public function test_ブックマーク閾値の段階的設定が正しい(): void
    {
        $this->assertGreaterThan(
            ScoringConstants::MEDIUM_BOOKMARKS_THRESHOLD,
            ScoringConstants::HIGH_BOOKMARKS_THRESHOLD,
            '高ブックマーク閾値は中ブックマーク閾値より大きいべき'
        );

        $this->assertGreaterThan(
            ScoringConstants::LOW_BOOKMARKS_THRESHOLD,
            ScoringConstants::MEDIUM_BOOKMARKS_THRESHOLD,
            '中ブックマーク閾値は低ブックマーク閾値より大きいべき'
        );

        $this->assertGreaterThan(0, ScoringConstants::LOW_BOOKMARKS_THRESHOLD, '低ブックマーク閾値は0より大きいべき');
    }

    #[Test]
    public function test_日数閾値の論理的順序が正しい(): void
    {
        $this->assertLessThan(
            ScoringConstants::SOMEWHAT_RECENT_DAYS_THRESHOLD,
            ScoringConstants::RECENT_DAYS_THRESHOLD,
            '最近の日数は多少最近より短いべき'
        );

        $this->assertLessThan(
            ScoringConstants::OLD_DAYS_THRESHOLD,
            ScoringConstants::SOMEWHAT_RECENT_DAYS_THRESHOLD,
            '多少最近の日数は古いより短いべき'
        );

        $this->assertGreaterThan(0, ScoringConstants::RECENT_DAYS_THRESHOLD, '最近の日数閾値は0より大きいべき');
    }

    #[Test]
    public function test_記事時期ボーナス重みの論理的関係が正しい(): void
    {
        $this->assertGreaterThan(
            ScoringConstants::ARTICLE_SOMEWHAT_RECENT_BONUS_WEIGHT,
            ScoringConstants::ARTICLE_RECENT_BONUS_WEIGHT,
            '最近のボーナスは多少最近のボーナスより大きいべき'
        );

        $this->assertGreaterThan(0, ScoringConstants::ARTICLE_RECENT_BONUS_WEIGHT, '最近のボーナス重みは正の値であるべき');
        $this->assertGreaterThan(0, ScoringConstants::ARTICLE_SOMEWHAT_RECENT_BONUS_WEIGHT, '多少最近のボーナス重みは正の値であるべき');
        $this->assertLessThan(0, ScoringConstants::ARTICLE_OLD_PENALTY_WEIGHT, '古い記事のペナルティは負の値であるべき');
    }

    #[Test]
    #[DataProvider('企業検索重みデータプロバイダー')]
    public function test_企業検索重み定数の型が正しい(string $constantName, float $expectedValue): void
    {
        $actualValue = constant(ScoringConstants::class.'::'.$constantName);

        $this->assertIsFloat($actualValue, "定数 {$constantName} は float 型であるべき");
        $this->assertSame($expectedValue, $actualValue, "定数 {$constantName} の値が期待値と一致しない");
    }

    public static function 企業検索重みデータプロバイダー(): array
    {
        return [
            'COMPANY_EXACT_MATCH_WEIGHT' => ['COMPANY_EXACT_MATCH_WEIGHT', 1.0],
            'COMPANY_PARTIAL_MATCH_WEIGHT' => ['COMPANY_PARTIAL_MATCH_WEIGHT', 0.8],
            'COMPANY_DOMAIN_MATCH_WEIGHT' => ['COMPANY_DOMAIN_MATCH_WEIGHT', 0.6],
            'COMPANY_DESCRIPTION_MATCH_WEIGHT' => ['COMPANY_DESCRIPTION_MATCH_WEIGHT', 0.4],
            'COMPANY_RANKING_BONUS_WEIGHT' => ['COMPANY_RANKING_BONUS_WEIGHT', 0.2],
        ];
    }

    #[Test]
    #[DataProvider('記事検索重みデータプロバイダー')]
    public function test_記事検索重み定数の型が正しい(string $constantName, float $expectedValue): void
    {
        $actualValue = constant(ScoringConstants::class.'::'.$constantName);

        $this->assertIsFloat($actualValue, "定数 {$constantName} は float 型であるべき");
        $this->assertSame($expectedValue, $actualValue, "定数 {$constantName} の値が期待値と一致しない");
    }

    public static function 記事検索重みデータプロバイダー(): array
    {
        return [
            'ARTICLE_TITLE_MATCH_WEIGHT' => ['ARTICLE_TITLE_MATCH_WEIGHT', 1.0],
            'ARTICLE_AUTHOR_MATCH_WEIGHT' => ['ARTICLE_AUTHOR_MATCH_WEIGHT', 0.5],
            'ARTICLE_HIGH_BOOKMARK_WEIGHT' => ['ARTICLE_HIGH_BOOKMARK_WEIGHT', 0.3],
            'ARTICLE_MEDIUM_BOOKMARK_WEIGHT' => ['ARTICLE_MEDIUM_BOOKMARK_WEIGHT', 0.2],
            'ARTICLE_LOW_BOOKMARK_WEIGHT' => ['ARTICLE_LOW_BOOKMARK_WEIGHT', 0.1],
            'ARTICLE_RECENT_BONUS_WEIGHT' => ['ARTICLE_RECENT_BONUS_WEIGHT', 0.2],
            'ARTICLE_SOMEWHAT_RECENT_BONUS_WEIGHT' => ['ARTICLE_SOMEWHAT_RECENT_BONUS_WEIGHT', 0.1],
            'ARTICLE_OLD_PENALTY_WEIGHT' => ['ARTICLE_OLD_PENALTY_WEIGHT', -0.1],
        ];
    }

    #[Test]
    #[DataProvider('閾値定数データプロバイダー')]
    public function test_閾値定数の型が正しい(string $constantName, int $expectedValue): void
    {
        $actualValue = constant(ScoringConstants::class.'::'.$constantName);

        $this->assertIsInt($actualValue, "定数 {$constantName} は int 型であるべき");
        $this->assertSame($expectedValue, $actualValue, "定数 {$constantName} の値が期待値と一致しない");
    }

    public static function 閾値定数データプロバイダー(): array
    {
        return [
            'HIGH_BOOKMARKS_THRESHOLD' => ['HIGH_BOOKMARKS_THRESHOLD', 100],
            'MEDIUM_BOOKMARKS_THRESHOLD' => ['MEDIUM_BOOKMARKS_THRESHOLD', 50],
            'LOW_BOOKMARKS_THRESHOLD' => ['LOW_BOOKMARKS_THRESHOLD', 10],
            'RECENT_DAYS_THRESHOLD' => ['RECENT_DAYS_THRESHOLD', 7],
            'SOMEWHAT_RECENT_DAYS_THRESHOLD' => ['SOMEWHAT_RECENT_DAYS_THRESHOLD', 30],
            'OLD_DAYS_THRESHOLD' => ['OLD_DAYS_THRESHOLD', 100],
        ];
    }

    #[Test]
    public function test_重み定数値の範囲が適切である(): void
    {
        $weights = [
            ScoringConstants::COMPANY_EXACT_MATCH_WEIGHT,
            ScoringConstants::COMPANY_PARTIAL_MATCH_WEIGHT,
            ScoringConstants::COMPANY_DOMAIN_MATCH_WEIGHT,
            ScoringConstants::COMPANY_DESCRIPTION_MATCH_WEIGHT,
            ScoringConstants::COMPANY_RANKING_BONUS_WEIGHT,
            ScoringConstants::ARTICLE_TITLE_MATCH_WEIGHT,
            ScoringConstants::ARTICLE_AUTHOR_MATCH_WEIGHT,
            ScoringConstants::ARTICLE_HIGH_BOOKMARK_WEIGHT,
            ScoringConstants::ARTICLE_MEDIUM_BOOKMARK_WEIGHT,
            ScoringConstants::ARTICLE_LOW_BOOKMARK_WEIGHT,
            ScoringConstants::ARTICLE_RECENT_BONUS_WEIGHT,
            ScoringConstants::ARTICLE_SOMEWHAT_RECENT_BONUS_WEIGHT,
        ];

        foreach ($weights as $weight) {
            $this->assertGreaterThanOrEqual(0, $weight, '重み値は0以上であるべき');
            $this->assertLessThanOrEqual(1.0, $weight, '重み値は1.0以下であるべき');
        }

        // ペナルティのみ負の値を許可
        $this->assertLessThan(0, ScoringConstants::ARTICLE_OLD_PENALTY_WEIGHT, 'ペナルティ重みは負の値であるべき');
        $this->assertGreaterThanOrEqual(-1.0, ScoringConstants::ARTICLE_OLD_PENALTY_WEIGHT, 'ペナルティ重みは-1.0以上であるべき');
    }

    #[Test]
    public function test_閾値定数値の範囲が現実的である(): void
    {
        // ブックマーク閾値の現実性チェック
        $this->assertGreaterThan(0, ScoringConstants::LOW_BOOKMARKS_THRESHOLD, '低ブックマーク閾値は正の整数であるべき');
        $this->assertLessThanOrEqual(1000, ScoringConstants::HIGH_BOOKMARKS_THRESHOLD, '高ブックマーク閾値は現実的な範囲内であるべき');

        // 日数閾値の現実性チェック
        $this->assertGreaterThan(0, ScoringConstants::RECENT_DAYS_THRESHOLD, '最近の日数閾値は正の整数であるべき');
        $this->assertLessThanOrEqual(365, ScoringConstants::OLD_DAYS_THRESHOLD, '古い日数閾値は1年以下であるべき');
    }

    #[Test]
    public function test_全定数がアクセス可能である(): void
    {
        $reflection = new ReflectionClass(ScoringConstants::class);
        $constants = $reflection->getConstants();

        $expectedConstants = [
            'COMPANY_EXACT_MATCH_WEIGHT',
            'COMPANY_PARTIAL_MATCH_WEIGHT',
            'COMPANY_DOMAIN_MATCH_WEIGHT',
            'COMPANY_DESCRIPTION_MATCH_WEIGHT',
            'COMPANY_RANKING_BONUS_WEIGHT',
            'ARTICLE_TITLE_MATCH_WEIGHT',
            'ARTICLE_AUTHOR_MATCH_WEIGHT',
            'ARTICLE_HIGH_BOOKMARK_WEIGHT',
            'ARTICLE_MEDIUM_BOOKMARK_WEIGHT',
            'ARTICLE_LOW_BOOKMARK_WEIGHT',
            'ARTICLE_RECENT_BONUS_WEIGHT',
            'ARTICLE_SOMEWHAT_RECENT_BONUS_WEIGHT',
            'ARTICLE_OLD_PENALTY_WEIGHT',
            'HIGH_BOOKMARKS_THRESHOLD',
            'MEDIUM_BOOKMARKS_THRESHOLD',
            'LOW_BOOKMARKS_THRESHOLD',
            'RECENT_DAYS_THRESHOLD',
            'SOMEWHAT_RECENT_DAYS_THRESHOLD',
            'OLD_DAYS_THRESHOLD',
        ];

        foreach ($expectedConstants as $constantName) {
            $this->assertArrayHasKey($constantName, $constants, "定数 {$constantName} が定義されていない");
        }

        $this->assertCount(count($expectedConstants), $constants, '期待される定数の数と実際の定数の数が一致しない');
    }

    #[Test]
    public function test_定数の不変性確認(): void
    {
        $originalCompanyExactMatch = ScoringConstants::COMPANY_EXACT_MATCH_WEIGHT;
        $originalArticleTitleMatch = ScoringConstants::ARTICLE_TITLE_MATCH_WEIGHT;
        $originalHighBookmarksThreshold = ScoringConstants::HIGH_BOOKMARKS_THRESHOLD;

        // 定数は変更されないことを確認
        $this->assertSame($originalCompanyExactMatch, ScoringConstants::COMPANY_EXACT_MATCH_WEIGHT);
        $this->assertSame($originalArticleTitleMatch, ScoringConstants::ARTICLE_TITLE_MATCH_WEIGHT);
        $this->assertSame($originalHighBookmarksThreshold, ScoringConstants::HIGH_BOOKMARKS_THRESHOLD);
    }

    #[Test]
    public function test_スコア計算ロジックの妥当性(): void
    {
        // 企業検索における単一最高重みの確認
        $maxSingleCompanyWeight = ScoringConstants::COMPANY_EXACT_MATCH_WEIGHT;
        $partialMatchWeight = ScoringConstants::COMPANY_PARTIAL_MATCH_WEIGHT;

        $this->assertGreaterThan(
            $partialMatchWeight,
            $maxSingleCompanyWeight,
            '完全一致重みは部分一致重みより高いべき'
        );

        // 企業検索における組み合わせ時の現実的妥当性確認
        $partialMatchWithBonus = ScoringConstants::COMPANY_PARTIAL_MATCH_WEIGHT
            + ScoringConstants::COMPANY_DOMAIN_MATCH_WEIGHT
            + ScoringConstants::COMPANY_RANKING_BONUS_WEIGHT;

        $this->assertLessThan(
            3.0,
            $partialMatchWithBonus,
            '組み合わせスコアが現実的な範囲内であるべき'
        );

        // 記事検索における単一最高重みの確認
        $maxSingleArticleWeight = ScoringConstants::ARTICLE_TITLE_MATCH_WEIGHT;
        $authorWeight = ScoringConstants::ARTICLE_AUTHOR_MATCH_WEIGHT;

        $this->assertGreaterThan(
            $authorWeight,
            $maxSingleArticleWeight,
            'タイトル一致重みは著者一致重みより高いべき'
        );

        // 記事検索における組み合わせ時の現実的妥当性確認
        $authorWithHighBookmark = ScoringConstants::ARTICLE_AUTHOR_MATCH_WEIGHT
            + ScoringConstants::ARTICLE_HIGH_BOOKMARK_WEIGHT;

        $this->assertLessThan(
            2.0,
            $authorWithHighBookmark,
            '記事の組み合わせスコアが現実的な範囲内であるべき'
        );
    }

    #[Test]
    public function test_ビジネスロジック要件との整合性(): void
    {
        // 企業検索における重要度順序: 完全一致 > 部分一致 > ドメイン一致 > 説明一致 > ランキングボーナス
        $companyWeights = [
            'exact' => ScoringConstants::COMPANY_EXACT_MATCH_WEIGHT,
            'partial' => ScoringConstants::COMPANY_PARTIAL_MATCH_WEIGHT,
            'domain' => ScoringConstants::COMPANY_DOMAIN_MATCH_WEIGHT,
            'description' => ScoringConstants::COMPANY_DESCRIPTION_MATCH_WEIGHT,
            'ranking' => ScoringConstants::COMPANY_RANKING_BONUS_WEIGHT,
        ];

        $sortedWeights = $companyWeights;
        arsort($sortedWeights);
        $expectedOrder = ['exact', 'partial', 'domain', 'description', 'ranking'];

        $this->assertSame($expectedOrder, array_keys($sortedWeights), '企業検索重みの優先順位が期待通りでない');

        // 記事検索におけるブックマーク重要度順序: 高 > 中 > 低
        $bookmarkWeights = [
            'high' => ScoringConstants::ARTICLE_HIGH_BOOKMARK_WEIGHT,
            'medium' => ScoringConstants::ARTICLE_MEDIUM_BOOKMARK_WEIGHT,
            'low' => ScoringConstants::ARTICLE_LOW_BOOKMARK_WEIGHT,
        ];

        $sortedBookmarkWeights = $bookmarkWeights;
        arsort($sortedBookmarkWeights);
        $expectedBookmarkOrder = ['high', 'medium', 'low'];

        $this->assertSame($expectedBookmarkOrder, array_keys($sortedBookmarkWeights), '記事ブックマーク重みの優先順位が期待通りでない');
    }
}
