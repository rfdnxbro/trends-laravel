<?php

namespace App\Jobs;

use App\Services\CompanyRankingService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateCompanyRankingsJob implements ShouldQueue
{
    use Queueable;

    private ?string $periodType;

    private ?Carbon $referenceDate;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $periodType = null, ?Carbon $referenceDate = null)
    {
        $this->periodType = $periodType;
        $this->referenceDate = $referenceDate;
    }

    /**
     * Execute the job.
     */
    public function handle(CompanyRankingService $rankingService): void
    {
        $referenceDate = $this->referenceDate ?? now();

        try {
            if ($this->periodType) {
                // 特定期間のランキングを生成
                Log::info('Starting company ranking generation for period', [
                    'period_type' => $this->periodType,
                    'reference_date' => $referenceDate->toDateString(),
                ]);

                $results = $rankingService->generateRankingForPeriod($this->periodType, $referenceDate);

                Log::info('Company ranking generation completed for period', [
                    'period_type' => $this->periodType,
                    'companies_ranked' => count($results),
                ]);
            } else {
                // 全期間のランキングを生成
                Log::info('Starting company ranking generation for all periods', [
                    'reference_date' => $referenceDate->toDateString(),
                ]);

                $results = $rankingService->generateAllRankings($referenceDate);

                $totalCompanies = array_sum(array_map('count', $results));
                Log::info('Company ranking generation completed for all periods', [
                    'total_companies_ranked' => $totalCompanies,
                    'periods_processed' => count($results),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Company ranking generation failed', [
                'period_type' => $this->periodType,
                'reference_date' => $referenceDate->toDateString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Company ranking generation job failed', [
            'period_type' => $this->periodType,
            'reference_date' => $this->referenceDate?->toDateString(),
            'error' => $exception->getMessage(),
        ]);
    }
}
