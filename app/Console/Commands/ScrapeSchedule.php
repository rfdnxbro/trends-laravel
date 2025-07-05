<?php

namespace App\Console\Commands;

use App\Services\HatenaBookmarkScraper;
use App\Services\QiitaScraper;
use App\Services\ZennScraper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScrapeSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:schedule {--platform= : 特定のプラットフォームのみ実行 (qiita, zenn, hatena)} {--silent : 詳細出力を抑制}';

    protected $description = '定期実行用のスクレイピングコマンド（cron job に最適化）';

    private $platformMap = [
        'qiita' => [
            'name' => 'Qiita',
            'class' => QiitaScraper::class,
        ],
        'zenn' => [
            'name' => 'Zenn',
            'class' => ZennScraper::class,
        ],
        'hatena' => [
            'name' => 'はてなブックマーク',
            'class' => HatenaBookmarkScraper::class,
        ],
    ];

    public function handle()
    {
        $startTime = microtime(true);
        $isSilent = $this->option('silent');
        $specificPlatform = $this->option('platform');

        if (! $isSilent) {
            $this->info('定期スクレイピングを開始します...');
        }

        // 実行するプラットフォームを決定
        $platforms = $this->platformMap;
        if ($specificPlatform) {
            $specificPlatform = strtolower($specificPlatform);
            if (! isset($this->platformMap[$specificPlatform])) {
                $this->error("無効なプラットフォームです: {$specificPlatform}");
                Log::error('ScrapeSchedule: 無効なプラットフォーム指定', ['platform' => $specificPlatform]);

                return Command::FAILURE;
            }
            $platforms = [$specificPlatform => $this->platformMap[$specificPlatform]];
        }

        $totalArticles = 0;
        $totalSaved = 0;
        $errors = [];

        foreach ($platforms as $platformKey => $platformConfig) {
            $platformName = $platformConfig['name'];
            $scraperClass = $platformConfig['class'];

            if (! $isSilent) {
                $this->line("{$platformName}のスクレイピングを実行中...");
            }

            try {
                $scraper = new $scraperClass;

                // プラットフォーム別のメソッド呼び出し
                if ($platformName === 'はてなブックマーク') {
                    $articles = $scraper->scrapePopularItEntries();
                } else {
                    $articles = $scraper->scrapeTrendingArticles();
                }
                $totalArticles += count($articles);

                $savedArticles = $scraper->normalizeAndSaveData($articles);
                $savedCount = count($savedArticles);
                $totalSaved += $savedCount;

                if (! $isSilent) {
                    $this->info("{$platformName}: 取得:".count($articles)." 保存:{$savedCount}");
                }

                // 成功ログ
                Log::info("ScrapeSchedule: {$platformName} 完了", [
                    'platform' => $platformKey,
                    'articles_fetched' => count($articles),
                    'articles_saved' => $savedCount,
                ]);

            } catch (\Exception $e) {
                $errorMessage = "{$platformName}: {$e->getMessage()}";
                $errors[] = $errorMessage;

                if (! $isSilent) {
                    $this->error($errorMessage);
                }

                // エラーログ
                Log::error("ScrapeSchedule: {$platformName} エラー", [
                    'platform' => $platformKey,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // 詳細エラーログ
                if (isset($scraper)) {
                    $errorLog = $scraper->getErrorLog();
                    if (! empty($errorLog)) {
                        Log::error("ScrapeSchedule: {$platformName} 詳細エラー", [
                            'platform' => $platformKey,
                            'errors' => $errorLog,
                        ]);
                    }
                }
            }
        }

        $executionTime = round(microtime(true) - $startTime, 2);

        // 実行結果のログ
        Log::info('ScrapeSchedule: 実行完了', [
            'total_articles_fetched' => $totalArticles,
            'total_articles_saved' => $totalSaved,
            'execution_time_seconds' => $executionTime,
            'error_count' => count($errors),
            'platforms_executed' => array_keys($platforms),
        ]);

        if (! $isSilent) {
            $this->info("定期スクレイピング完了: 取得:{$totalArticles} 保存:{$totalSaved} 実行時間:{$executionTime}秒");
        }

        // エラーがあった場合は警告ログ
        if (! empty($errors)) {
            Log::warning('ScrapeSchedule: エラーが発生しました', [
                'errors' => $errors,
            ]);

            if (! $isSilent) {
                $this->warn('一部のプラットフォームでエラーが発生しました:');
                foreach ($errors as $error) {
                    $this->line("- {$error}");
                }
            }
        }

        return empty($errors) ? Command::SUCCESS : Command::FAILURE;
    }
}
