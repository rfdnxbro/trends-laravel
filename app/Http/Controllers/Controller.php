<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Trends Laravel API",
 *     version="1.0.0",
 *     description="企業の技術コミュニティでの影響力を分析・追跡するAPI",
 *     @OA\Contact(
 *         email="admin@example.com"
 *     )
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * @OA\Tag(
 *     name="企業詳細",
 *     description="企業の詳細情報、記事一覧、影響力スコア履歴、ランキング情報を提供するAPI"
 * )
 * @OA\Tag(
 *     name="企業ランキング",
 *     description="企業の技術コミュニティでの影響力ランキングデータを提供するAPI"
 * )
 * @OA\Tag(
 *     name="検索",
 *     description="企業・記事・技術キーワードを検索するAPI"
 * )
 */
abstract class Controller
{
    //
}
