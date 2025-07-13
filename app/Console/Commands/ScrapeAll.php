<?php

namespace App\Console\Commands;

use App\Services\HatenaBookmarkScraper;
use App\Services\QiitaScraper;
use App\Services\ZennScraper;
use Illuminate\Console\Command;

class ScrapeAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:all {--dry-run : データを保存せずに取得のみ行う}';

    protected $description = '全プラットフォーム（Qiita、Zenn、はてなブックマーク）のトレンド記事をスクレイピングします';

    public function handle()
    {
        $this->info('全プラットフォームのスクレイピングを開始します...');

        $platforms = [
            'Qiita' => QiitaScraper::class,
            'Zenn' => ZennScraper::class,
            'はてなブックマーク' => HatenaBookmarkScraper::class,
        ];

        $progressBar = $this->output->createProgressBar(count($platforms));
        $progressBar->start();

        $totalArticles = 0;
        $totalSaved = 0;
        $errors = [];

        foreach ($platforms as $platformName => $scraperClass) {
            $this->line("\n{$platformName}のスクレイピングを開始...");

            try {
                $scraper = app($scraperClass);

                // プラットフォーム別のメソッド呼び出し
                if ($platformName === 'はてなブックマーク') {
                    $articles = $scraper->scrapePopularItEntries();
                } else {
                    $articles = $scraper->scrapeTrendingArticles();
                }

                // Collectionを配列に変換（テスト用モックに対応）
                if ($articles instanceof \Illuminate\Support\Collection) {
                    $articles = $articles->toArray();
                }

                $this->info("{$platformName}: 取得した記事数: ".count($articles));
                $totalArticles += count($articles);

                if ($this->option('dry-run')) {
                    $scraper->normalizeAndSaveData($articles, true);
                    $this->warn('--dry-run オプションが指定されているため、データは保存されません');
                } else {
                    $savedArticles = $scraper->normalizeAndSaveData($articles, false);
                    $savedCount = is_array($savedArticles) ? count($savedArticles) : $savedArticles;
                    $this->info("{$platformName}: 保存した記事数: {$savedCount}");
                    $totalSaved += $savedCount;
                }

                $this->info("{$platformName}: 完了");

            } catch (\Exception $e) {
                $this->error("{$platformName}: エラーが発生しました: ".$e->getMessage());
                $errors[] = [
                    'platform' => $platformName,
                    'error' => $e->getMessage(),
                ];

                // エラーログの表示
                if (isset($scraper)) {
                    $errorLog = $scraper->getErrorLog();
                    if (! empty($errorLog)) {
                        $this->warn("{$platformName} エラーログ:");
                        foreach ($errorLog as $error) {
                            $this->line("- {$error['error']} (試行: {$error['attempt']})");
                        }
                    }
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        // 結果サマリーの表示
        $this->line("\n\n=== スクレイピング結果サマリー ===");
        $this->info("総取得記事数: {$totalArticles}");

        if (! $this->option('dry-run')) {
            $this->info("総保存記事数: {$totalSaved}");
        }

        if (! empty($errors)) {
            $this->warn('エラーが発生したプラットフォーム:');
            foreach ($errors as $error) {
                $this->line("- {$error['platform']}: {$error['error']}");
            }
        }

        $this->info('全プラットフォームのスクレイピングが完了しました！');

        return empty($errors) ? Command::SUCCESS : Command::FAILURE;
    }
}
