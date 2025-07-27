<?php

namespace App\Services;

use App\Constants\CacheTime;
use App\Models\Article;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class StatisticsService
{
    /**
     * Get overall statistics
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
     * Get total number of active companies
     */
    private function getTotalCompanies(): int
    {
        return Company::where('is_active', true)->count();
    }

    /**
     * Get total number of articles (excluding deleted)
     */
    private function getTotalArticles(): int
    {
        return Article::whereNull('deleted_at')->count();
    }

    /**
     * Get total engagements (excluding deleted)
     */
    private function getTotalEngagements(): int
    {
        return (int) Article::whereNull('deleted_at')->sum('engagement_count');
    }
}