<?php

namespace App\Jobs;

use App\Services\CompanyInfluenceScoreService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateCompanyInfluenceScoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Job実行タイムアウト（秒）
     */
    public int $timeout = 300;

    /**
     * 最大試行回数
     */
    public int $tries = 3;

    /**
     * 基準日付
     */
    private ?Carbon $referenceDate;

    /**
     * 処理対象期間タイプ
     */
    private ?string $periodType;

    /**
     * コンストラクタ
     */
    public function __construct(?Carbon $referenceDate = null, ?string $periodType = null)
    {
        $this->referenceDate = $referenceDate;
        $this->periodType = $periodType;
    }

    /**
     * Jobを実行
     */
    public function handle(CompanyInfluenceScoreService $scoreService): void
    {
        $startTime = microtime(true);

        try {
            Log::info('Company influence scores calculation job started', [
                'reference_date' => $this->referenceDate?->toDateString(),
                'period_type' => $this->periodType,
            ]);

            if ($this->periodType) {
                // 特定の期間タイプのみ処理
                $this->calculateSpecificPeriod($scoreService);
            } else {
                // 全期間タイプを処理
                $this->calculateAllPeriods($scoreService);
            }

            $executionTime = microtime(true) - $startTime;

            Log::info('Company influence scores calculation job completed', [
                'execution_time' => round($executionTime, 2),
                'reference_date' => $this->referenceDate?->toDateString(),
                'period_type' => $this->periodType,
            ]);

        } catch (\Exception $e) {
            $executionTime = microtime(true) - $startTime;

            Log::error('Company influence scores calculation job failed', [
                'error' => $e->getMessage(),
                'execution_time' => round($executionTime, 2),
                'reference_date' => $this->referenceDate?->toDateString(),
                'period_type' => $this->periodType,
            ]);

            throw $e;
        }
    }

    /**
     * 特定の期間タイプのスコアを計算
     */
    private function calculateSpecificPeriod(CompanyInfluenceScoreService $scoreService): void
    {
        $referenceDate = $this->referenceDate ?? now();
        $periodDays = $this->getPeriodDays($this->periodType);

        $periodEnd = $referenceDate->copy();
        $periodStart = $referenceDate->copy()->subDays($periodDays);

        $scores = $scoreService->calculateAllCompaniesScore(
            $this->periodType,
            $periodStart,
            $periodEnd
        );

        Log::info('Specific period calculation completed', [
            'period_type' => $this->periodType,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'companies_processed' => count($scores),
        ]);
    }

    /**
     * 全期間タイプのスコアを計算
     */
    private function calculateAllPeriods(CompanyInfluenceScoreService $scoreService): void
    {
        $results = $scoreService->calculateScoresByPeriod($this->referenceDate);

        $totalCompanies = 0;
        foreach ($results as $periodType => $scores) {
            $totalCompanies += count($scores);
        }

        Log::info('All periods calculation completed', [
            'periods_calculated' => count($results),
            'total_companies_processed' => $totalCompanies,
        ]);
    }

    /**
     * 期間タイプに対応する日数を取得
     */
    private function getPeriodDays(string $periodType): int
    {
        return match ($periodType) {
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            default => throw new \InvalidArgumentException("Invalid period type: {$periodType}"),
        };
    }

    /**
     * Job失敗時の処理
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Company influence scores calculation job failed permanently', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'reference_date' => $this->referenceDate?->toDateString(),
            'period_type' => $this->periodType,
        ]);
    }

    /**
     * 一意のJob識別子を生成
     */
    public function uniqueId(): string
    {
        $dateStr = $this->referenceDate ? $this->referenceDate->toDateString() : 'now';
        $periodStr = $this->periodType ?? 'all';

        return "company_influence_scores_{$dateStr}_{$periodStr}";
    }
}