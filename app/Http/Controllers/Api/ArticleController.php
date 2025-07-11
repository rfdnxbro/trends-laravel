<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::with(['company', 'platform']);

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('platform_id')) {
            $query->where('platform_id', $request->platform_id);
        }

        if ($request->has('limit')) {
            $query->limit($request->limit);
        }

        $articles = $query->orderBy('published_at', 'desc')->paginate(20);

        return response()->json($articles);
    }
}
