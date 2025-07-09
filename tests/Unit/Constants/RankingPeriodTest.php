<?php

namespace Tests\Unit\Constants;

use App\Constants\RankingPeriod;
use Tests\TestCase;

class RankingPeriodTest extends TestCase
{
    public function test_期間タイプ定数が存在する()
    {
        $this->assertIsArray(RankingPeriod::TYPES);
        $this->assertNotEmpty(RankingPeriod::TYPES);
    }

    public function test_期間タイプ定数が期待される期間を含む()
    {
        $expectedPeriods = ['1w', '1m', '3m', '6m', '1y', '3y', 'all'];

        foreach ($expectedPeriods as $period) {
            $this->assertArrayHasKey($period, RankingPeriod::TYPES);
        }
    }

    public function test_期間タイプ定数が正しい値を持つ()
    {
        $expectedValues = [
            '1w' => 7,
            '1m' => 30,
            '3m' => 90,
            '6m' => 180,
            '1y' => 365,
            '3y' => 1095,
            'all' => null,
        ];

        $this->assertEquals($expectedValues, RankingPeriod::TYPES);
    }

    public function test_有効な期間を取得する()
    {
        $validPeriods = RankingPeriod::getValidPeriods();

        $this->assertIsArray($validPeriods);
        $this->assertNotEmpty($validPeriods);

        $expectedPeriods = ['1w', '1m', '3m', '6m', '1y', '3y', 'all'];
        $this->assertEquals($expectedPeriods, $validPeriods);
    }

    public function test_有効な期間で日数を取得する()
    {
        $testCases = [
            '1w' => 7,
            '1m' => 30,
            '3m' => 90,
            '6m' => 180,
            '1y' => 365,
            '3y' => 1095,
            'all' => null,
        ];

        foreach ($testCases as $period => $expectedDays) {
            $this->assertEquals($expectedDays, RankingPeriod::getDays($period));
        }
    }

    public function test_無効な期間で日数取得時に例外が発生する()
    {
        $this->assertNull(RankingPeriod::getDays('invalid'));
        $this->assertNull(RankingPeriod::getDays('2w'));
        $this->assertNull(RankingPeriod::getDays(''));
    }

    public function test_有効な期間でバリデーションが成功する()
    {
        $validPeriods = ['1w', '1m', '3m', '6m', '1y', '3y', 'all'];

        foreach ($validPeriods as $period) {
            $this->assertTrue(RankingPeriod::isValid($period));
        }
    }

    public function test_無効な期間でバリデーションが失敗する()
    {
        $invalidPeriods = ['invalid', '2w', '2m', '5y', '', 'weekly', 'monthly'];

        foreach ($invalidPeriods as $period) {
            $this->assertFalse(RankingPeriod::isValid($period));
        }
    }

    public function test_無効なタイプでバリデーションが失敗する()
    {
        // null、数値、真偽値などの非文字列型
        $this->assertFalse(RankingPeriod::isValid(null));
        $this->assertFalse(RankingPeriod::isValid(1));
        $this->assertFalse(RankingPeriod::isValid(30));
        $this->assertFalse(RankingPeriod::isValid(true));
        $this->assertFalse(RankingPeriod::isValid(false));
        $this->assertFalse(RankingPeriod::isValid([]));
        $this->assertFalse(RankingPeriod::isValid(new \stdClass));

        // 無効な文字列
        $this->assertFalse(RankingPeriod::isValid('1'));
        $this->assertFalse(RankingPeriod::isValid('30'));
    }

    public function test_バリデーションルールを取得する()
    {
        $validationRule = RankingPeriod::getValidationRule();

        $this->assertIsString($validationRule);
        $this->assertStringStartsWith('in:', $validationRule);

        $expectedRule = 'in:1w,1m,3m,6m,1y,3y,all';
        $this->assertEquals($expectedRule, $validationRule);
    }

    public function test_エラーメッセージを取得する()
    {
        $errorMessage = RankingPeriod::getErrorMessage();

        $this->assertIsString($errorMessage);
        $this->assertStringContainsString('Invalid period', $errorMessage);
        $this->assertStringContainsString('Must be one of:', $errorMessage);

        $expectedMessage = 'Invalid period. Must be one of: 1w, 1m, 3m, 6m, 1y, 3y, all';
        $this->assertEquals($expectedMessage, $errorMessage);
    }

