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

    protected function parseResponse(Response $response): array
    {
        $html = $response->body();
        $crawler = new Crawler($html);
        $articles = [];

        // デバッグ: HTMLの一部をログに出力
        Log::debug('Zenn HTML preview', [
            'html_length' => strlen($html),
            'html_preview' => substr($html, 0, 1000),
        ]);

        // ZennのTechセクション内の記事を取得（16記事を上限とする、CSS Modules対応）
        $selectors = [
            '[class*="ArticleList_item"]',  // CSS ModulesのArticleListアイテム
            '[class*="ArticleListItem"]',  // ArticleListItemを含むクラス
            'article',  // 汎用的なarticleタグ
            '[data-testid="article-list-item"]',  // テストID付きリストアイテム
            'a[href*="/articles/"]',  // 記事リンク
            '.View_container',  // Zennのコンテナクラス
            'div[class*="View"]',  // Viewを含むクラス
        ];

        foreach ($selectors as $selector) {
            Log::debug("Testing selector: {$selector}");
            $elements = $crawler->filter($selector);
            Log::debug("Found {$elements->count()} elements with selector: {$selector}");

            if ($elements->count() > 0) {
                $articleCount = 0;
                $elements->each(function (Crawler $node) use (&$articles, &$articleCount) {
                    if ($articleCount >= 16) {
                        return false; // 16記事で停止
                    }
                    try {
                        Log::debug('Zenn 記事ノードHTML', [
                            'html' => substr($node->html(), 0, 500),
                        ]);

                        $title = $this->extractTitle($node);
                        $url = $this->extractUrl($node);
                        $likesCount = $this->extractLikesCount($node);
                        $author = $this->extractAuthor($node);
                        $authorUrl = $this->extractAuthorUrl($node);
                        $publishedAt = $this->extractPublishedAt($node);

                        Log::debug('Zenn 抽出結果', [
                            'title' => $title,
                            'url' => $url,
                            'likes_count' => $likesCount,
                            'author' => $author,
                        ]);

                        if ($title && $url) {
                            $articles[] = [
                                'title' => $title,
                                'url' => $url,
                                'likes_count' => $likesCount,
                                'author' => $author,
                                'author_url' => $authorUrl,
                                'published_at' => $publishedAt,
                                'scraped_at' => now(),
                                'platform' => 'zenn',
                            ];
                            $articleCount++; // 記事追加時にカウンターをインクリメント
                        }
                    } catch (\Exception $e) {
                        Log::warning('Zenn記事の解析中にエラー', [
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
            // Zenn特有のタイトルセレクタパターンを試す
            $selectors = [
                'h1',  // 記事のメインタイトル
                'h2',  // 記事のサブタイトル
                'h3',  // 記事のヘッダー
                'a[href*="/articles/"]',  // 記事リンクのテキスト
                '.View_title',  // Zennのタイトルクラス
                '[class*="Title"]',  // Titleを含むクラス
                'p',  // 段落内のタイトル
            ];

            foreach ($selectors as $selector) {
                $titleElement = $node->filter($selector);
                if ($titleElement->count() > 0) {
                    $title = trim($titleElement->text());
                    Log::debug('Zenn タイトル抽出デバッグ', [
                        'selector' => $selector,
                        'title' => $title,
                        'html' => $titleElement->html(),
                    ]);
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
            // ZennのURL抽出セレクタパターンを試す
            $selectors = [
                'a[href*="/articles/"]',  // 記事リンク（最優先）
                'h1 a',  // h1内のリンク
                'h2 a',  // h2内のリンク
                'h3 a',  // h3内のリンク
                'a',  // 一般的なリンク
            ];

            foreach ($selectors as $selector) {
                $linkElement = $node->filter($selector);
                if ($linkElement->count() > 0) {
                    $href = $linkElement->attr('href');
                    if ($href && strpos($href, '/articles/') !== false) {
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
            // Zennのいいね数抽出セレクタパターンを試す
            $selectors = [
                '[data-testid="like-count"]',
                '[aria-label*="いいね"]',
                '[aria-label*="like"]',
                '[class*="Like"]',
                '[class*="like"]',
                'span[aria-label]',
                '.View_likeCount',
                'button[aria-label*="いいね"]',
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
            // Zennの著者抽出セレクタパターンを試す（CSS Modules対応）
            $selectors = [
                '[class*="userName"]',  // userNameを含むクラス（CSS Modules）
                '[class*="ArticleList_userName"]',  // 具体的なクラス名
                'a[href*="/@"]',  // @付きユーザーリンク
                '[data-testid="author-link"]',
                '[class*="Author"]',
                '[class*="author"]',
                '.View_author',
                'img[alt]',  // アバター画像のalt属性
                '[class*="User"]',  // Userを含むクラス
                '[class*="Profile"]',  // Profileを含むクラス
                'a[href^="/"]',  // 相対パスのリンク
            ];

            foreach ($selectors as $selector) {
                $authorElement = $node->filter($selector);
                if ($authorElement->count() > 0) {
                    Log::debug('Zenn 著者抽出デバッグ', [
                        'selector' => $selector,
                        'count' => $authorElement->count(),
                        'html' => $authorElement->html(),
                    ]);

                    // hrefがある場合（リンク要素）
                    $href = $authorElement->attr('href');
                    if ($href) {
                        // 記事URLでない場合はユーザーURLとみなす
                        if (strpos($href, '/articles/') === false) {
                            Log::debug("Found author href: {$href}");

                            return trim($href);
                        }
                    }

                    // alt属性がある場合（画像要素）
                    $alt = $authorElement->attr('alt');
                    if ($alt && ! empty(trim($alt))) {
                        Log::debug("Found author alt: {$alt}");

                        return trim($alt);
                    }

                    // テキストコンテンツがある場合
                    $text = trim($authorElement->text());
                    if (! empty($text) && strlen($text) < 50) { // 長すぎるテキストは除外
                        Log::debug("Found author text: {$text}");

                        return $text;
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
            // Zennの日時抽出セレクタパターンを試す
            $selectors = [
                'time[datetime]',
                'time',
                '[datetime]',
                '[data-testid="published-date"]',
                '[class*="Date"]',
                '[class*="date"]',
                '.View_date',
                '[class*="Time"]',
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
