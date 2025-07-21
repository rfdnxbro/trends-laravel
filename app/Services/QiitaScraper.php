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

    /**
     * レスポンスからQiita記事データを解析
     *
     * @param  Response  $response  HTTPレスポンス
     * @return array 記事データ配列
     */
    protected function parseResponse(Response $response): array
    {
        $html = $response->body();
        $crawler = new Crawler($html);

        $this->logHtmlPreview($html);

        $elements = $this->findArticleElements($crawler);
        if (! $elements) {
            return [];
        }

        return $this->extractArticlesFromElements($elements);
    }

    /**
     * HTMLプレビューをログ出力
     *
     * @param  string  $html  HTML文字列
     */
    private function logHtmlPreview(string $html): void
    {
        Log::debug('Qiita HTML preview', [
            'html_length' => strlen($html),
            'html_preview' => substr($html, 0, 1000),
        ]);
    }

    /**
     * 記事要素を検索して取得
     *
     * @param  Crawler  $crawler  DOMクローラー
     * @return Crawler|null 見つかった記事要素
     */
    private function findArticleElements(Crawler $crawler): ?Crawler
    {
        $selectors = [
            'article',
            '.style-1uma8mh',
            '.style-1w7apwp',
            '[class*="ArticleCard"]',
            '[data-testid*="article"]',
            'div[class*="style-"]',
        ];

        foreach ($selectors as $selector) {
            Log::debug("Testing selector: {$selector}");
            $elements = $crawler->filter($selector);
            Log::debug("Found {$elements->count()} elements with selector: {$selector}");

            if ($elements->count() > 0) {
                return $elements;
            }
        }

        return null;
    }

    /**
     * 要素から記事データを抽出
     *
     * @param  Crawler  $elements  記事要素
     * @return array 記事データ配列
     */
    private function extractArticlesFromElements(Crawler $elements): array
    {
        $articles = [];

        $elements->each(function (Crawler $node) use (&$articles) {
            $articleData = $this->extractSingleArticleData($node);
            if ($articleData) {
                $articles[] = $articleData;
            }
        });

        return $articles;
    }

    /**
     * 単一記事ノードからデータを抽出
     *
     * @param  Crawler  $node  記事ノード
     * @return array|null 記事データまたはnull
     */
    private function extractSingleArticleData(Crawler $node): ?array
    {
        try {
            $title = $this->extractTitle($node);
            $url = $this->extractUrl($node);

            if (! $title || ! $url) {
                return null;
            }

            $author = $this->extractAuthor($node);
            $authorName = $this->extractAuthorName($author);
            $organizationName = $this->extractOrganizationNameDirect($node);

            return [
                'title' => $title,
                'url' => $url,
                'engagement_count' => $this->extractLikesCount($node),
                'author' => $author,
                'author_name' => $authorName,
                'organization_name' => $organizationName,
                'author_url' => $this->extractAuthorUrl($node),
                'published_at' => $this->extractPublishedAt($node),
                'scraped_at' => now(),
                'platform' => 'qiita',
            ];
        } catch (\Exception $e) {
            Log::warning('Qiita記事の解析中にエラー', [
                'error' => $e->getMessage(),
                'html' => $node->html(),
            ]);

            return null;
        }
    }

    /**
     * ノードからタイトルを抽出
     *
     * @param  Crawler  $node  記事ノード
     * @return string|null タイトルまたはnull
     */
    protected function extractTitle(Crawler $node): ?string
    {
        $selectors = [
            'h2 a',
            'h1 a',
            'a[href*="/items/"]',
            '.style-2vm86z',
            '[class*="title"]',
        ];

        return $this->extractTextBySelectors($node, $selectors, 'タイトル');
    }

    /**
     * ノードから記事URLを抽出
     *
     * @param  Crawler  $node  記事ノード
     * @return string|null 記事URLまたはnull
     */
    protected function extractUrl(Crawler $node): ?string
    {
        // 新しい設定ベースのセレクタ戦略を使用
        $url = $this->extractByStrategies($node, 'url', 'link', [
            'base_url' => $this->baseUrl,
            'path_pattern' => '/items/',
            'exclude_patterns' => ['#', 'mailto:', 'tel:'],
        ]);

        if ($url) {
            return $url;
        }

        // フォールバック: 既存のセレクタも試行
        $fallbackSelectors = [
            'a',
        ];

        return $this->extractLinkBySelectors($node, $fallbackSelectors, '/items/', $this->baseUrl);
    }

    /**
     * ノードからLGTM数を抽出
     *
     * @param  Crawler  $node  記事ノード
     * @return int LGTM数
     */
    protected function extractLikesCount(Crawler $node): int
    {
        // 優先セレクタ: footer内の数字要素（実際のHTML構造に基づく）
        $prioritySelectors = [
            'footer div[class*="style-"]',  // footer内のstyle-クラスを持つdiv
            'footer div',  // footer内の全てのdiv
            'footer span',  // footer内のspan
        ];

        // 優先セレクタでいいね数を検索
        foreach ($prioritySelectors as $selector) {
            $elements = $node->filter($selector);
            if ($elements->count() > 0) {
                foreach ($elements as $element) {
                    $text = trim($element->textContent);
                    if (preg_match('/^\d+$/', $text) && strlen($text) <= 4) {
                        return (int) $text;
                    }
                }
            }
        }

        // フォールバック: 従来のセレクタ
        $fallbackSelectors = [
            '[data-testid="like-count"]',
            '[aria-label*="LGTM"]',
            '[aria-label*="いいね"]',
            'span[aria-label]',
        ];

        return $this->extractNumberBySelectors($node, $fallbackSelectors);
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
                            // スラッシュを除去してユーザー名を返す
                            return ltrim(trim($href), '/');
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('著者名抽出エラー', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * authorからuser_nameを抽出
     *
     * @param  string|null  $author  著者情報
     * @return string|null ユーザー名またはnull
     */
    private function extractAuthorName(?string $author): ?string
    {
        if (! $author) {
            return null;
        }

        // スラッシュと@記号を除去してユーザー名を取得
        return ltrim(trim($author), '/@');
    }

    /**
     * DOM構造から組織名を直接抽出
     *
     * @param  Crawler  $node  記事ノード
     * @return string|null 組織名またはnull
     */
    private function extractOrganizationNameDirect(Crawler $node): ?string
    {
        try {
            // Qiitaの組織記事の場合、組織名セレクタを試行
            $organizationSelectors = [
                '.organizationCard_name',
                '[data-testid="organization-name"]',
                '.organization-name',
                '.OrganizationCard_name',
                '.u-organization-name',
            ];

            foreach ($organizationSelectors as $selector) {
                $element = $node->filter($selector);
                if ($element->count() > 0) {
                    $text = trim($element->text());
                    if ($text) {
                        Log::debug("Qiita組織名抽出成功: {$selector} -> {$text}");

                        return $text;
                    }
                }
            }

            Log::debug('Qiita組織名要素が見つかりませんでした');

            return null;
        } catch (\Exception $e) {
            Log::debug('Qiita組織名抽出エラー', ['error' => $e->getMessage()]);

            return null;
        }
    }

    protected function extractAuthorUrl(Crawler $node): ?string
    {
        try {
            // extractAuthorと同じロジックを使用
            $author = $this->extractAuthor($node);
            if ($author) {
                // authorは既にクリーンなユーザー名なので、URLを構築
                return $this->baseUrl.'/'.$author;
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

                // organization_nameを取得（extractOrganizationNameDirectで既に処理済み）
                $organizationName = $article['organization_name'] ?? null;

                // 拡張された会社マッチングを使用
                $articleData = array_merge($article, [
                    'author_name' => $authorName,
                    'organization_name' => $organizationName,
                    'platform' => 'qiita',
                ]);
                $company = $companyMatcher->identifyCompany($articleData);

                $savedArticle = Article::updateOrCreate(
                    ['url' => $article['url']],
                    [
                        'title' => $article['title'],
                        'platform_id' => $qiitaPlatform?->id,
                        'company_id' => $company?->id,
                        'engagement_count' => $article['engagement_count'],
                        'author' => $article['author'],
                        'author_name' => $authorName,
                        'organization_name' => $organizationName,
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
                    'engagement_count' => $article['engagement_count'],
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
