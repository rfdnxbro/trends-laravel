<?php

namespace Tests\Unit\Constants;

use App\Constants\RankingConstants;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RankingConstantsTest extends TestCase
{
    #[Test]
    public function test_全期間開始年定数が正しい値を持つ(): void
    {
        $this->assertSame(2020, RankingConstants::ALL_TIME_START_YEAR);
    }

    #[Test]
    public function test_計算乗数定数が正しい値を持つ(): void
    {
        $this->assertSame(10, RankingConstants::CALCULATION_MULTIPLIER);
    }

    #[Test]
    #[DataProvider('定数値データプロバイダー')]
    public function test_定数の型が正しい(string $constantName, int $expectedValue): void
    {
        $actualValue = constant(RankingConstants::class.'::'.$constantName);

        $this->assertIsInt($actualValue, "定数 {$constantName} は int 型であるべき");
        $this->assertSame($expectedValue, $actualValue, "定数 {$constantName} の値が期待値と一致しない");
    }

    public static function 定数値データプロバイダー(): array
    {
        return [
            'ALL_TIME_START_YEAR' => ['ALL_TIME_START_YEAR', 2020],
            'CALCULATION_MULTIPLIER' => ['CALCULATION_MULTIPLIER', 10],
        ];
    }

    #[Test]
    public function test_全期間開始年の値が現実的である(): void
    {
        $this->assertGreaterThanOrEqual(2000, RankingConstants::ALL_TIME_START_YEAR, '開始年は2000年以降であるべき');
        $this->assertLessThanOrEqual(date('Y'), RankingConstants::ALL_TIME_START_YEAR, '開始年は現在年以前であるべき');
    }

    #[Test]
    public function test_計算乗数の値が妥当である(): void
    {
        $this->assertGreaterThan(0, RankingConstants::CALCULATION_MULTIPLIER, '計算乗数は正の整数であるべき');
        $this->assertLessThanOrEqual(100, RankingConstants::CALCULATION_MULTIPLIER, '計算乗数は現実的な範囲内であるべき');
    }

    #[Test]
    public function test_全定数がアクセス可能である(): void
    {
        $reflection = new ReflectionClass(RankingConstants::class);
        $constants = $reflection->getConstants();

        $expectedConstants = [
            'ALL_TIME_START_YEAR',
            'CALCULATION_MULTIPLIER',
        ];

        foreach ($expectedConstants as $constantName) {
            $this->assertArrayHasKey($constantName, $constants, "定数 {$constantName} が定義されていない");
        }

        $this->assertCount(count($expectedConstants), $constants, '期待される定数の数と実際の定数の数が一致しない');
    }

    #[Test]
    public function test_定数の不変性確認(): void
    {
        $originalStartYear = RankingConstants::ALL_TIME_START_YEAR;
        $originalMultiplier = RankingConstants::CALCULATION_MULTIPLIER;

        // 定数は変更されないことを確認
        $this->assertSame($originalStartYear, RankingConstants::ALL_TIME_START_YEAR);
        $this->assertSame($originalMultiplier, RankingConstants::CALCULATION_MULTIPLIER);
    }

    #[Test]
    public function test_ビジネスロジック要件との整合性(): void
    {
        // 開始年が計算対象期間として妥当
        $currentYear = (int) date('Y');
        $yearSpan = $currentYear - RankingConstants::ALL_TIME_START_YEAR;

        $this->assertGreaterThan(0, $yearSpan, '開始年から現在年までの期間が正の値であるべき');
        $this->assertLessThanOrEqual(50, $yearSpan, '開始年から現在年までの期間が妥当な範囲内であるべき');

        // 計算乗数がランキング算出に適している
        $this->assertSame(10, RankingConstants::CALCULATION_MULTIPLIER, '計算乗数は10であることが期待される');
    }

    #[Test]
    public function test_ランキング計算での数値オーバーフロー回避(): void
    {
        // 最大想定データ数での計算結果が integer 範囲内か確認
        $maxExpectedItems = 10000; // 想定される最大アイテム数
        $calculatedResult = $maxExpectedItems * RankingConstants::CALCULATION_MULTIPLIER;

        $this->assertLessThan(PHP_INT_MAX, $calculatedResult, 'ランキング計算結果が integer の最大値を超えない');
        $this->assertGreaterThan(0, $calculatedResult, 'ランキング計算結果が正の値である');
    }

    #[Test]
    public function test_開始年の過去データ収集妥当性(): void
    {
        // GitHubやQiitaなど主要サービスの開始年以降であることを確認
        $githubLaunchYear = 2008;
        $qiitaLaunchYear = 2011;

        $this->assertGreaterThanOrEqual($githubLaunchYear, RankingConstants::ALL_TIME_START_YEAR, 'GitHub開始年以降であるべき');

        // 設定された開始年が実用的な期間であることを確認
        $practicalDataStartYear = 2015; // 実用的なデータが蓄積され始めた年
        $this->assertGreaterThanOrEqual($practicalDataStartYear, RankingConstants::ALL_TIME_START_YEAR, '実用的なデータ蓄積開始年以降であるべき');
    }
}
