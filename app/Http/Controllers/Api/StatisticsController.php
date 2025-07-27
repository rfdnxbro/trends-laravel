<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Http\JsonResponse;

class StatisticsController extends Controller
{
    private StatisticsService $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    /**
     * @OA\Get(
     *     path="/api/statistics/overall",
     *     tags={"Statistics"},
     *     summary="全体統計の取得",
     *     description="システム全体の統計情報（企業数、記事数、エンゲージメント数）を取得します。",
     *
     *     @OA\Response(
     *         response=200,
     *         description="全体統計情報",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_companies", type="integer", example=100, description="アクティブな企業の総数"),
     *                 @OA\Property(property="total_articles", type="integer", example=1000, description="記事の総数（削除済みを除く）"),
     *                 @OA\Property(property="total_engagements", type="integer", example=50000, description="総エンゲージメント数（削除済みを除く）"),
     *                 @OA\Property(property="last_updated", type="string", format="date-time", description="最終更新日時")
     *             )
     *         )
     *     )
     * )
     */
    public function overall(): JsonResponse
    {
        $statistics = $this->statisticsService->getOverallStatistics();

        return response()->json([
            'data' => $statistics,
        ]);
    }
}
