<?php

namespace App\Constants;

class ScorePeriod
{
    public const DAILY = 'daily';

    public const WEEKLY = 'weekly';

    public const MONTHLY = 'monthly';

    public const ALL = [
        self::DAILY,
        self::WEEKLY,
        self::MONTHLY,
    ];

    /**
     * 有効な期間タイプの配列を取得
     */
    public static function getValidPeriods(): array
    {
        return self::ALL;
    }

    /**
     * 期間タイプが有効かチェック
     */
    public static function isValid(string $periodType): bool
    {
        return in_array($periodType, self::ALL, true);
    }

    /**
     * バリデーションルール用の文字列を取得
     */
    public static function getValidationRule(): string
    {
        return 'in:'.implode(',', self::getValidPeriods());
    }

    /**
     * 期間タイプの表示名を取得
     */
    public static function getDisplayName(string $periodType): string
    {
        return match ($periodType) {
            self::DAILY => '日次',
            self::WEEKLY => '週次',
            self::MONTHLY => '月次',
            default => $periodType,
        };
    }
}
