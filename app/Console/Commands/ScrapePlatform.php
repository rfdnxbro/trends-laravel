<?php

namespace App\Console\Commands;

use App\Services\HatenaBookmarkScraper;
use App\Services\QiitaScraper;
use App\Services\ZennScraper;
use Illuminate\Console\Command;

class ScrapePlatform extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:platform {platform : スクレイピングするプラットフォーム (qiita, zenn, hatena)} {--dry-run : データを保存せずに取得のみ行う}';

    protected $description = '指定されたプラットフォームのトレンド記事をスクレイピングします';

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
        $platform = strtolower($this->argument('platform'));

        if (! isset($this->platformMap[$platform])) {
            $this->error("無効なプラットフォームです: {$platform}");
            $this->line('利用可能なプラットフォーム: '.implode(', ', array_keys($this->platformMap)));

            return Command::FAILURE;
        }

        $platformConfig = $this->platformMap[$platform];
        $platformName = $platformConfig['name'];
        $scraperClass = $platformConfig['class'];

        $this->info("{$platformName}のスクレイピングを開始します...");

        try {
            $scraper = new $scraperClass;

            // プラットフォーム別のメソッド呼び出し
            if ($platformName === 'はてなブックマーク') {
                $articles = $scraper->scrapePopularItEntries();
            } else {
                $articles = $scraper->scrapeTrendingArticles();
            }

            $this->info('取得した記事数: '.count($articles));

            if ($this->option('dry-run')) {
                $this->warn('--dry-run オプションが指定されているため、データは保存されません');

                // 取得データの表示
                foreach ($articles as $index => $article) {
                    $this->line('【'.($index + 1).'】');
                    $this->line('タイトル: '.$article['title']);
                    $this->line('URL: '.$article['url']);

                    // プラットフォーム別の表示
                    if ($platformName === 'はてなブックマーク') {
                        $this->line('ブックマーク数: '.($article['bookmark_count'] ?? 0));
                        $this->line('ドメイン: '.($article['domain'] ?? 'N/A'));
                    } else {
                        $this->line('いいね数: '.($article['likes_count'] ?? 0));
                        $this->line('投稿者: '.($article['author'] ?? 'N/A'));
                        $this->line('投稿日時: '.($article['published_at'] ?? 'N/A'));
                    }

                    $this->line('---');
                }
            } else {
                // データ保存
                $savedArticles = $scraper->normalizeAndSaveData($articles);
                $this->info('保存した記事数: '.count($savedArticles));

                // 保存結果の表示
                foreach ($savedArticles as $article) {
                    $companyName = $article->company ? $article->company->name : 'その他';

                    if ($platformName === 'はてなブックマーク') {
                        $count = $article->bookmark_count ?? 0;
                        $this->line("保存: {$article->title} ({$companyName}) - {$count}ブックマーク");
                    } else {
                        $count = $article->likes_count ?? 0;
                        $this->line("保存: {$article->title} ({$companyName}) - {$count}いいね");
                    }
                }
            }

            $this->info("{$platformName}のスクレイピングが完了しました！");

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
