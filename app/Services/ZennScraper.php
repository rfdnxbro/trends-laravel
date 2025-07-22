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

            // 個人記事の場合はorganization情報をnullにする
            $isPersonalArticle = $this->isPersonalArticle($url, $companyName);
            $organizationName = $isPersonalArticle ? null : $companyName;
            $organizationSlug = $isPersonalArticle ? null : $this->extractOrganizationDirect($node, $authorName);
            $organizationUrl = $isPersonalArticle ? null : $this->extractOrganizationUrlFromZenn($node, $companyName);

            $articleData = [
                'title' => $title,
                'url' => $url,
                'engagement_count' => $engagementCount,
                'author' => $author,
                'author_name' => $authorName,
                'organization' => $organizationSlug,
                'organization_name' => $organizationName,
                'organization_url' => $organizationUrl,
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
        // 最優先: 実際のZennのいいね数セレクタを使用
        $directSelectors = [
            '.ArticleList_like__7aNZE',  // 実際のZennのいいね数クラス
            'span.ArticleList_like__7aNZE',  // スパン要素として
            '[class*="ArticleList_like"]',  // 部分一致
        ];

        $likesCount = $this->extractNumberBySelectors($node, $directSelectors);
        if ($likesCount > 0) {
            return $likesCount;
        }

        // 汎用的なセレクタも試行（共通セレクタを使用）
        $genericSelectors = $this->combineSelectors([
            '.ArticleList_like',  // 元の汎用セレクタ
            'svg[aria-label*="いいね"] + *',  // SVGアイコンの次の要素
        ], 'generic_aria');

        $likesCount = $this->extractNumberBySelectors($node, $genericSelectors);
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
            // 著者名を正確に抽出（組織名を除外）
            $directAuthorSelectors = [
                '.ArticleList_userName__MlDD5 a:not(.ArticleList_publicationLink__RvZTZ)', // publicationLinkクラスを除外
                '.ArticleList_userName__MlDD5 > a:first-child', // 直接の子要素の最初のaタグ
                '.ArticleList_userName a:not([class*="publication"])', // publication関連クラスを除外
            ];

            foreach ($directAuthorSelectors as $selector) {
                $element = $node->filter($selector);
                if ($element->count() > 0) {
                    $text = trim($element->first()->text());
                    if (! empty($text)) {
                        Log::debug("Zenn著者名抽出成功: {$selector} -> {$text}");

                        return $text;
                    }
                }
            }

            // フォールバック: 旧来のセレクタ（しかし組織名リンクを除外）
            $userNameSelectors = $this->combineSelectors([
                '.ArticleList_userName',
                '[class*="userName"]',
            ], 'generic_testid');

            foreach ($userNameSelectors as $selector) {
                $element = $node->filter($selector);
                if ($element->count() > 0) {
                    // 組織名リンクを除外して著者名のみを抽出
                    $authorLinks = $element->filter('a:not(.ArticleList_publicationLink__RvZTZ):not([class*="publication"])');
                    if ($authorLinks->count() > 0) {
                        $text = trim($authorLinks->first()->text());
                        if (! empty($text)) {
                            Log::debug("Zenn著者名抽出（フォールバック）: {$selector} -> {$text}");

                            return $text;
                        }
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
            // Publication専用のセレクタ（個人ユーザーを除外）
            $publicationSelectors = [
                '.ArticleList_publicationLink__RvZTZ',   // 具体的なPublication CSSクラス
                '.ArticleList_publicationLink',          // Publication表示名
            ];

            foreach ($publicationSelectors as $selector) {
                $element = $node->filter($selector);
                if ($element->count() > 0) {
                    $href = $element->attr('href');
                    $text = trim($element->text());
                    
                    // Publication URLパターンをチェック（/p/organization形式）
                    if ($href && preg_match('/\/p\/([^\/]+)/', $href) && !empty($text)) {
                        Log::debug("Zenn Publication抽出成功: {$text} (URL: {$href})");
                        return $text;
                    }
                }
            }

            // フォールバック: URL内にpublicationパターンがあるリンクを探す
            $publicationLinks = $node->filter('a[href*="/p/"]');
            if ($publicationLinks->count() > 0) {
                $publicationLinks->each(function (Crawler $link) {
                    $href = $link->attr('href');
                    $text = trim($link->text());
                    
                    if (preg_match('/\/p\/([^\/]+)/', $href) && !empty($text)) {
                        Log::debug("Zenn Publication フォールバック抽出: {$text} (URL: {$href})");
                        return $text;
                    }
                });
            }

            return null;
        } catch (\Exception $e) {
            Log::debug('企業名の直接抽出エラー', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * DOM構造から組織スラグを直接抽出
     *
     * @param  Crawler  $node  記事ノード
     * @param  string|null  $authorName  著者名
     * @return string|null 組織スラグまたはnull
     */
    private function extractOrganizationDirect(Crawler $node, ?string $authorName): ?string
    {
        try {
            // ZennのURLから組織情報を抽出
            $url = $this->extractUrl($node);
            if ($url) {
                $organizationSlug = $this->extractOrganizationSlugFromUrl($url);
                if ($organizationSlug && $organizationSlug !== $authorName) {
                    Log::debug("Zenn組織スラグ抽出成功: {$organizationSlug}");

                    return $organizationSlug;
                }
            }

            // Publication専用のセレクタからスラグを抽出
            $publicationSelectors = [
                '.ArticleList_publicationLink__RvZTZ',
                '.ArticleList_publicationLink',
            ];
            
            foreach ($publicationSelectors as $selector) {
                $publicationLink = $node->filter($selector);
                if ($publicationLink->count() > 0) {
                    $href = $publicationLink->attr('href');
                    if ($href && preg_match('/\/p\/([^\/]+)/', $href, $matches)) {
                        $slug = $matches[1];
                        // 個人ユーザー（@付き）でないPublicationのみを対象
                        if ($slug && $slug !== $authorName && !str_starts_with($slug, '@')) {
                            Log::debug("Zenn組織スラグ（Publication）抽出成功: {$slug}");
                            return $slug;
                        }
                    }
                }
            }

            // authorから「in 企業名」形式を抽出してスラグ化
            $author = $this->extractAuthor($node);
            if ($author) {
                $organizationFromAuthor = $this->extractOrganizationFromAuthorText($author, $authorName);
                if ($organizationFromAuthor) {
                    $slug = $this->generateSlug($organizationFromAuthor);
                    Log::debug("Zenn組織スラグ（著者テキスト）抽出成功: {$slug}");

                    return $slug;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::debug('Zenn組織スラグ抽出エラー', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * ZennのURLから組織スラグを抽出
     *
     * @param  string  $url  記事URL
     * @return string|null 組織スラグまたはnull
     */
    private function extractOrganizationSlugFromUrl(string $url): ?string
    {
        // ZennのPublication記事URLパターンから組織スラグを抽出
        // 例: https://zenn.dev/p/cybozu/articles/xxx (cybozu がPublication名)
        // 例: https://zenn.dev/cybozu/articles/xxx (cybozu が組織スラグ - 新形式)
        // 例: https://zenn.dev/@username/articles/xxx (@username は個人ユーザー)
        
        // Publication形式: /p/組織名/articles/
        if (preg_match('/zenn\.dev\/p\/([^\/]+)\/articles\//', $url, $matches)) {
            return $matches[1];
        }
        
        // 組織記事の新形式: /組織名/articles/ (@記号なし)
        if (preg_match('/zenn\.dev\/([^\/]+)\/articles\//', $url, $matches)) {
            $slug = $matches[1];
            // @で始まるものは個人ユーザーなので除外
            // pで始まるものはPublication形式なので除外（上で処理済み）
            if (!str_starts_with($slug, '@') && $slug !== 'p') {
                return $slug;
            }
        }

        return null;
    }

    /**
     * 著者テキストから「in 企業名」形式の組織名を抽出
     *
     * @param  string  $authorText  著者テキスト
     * @param  string|null  $authorName  著者名
     * @return string|null 組織名またはnull
     */
    private function extractOrganizationFromAuthorText(string $authorText, ?string $authorName): ?string
    {
        // 「username in 企業名」のパターンをより正確に抽出
        $patterns = [
            '/^([a-zA-Z0-9_-]+)\s+in\s+(.+)$/u',  // "username in 企業名"
            '/^([a-zA-Z0-9_-]+)\s*in\s*([^0-9\s].+)$/u',  // "username in企業名" (スペースなし)
            '/^([a-zA-Z0-9_-]+)\s+@\s*(.+)$/u',  // "username @ 企業名"
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, trim($authorText), $matches)) {
                $extractedUsername = trim($matches[1]);
                $extractedOrganization = trim($matches[2]);

                // 著者名と一致する場合のみ組織名を返す
                if ($extractedUsername === $authorName && ! empty($extractedOrganization)) {
                    // 数字のみや短すぎる文字列は除外
                    if (! preg_match('/^\d+$/', $extractedOrganization) && strlen($extractedOrganization) > 2) {
                        return $extractedOrganization;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Zennから組織URLを抽出
     *
     * @param  Crawler  $node  記事ノード
     * @param  string|null  $organizationName  組織名
     * @return string|null 組織URLまたはnull
     */
    private function extractOrganizationUrlFromZenn(Crawler $node, ?string $organizationName): ?string
    {
        try {
            // publicationLinkから直接URLを抽出
            $publicationLink = $node->filter('.ArticleList_publicationLink');
            if ($publicationLink->count() > 0) {
                $href = $publicationLink->attr('href');
                if ($href) {
                    $url = str_starts_with($href, 'http') ? $href : $this->baseUrl.$href;
                    Log::debug("Zenn組織URL抽出成功: {$url}");

                    return $url;
                }
            }

            // 記事URLから組織URLを推定
            $articleUrl = $this->extractUrl($node);
            if ($articleUrl) {
                $organizationSlug = $this->extractOrganizationSlugFromUrl($articleUrl);
                if ($organizationSlug) {
                    // Publicationの場合は /p/組織名 形式のURL
                    if (str_contains($articleUrl, '/p/')) {
                        $estimatedUrl = $this->baseUrl.'/p/'.$organizationSlug;
                    } else {
                        // 通常の組織形式
                        $estimatedUrl = $this->baseUrl.'/'.$organizationSlug;
                    }
                    Log::debug("Zenn組織URL推定生成: {$estimatedUrl}");

                    return $estimatedUrl;
                }
            }

            // 組織名からスラグを生成してURLを構築
            if ($organizationName) {
                $slug = $this->generateSlug($organizationName);
                if ($slug) {
                    $estimatedUrl = $this->baseUrl.'/'.$slug;
                    Log::debug("Zenn組織URL（組織名ベース）生成: {$estimatedUrl}");

                    return $estimatedUrl;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::debug('Zenn組織URL抽出エラー', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * 個人記事かどうかを判定
     *
     * @param  string  $url  記事URL
     * @param  string|null  $companyName  抽出された企業名
     * @return bool 個人記事の場合true
     */
    private function isPersonalArticle(string $url, ?string $companyName): bool
    {
        // Publication URLパターンでない場合は個人記事の可能性が高い
        if (!preg_match('/\/p\/([^\/]+)\//', $url)) {
            // さらに企業名が抽出されていない場合は確実に個人記事
            if (empty($companyName)) {
                return true;
            }
            
            // @で始まるURL（個人ユーザー）の場合
            if (preg_match('/zenn\.dev\/@([^\/]+)\//', $url)) {
                return true;
            }
        }
        
        // Publication URLパターンで、かつ企業名が抽出されている場合は組織記事
        return false;
    }

    /**
     * 文字列からスラグを生成
     *
     * @param  string  $text  変換対象文字列
     * @return string|null スラグまたはnull
     */
    private function generateSlug(string $text): ?string
    {
        // 基本的なスラグ化処理
        $slug = strtolower(trim($text));
        // 日本語文字や特殊文字を除去、英数字とハイフン・アンダースコアのみ残す
        $slug = preg_replace('/[^a-z0-9\-_]/', '', $slug);

        return ! empty($slug) ? $slug : null;
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

                // organization情報を取得（extractOrganizationDirectで既に処理済み）
                $organization = $article['organization'] ?? null;
                $organizationName = $article['organization_name'] ?? null;
                $organizationUrl = $article['organization_url'] ?? null;

                // 拡張された会社マッチングを使用
                $articleData = array_merge($article, [
                    'author_name' => $authorName,
                    'organization' => $organization,
                    'organization_name' => $organizationName,
                    'organization_url' => $organizationUrl,
                    'platform' => 'zenn',
                ]);
                $company = $companyMatcher->identifyOrCreateCompany($articleData);

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
                        'organization' => $organization,
                        'organization_url' => $organizationUrl,
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
