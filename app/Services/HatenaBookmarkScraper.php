<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Company;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class HatenaBookmarkScraper extends BaseScraper
{
    protected string $baseUrl = 'https://b.hatena.ne.jp';

    protected string $itCategoryUrl = 'https://b.hatena.ne.jp/hotentry/it';

    protected int $requestsPerMinute;

    public function __construct()
    {
        parent::__construct();
        $this->requestsPerMinute = config('constants.hatena.rate_limit_per_minute');
        $this->setHeaders([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'ja,en-US;q=0.5,en;q=0.3',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
        ]);
    }

    public function scrapePopularItEntries(): array
    {
        Log::info('はてなブックマーク人気ITエントリーのスクレイピングを開始');

        $entries = $this->scrape($this->itCategoryUrl);

        Log::info('はてなブックマークスクレイピング完了', [
            'entries_count' => count($entries),
        ]);

        return $entries;
    }

    protected function parseResponse(Response $response): array
    {
        $html = $response->body();
        $crawler = new Crawler($html);
        $entries = [];

        $crawler->filter('.entrylist-contents')->each(function (Crawler $node) use (&$entries) {
            try {
                $title = $this->extractTitle($node);
                $url = $this->extractUrl($node);
                $bookmarkCount = $this->extractBookmarkCount($node);
                $domain = $url ? $this->extractDomain($url) : '';
                $publishedAt = $this->extractPublishedAt($node);

                if ($title && $url) {
                    $entries[] = [
                        'title' => $title,
                        'url' => $url,
                        'engagement_count' => $bookmarkCount,
                        'domain' => $domain,
                        'published_at' => $publishedAt,
                        'scraped_at' => now(),
                        'platform' => 'hatena_bookmark',
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('はてなブックマークエントリーの解析中にエラー', [
                    'error' => $e->getMessage(),
                    'html' => $node->html(),
                ]);
            }
        });

        return $entries;
    }

    protected function extractTitle(Crawler $node): ?string
    {
        try {
            $titleElement = $node->filter('.entrylist-contents-title a');
            if ($titleElement->count() > 0) {
                $title = trim($titleElement->text());

                return $title !== '' ? $title : null;
            }
        } catch (\Exception $e) {
            Log::debug('タイトル抽出エラー', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function extractUrl(Crawler $node): ?string
    {
        try {
            $titleElement = $node->filter('.entrylist-contents-title a');
            if ($titleElement->count() > 0) {
                return $titleElement->attr('href');
            }
        } catch (\Exception $e) {
            Log::debug('URL抽出エラー', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function extractBookmarkCount(Crawler $node): int
    {
        try {
            $bookmarkElement = $node->filter('.entrylist-contents-users a');
            if ($bookmarkElement->count() > 0) {
                $bookmarkText = $bookmarkElement->text();

                return (int) preg_replace('/[^0-9]/', '', $bookmarkText);
            }
        } catch (\Exception $e) {
            Log::debug('ブックマーク数抽出エラー', ['error' => $e->getMessage()]);
        }

        return 0;
    }

    protected function extractDomain(string $url): string
    {
        $parsedUrl = parse_url($url);

        return $parsedUrl['host'] ?? '';
    }

    protected function extractPublishedAt(Crawler $node): ?string
    {
        try {
            // はてなブックマークの日時情報を取得
            $selectors = [
                '.entrylist-contents-date',
                '.entrylist-contents-meta time',
                'time[datetime]',
                'time',
                '[datetime]',
            ];

            foreach ($selectors as $selector) {
                $timeElement = $node->filter($selector);
                if ($timeElement->count() > 0) {
                    $datetime = $timeElement->attr('datetime') ?: $timeElement->text();
                    if ($datetime) {
                        // 相対時間（例：「2時間前」）を現在時刻ベースで変換
                        if (preg_match('/(.+)前/', $datetime, $matches)) {
                            $timeStr = $matches[1];
                            if (preg_match('/([0-9]+)時間/', $timeStr, $hourMatches)) {
                                return now()->subHours((int) $hourMatches[1])->toDateTimeString();
                            }
                            if (preg_match('/([0-9]+)分/', $timeStr, $minuteMatches)) {
                                return now()->subMinutes((int) $minuteMatches[1])->toDateTimeString();
                            }
                        }

                        return $datetime;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('投稿日時抽出エラー', ['error' => $e->getMessage()]);
        }

        return null;
    }

    public function identifyCompanyDomain(string $domain): ?Company
    {
        return Company::where('domain', $domain)->first();
    }

    public function normalizeAndSaveData(array $entries): array
    {
        $savedEntries = [];
        $hatenaPlatform = \App\Models\Platform::where('name', 'はてなブックマーク')->first();
        $companyMatcher = new CompanyMatcher;

        foreach ($entries as $entry) {
            try {
                // 拡張された会社マッチングを使用
                $articleData = array_merge($entry, [
                    'platform' => 'hatena_bookmark',
                ]);
                $company = $companyMatcher->identifyCompany($articleData);

                $article = Article::updateOrCreate(
                    ['url' => $entry['url']],
                    [
                        'title' => $entry['title'],
                        'platform_id' => $hatenaPlatform?->id,
                        'company_id' => $company?->id,
                        'domain' => $entry['domain'],
                        'engagement_count' => $entry['engagement_count'],
                        'published_at' => $entry['published_at'] ?? null,
                        'platform' => $entry['platform'],
                        'scraped_at' => $entry['scraped_at'],
                    ]
                );

                $savedEntries[] = $article;

                Log::debug('記事データを保存', [
                    'title' => $entry['title'],
                    'company' => $company?->name,
                    'engagement_count' => $entry['engagement_count'],
                ]);

            } catch (\Exception $e) {
                Log::error('記事データ保存エラー', [
                    'entry' => $entry,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('データ正規化・保存完了', [
            'total_entries' => count($entries),
            'saved_entries' => count($savedEntries),
        ]);

        return $savedEntries;
    }
}
