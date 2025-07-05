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

    protected string $trendUrl = 'https://qiita.com/trend';

    protected int $requestsPerMinute = 20;

    public function __construct()
    {
        parent::__construct();
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

        $crawler->filter('article[data-hyperapp-app="Trend"]')->each(function (Crawler $node) use (&$articles) {
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

        return $articles;
    }

    protected function extractTitle(Crawler $node): ?string
    {
        try {
            $titleElement = $node->filter('h2 a');
            if ($titleElement->count() > 0) {
                return trim($titleElement->text());
            }
        } catch (\Exception $e) {
            Log::debug('タイトル抽出エラー', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function extractUrl(Crawler $node): ?string
    {
        try {
            $linkElement = $node->filter('h2 a');
            if ($linkElement->count() > 0) {
                $href = $linkElement->attr('href');

                return $href ? $this->baseUrl.$href : null;
            }
        } catch (\Exception $e) {
            Log::debug('URL抽出エラー', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function extractLikesCount(Crawler $node): int
    {
        try {
            $likesElement = $node->filter('[data-testid="like-count"]');
            if ($likesElement->count() > 0) {
                $likesText = $likesElement->text();

                return (int) preg_replace('/[^0-9]/', '', $likesText);
            }
        } catch (\Exception $e) {
            Log::debug('いいね数抽出エラー', ['error' => $e->getMessage()]);
        }

        return 0;
    }

    protected function extractAuthor(Crawler $node): ?string
    {
        try {
            $authorElement = $node->filter('[data-hyperapp-app="UserIcon"] a');
            if ($authorElement->count() > 0) {
                return trim($authorElement->attr('href') ?? '');
            }
        } catch (\Exception $e) {
            Log::debug('著者名抽出エラー', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function extractAuthorUrl(Crawler $node): ?string
    {
        try {
            $authorElement = $node->filter('[data-hyperapp-app="UserIcon"] a');
            if ($authorElement->count() > 0) {
                $href = $authorElement->attr('href');

                return $href ? $this->baseUrl.$href : null;
            }
        } catch (\Exception $e) {
            Log::debug('著者URL抽出エラー', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function extractPublishedAt(Crawler $node): ?string
    {
        try {
            $timeElement = $node->filter('time');
            if ($timeElement->count() > 0) {
                return $timeElement->attr('datetime');
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

        foreach ($articles as $article) {
            try {
                $company = $this->identifyCompanyAccount($article['author_url']);

                $savedArticle = Article::updateOrCreate(
                    ['url' => $article['url']],
                    [
                        'title' => $article['title'],
                        'company_id' => $company?->id,
                        'likes_count' => $article['likes_count'],
                        'author' => $article['author'],
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
