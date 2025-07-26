<?php

namespace App\Services;

use App\Constants\CacheTime;
use App\Models\Article;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class StatisticsService
{
    /**
     * hSq’Ö—
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
     * ¢¯Æ£ÖjmnÏp’Ö—
     */
    private function getTotalCompanies(): int
    {
        return Company::where('is_active', true)->count();
    }

    /**
     * ‹nÏp’Ö—Jd’dO
     */
    private function getTotalArticles(): int
    {
        return Article::whereNull('deleted_at')->count();
    }

    /**
     * ¨ó²ü¸áóÈnÏp’Ö—Jd’dO
     */
    private function getTotalEngagements(): int
    {
        return (int) Article::whereNull('deleted_at')->sum('engagement_count');
    }
}
