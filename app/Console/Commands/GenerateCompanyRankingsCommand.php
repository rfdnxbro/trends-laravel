<?php

namespace App\Console\Commands;

use App\Jobs\GenerateCompanyRankingsJob;
use App\Services\CompanyRankingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateCompanyRankingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'company:generate-rankings 
                          {--period= : 特定期間のランキングを生成 (1w, 1m, 3m, 6m, 1y, 3y, all)}
                          {--date= : 基準日を指定 (YYYY-MM-DD形式)}
                          {--queue : キュー処理で実行}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '企業の期間別ランキングを生成します';

    /**
     * Execute the console command.
     */
    public function handle(CompanyRankingService $rankingService)
    {
        $periodType = $this->option('period');
        $dateOption = $this->option('date');
        $useQueue = $this->option('queue');

        // 基準日の設定
        $referenceDate = $dateOption ? Carbon::parse($dateOption) : now();

        try {
            if ($useQueue) {
                // キュー処理で実行
                $this->handleWithQueue($periodType, $referenceDate);
            } else {
                // 同期処理で実行
                $this->handleSynchronously($rankingService, $periodType, $referenceDate);
            }
        } catch (\Exception $e) {
            $this->error('ランキング生成でエラーが発生しました: ' . $e->getMessage());
            Log::error('Company ranking generation command failed', [
                'period_type' => $periodType,
                'reference_date' => $referenceDate->toDateString(),
                'error' => $e->getMessage(),
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * キュー処理でランキング生成を実行
     */
    private function handleWithQueue(?string $periodType, Carbon $referenceDate): void
    {
        if ($periodType) {
            $this->info("期間 {$periodType} のランキング生成をキューに追加しています...");
            GenerateCompanyRankingsJob::dispatch($periodType, $referenceDate);
            $this->info('キューに追加されました。');
        } else {
            $this->info('全期間のランキング生成をキューに追加しています...');
            $periods = ['1w', '1m', '3m', '6m', '1y', '3y', 'all'];
            
            foreach ($periods as $period) {
                GenerateCompanyRankingsJob::dispatch($period, $referenceDate);
            }
            
            $this->info('全期間のジョブがキューに追加されました。');
        }
    }

    /**
     * 同期処理でランキング生成を実行
     */
    private function handleSynchronously(CompanyRankingService $rankingService, ?string $periodType, Carbon $referenceDate): void
    {
        if ($periodType) {
            $this->info("期間 {$periodType} のランキングを生成中...");
            $results = $rankingService->generateRankingForPeriod($periodType, $referenceDate);
            $this->info("完了: {$periodType} 期間で " . count($results) . " 社のランキングを生成しました。");
        } else {
            $this->info('全期間のランキングを生成中...');
            $bar = $this->output->createProgressBar(7);
            $bar->start();

            $results = $rankingService->generateAllRankings($referenceDate);
            
            $totalCompanies = 0;
            foreach ($results as $period => $rankings) {
                $totalCompanies += count($rankings);
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
            $this->info("完了: 全期間で合計 {$totalCompanies} 社のランキングを生成しました。");
        }

        // 統計情報の表示
        $this->displayStatistics($rankingService);
    }

    /**
     * 統計情報を表示
     */
    private function displayStatistics(CompanyRankingService $rankingService): void
    {
        $this->newLine();
        $this->info('=== ランキング統計 ===');
        
        $statistics = $rankingService->getRankingStatistics();
        
        $headers = ['期間', '企業数', '平均スコア', '最高スコア', '最低スコア', '総記事数', '総ブックマーク数'];
        $rows = [];
        
        foreach ($statistics as $period => $stats) {
            $rows[] = [
                $period,
                number_format($stats['total_companies']),
                number_format($stats['average_score'], 2),
                number_format($stats['max_score'], 2),
                number_format($stats['min_score'], 2),
                number_format($stats['total_articles']),
                number_format($stats['total_bookmarks']),
            ];
        }
        
        $this->table($headers, $rows);
    }
}
