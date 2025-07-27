<?php

namespace App\Services;

use App\Constants\CacheTime;
use App\Models\Article;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class StatisticsService
{
    /**
     * 全体統計を取得する
     */
    public function getOverallStatistics(): array
    {
        return Cache::remember('overall_statistics', CacheTime::STATISTICS, function () {
            return [
                'total_companies' => $this->getTotalCompanies(),
                'total_articles' => $this->getTotalArticles(),
                'total_engagements' => $this->getTotalEngagements(),
                'last_updated' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * アクティブな企業の総数を取得する
     */
    private function getTotalCompanies(): int
    {
        return Company::where('is_active', true)->count();
    }

    /**
     * 記事の総数を取得する（削除済みを除く）
     */
    private function getTotalArticles(): int
    {
        return Article::whereNull('deleted_at')->count();
    }

    /**
     * 総エンゲージメント数を取得する（削除済みを除く）
     */
    private function getTotalEngagements(): int
    {
        return (int) Article::whereNull('deleted_at')->sum('engagement_count');
    }
}