<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompanyRankingHistoryService
{
    /**
     * 履歴データ保持期間（日数）
     */
    private const HISTORY_RETENTION_DAYS = 365;

    /**
     * 順位変動を計算して履歴を記録
     */
    public function recordRankingHistory(string $periodType, Carbon $calculatedAt): array
    {
        Log::info('Recording ranking history', [
            'period_type' => $periodType,
            'calculated_at' => $calculatedAt->toDateTimeString(),
        ]);

        $changes = [];

        // 現在のランキングを取得
        $currentRankings = $this->getCurrentRankings($periodType, $calculatedAt);

        // 前回のランキングを取得
        $previousRankings = $this->getPreviousRankings($periodType, $calculatedAt);

        // 順位変動を計算
        foreach ($currentRankings as $current) {
            $companyId = $current->company_id;
            $currentRank = $current->rank_position;

            // 前回の順位を取得
            $previousRank = null;
            foreach ($previousRankings as $previous) {
                if ($previous->company_id === $companyId) {
                    $previousRank = $previous->rank_position;
                    break;
                }
            }

            // 順位変動を計算
            $change = $this->calculateRankingChange($currentRank, $previousRank);

            if ($change !== null) {
                $changes[] = [
                    'company_id' => $companyId,
                    'period_type' => $periodType,
                    'current_rank' => $currentRank,
                    'previous_rank' => $previousRank,
                    'rank_change' => $change,
                    'calculated_at' => $calculatedAt,
                ];
            }
        }

        // 履歴データをデータベースに保存
        $this->saveRankingHistory($changes);

        Log::info('Ranking history recorded', [
            'period_type' => $periodType,
            'changes_count' => count($changes),
        ]);

        return $changes;
    }

    /**
     * 現在のランキングを取得
     */
    private function getCurrentRankings(string $periodType, Carbon $calculatedAt): array
    {
        return DB::table('company_rankings')
            ->where('ranking_period', $periodType)
            ->where('calculated_at', $calculatedAt)
            ->orderBy('rank_position')
            ->get()
            ->toArray();
    }

    /**
     * 前回のランキングを取得
     */
    private function getPreviousRankings(string $periodType, Carbon $calculatedAt): array
    {
        // 現在の計算時刻より前の最新のランキングを取得
        $previousCalculatedAt = DB::table('company_rankings')
            ->where('ranking_period', $periodType)
            ->where('calculated_at', '<', $calculatedAt)
            ->max('calculated_at');

        if (! $previousCalculatedAt) {
            return [];
        }

        return DB::table('company_rankings')
            ->where('ranking_period', $periodType)
            ->where('calculated_at', $previousCalculatedAt)
            ->orderBy('rank_position')
            ->get()
            ->toArray();
    }

    /**
     * 順位変動を計算
     */
    private function calculateRankingChange(?int $currentRank, ?int $previousRank): ?int
    {
        if ($previousRank === null) {
            // 初回ランクイン
            return null;
        }

        if ($currentRank === null) {
            // ランク圏外
            return null;
        }

        // 順位変動を計算（上昇は負の値、下降は正の値）
        return $previousRank - $currentRank;
    }

    /**
     * 履歴データをデータベースに保存
     */
    private function saveRankingHistory(array $changes): void
    {
        if (empty($changes)) {
            return;
        }

        DB::transaction(function () use ($changes) {
            foreach ($changes as $change) {
                DB::table('company_ranking_history')->updateOrInsert([
                    'company_id' => $change['company_id'],
                    'period_type' => $change['period_type'],
                    'calculated_at' => $change['calculated_at'],
                ], [
                    'current_rank' => $change['current_rank'],
                    'previous_rank' => $change['previous_rank'],
                    'rank_change' => $change['rank_change'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    /**
     * 企業の順位変動履歴を取得
     */
    public function getCompanyRankingHistory(int $companyId, string $periodType, int $days = 30): array
    {
        $endDate = now();
        $startDate = $endDate->copy()->subDays($days);

        return DB::table('company_ranking_history as crh')
            ->join('companies as c', 'crh.company_id', '=', 'c.id')
            ->select([
                'crh.current_rank',
                'crh.previous_rank',
                'crh.rank_change',
                'crh.calculated_at',
                'c.name as company_name',
            ])
            ->where('crh.company_id', $companyId)
            ->where('crh.period_type', $periodType)
            ->whereBetween('crh.calculated_at', [$startDate, $endDate])
            ->orderBy('crh.calculated_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * 最大の順位上昇企業を取得
     */
    public function getTopRankingRisers(string $periodType, int $limit = 10): array
    {
        $latestCalculatedAt = DB::table('company_ranking_history')
            ->where('period_type', $periodType)
            ->max('calculated_at');

        if (! $latestCalculatedAt) {
            return [];
        }

        return DB::table('company_ranking_history as crh')
            ->join('companies as c', 'crh.company_id', '=', 'c.id')
            ->select([
                'c.name as company_name',
                'c.domain',
                'crh.current_rank',
                'crh.previous_rank',
                'crh.rank_change',
                'crh.calculated_at',
            ])
            ->where('crh.period_type', $periodType)
            ->where('crh.calculated_at', $latestCalculatedAt)
            ->where('crh.rank_change', '>', 0)
            ->where('c.is_active', true)
            ->orderBy('crh.rank_change', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * 最大の順位下降企業を取得
     */
    public function getTopRankingFallers(string $periodType, int $limit = 10): array
    {
        $latestCalculatedAt = DB::table('company_ranking_history')
            ->where('period_type', $periodType)
            ->max('calculated_at');

        if (! $latestCalculatedAt) {
            return [];
        }

        return DB::table('company_ranking_history as crh')
            ->join('companies as c', 'crh.company_id', '=', 'c.id')
            ->select([
                'c.name as company_name',
                'c.domain',
                'crh.current_rank',
                'crh.previous_rank',
                'crh.rank_change',
                'crh.calculated_at',
            ])
            ->where('crh.period_type', $periodType)
            ->where('crh.calculated_at', $latestCalculatedAt)
            ->where('crh.rank_change', '<', 0)
            ->where('c.is_active', true)
            ->orderBy('crh.rank_change', 'asc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * 順位変動統計を取得
     */
    public function getRankingChangeStatistics(string $periodType): array
    {
        $latestCalculatedAt = DB::table('company_ranking_history')
            ->where('period_type', $periodType)
            ->max('calculated_at');

        if (! $latestCalculatedAt) {
            return [];
        }

        $stats = DB::table('company_ranking_history')
            ->where('period_type', $periodType)
            ->where('calculated_at', $latestCalculatedAt)
            ->selectRaw('
                COUNT(*) as total_companies,
                COUNT(CASE WHEN rank_change > 0 THEN 1 END) as rising_companies,
                COUNT(CASE WHEN rank_change < 0 THEN 1 END) as falling_companies,
                COUNT(CASE WHEN rank_change = 0 THEN 1 END) as unchanged_companies,
                AVG(rank_change) as avg_change,
                MAX(rank_change) as max_rise,
                MIN(rank_change) as max_fall
            ')
            ->first();

        return [
            'total_companies' => $stats->total_companies ?? 0,
            'rising_companies' => $stats->rising_companies ?? 0,
            'falling_companies' => $stats->falling_companies ?? 0,
            'unchanged_companies' => $stats->unchanged_companies ?? 0,
            'average_change' => round($stats->avg_change ?? 0, 2),
            'max_rise' => $stats->max_rise ?? 0,
            'max_fall' => abs($stats->max_fall ?? 0),
            'calculated_at' => $latestCalculatedAt,
        ];
    }

    /**
     * 古い履歴データを削除
     */
    public function cleanupOldHistory(): int
    {
        $cutoffDate = now()->subDays(self::HISTORY_RETENTION_DAYS);

        $deletedCount = DB::table('company_ranking_history')
            ->where('calculated_at', '<', $cutoffDate)
            ->delete();

        Log::info('Old ranking history cleaned up', [
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate->toDateString(),
        ]);

        return $deletedCount;
    }

    /**
     * 履歴データのストレージ使用量を取得
     */
    public function getHistoryStorageStats(): array
    {
        $stats = DB::table('company_ranking_history')
            ->selectRaw('
                COUNT(*) as total_records,
                COUNT(DISTINCT company_id) as unique_companies,
                COUNT(DISTINCT period_type) as period_types,
                MIN(calculated_at) as oldest_record,
                MAX(calculated_at) as newest_record
            ')
            ->first();

        return [
            'total_records' => $stats->total_records ?? 0,
            'unique_companies' => $stats->unique_companies ?? 0,
            'period_types' => $stats->period_types ?? 0,
            'oldest_record' => $stats->oldest_record,
            'newest_record' => $stats->newest_record,
            'retention_days' => self::HISTORY_RETENTION_DAYS,
        ];
    }
}
