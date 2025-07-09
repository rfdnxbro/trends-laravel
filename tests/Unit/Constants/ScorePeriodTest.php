<?php

namespace Tests\Unit\Constants;

use App\Constants\ScorePeriod;
use Tests\TestCase;

class ScorePeriodTest extends TestCase
{
    public function test_定数値が正しく設定されている()
    {
        $this->assertEquals('daily', ScorePeriod::DAILY);
        $this->assertEquals('weekly', ScorePeriod::WEEKLY);
        $this->assertEquals('monthly', ScorePeriod::MONTHLY);
    }

    public function test_all定数が全ての期間タイプを含んでいる()
    {
        $expected = ['daily', 'weekly', 'monthly'];
        $this->assertEquals($expected, ScorePeriod::ALL);
    }

    public function test_getValidPeriodsが正しく全期間タイプを返す()
    {
        $periods = ScorePeriod::getValidPeriods();
        $expected = ['daily', 'weekly', 'monthly'];

        $this->assertEquals($expected, $periods);
        $this->assertEquals(ScorePeriod::ALL, $periods);
    }

    public function test_isValidが有効な期間タイプでtrueを返す()
    {
        $this->assertTrue(ScorePeriod::isValid('daily'));
        $this->assertTrue(ScorePeriod::isValid('weekly'));
        $this->assertTrue(ScorePeriod::isValid('monthly'));
    }

    public function test_isValidが無効な期間タイプでfalseを返す()
    {
        $this->assertFalse(ScorePeriod::isValid('invalid'));
        $this->assertFalse(ScorePeriod::isValid('yearly'));
        $this->assertFalse(ScorePeriod::isValid(''));
        $this->assertFalse(ScorePeriod::isValid('DAILY'));
    }

    public function test_getValidationRuleが正しいバリデーションルール文字列を返す()
    {
        $rule = ScorePeriod::getValidationRule();
        $expected = 'in:daily,weekly,monthly';

        $this->assertEquals($expected, $rule);
    }

    public function test_getDisplayNameが正しい表示名を返す()
    {
        $this->assertEquals('日次', ScorePeriod::getDisplayName('daily'));
        $this->assertEquals('週次', ScorePeriod::getDisplayName('weekly'));
        $this->assertEquals('月次', ScorePeriod::getDisplayName('monthly'));
    }

    public function test_getDisplayNameが無効な期間タイプでそのまま返す()
    {
        $this->assertEquals('invalid', ScorePeriod::getDisplayName('invalid'));
        $this->assertEquals('yearly', ScorePeriod::getDisplayName('yearly'));
        $this->assertEquals('', ScorePeriod::getDisplayName(''));
    }

    public function test_定数を使用したバリデーションルールテスト()
    {
        $this->assertTrue(ScorePeriod::isValid(ScorePeriod::DAILY));
        $this->assertTrue(ScorePeriod::isValid(ScorePeriod::WEEKLY));
        $this->assertTrue(ScorePeriod::isValid(ScorePeriod::MONTHLY));
    }

    public function test_期間タイプの数が期待通りである()
    {
        $this->assertCount(3, ScorePeriod::ALL);
        $this->assertCount(3, ScorePeriod::getValidPeriods());
    }
}