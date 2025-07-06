<?php

namespace App\Constants;

class RankingPeriod
{
    /**
     * 期間タイプの定義
     */
    public const TYPES = [
        '1w' => 7,
        '1m' => 30,
        '3m' => 90,
        '6m' => 180,
        '1y' => 365,
        '3y' => 1095,
        'all' => null,
    ];

    /**
     * 有効な期間タイプの配列を取得
     */
    public static function getValidPeriods(): array
    {
        return array_keys(self::TYPES);
    }

    /**
     * 期間タイプの日数を取得
     */
    public static function getDays(string $periodType): ?int
    {
        return self::TYPES[$periodType] ?? null;
    }

    /**
     * 期間タイプが有効かチェック
     */
    public static function isValid($periodType): bool
    {
        if (! is_string($periodType)) {
            return false;
        }

        return array_key_exists($periodType, self::TYPES);
    }

    /**
     * バリデーションルール用の文字列を取得
     */
    public static function getValidationRule(): string
    {
        return 'in:'.implode(',', self::getValidPeriods());
    }

    /**
     * エラーメッセージ用の文字列を取得
     */
    public static function getErrorMessage(): string
    {
        return 'Invalid period. Must be one of: '.implode(', ', self::getValidPeriods());
    }
}
