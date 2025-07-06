<?php

namespace App\Services;

use App\Constants\RankingPeriod;
use App\Models\Company;
use App\Models\CompanyInfluenceScore;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompanyRankingService
{

    private CompanyInfluenceScoreService $scoreService;

    public function __construct(CompanyInfluenceScoreService $scoreService)
    {
        $this->scoreService = $scoreService;
    }

    /**
     * 全期間のランキングを生成
     */
    public function generateAllRankings(?Carbon $referenceDate = null): array
    {
        $referenceDate = $referenceDate ?? now();
        $results = [];

        foreach (RankingPeriod::TYPES as $periodType => $days) {
            $results[$periodType] = $this->generateRankingForPeriod($periodType, $referenceDate);
        }

        return $results;
    }

    /**
     * 指定期間のランキングを生成
     */
    public function generateRankingForPeriod(string $periodType, ?Carbon $referenceDate = null): array
    {
        $referenceDate = $referenceDate ?? now();
        $periods = $this->calculatePeriodDates($periodType, $referenceDate);

        Log::info('Generating ranking for period', [
            'period_type' => $periodType,
            'period_start' => $periods['start']->toDateString(),
            'period_end' => $periods['end']->toDateString(),
        ]);

        // 影響力スコアを計算
        $scores = $this->scoreService->calculateAllCompaniesScore(
            $periodType,
            $periods['start'],
            $periods['end']
        );

        // ランキングを作成
        $rankings = $this->createRankings($scores, $periodType, $periods);

        // データベースに保存
        $this->saveRankings($rankings);

        Log::info('Ranking generated successfully', [
            'period_type' => $periodType,
            'companies_ranked' => count($rankings),
        ]);

        return $rankings;
    }

    /**
     * 期間の開始日と終了日を計算
     */
    private function calculatePeriodDates(string $periodType, Carbon $referenceDate): array
    {
        $days = RankingPeriod::getDays($periodType);
        $endDate = $referenceDate->copy()->endOfDay();

        if ($days === null) {
            // 全期間の場合
            $startDate = Carbon::create(2020, 1, 1)->startOfDay();
        } else {
            $startDate = $referenceDate->copy()->subDays($days)->startOfDay();
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }

    /**
     * スコアからランキングを作成
     */
    private function createRankings(array $scores, string $periodType, array $periods): array
    {
        // スコア順にソート
        usort($scores, function ($a, $b) {
            return $b->total_score <=> $a->total_score;
        });

        $rankings = [];
        $currentRank = 1;
        $previousScore = null;

        foreach ($scores as $index => $score) {
            // 同じスコアの場合は同じ順位
            if ($previousScore !== null && $score->total_score < $previousScore) {
                $currentRank = $index + 1;
            }

            $rankings[] = [
                'company_id' => $score->company_id,
                'ranking_period' => $periodType,
                'rank_position' => $currentRank,
                'total_score' => $score->total_score,
                'article_count' => $score->article_count,
                'total_bookmarks' => $score->total_bookmarks,
                'period_start' => $periods['start']->toDateString(),
                'period_end' => $periods['end']->toDateString(),
                'calculated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $previousScore = $score->total_score;
        }

        return $rankings;
    }

    /**
     * ランキングをデータベースに保存
     */
    private function saveRankings(array $rankings): void
    {
        if (empty($rankings)) {
            return;
        }

        DB::transaction(function () use ($rankings) {
            $periodType = $rankings[0]['ranking_period'];
            $periodStart = $rankings[0]['period_start'];
            $periodEnd = $rankings[0]['period_end'];

            // 既存のランキングを削除
            DB::table('company_rankings')
                ->where('ranking_period', $periodType)
                ->where('period_start', $periodStart)
                ->where('period_end', $periodEnd)
                ->delete();

            // 新しいランキングを一括挿入
            DB::table('company_rankings')->insert($rankings);
        });
    }

    /**
     * 指定期間のランキングを取得
     */
    public function getRankingForPeriod(string $periodType, int $limit = 50): array
    {
        return DB::table('company_rankings as cr')
            ->join('companies as c', 'cr.company_id', '=', 'c.id')
            ->select([
                'cr.rank_position',
                'c.name as company_name',
                'c.domain',
                'c.logo_url',
                'cr.total_score',
                'cr.article_count',
                'cr.total_bookmarks',
                'cr.period_start',
                'cr.period_end',
                'cr.calculated_at',
            ])
            ->where('cr.ranking_period', $periodType)
            ->where('c.is_active', true)
            ->orderBy('cr.rank_position')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * 企業の期間別ランキングを取得
     */
    public function getCompanyRankings(int $companyId): array
    {
        $rankings = [];

        foreach (RankingPeriod::TYPES as $periodType => $days) {
            $ranking = DB::table('company_rankings as cr')
                ->join('companies as c', 'cr.company_id', '=', 'c.id')
                ->select([
                    'cr.rank_position',
                    'cr.total_score',
                    'cr.article_count',
                    'cr.total_bookmarks',
                    'cr.period_start',
                    'cr.period_end',
                    'cr.calculated_at',
                ])
                ->where('cr.company_id', $companyId)
                ->where('cr.ranking_period', $periodType)
                ->where('c.is_active', true)
                ->orderBy('cr.calculated_at', 'desc')
                ->first();

            $rankings[$periodType] = $ranking;
        }

        return $rankings;
    }

    /**
     * トップ企業のランキング推移を取得
     */
    public function getTopCompaniesRankingHistory(int $topCount = 10, int $historyDays = 30): array
    {
        $endDate = now();
        $startDate = $endDate->copy()->subDays($historyDays);

        return DB::table('company_rankings as cr')
            ->join('companies as c', 'cr.company_id', '=', 'c.id')
            ->select([
                'c.name as company_name',
                'c.domain',
                'cr.ranking_period',
                'cr.rank_position',
                'cr.total_score',
                'cr.calculated_at',
            ])
            ->where('cr.rank_position', '<=', $topCount)
            ->whereBetween('cr.calculated_at', [$startDate, $endDate])
            ->where('c.is_active', true)
            ->orderBy('cr.calculated_at', 'desc')
            ->orderBy('cr.rank_position')
            ->get()
            ->groupBy(['company_name', 'ranking_period'])
            ->toArray();
    }

    /**
     * ランキング統計情報を取得
     */
    public function getRankingStatistics(): array
    {
        $statistics = [];

        foreach (RankingPeriod::TYPES as $periodType => $days) {
            $stats = DB::table('company_rankings')
                ->where('ranking_period', $periodType)
                ->selectRaw('
                    COUNT(*) as total_companies,
                    AVG(total_score) as avg_score,
                    MAX(total_score) as max_score,
                    MIN(total_score) as min_score,
                    SUM(article_count) as total_articles,
                    SUM(total_bookmarks) as total_bookmarks,
                    MAX(calculated_at) as last_calculated
                ')
                ->first();

            $statistics[$periodType] = [
                'total_companies' => $stats->total_companies ?? 0,
                'average_score' => round($stats->avg_score ?? 0, 2),
                'max_score' => round($stats->max_score ?? 0, 2),
                'min_score' => round($stats->min_score ?? 0, 2),
                'total_articles' => $stats->total_articles ?? 0,
                'total_bookmarks' => $stats->total_bookmarks ?? 0,
                'last_calculated' => $stats->last_calculated,
            ];
        }

        return $statistics;
    }

    /**
     * 並行処理でランキングを生成
     */
    public function generateRankingsConcurrently(?Carbon $referenceDate = null): array
    {
        $referenceDate = $referenceDate ?? now();
        $results = [];

        // 各期間タイプのジョブを並行実行
        $jobs = [];
        foreach (RankingPeriod::TYPES as $periodType => $days) {
            $jobs[] = function () use ($periodType, $referenceDate) {
                return $this->generateRankingForPeriod($periodType, $referenceDate);
            };
        }

        // 並行処理実行（Laravel Jobsを使用）
        foreach ($jobs as $index => $job) {
            $periodType = RankingPeriod::getValidPeriods()[$index];
            $results[$periodType] = $job();
        }

        return $results;
    }
}