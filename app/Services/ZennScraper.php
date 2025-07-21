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

            // DOM構造から直接各要素を抽出（より効率的で正確）
            $authorName = $this->extractAuthorNameDirect($node);
            $companyName = $this->extractCompanyNameDirect($node);
            $engagementCount = $this->extractLikesCount($node);

            // 後方互換性のため、従来の統合テキストも保持
            $author = $authorName ? $authorName : $this->extractAuthor($node);

            $articleData = [
                'title' => $title,
                'url' => $url,
                'engagement_count' => $engagementCount,
                'author' => $author,
                'author_name' => $authorName,
                'organization_name' => $companyName,
                'author_url' => $this->extractAuthorUrl($node),
                'published_at' => $this->extractPublishedAt($node),
                'scraped_at' => now(),
                'platform' => 'zenn',
                // 将来的に企業マッチングで使用する情報
                'company_name_hint' => $companyName,
            ];

            Log::debug('Zenn 抽出結果', [
                'title' => $title,
                'url' => $url,
                'engagement_count' => $articleData['engagement_count'],
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
        // 最優先: 具体的なCSSクラス名から直接抽出
        $directSelectors = [
            '.ArticleList_like',  // いいね数
        ];

        $likesCount = $this->extractNumberBySelectors($node, $directSelectors);
        if ($likesCount > 0) {
            return $likesCount;
        }

        // 設定ベースのセレクタ戦略をフォールバックとして使用
        $likesCount = $this->extractByStrategies($node, 'engagement', 'number', [
            'min_value' => 0,
        ]);

        if ($likesCount !== null) {
            return $likesCount;
        }

        // 最終フォールバック: 既存のセレクタも試行
        $fallbackSelectors = [
            '.View_likeCount',
            'button[aria-label*="いいね"]',
        ];

        return $this->extractNumberBySelectors($node, $fallbackSelectors);
    }

    protected function extractAuthor(Crawler $node): ?string
    {
        try {
            // 最優先: 具体的なCSSクラス名から直接抽出
            $directSelectors = [
                '.ArticleList_userName',  // ユーザー名
                '.ArticleList_publicationLink',  // 企業名（含む可能性）
            ];

            foreach ($directSelectors as $selector) {
                $result = $this->extractAuthorFromElement($node, $selector);
                if ($result) {
                    return $result;
                }
            }

            // 従来の抽出方法をフォールバックとして使用
            $extractionMethods = [
                // リンクベースの著者抽出
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

            // 最終フォールバック
            return $this->extractAuthorFromFallbackSelectors($node);
        } catch (\Exception $e) {
            Log::debug('著者名抽出エラー', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * DOM構造から著者名を直接抽出
     *
     * @param  Crawler  $node  記事ノード
     * @return string|null 著者名またはnull
     */
    private function extractAuthorNameDirect(Crawler $node): ?string
    {
        try {
            // Zennの記事リスト固有のクラスから著者名を抽出
            $userNameSelectors = [
                '.ArticleList_userName',
                '[class*="userName"]',
                '[data-testid="author-name"]',
            ];

            foreach ($userNameSelectors as $selector) {
                $element = $node->filter($selector);
                if ($element->count() > 0) {
                    $text = trim($element->text());
                    if (! empty($text)) {
                        return $text;
                    }
                }
            }

            // フォールバック: 画像のalt属性から抽出
            $imgElements = $node->filter('img');
            if ($imgElements->count() > 0) {
                $foundAuthor = null;
                $imgElements->each(function (Crawler $imgNode) use (&$foundAuthor) {
                    if ($foundAuthor) {
                        return false;
                    } // 既に見つかった場合は処理終了

                    $alt = $imgNode->attr('alt');
                    if (! empty($alt) && ! str_contains($alt, 'logo') && ! str_contains($alt, 'icon')) {
                        $foundAuthor = $alt;

                        return false; // 処理終了
                    }
                });

                if ($foundAuthor) {
                    return $foundAuthor;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::debug('著者名の直接抽出エラー', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * DOM構造から企業名を直接抽出
     *
     * @param  Crawler  $node  記事ノード
     * @return string|null 企業名またはnull
     */
    private function extractCompanyNameDirect(Crawler $node): ?string
    {
        try {
            // Zennの企業情報固有のクラスから企業名を抽出
            $companySelectors = [
                '.ArticleList_publicationLink',
                '[class*="publication"]',
                '[data-testid="company-name"]',
            ];

            foreach ($companySelectors as $selector) {
                $element = $node->filter($selector);
                if ($element->count() > 0) {
                    $text = trim($element->text());
                    if (! empty($text)) {
                        return $text;
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::debug('企業名の直接抽出エラー', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * authorから詳細情報を抽出（ユーザー名、企業名、いいね数、投稿日）
     *
     * @deprecated 新しいDOM直接抽出メソッドを使用してください
     *
     * @param  string|null  $author  著者情報
     * @return array 抽出された情報の配列
     */
    private function parseAuthorInfo(?string $author): array
    {
        if (! $author) {
            return [
                'author_name' => null,
                'company_name' => null,
                'engagement_count' => 0,
                'relative_date' => null,
            ];
        }

        // authorがURLの場合は、URLからユーザー名を抽出
        if (strpos($author, '/') !== false) {
            $pathParts = explode('/', trim($author, '/'));

            return [
                'author_name' => end($pathParts),
                'company_name' => null,
                'engagement_count' => 0,
                'relative_date' => null,
            ];
        }

        $text = trim($author);
        $result = [
            'author_name' => null,
            'company_name' => null,
            'engagement_count' => 0,
            'relative_date' => null,
        ];

        // ステップ1: 末尾の数字（エンゲージメント数）を抽出
        if (preg_match('/^(.+?)\s+(\d+)$/', $text, $matches)) {
            $text = trim($matches[1]);
            $result['engagement_count'] = (int) $matches[2];
        }

        // ステップ2: 日時表現を抽出
        if (preg_match('/^(.+?)(\d+(?:日|時間|分|秒|週間|月|年)前)/', $text, $matches)) {
            $text = trim($matches[1]);
            $result['relative_date'] = $matches[2];
        }

        // ステップ3: 企業名を抽出
        // パターン1: 「in企業名」形式（意図的なin区切りのみ）
        if (preg_match('/^([a-zA-Z0-9_]+)\s+in\s+(.+)$/u', $text, $matches) ||
            preg_match('/^([a-zA-Z0-9_]+)\s*in([^a-zA-Z0-9].+)$/u', $text, $matches)) {
            $result['author_name'] = trim($matches[1]);
            $result['company_name'] = trim($matches[2]);
        }
        // パターン2: 直接企業名が含まれる場合
        elseif (preg_match('/^([a-zA-Z0-9_]+)[^a-zA-Z0-9_]*?(株式会社|有限会社|合同会社|合資会社|合名会社)/u', $text, $matches)) {
            $result['author_name'] = trim($matches[1]);
            // 企業名は残りの部分から抽出
            $companyPart = substr($text, strlen($matches[1]));
            $result['company_name'] = trim($companyPart);
        }
        // パターン3: 英語企業名
        elseif (preg_match('/^([a-zA-Z0-9_]+)(.+?(?:Corporation|Inc|Corp|Ltd|Company|Group|Holdings).*)$/u', $text, $matches)) {
            $result['author_name'] = trim($matches[1]);
            $result['company_name'] = trim($matches[2]);
        }
        // パターン4: 企業名なし
        else {
            // 複数単語がある場合は最初の単語のみを使用（Zennのユーザー名形式を想定）
            $result['author_name'] = explode(' ', $text)[0];
        }

        return $result;
    }

    /**
     * authorからuser_nameを抽出（後方互換性のため残す）
     *
     * @param  string|null  $author  著者情報
     * @return string|null ユーザー名またはnull
     */
    private function extractAuthorName(?string $author): ?string
    {
        $info = $this->parseAuthorInfo($author);

        return $info['author_name'];
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
                // extractAuthorNameで既に処理済みのauthor_nameを使用、なければauthorから抽出
                $authorName = $article['author_name'] ?? $this->extractAuthorName($article['author'] ?? null);

                // organization_nameを取得（extractCompanyNameDirectで既に処理済み）
                $organizationName = $article['organization_name'] ?? null;

                // 拡張された会社マッチングを使用
                $articleData = array_merge($article, [
                    'author_name' => $authorName,
                    'organization_name' => $organizationName,
                    'platform' => 'zenn',
                ]);
                $company = $companyMatcher->identifyCompany($articleData);

                $savedArticle = Article::updateOrCreate(
                    ['url' => $article['url']],
                    [
                        'title' => $article['title'],
                        'platform_id' => $zennPlatform?->id,
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

                Log::debug('Zenn記事データを保存', [
                    'title' => $article['title'],
                    'author' => $article['author'],
                    'company' => $company?->name,
                    'engagement_count' => $article['engagement_count'],
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
