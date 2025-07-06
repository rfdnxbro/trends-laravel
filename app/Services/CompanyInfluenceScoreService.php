<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Company;
use App\Models\CompanyInfluenceScore;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CompanyInfluenceScoreService
{
    /**
     * 重み付け設定
     */
    private const WEIGHTS = [
        'article_base' => 1.0,
        'bookmark_multiplier' => 0.1,
        'likes_multiplier' => 0.05,
        'platform_qiita' => 1.0,
        'platform_zenn' => 1.0,
        'platform_hatena' => 0.8,
        'time_decay_factor' => 0.95,
    ];

    /**
     * 期間タイプ定義
     */
    private const PERIOD_TYPES = [
        'daily' => 1,
        'weekly' => 7,
        'monthly' => 30,
    ];

    /**
     * 企業の影響力スコアを計算
     */
    public function calculateCompanyScore(Company $company, string $periodType, Carbon $periodStart, Carbon $periodEnd): float
    {
        $articles = $this->getArticlesForPeriod($company, $periodStart, $periodEnd);

        if ($articles->isEmpty()) {
            return 0.0;
        }

        $totalScore = 0.0;

        foreach ($articles as $article) {
            $score = $this->calculateArticleScore($article, $periodStart, $periodEnd);
            $totalScore += $score;
        }

        Log::info('Company influence score calculated', [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'period_type' => $periodType,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'article_count' => $articles->count(),
            'total_score' => $totalScore,
        ]);

        return round($totalScore, 2);
    }

    /**
     * 記事の影響力スコアを計算
     */
    private function calculateArticleScore(Article $article, Carbon $periodStart, Carbon $periodEnd): float
    {
        // 基本スコア（記事の存在による基本点）
        $baseScore = self::WEIGHTS['article_base'];

        // ブックマーク数による重み付け
        $bookmarkScore = $article->bookmark_count * self::WEIGHTS['bookmark_multiplier'];

        // いいね数による重み付け
        $likesScore = $article->likes_count * self::WEIGHTS['likes_multiplier'];

        // プラットフォームによる重み付け
        $platformWeight = $this->getPlatformWeight($article->platform);

        // 時系列による重み付け（新しい記事ほど高スコア）
        $timeWeight = $this->getTimeWeight($article->published_at, $periodStart, $periodEnd);

        $score = ($baseScore + $bookmarkScore + $likesScore) * $platformWeight * $timeWeight;

        return $score;
    }

    /**
     * プラットフォームによる重み付けを取得
     */
    private function getPlatformWeight(string $platform): float
    {
        $platform = strtolower($platform);

        return match ($platform) {
            'qiita' => self::WEIGHTS['platform_qiita'],
            'zenn' => self::WEIGHTS['platform_zenn'],
            'hatena' => self::WEIGHTS['platform_hatena'],
            default => 0.5,
        };
    }

    /**
     * 時系列による重み付けを取得
     */
    private function getTimeWeight(?Carbon $publishedAt, Carbon $periodStart, Carbon $periodEnd): float
    {
        if (! $publishedAt || $publishedAt->lt($periodStart) || $publishedAt->gt($periodEnd)) {
            return 0.5; // 公開日が不明または期間外の場合は低い重み
        }

        $periodDays = $periodEnd->diffInDays($periodStart);
        $daysSinceStart = $publishedAt->diffInDays($periodStart);

        // 期間内での相対的な新しさを計算（0.0～1.0）
        $relativeNewness = $periodDays > 0 ? (1.0 - ($daysSinceStart / $periodDays)) : 1.0;

        // 時間減衰を適用
        $decayFactor = self::WEIGHTS['time_decay_factor'];
        $timeWeight = pow($decayFactor, $daysSinceStart);

        return max(0.1, $timeWeight * (0.5 + $relativeNewness * 0.5));
    }

    /**
     * 指定期間の記事を取得
     */
    private function getArticlesForPeriod(Company $company, Carbon $periodStart, Carbon $periodEnd)
    {
        return Article::where('company_id', $company->id)
            ->whereBetween('published_at', [$periodStart, $periodEnd])
            ->orWhere(function ($query) use ($company, $periodStart, $periodEnd) {
                $query->where('company_id', $company->id)
                    ->whereNull('published_at')
                    ->whereBetween('scraped_at', [$periodStart, $periodEnd]);
            })
            ->get();
    }

    /**
     * 企業の影響力スコアを保存
     */
    public function saveCompanyInfluenceScore(
        Company $company,
        string $periodType,
        Carbon $periodStart,
        Carbon $periodEnd,
        float $totalScore
    ): CompanyInfluenceScore {
        $articles = $this->getArticlesForPeriod($company, $periodStart, $periodEnd);
        $articleCount = $articles->count();
        $totalBookmarks = $articles->sum('bookmark_count');

        return CompanyInfluenceScore::updateOrCreate(
            [
                'company_id' => $company->id,
                'period_type' => $periodType,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ],
            [
                'total_score' => $totalScore,
                'article_count' => $articleCount,
                'total_bookmarks' => $totalBookmarks,
                'calculated_at' => now(),
            ]
        );
    }

    /**
     * 全企業の影響力スコアを一括計算
     */
    public function calculateAllCompaniesScore(string $periodType, Carbon $periodStart, Carbon $periodEnd): array
    {
        $companies = Company::where('is_active', true)->get();
        $results = [];

        foreach ($companies as $company) {
            $score = $this->calculateCompanyScore($company, $periodType, $periodStart, $periodEnd);

            if ($score > 0) {
                $influenceScore = $this->saveCompanyInfluenceScore(
                    $company,
                    $periodType,
                    $periodStart,
                    $periodEnd,
                    $score
                );
                $results[] = $influenceScore;
            }
        }

        Log::info('All companies influence scores calculated', [
            'period_type' => $periodType,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'companies_processed' => count($results),
        ]);

        return $results;
    }

    /**
     * 期間別スコア計算のヘルパーメソッド
     */
    public function calculateScoresByPeriod(?Carbon $referenceDate = null): array
    {
        $referenceDate = $referenceDate ?? now();
        $results = [];

        foreach (self::PERIOD_TYPES as $periodType => $days) {
            $periodEnd = $referenceDate->copy();
            $periodStart = $referenceDate->copy()->subDays($days);

            $scores = $this->calculateAllCompaniesScore($periodType, $periodStart, $periodEnd);
            $results[$periodType] = $scores;
        }

        return $results;
    }

    /**
     * 指定企業の期間別スコアを取得
     */
    public function getCompanyScoresByPeriod(Company $company, int $limit = 10): array
    {
        $scores = [];

        foreach (self::PERIOD_TYPES as $periodType => $days) {
            $scores[$periodType] = CompanyInfluenceScore::where('company_id', $company->id)
                ->where('period_type', $periodType)
                ->orderBy('calculated_at', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
        }

        return $scores;
    }

    /**
     * 企業スコアの統計情報を取得
     */
    public function getCompanyScoreStatistics(Company $company): array
    {
        $statistics = [];

        foreach (self::PERIOD_TYPES as $periodType => $days) {
            $stats = CompanyInfluenceScore::where('company_id', $company->id)
                ->where('period_type', $periodType)
                ->selectRaw('
                    AVG(total_score) as avg_score,
                    MAX(total_score) as max_score,
                    MIN(total_score) as min_score,
                    COUNT(*) as score_count
                ')
                ->first();

            $statistics[$periodType] = [
                'average_score' => round($stats->avg_score ?? 0, 2),
                'max_score' => round($stats->max_score ?? 0, 2),
                'min_score' => round($stats->min_score ?? 0, 2),
                'score_count' => $stats->score_count ?? 0,
            ];
        }

        return $statistics;
    }
}
