<?php

namespace Tests\Unit\Constants;

use App\Constants\CacheTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CacheTimeTest extends TestCase
{
    #[Test]
    public function test_基本キャッシュ時間定数が正しい値を持つ(): void
    {
        $this->assertSame(300, CacheTime::DEFAULT);
        $this->assertSame(60, CacheTime::SHORT);
        $this->assertSame(600, CacheTime::LONG);
    }

    #[Test]
    public function test_用途別キャッシュ時間定数が正しい値を持つ(): void
    {
        $this->assertSame(600, CacheTime::STATISTICS);
        $this->assertSame(300, CacheTime::RANKING);
        $this->assertSame(300, CacheTime::COMPANY_DETAIL);
        $this->assertSame(600, CacheTime::ARTICLE_LIST);
        $this->assertSame(600, CacheTime::ARTICLE_DETAIL);
    }

    #[Test]
    public function test_キャッシュ時間の論理的順序が正しい(): void
    {
        $this->assertLessThan(
            CacheTime::DEFAULT,
            CacheTime::SHORT,
            'SHORT時間はDEFAULT時間より短いべき'
        );

        $this->assertLessThan(
            CacheTime::LONG,
            CacheTime::DEFAULT,
            'DEFAULT時間はLONG時間より短いべき'
        );

        $this->assertLessThan(
            CacheTime::LONG,
            CacheTime::SHORT,
            'SHORT時間はLONG時間より短いべき'
        );
    }

    #[Test]
    public function test_用途別キャッシュ時間の妥当性(): void
    {
        // 統計情報は長期キャッシュが妥当
        $this->assertSame(
            CacheTime::LONG,
            CacheTime::STATISTICS,
            '統計情報キャッシュ時間は長期キャッシュと同じであるべき'
        );

        // ランキングと企業詳細はデフォルト時間が妥当
        $this->assertSame(
            CacheTime::DEFAULT,
            CacheTime::RANKING,
            'ランキングキャッシュ時間はデフォルト時間と同じであるべき'
        );

        $this->assertSame(
            CacheTime::DEFAULT,
            CacheTime::COMPANY_DETAIL,
            '企業詳細キャッシュ時間はデフォルト時間と同じであるべき'
        );

        // 記事一覧と記事詳細は長期キャッシュが妥当
        $this->assertSame(
            CacheTime::LONG,
            CacheTime::ARTICLE_LIST,
            '記事一覧キャッシュ時間は長期キャッシュと同じであるべき'
        );

        $this->assertSame(
            CacheTime::LONG,
            CacheTime::ARTICLE_DETAIL,
            '記事詳細キャッシュ時間は長期キャッシュと同じであるべき'
        );
    }

    #[Test]
    #[DataProvider('キャッシュ時間データプロバイダー')]
    public function test_キャッシュ時間定数の型が正しい(string $constantName, int $expectedValue): void
    {
        $actualValue = constant(CacheTime::class.'::'.$constantName);

        $this->assertIsInt($actualValue, "定数 {$constantName} は int 型であるべき");
        $this->assertSame($expectedValue, $actualValue, "定数 {$constantName} の値が期待値と一致しない");
    }

    public static function キャッシュ時間データプロバイダー(): array
    {
        return [
            'DEFAULT' => ['DEFAULT', 300],
            'SHORT' => ['SHORT', 60],
            'LONG' => ['LONG', 600],
            'STATISTICS' => ['STATISTICS', 600],
            'RANKING' => ['RANKING', 300],
            'COMPANY_DETAIL' => ['COMPANY_DETAIL', 300],
            'ARTICLE_LIST' => ['ARTICLE_LIST', 600],
            'ARTICLE_DETAIL' => ['ARTICLE_DETAIL', 600],
        ];
    }

    #[Test]
    public function test_キャッシュ時間値の範囲が適切である(): void
    {
        $cacheTimeValues = [
            CacheTime::DEFAULT,
            CacheTime::SHORT,
            CacheTime::LONG,
            CacheTime::STATISTICS,
            CacheTime::RANKING,
            CacheTime::COMPANY_DETAIL,
            CacheTime::ARTICLE_LIST,
            CacheTime::ARTICLE_DETAIL,
        ];

        foreach ($cacheTimeValues as $cacheTime) {
            $this->assertGreaterThan(0, $cacheTime, 'キャッシュ時間は正の整数であるべき');
            $this->assertLessThanOrEqual(3600, $cacheTime, 'キャッシュ時間は1時間以下であるべき');
        }
    }

    #[Test]
    public function test_秒単位での時間設定の妥当性(): void
    {
        // SHORT: 1分 = 60秒
        $this->assertSame(60, CacheTime::SHORT, 'SHORT時間は60秒（1分）であるべき');

        // DEFAULT: 5分 = 300秒
        $this->assertSame(300, CacheTime::DEFAULT, 'DEFAULT時間は300秒（5分）であるべき');

        // LONG: 10分 = 600秒
        $this->assertSame(600, CacheTime::LONG, 'LONG時間は600秒（10分）であるべき');
    }

    #[Test]
    public function test_ビジネス要件との整合性(): void
    {
        // 短期キャッシュ（1分）：頻繁に変更される可能性があるデータ
        $this->assertSame(60, CacheTime::SHORT, '短期キャッシュは1分が適切');

        // デフォルト（5分）：一般的なデータの適度なキャッシュ
        $this->assertSame(300, CacheTime::DEFAULT, 'デフォルトキャッシュは5分が適切');

        // 長期キャッシュ（10分）：あまり変更されないデータ
        $this->assertSame(600, CacheTime::LONG, '長期キャッシュは10分が適切');

        // 統計情報（10分）：計算コストが高く、リアルタイム性を重視しないデータ
        $this->assertSame(600, CacheTime::STATISTICS, '統計情報キャッシュは10分が適切');

        // ランキング（5分）：適度な更新頻度が必要なデータ
        $this->assertSame(300, CacheTime::RANKING, 'ランキングキャッシュは5分が適切');

        // 企業詳細（5分）：基本情報で適度なキャッシュが適切
        $this->assertSame(300, CacheTime::COMPANY_DETAIL, '企業詳細キャッシュは5分が適切');

        // 記事一覧（10分）：検索結果のキャッシュで長期が適切
        $this->assertSame(600, CacheTime::ARTICLE_LIST, '記事一覧キャッシュは10分が適切');

        // 記事詳細（10分）：記事内容は変更されないため長期が適切
        $this->assertSame(600, CacheTime::ARTICLE_DETAIL, '記事詳細キャッシュは10分が適切');
    }

    #[Test]
    public function test_全定数がアクセス可能である(): void
    {
        $reflection = new ReflectionClass(CacheTime::class);
        $constants = $reflection->getConstants();

        $expectedConstants = [
            'DEFAULT',
            'SHORT',
            'LONG',
            'STATISTICS',
            'RANKING',
            'COMPANY_DETAIL',
            'ARTICLE_LIST',
            'ARTICLE_DETAIL',
        ];

        foreach ($expectedConstants as $constantName) {
            $this->assertArrayHasKey($constantName, $constants, "定数 {$constantName} が定義されていない");
        }

        $this->assertCount(count($expectedConstants), $constants, '期待される定数の数と実際の定数の数が一致しない');
    }

    #[Test]
    public function test_定数の不変性確認(): void
    {
        $originalDefault = CacheTime::DEFAULT;
        $originalShort = CacheTime::SHORT;
        $originalLong = CacheTime::LONG;

        // 定数は変更されないことを確認
        $this->assertSame($originalDefault, CacheTime::DEFAULT);
        $this->assertSame($originalShort, CacheTime::SHORT);
        $this->assertSame($originalLong, CacheTime::LONG);
    }

    #[Test]
    public function test_パフォーマンス要件との整合性(): void
    {
        // データ更新頻度の低いものほど長いキャッシュ時間
        $updateFrequencyOrder = [
            'frequent' => CacheTime::SHORT,          // 頻繁に更新される
            'moderate' => CacheTime::DEFAULT,        // 適度に更新される
            'infrequent' => CacheTime::LONG,         // あまり更新されない
        ];

        $previousTime = 0;
        foreach ($updateFrequencyOrder as $frequency => $cacheTime) {
            $this->assertGreaterThan(
                $previousTime,
                $cacheTime,
                "更新頻度が低いほど長いキャッシュ時間を持つべき ({$frequency})"
            );
            $previousTime = $cacheTime;
        }
    }

    #[Test]
    public function test_キャッシュ戦略の分類妥当性(): void
    {
        // 短期キャッシュに分類されるべき時間
        $shortTermCaches = [CacheTime::SHORT];
        foreach ($shortTermCaches as $cacheTime) {
            $this->assertLessThan(120, $cacheTime, '短期キャッシュは2分未満であるべき');
        }

        // 中期キャッシュに分類されるべき時間
        $mediumTermCaches = [CacheTime::DEFAULT, CacheTime::RANKING, CacheTime::COMPANY_DETAIL];
        foreach ($mediumTermCaches as $cacheTime) {
            $this->assertGreaterThanOrEqual(120, $cacheTime, '中期キャッシュは2分以上であるべき');
            $this->assertLessThan(600, $cacheTime, '中期キャッシュは10分未満であるべき');
        }

        // 長期キャッシュに分類されるべき時間
        $longTermCaches = [CacheTime::LONG, CacheTime::STATISTICS, CacheTime::ARTICLE_LIST, CacheTime::ARTICLE_DETAIL];
        foreach ($longTermCaches as $cacheTime) {
            $this->assertGreaterThanOrEqual(600, $cacheTime, '長期キャッシュは10分以上であるべき');
        }
    }

    #[Test]
    public function test_実用的な時間設定確認(): void
    {
        // 最小キャッシュ時間が実用的であること（30秒以上）
        $minCacheTime = min([
            CacheTime::DEFAULT,
            CacheTime::SHORT,
            CacheTime::LONG,
            CacheTime::STATISTICS,
            CacheTime::RANKING,
            CacheTime::COMPANY_DETAIL,
            CacheTime::ARTICLE_LIST,
            CacheTime::ARTICLE_DETAIL,
        ]);

        $this->assertGreaterThanOrEqual(30, $minCacheTime, '最小キャッシュ時間は30秒以上であるべき');

        // 最大キャッシュ時間が実用的であること（1時間以下）
        $maxCacheTime = max([
            CacheTime::DEFAULT,
            CacheTime::SHORT,
            CacheTime::LONG,
            CacheTime::STATISTICS,
            CacheTime::RANKING,
            CacheTime::COMPANY_DETAIL,
            CacheTime::ARTICLE_LIST,
            CacheTime::ARTICLE_DETAIL,
        ]);

        $this->assertLessThanOrEqual(3600, $maxCacheTime, '最大キャッシュ時間は1時間以下であるべき');
    }
}
