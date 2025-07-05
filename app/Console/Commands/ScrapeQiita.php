<?php

namespace App\Console\Commands;

use App\Services\QiitaScraper;
use Illuminate\Console\Command;

class ScrapeQiita extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:qiita {--dry-run : データを保存せずに取得のみ行う}';

    protected $description = 'Qiitaトレンド記事をスクレイピングします';

    public function handle()
    {
        $this->info('Qiitaスクレイピングを開始します...');

        $scraper = new QiitaScraper;

        try {
            // スクレイピング実行
            $articles = $scraper->scrapeTrendingArticles();

            $this->info('取得した記事数: '.count($articles));

            if ($this->option('dry-run')) {
                $this->warn('--dry-run オプションが指定されているため、データは保存されません');

                // 取得データの表示
                foreach ($articles as $index => $article) {
                    $this->line('【'.($index + 1).'】');
                    $this->line('タイトル: '.$article['title']);
                    $this->line('URL: '.$article['url']);
                    $this->line('いいね数: '.$article['likes_count']);
                    $this->line('投稿者: '.($article['author'] ?? 'N/A'));
                    $this->line('投稿日時: '.($article['published_at'] ?? 'N/A'));
                    $this->line('---');
                }
            } else {
                // データ保存
                $savedArticles = $scraper->normalizeAndSaveData($articles);
                $this->info('保存した記事数: '.count($savedArticles));

                // 保存結果の表示
                foreach ($savedArticles as $article) {
                    $companyName = $article->company ? $article->company->name : 'その他';
                    $this->line("保存: {$article->title} ({$companyName}) - {$article->likes_count}いいね");
                }
            }

            $this->info('スクレイピング完了しました！');

        } catch (\Exception $e) {
            $this->error('エラーが発生しました: '.$e->getMessage());

            // エラーログの表示
            $errorLog = $scraper->getErrorLog();
            if (! empty($errorLog)) {
                $this->warn('エラーログ:');
                foreach ($errorLog as $error) {
                    $this->line("- {$error['error']} (試行: {$error['attempt']})");
                }
            }

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
