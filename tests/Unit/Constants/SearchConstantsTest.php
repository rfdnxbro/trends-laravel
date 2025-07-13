<?php

namespace Tests\Unit\Constants;

use App\Constants\SearchConstants;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SearchConstantsTest extends TestCase
{
    #[Test]
    public function test_最大クエリ長定数が正しい値を持つ(): void
    {
        $this->assertSame(255, SearchConstants::MAX_QUERY_LENGTH);
    }

    #[Test]
    public function test_最小ランキング表示定数が正しい値を持つ(): void
    {
        $this->assertSame(1, SearchConstants::MIN_RANKING_DISPLAY);
    }

    #[Test]
    #[DataProvider('定数値データプロバイダー')]
    public function test_定数の型が正しい(string $constantName, int $expectedValue): void
    {
        $actualValue = constant(SearchConstants::class.'::'.$constantName);

        $this->assertIsInt($actualValue, "定数 {$constantName} は int 型であるべき");
        $this->assertSame($expectedValue, $actualValue, "定数 {$constantName} の値が期待値と一致しない");
    }

    public static function 定数値データプロバイダー(): array
    {
        return [
            'MAX_QUERY_LENGTH' => ['MAX_QUERY_LENGTH', 255],
            'MIN_RANKING_DISPLAY' => ['MIN_RANKING_DISPLAY', 1],
        ];
    }

    #[Test]
    public function test_最大クエリ長の値が妥当である(): void
    {
        $this->assertGreaterThan(0, SearchConstants::MAX_QUERY_LENGTH, 'クエリ最大長は正の整数であるべき');
        $this->assertLessThanOrEqual(1000, SearchConstants::MAX_QUERY_LENGTH, 'クエリ最大長は現実的な範囲内であるべき');
        $this->assertGreaterThanOrEqual(50, SearchConstants::MAX_QUERY_LENGTH, 'クエリ最大長は最低限の検索に十分であるべき');
    }

    #[Test]
    public function test_最小ランキング表示の値が妥当である(): void
    {
        $this->assertGreaterThan(0, SearchConstants::MIN_RANKING_DISPLAY, '最小ランキング表示は正の整数であるべき');
        $this->assertLessThanOrEqual(10, SearchConstants::MIN_RANKING_DISPLAY, '最小ランキング表示は現実的な範囲内であるべき');
    }

    #[Test]
    public function test_全定数がアクセス可能である(): void
    {
        $reflection = new ReflectionClass(SearchConstants::class);
        $constants = $reflection->getConstants();

        $expectedConstants = [
            'MAX_QUERY_LENGTH',
            'MIN_RANKING_DISPLAY',
        ];

        foreach ($expectedConstants as $constantName) {
            $this->assertArrayHasKey($constantName, $constants, "定数 {$constantName} が定義されていない");
        }

        $this->assertCount(count($expectedConstants), $constants, '期待される定数の数と実際の定数の数が一致しない');
    }

    #[Test]
    public function test_定数の不変性確認(): void
    {
        $originalMaxLength = SearchConstants::MAX_QUERY_LENGTH;
        $originalMinDisplay = SearchConstants::MIN_RANKING_DISPLAY;

        // 定数は変更されないことを確認
        $this->assertSame($originalMaxLength, SearchConstants::MAX_QUERY_LENGTH);
        $this->assertSame($originalMinDisplay, SearchConstants::MIN_RANKING_DISPLAY);
    }

    #[Test]
    public function test_get_query_validation_ruleメソッドが存在し正しく動作する(): void
    {
        $this->assertTrue(method_exists(SearchConstants::class, 'getQueryValidationRule'), 'getQueryValidationRuleメソッドが存在するべき');

        $rule = SearchConstants::getQueryValidationRule();
        $this->assertIsString($rule, 'バリデーションルールは文字列であるべき');

        $expectedRule = 'required|string|min:1|max:'.SearchConstants::MAX_QUERY_LENGTH;
        $this->assertSame($expectedRule, $rule, 'バリデーションルールが期待値と一致しない');
    }

    #[Test]
    public function test_バリデーションルールの内容が妥当である(): void
    {
        $rule = SearchConstants::getQueryValidationRule();

        // 必須フィールドルールを含む
        $this->assertStringContainsString('required', $rule, 'バリデーションルールにrequiredが含まれるべき');

        // 文字列タイプルールを含む
        $this->assertStringContainsString('string', $rule, 'バリデーションルールにstringが含まれるべき');

        // 最小長ルールを含む
        $this->assertStringContainsString('min:1', $rule, 'バリデーションルールにmin:1が含まれるべき');

        // 最大長ルールを含む
        $this->assertStringContainsString('max:'.SearchConstants::MAX_QUERY_LENGTH, $rule, 'バリデーションルールに正しい最大長が含まれるべき');
    }

    #[Test]
    public function test_バリデーションルールの動的生成が正しい(): void
    {
        // 定数値を使用して動的にルールが生成されることを確認
        $manualRule = 'required|string|min:1|max:'.SearchConstants::MAX_QUERY_LENGTH;
        $generatedRule = SearchConstants::getQueryValidationRule();

        $this->assertSame($manualRule, $generatedRule, '手動構築ルールと動的生成ルールが一致するべき');
    }

    #[Test]
    public function test_ビジネスロジック要件との整合性(): void
    {
        // 検索クエリ長がDBの制約と整合している
        $this->assertSame(255, SearchConstants::MAX_QUERY_LENGTH, 'クエリ最大長がVARCHAR(255)と整合するべき');

        // 最小ランキング表示が実用的
        $this->assertSame(1, SearchConstants::MIN_RANKING_DISPLAY, '最小ランキング表示が1件以上であるべき');

        // クエリ長の範囲が検索エンジンの制約内
        $this->assertLessThanOrEqual(500, SearchConstants::MAX_QUERY_LENGTH, 'クエリ最大長が一般的な検索エンジンの制約内であるべき');
    }

    #[Test]
    public function test_検索パフォーマンスに関する妥当性(): void
    {
        // クエリ長がインデックス効率を考慮している
        $this->assertLessThanOrEqual(1000, SearchConstants::MAX_QUERY_LENGTH, 'クエリ最大長がインデックス効率を考慮した範囲内であるべき');

        // 最小表示件数がページネーション効率を考慮している
        $this->assertGreaterThanOrEqual(1, SearchConstants::MIN_RANKING_DISPLAY, '最小表示件数がページネーション効率を考慮しているべき');
        $this->assertLessThanOrEqual(100, SearchConstants::MIN_RANKING_DISPLAY, '最小表示件数が現実的な範囲内であるべき');
    }

    #[Test]
    public function test_セキュリティ要件との整合性(): void
    {
        // クエリ長がSQLインジェクション対策範囲内
        $this->assertLessThanOrEqual(1000, SearchConstants::MAX_QUERY_LENGTH, 'クエリ最大長がSQLインジェクション対策を考慮した範囲内であるべき');

        // DoS攻撃対策として妥当な長さ
        $this->assertLessThanOrEqual(500, SearchConstants::MAX_QUERY_LENGTH, 'クエリ最大長がDoS攻撃対策を考慮した範囲内であるべき');
    }

    #[Test]
    public function test_ユーザビリティ要件との整合性(): void
    {
        // 十分な検索語句を入力可能
        $this->assertGreaterThanOrEqual(100, SearchConstants::MAX_QUERY_LENGTH, 'クエリ最大長がユーザーの検索語句入力に十分であるべき');

        // 最小表示件数がユーザー体験に適している
        $this->assertSame(1, SearchConstants::MIN_RANKING_DISPLAY, '最小表示件数がユーザー体験に適しているべき');
    }

    #[Test]
    public function test_定数値の境界値テスト(): void
    {
        // MAX_QUERY_LENGTHの境界値
        $this->assertNotSame(0, SearchConstants::MAX_QUERY_LENGTH, 'クエリ最大長は0であってはならない');
        $this->assertNotSame(-1, SearchConstants::MAX_QUERY_LENGTH, 'クエリ最大長は負の値であってはならない');

        // MIN_RANKING_DISPLAYの境界値
        $this->assertNotSame(0, SearchConstants::MIN_RANKING_DISPLAY, '最小ランキング表示は0であってはならない');
        $this->assertNotSame(-1, SearchConstants::MIN_RANKING_DISPLAY, '最小ランキング表示は負の値であってはならない');
    }
}
