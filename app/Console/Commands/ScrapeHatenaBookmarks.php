<?php

namespace App\Console\Commands;

use App\Services\HatenaBookmarkScraper;
use Illuminate\Console\Command;

class ScrapeHatenaBookmarks extends Command
{
    protected $signature = 'scrape:hatena {--dry-run : データを保存せずに取得のみ行う}';

    protected $description = 'はてなブックマーク人気ITエントリーをスクレイピングします';

    public function handle()
    {
        $this->info('はてなブックマークスクレイピングを開始します...');
        
        $scraper = new HatenaBookmarkScraper();
        
        try {
            // スクレイピング実行
            $entries = $scraper->scrapePopularItEntries();
            
            $this->info("取得したエントリー数: " . count($entries));
            
            if ($this->option('dry-run')) {
                $this->warn('--dry-run オプションが指定されているため、データは保存されません');
                
                // 取得データの表示
                foreach ($entries as $index => $entry) {
                    $this->line("【" . ($index + 1) . "】");
                    $this->line("タイトル: " . $entry['title']);
                    $this->line("URL: " . $entry['url']);
                    $this->line("ブックマーク数: " . $entry['bookmark_count']);
                    $this->line("ドメイン: " . $entry['domain']);
                    $this->line("---");
                }
            } else {
                // データ保存
                $savedEntries = $scraper->normalizeAndSaveData($entries);
                $this->info("保存したエントリー数: " . count($savedEntries));
                
                // 保存結果の表示
                foreach ($savedEntries as $article) {
                    $companyName = $article->company ? $article->company->name : 'その他';
                    $this->line("保存: {$article->title} ({$companyName}) - {$article->bookmark_count}ブックマーク");
                }
            }
            
            $this->info('スクレイピング完了しました！');
            
        } catch (\Exception $e) {
            $this->error('エラーが発生しました: ' . $e->getMessage());
            
            // エラーログの表示
            $errorLog = $scraper->getErrorLog();
            if (!empty($errorLog)) {
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