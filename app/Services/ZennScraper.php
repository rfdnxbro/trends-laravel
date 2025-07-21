<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Company;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class ZennScraper extends BaseScraper
{
    protected string $baseUrl = 'https://zenn.dev';

    protected string $trendUrl = 'https://zenn.dev';

    protected int $requestsPerMinute;

    public function __construct()
    {
        parent::__construct();
        $this->requestsPerMinute = config('constants.zenn.rate_limit_per_minute');
        $this->setHeaders([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'ja,en-US;q=0.5,en;q=0.3',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
        ]);
    }

    public function scrapeTrendingArticles(): array
    {
        Log::info('Zennトレンド記事のスクレイピングを開始');

        $articles = $this->scrape($this->trendUrl);

        Log::info('Zennスクレイピング完了', [
            'articles_count' => count($articles),
        ]);

        return $articles;
    }

    /**
     * レスポンスからZenn記事データを解析
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
        Log::debug('Zenn HTML preview', [
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
        // 新しい設定ベースのセレクタ戦略を使用
        $selectors = $this->getSelectorsFromConfig('article_container');

        foreach ($selectors as $categoryName => $categorySelectors) {
            Log::debug("Testing article container category: {$categoryName}");

            foreach ($categorySelectors as $selector) {
                Log::debug("Testing selector: {$selector}");
                $elements = $crawler->filter($selector);
                Log::debug("Found {$elements->count()} elements with selector: {$selector}");

                if ($elements->count() > 0) {
                    return $elements;
                }
            }
        }

        // フォールバック: 既存の特殊セレクタも試行
        $fallbackSelectors = [
            '[class*="ArticleList_item"]',
            '[class*="ArticleListItem"]',
            'a[href*="/articles/"]',
            '.View_container',
            'div[class*="View"]',
        ];

        foreach ($fallbackSelectors as $selector) {
            Log::debug("Testing fallback selector: {$selector}");
            $elements = $crawler->filter($selector);
            Log::debug("Found {$elements->count()} elements with fallback selector: {$selector}");

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
        $articleCount = 0;

        $elements->each(function (Crawler $node) use (&$articles, &$articleCount) {
            if ($articleCount >= 16) {
                return false;
            }

            $articleData = $this->extractSingleArticleData($node);
            if ($articleData) {
                $articles[] = $articleData;
                $articleCount++;
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
            Log::debug('Zenn 記事ノードHTML', [
                'html' => substr($node->html(), 0, 500),
            ]);

            $title = $this->extractTitle($node);
            $url = $this->extractUrl($node);

            if (! $title || ! $url) {
                return null;
            }

            $author = $this->extractAuthor($node);
            $authorName = $this->extractAuthorName($author);

            $articleData = [
                'title' => $title,
                'url' => $url,
                'likes_count' => $this->extractLikesCount($node),
                'author' => $author,
                'author_name' => $authorName,
                'author_url' => $this->extractAuthorUrl($node),
                'published_at' => $this->extractPublishedAt($node),
                'scraped_at' => now(),
                'platform' => 'zenn',
            ];

            Log::debug('Zenn 抽出結果', [
                'title' => $title,
                'url' => $url,
                'likes_count' => $articleData['likes_count'],
                'author' => $articleData['author'],
            ]);

            return $articleData;
        } catch (\Exception $e) {
            Log::warning('Zenn記事の解析中にエラー', [
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
        // 新しい設定ベースのセレクタ戦略を使用
        $title = $this->extractByStrategies($node, 'title', 'text', [
            'max_length' => 500,
            'min_length' => 5,
        ]);

        if ($title) {
            return $title;
        }

        // フォールバック: 既存のセレクタも試行
        $fallbackSelectors = [
            '.View_title',
            '[class*="Title"]',
            'p',
        ];

        return $this->extractTextBySelectors($node, $fallbackSelectors, 'タイトル');
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
            'path_pattern' => '/articles/',
            'exclude_patterns' => ['#', 'mailto:', 'tel:'],
        ]);

        if ($url) {
            return $url;
        }

        // フォールバック: 既存のセレクタも試行
        $fallbackSelectors = [
            'a',
        ];

        return $this->extractLinkBySelectors($node, $fallbackSelectors, '/articles/', $this->baseUrl);
    }

    /**
     * ノードからいいね数を抽出
     *
     * @param  Crawler  $node  記事ノード
     * @return int いいね数
     */
    protected function extractLikesCount(Crawler $node): int
    {
        // 新しい設定ベースのセレクタ戦略を使用
        $likesCount = $this->extractByStrategies($node, 'engagement', 'number', [
            'min_value' => 0,
        ]);

        if ($likesCount !== null) {
            return $likesCount;
        }

        // フォールバック: 既存のセレクタも試行
        $fallbackSelectors = [
            '.View_likeCount',
            'button[aria-label*="いいね"]',
        ];

        return $this->extractNumberBySelectors($node, $fallbackSelectors);
    }

    protected function extractAuthor(Crawler $node): ?string
    {
        try {
            // 各種抽出方法を試行
            $extractionMethods = [
                // リンクベースの著者抽出を優先（Zennのテストで期待されている形式）
                ['type' => 'link', 'options' => ['base_url' => '', 'exclude_patterns' => ['/articles/']]],
                // テキストベースの著者抽出
                ['type' => 'text', 'options' => ['max_length' => 50, 'min_length' => 1]],
                // 属性ベースの著者抽出（画像のalt属性など）
                ['type' => 'attribute', 'options' => ['attribute_name' => 'alt']],
            ];

            foreach ($extractionMethods as $method) {
                $result = $this->extractByStrategies($node, 'author', $method['type'], $method['options']);
                if ($result) {
                    return $result;
                }
            }

            // フォールバック: 既存の特殊セレクタも試行
            return $this->extractAuthorFromFallbackSelectors($node);
        } catch (\Exception $e) {
            Log::debug('著者名抽出エラー', ['error' => $e->getMessage()]);

            return null;
        }
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

        // authorがURLの場合は、URLからユーザー名を抽出
        if (strpos($author, '/') !== false) {
            // URLパスからユーザー名を抽出（最後のセグメント）
            $pathParts = explode('/', trim($author, '/'));

            return end($pathParts);
        }

        // テキストの場合、日時や数字部分を除去
        // 「Gota2日前 196」→「Gota」のような変換
        $cleanAuthor = trim($author);

        // 「in 企業名」のような形式から企業名の前の部分を抽出
        if (preg_match('/^(.+?)(?:in\s+.+)?$/u', $cleanAuthor, $matches)) {
            $cleanAuthor = trim($matches[1]);
        }

        // 「n日前」「n時間前」などの日時表現を除去
        $cleanAuthor = preg_replace('/\d+[日時間分秒週月年]前/', '', $cleanAuthor);

        // 末尾の数字を除去
        $cleanAuthor = preg_replace('/\s+\d+$/', '', $cleanAuthor);

        // 数字が混在している場合、最初の英数字部分のみを抽出
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9_-]*?)(?:\d+[日時間分秒週月年]|$)/u', $cleanAuthor, $matches)) {
            $cleanAuthor = $matches[1];
        }

        return ! empty($cleanAuthor) ? $cleanAuthor : null;
    }

    /**
     * フォールバックセレクタから著者情報を抽出
     */
    private function extractAuthorFromFallbackSelectors(Crawler $node): ?string
    {
        $fallbackSelectors = [
            '.View_author',
            '[class*="userName"]',  // CSS Modules対応
            '[class*="ArticleList_userName"]',
        ];

        foreach ($fallbackSelectors as $selector) {
            $result = $this->extractAuthorFromElement($node, $selector);
            if ($result) {
                return $result;
            }
        }

        return null;
    }

    /**
     * 要素から著者情報を抽出
     */
    private function extractAuthorFromElement(Crawler $node, string $selector): ?string
    {
        $authorElement = $node->filter($selector);
        if ($authorElement->count() === 0) {
            return null;
        }

        // hrefがある場合（リンク要素）
        $href = $authorElement->attr('href');
        if ($href && strpos($href, '/articles/') === false) {
            return trim($href);
        }

        // alt属性がある場合（画像要素）
        $alt = $authorElement->attr('alt');
        if ($alt && ! empty(trim($alt))) {
            return trim($alt);
        }

        // テキストコンテンツがある場合
        $text = trim($authorElement->text());
        if (! empty($text) && strlen($text) < 50) {
            return $text;
        }

        return null;
    }

    protected function extractAuthorUrl(Crawler $node): ?string
    {
        try {
            $author = $this->extractAuthor($node);
            if ($author) {
                // authorが既にURLの場合はそのまま返す
                if (strpos($author, 'http') === 0) {
                    return $author;
                }

                // authorがパス形式の場合はベースURLと結合
                if (strpos($author, '/') !== false) {
                    return $this->baseUrl.$author;
                }

                // テキストの場合はクリーンなユーザー名を使ってURL構築
                $authorName = $this->extractAuthorName($author);
                if ($authorName) {
                    return $this->baseUrl.'/'.$authorName;
                }
            }
        } catch (\Exception $e) {
            Log::debug('著者URL抽出エラー', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function extractPublishedAt(Crawler $node): ?string
    {
        try {
            // datetime属性を優先的に抽出
            $datetime = $this->extractByStrategies($node, 'datetime', 'attribute', [
                'attribute_name' => 'datetime',
            ]);

            if ($datetime) {
                return $datetime;
            }

            // title属性からも抽出を試行
            $titleAttr = $this->extractByStrategies($node, 'datetime', 'attribute', [
                'attribute_name' => 'title',
            ]);

            if ($titleAttr) {
                return $titleAttr;
            }

            // テキストベースでも抽出を試行
            $datetimeText = $this->extractByStrategies($node, 'datetime', 'text', [
                'max_length' => 50,
                'min_length' => 5,
            ]);

            if ($datetimeText) {
                return $datetimeText;
            }

            // フォールバック: 既存の特殊セレクタも試行
            $fallbackSelectors = [
                '.View_date',
                '[data-testid="published-date"]',
            ];

            foreach ($fallbackSelectors as $selector) {
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
        // @記号を削除してユーザー名のみを取得
        $username = ltrim($username, '@');

        return Company::where('zenn_username', $username)->first();
    }

    public function normalizeAndSaveData(array $articles): array
    {
        $savedArticles = [];
        $zennPlatform = \App\Models\Platform::where('name', 'Zenn')->first();
        $companyMatcher = new CompanyMatcher;

        foreach ($articles as $article) {
            try {
                // author_nameを抽出（authorから会社名を除いたユーザー名を取得）
                $authorName = null;
                if ($article['author']) {
                    $authorText = trim($article['author']);
                    // 「in株式会社」や「in」で区切ってユーザー名を抽出
                    if (preg_match('/(.+?)(?:in.+)/u', $authorText, $matches)) {
                        $authorName = trim($matches[1]);
                    } else {
                        $authorName = $authorText;
                    }
                }

                // 拡張された会社マッチングを使用
                $articleData = array_merge($article, [
                    'author_name' => $authorName,
                    'platform' => 'zenn',
                ]);
                $company = $companyMatcher->identifyCompany($articleData);

                $savedArticle = Article::updateOrCreate(
                    ['url' => $article['url']],
                    [
                        'title' => $article['title'],
                        'platform_id' => $zennPlatform?->id,
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

                Log::debug('Zenn記事データを保存', [
                    'title' => $article['title'],
                    'author' => $article['author'],
                    'company' => $company?->name,
                    'likes_count' => $article['likes_count'],
                ]);

            } catch (\Exception $e) {
                Log::error('Zenn記事データ保存エラー', [
                    'article' => $article,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Zennデータ正規化・保存完了', [
            'total_articles' => count($articles),
            'saved_articles' => count($savedArticles),
        ]);

        return $savedArticles;
    }
}
