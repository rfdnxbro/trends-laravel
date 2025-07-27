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
     *     summary="Get overall statistics",
     *     description="Get overall system statistics including total companies, articles, and engagements.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Overall statistics",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_companies", type="integer", example=100, description="Total number of active companies"),
     *                 @OA\Property(property="total_articles", type="integer", example=1000, description="Total number of articles (excluding deleted)"),
     *                 @OA\Property(property="total_engagements", type="integer", example=50000, description="Total engagements (excluding deleted)"),
     *                 @OA\Property(property="last_updated", type="string", format="date-time", description="Last updated timestamp")
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