    public function test_全メソッドが一貫したデータを返す()
    {
        $validPeriods = RankingPeriod::getValidPeriods();
        $typesKeys = array_keys(RankingPeriod::TYPES);

        // getValidPeriods()とTYPESのキーが一致することを確認
        $this->assertEquals($typesKeys, $validPeriods);

        // isValid()がgetValidPeriods()と一致することを確認
        foreach ($validPeriods as $period) {
            $this->assertTrue(RankingPeriod::isValid($period));
        }

        // getDays()がTYPES配列と一致することを確認
        foreach (RankingPeriod::TYPES as $period => $days) {
            $this->assertEquals($days, RankingPeriod::getDays($period));
        }
    }

    public function test_バリデーションルールが全有効期間を含む()
    {
        $validationRule = RankingPeriod::getValidationRule();
        $validPeriods = RankingPeriod::getValidPeriods();

        // バリデーションルールから期間リストを抽出
        $rulePattern = '/^in:(.+)$/';
        preg_match($rulePattern, $validationRule, $matches);
        $rulePeriods = explode(',', $matches[1]);

        $this->assertEquals($validPeriods, $rulePeriods);
    }

    public function test_エラーメッセージが全有効期間を含む()
    {
        $errorMessage = RankingPeriod::getErrorMessage();
        $validPeriods = RankingPeriod::getValidPeriods();

        foreach ($validPeriods as $period) {
            $this->assertStringContainsString($period, $errorMessage);
        }
    }

    public function test_期間タイプ定数が不変である()
    {
        // PHP定数は変更不可であることを確認
        $originalTypes = RankingPeriod::TYPES;

        // 複数回呼び出しても同じ値を返すことを確認
        $this->assertEquals($originalTypes, RankingPeriod::TYPES);
        $this->assertEquals($originalTypes, RankingPeriod::TYPES);
    }

    public function test_大文字小文字を区別しない()
    {
        // 大文字小文字の違いで無効になることを確認
        $this->assertFalse(RankingPeriod::isValid('1W'));
        $this->assertFalse(RankingPeriod::isValid('1M'));
        $this->assertFalse(RankingPeriod::isValid('ALL'));

        $this->assertNull(RankingPeriod::getDays('1W'));
        $this->assertNull(RankingPeriod::getDays('1M'));
        $this->assertNull(RankingPeriod::getDays('ALL'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('periodDataProvider')]
    public function test_期間データプロバイダーテスト($period, $expectedDays, $expectedValid)
    {
        $this->assertEquals($expectedDays, RankingPeriod::getDays($period));
        $this->assertEquals($expectedValid, RankingPeriod::isValid($period));
    }

    public static function periodDataProvider(): array
    {
        return [
            'one week' => ['1w', 7, true],
            'one month' => ['1m', 30, true],
            'three months' => ['3m', 90, true],
            'six months' => ['6m', 180, true],
            'one year' => ['1y', 365, true],
            'three years' => ['3y', 1095, true],
            'all time' => ['all', null, true],
            'invalid period' => ['invalid', null, false],
            'empty string' => ['', null, false],
            'two weeks' => ['2w', null, false],
        ];
    }

    public function test_特殊文字のエッジケース()
    {
        $specialCases = [
            '1w ',  // 末尾スペース
            ' 1w',  // 先頭スペース
            '1w\n', // 改行文字
            '1w\t', // タブ文字
        ];

        foreach ($specialCases as $period) {
            $this->assertFalse(RankingPeriod::isValid($period));
            $this->assertNull(RankingPeriod::getDays($period));
        }
    }

    public function test_大量データでのパフォーマンス()
    {
        // パフォーマンステスト: 大量の呼び出しでも問題ないことを確認
        $startTime = microtime(true);

        for ($i = 0; $i < 1000; $i++) {
            RankingPeriod::getValidPeriods();
            RankingPeriod::isValid('1m');
            RankingPeriod::getDays('1y');
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // 1秒以内に完了することを確認
        $this->assertLessThan(1.0, $executionTime);
    }
}
