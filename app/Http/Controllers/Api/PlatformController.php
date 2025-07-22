<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Platform;
use Illuminate\Http\JsonResponse;

class PlatformController extends Controller
{
    /**
     * プラットフォーム一覧を取得
     *
     * @return JsonResponse プラットフォーム一覧
     */
    public function index(): JsonResponse
    {
        $platforms = Platform::getForApi();

        return response()->json([
            'success' => true,
            'data' => $platforms,
        ]);
    }
}
