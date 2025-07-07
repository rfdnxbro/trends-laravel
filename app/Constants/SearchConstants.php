<?php

namespace App\Constants;

class SearchConstants
{
    /**
     * 検索クエリの最大文字数
     */
    public const MAX_QUERY_LENGTH = 255;

    /**
     * ランキング表示の最小件数
     */
    public const MIN_RANKING_DISPLAY = 1;

    /**
     * バリデーションルールを取得
     */
    public static function getQueryValidationRule(): string
    {
        return 'required|string|min:1|max:'.self::MAX_QUERY_LENGTH;
    }
}
