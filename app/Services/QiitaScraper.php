<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Company;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class QiitaScraper extends BaseScraper
{
    protected string $baseUrl = 'https://qiita.com';

    protected string $trendUrl = 'https://qiita.com';

    protected int $requestsPerMinute;

    public function __construct()
    {
        parent::__construct();
        $this->requestsPerMinute = config('constants.qiita.rate_limit_per_minute');
        $this->setHeaders([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'ja,en-US;q=0.5,en;q=0.3',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
        ]);
    }

    public function scrapeTrendingArticles(): array
    {
        Log::info('Qiitaトレンド記事のスクレイピングを開始');

        $articles = $this->scrape($this->trendUrl);

        Log::info('Qiitaスクレイピング完了', [
            'articles_count' => count($articles),
        ]);

        return $articles;
    }

    protected function parseResponse(Response $response): array
    {
        $html = $response->body();
        $crawler = new Crawler($html);
        $articles = [];

        // デバッグ: HTMLの一部をログに出力
        Log::debug('Qiita HTML preview', [
            'html_length' => strlen($html),
            'html_preview' => substr($html, 0, 1000),
        ]);

        // 複数のセレクタパターンを試す
        $selectors = [
            'article',  // 汎用的なarticleタグ
            '.style-1uma8mh',  // 新しいスタイルクラス
            '.style-1w7apwp',  // 別のスタイルクラス
            '[class*="ArticleCard"]',  // ArticleCardを含むクラス
            '[data-testid*="article"]',  // data-testid属性
            'div[class*="style-"]',  // スタイルクラスを持つdiv
        ];

        foreach ($selectors as $selector) {
            Log::debug("Testing selector: {$selector}");
            $elements = $crawler->filter($selector);
            Log::debug("Found {$elements->count()} elements with selector: {$selector}");

            if ($elements->count() > 0) {
                $elements->each(function (Crawler $node) use (&$articles) {
                    try {
                        $title = $this->extractTitle($node);
                        $url = $this->extractUrl($node);
                        $likesCount = $this->extractLikesCount($node);
                        $author = $this->extractAuthor($node);
                        $authorUrl = $this->extractAuthorUrl($node);
                        $publishedAt = $this->extractPublishedAt($node);

                        if ($title && $url) {
                            $articles[] = [
                                'title' => $title,
                                'url' => $url,
                                'likes_count' => $likesCount,
                                'author' => $author,
                                'author_url' => $authorUrl,
                                'published_at' => $publishedAt,
                                'scraped_at' => now(),
                                'platform' => 'qiita',
                            ];
                        }
                    } catch (\Exception $e) {
                        Log::warning('Qiita記事の解析中にエラー', [
                            'error' => $e->getMessage(),
                            'html' => $node->html(),
                        ]);
                    }
                });
                break; // 最初に見つかったセレクタを使用
            }
        }

        return $articles;
    }

    protected function extractTitle(Crawler $node): ?string
    {
        try {
            // 複数のセレクタパターンを試す
            $selectors = [
                'h2 a',
                'h1 a',
                'a[href*="/items/"]',
                '.style-2vm86z',
                '[class*="title"]',
            ];

            foreach ($selectors as $selector) {
                $titleElement = $node->filter($selector);
                if ($titleElement->count() > 0) {
                    $title = trim($titleElement->text());
                    if (! empty($title)) {
                        return $title;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('タイトル抽出エラー', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function extractUrl(Crawler $node): ?string
    {
        try {
            // 複数のセレクタパターンを試す
            $selectors = [
                'h2 a',
                'h1 a',
                'a[href*="/items/"]',
                'a',
            ];

            foreach ($selectors as $selector) {
                $linkElement = $node->filter($selector);
                if ($linkElement->count() > 0) {
                    $href = $linkElement->attr('href');
                    if ($href && strpos($href, '/items/') !== false) {
                        return strpos($href, 'http') === 0 ? $href : $this->baseUrl.$href;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('URL抽出エラー', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function extractLikesCount(Crawler $node): int
    {
        try {
            // 複数のセレクタパターンを試す
            $selectors = [
                '[data-testid="like-count"]',
                '[aria-label*="LGTM"]',
                '[aria-label*="いいね"]',
                '.style-*[aria-label*="LGTM"]',
                'span[aria-label]',
            ];

            foreach ($selectors as $selector) {
                $likesElement = $node->filter($selector);
                if ($likesElement->count() > 0) {
                    $likesText = $likesElement->attr('aria-label') ?: $likesElement->text();
                    if ($likesText) {
                        $number = (int) preg_replace('/[^0-9]/', '', $likesText);
                        if ($number > 0) {
                            return $number;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('いいね数抽出エラー', ['error' => $e->getMessage()]);
        }

        return 0;
    }

    protected function extractAuthor(Crawler $node): ?string
    {
        try {
            // 複数のセレクタパターンを試す
            $selectors = [
                'a[href*="/@"]',
                '[data-hyperapp-app="UserIcon"] a',
                '.style-j198x4',
                '.style-y87z4f',
                '.style-i9qys6',
                'a[href^="/"]',
            ];

            foreach ($selectors as $selector) {
                $authorElement = $node->filter($selector);
                if ($authorElement->count() > 0) {
                    $href = $authorElement->attr('href');
                    if ($href) {
                        // 記事URLでない場合はユーザーURLとみなす
                        if (strpos($href, '/items/') === false) {
                            return trim($href);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('著者名抽出エラー', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function extractAuthorUrl(Crawler $node): ?string
    {
        try {
            // extractAuthorと同じロジックを使用
            $author = $this->extractAuthor($node);
            if ($author) {
                return strpos($author, 'http') === 0 ? $author : $this->baseUrl.$author;
            }
        } catch (\Exception $e) {
            Log::debug('著者URL抽出エラー', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function extractPublishedAt(Crawler $node): ?string
    {
        try {
            // 複数のセレクタパターンを試す
            $selectors = [
                'time[datetime]',
                'time',
                '[datetime]',
                '.style-*[title]',
            ];

            foreach ($selectors as $selector) {
                $timeElement = $node->filter($selector);
                if ($timeElement->count() > 0) {
                    $datetime = $timeElement->attr('datetime') ?: $timeElement->attr('title');
                    if ($datetime) {
                        return $datetime;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('投稿日時抽出エラー', ['error' => $e->getMessage()]);
        }

        return null;
    }

    public function identifyCompanyAccount(?string $authorUrl): ?Company
    {
        if (! $authorUrl) {
            return null;
        }

        $username = basename(parse_url($authorUrl, PHP_URL_PATH));

        return Company::where('qiita_username', $username)->first();
    }

    public function normalizeAndSaveData(array $articles): array
    {
        $savedArticles = [];
        $qiitaPlatform = \App\Models\Platform::where('name', 'Qiita')->first();
        $companyMatcher = new CompanyMatcher;

        foreach ($articles as $article) {
            try {
                // author_nameを抽出（authorから@記号を削除）
                $authorName = null;
                if ($article['author']) {
                    $authorName = ltrim(trim($article['author']), '/@');
                }

                // 拡張された会社マッチングを使用
                $articleData = array_merge($article, [
                    'author_name' => $authorName,
                    'platform' => 'qiita',
                ]);
                $company = $companyMatcher->identifyCompany($articleData);

                $savedArticle = Article::updateOrCreate(
                    ['url' => $article['url']],
                    [
                        'title' => $article['title'],
                        'platform_id' => $qiitaPlatform?->id,
                        'company_id' => $company?->id,
                        'likes_count' => $article['likes_count'],
                        'author' => $article['author'],
                        'author_name' => $authorName,
                        'author_url' => $article['author_url'],
                        'published_at' => $article['published_at'],
                        'platform' => $article['platform'],
                        'scraped_at' => $article['scraped_at'],
                    ]
                );

                $savedArticles[] = $savedArticle;

                Log::debug('Qiita記事データを保存', [
                    'title' => $article['title'],
                    'author' => $article['author'],
                    'company' => $company?->name,
                    'likes_count' => $article['likes_count'],
                ]);

            } catch (\Exception $e) {
                Log::error('Qiita記事データ保存エラー', [
                    'article' => $article,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Qiitaデータ正規化・保存完了', [
            'total_articles' => count($articles),
            'saved_articles' => count($savedArticles),
        ]);

        return $savedArticles;
    }
}
