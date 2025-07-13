<?php

namespace Tests\Unit;

use App\Constants\Platform;
use App\Models\Article;
use App\Models\Company;
use App\Services\ZennScraper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZennScraperTest extends TestCase
{
    use RefreshDatabase;

    private ZennScraper $scraper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scraper = new ZennScraper;
    }

    public function test_スクレイパーがベーススクレイパーインターフェースを実装する(): void
    {
        $this->assertInstanceOf(\App\Services\BaseScraper::class, $this->scraper);
    }

    public function test_スクレイパーが正しい設定を持つ(): void
    {
        $reflection = new \ReflectionClass($this->scraper);

        $baseUrlProperty = $reflection->getProperty('baseUrl');
        $baseUrlProperty->setAccessible(true);
        $this->assertEquals('https://zenn.dev', $baseUrlProperty->getValue($this->scraper));

        $trendUrlProperty = $reflection->getProperty('trendUrl');
        $trendUrlProperty->setAccessible(true);
        $this->assertEquals('https://zenn.dev', $trendUrlProperty->getValue($this->scraper));

        $requestsPerMinuteProperty = $reflection->getProperty('requestsPerMinute');
        $requestsPerMinuteProperty->setAccessible(true);
        $this->assertEquals(Platform::getRateLimit(Platform::ZENN), $requestsPerMinuteProperty->getValue($this->scraper));
    }

    public function test_モックレスポンスでトレンド記事をスクレイピングする(): void
    {
        $mockHtml = '
            <article>
                <h2><a href="/articles/test-article-1">Test Article 1</a></h2>
                <div data-testid="like-count" aria-label="10 いいね">10</div>
                <a href="/@testuser1">@testuser1</a>
                <time datetime="2023-01-01T00:00:00Z">2023-01-01</time>
            </article>
            <article>
                <h2><a href="/articles/test-article-2">Test Article 2</a></h2>
                <div data-testid="like-count" aria-label="5 いいね">5</div>
                <a href="/@testuser2">@testuser2</a>
                <time datetime="2023-01-02T00:00:00Z">2023-01-02</time>
            </article>
        ';

        Http::fake([
            'https://zenn.dev' => Http::response($mockHtml, 200),
        ]);

        $articles = $this->scraper->scrapeTrendingArticles();

        $this->assertIsArray($articles);
        $this->assertCount(2, $articles);

        $this->assertEquals('Test Article 1', $articles[0]['title']);
        $this->assertEquals('https://zenn.dev/articles/test-article-1', $articles[0]['url']);
        $this->assertEquals(10, $articles[0]['likes_count']);
        $this->assertEquals('/@testuser1', $articles[0]['author']);
        $this->assertEquals('https://zenn.dev/@testuser1', $articles[0]['author_url']);
        $this->assertEquals('2023-01-01T00:00:00Z', $articles[0]['published_at']);
        $this->assertEquals('zenn', $articles[0]['platform']);

        $this->assertEquals('Test Article 2', $articles[1]['title']);
        $this->assertEquals('https://zenn.dev/articles/test-article-2', $articles[1]['url']);
        $this->assertEquals(5, $articles[1]['likes_count']);
        $this->assertEquals('/@testuser2', $articles[1]['author']);
        $this->assertEquals('https://zenn.dev/@testuser2', $articles[1]['author_url']);
        $this->assertEquals('2023-01-02T00:00:00Z', $articles[1]['published_at']);
        $this->assertEquals('zenn', $articles[1]['platform']);
    }

    public function test_企業アカウントを正しく特定する(): void
    {
        $company = Company::factory()->create([
            'name' => 'テスト企業',
            'zenn_username' => 'testcompany',
        ]);

        $result = $this->scraper->identifyCompanyAccount('https://zenn.dev/@testcompany');
        $this->assertInstanceOf(Company::class, $result);
        $this->assertEquals('テスト企業', $result->name);

        $result = $this->scraper->identifyCompanyAccount('https://zenn.dev/@unknownuser');
        $this->assertNull($result);

        $result = $this->scraper->identifyCompanyAccount(null);
        $this->assertNull($result);
    }

    public function test_データの正規化と保存が正常に動作する(): void
    {
        $company = Company::factory()->create([
            'name' => 'テスト企業',
            'zenn_username' => 'testcompany',
        ]);

        $articlesData = [
            [
                'title' => 'テスト記事',
                'url' => 'https://zenn.dev/articles/test-article',
                'likes_count' => 10,
                'author' => '/@testcompany',
                'author_url' => 'https://zenn.dev/@testcompany',
                'published_at' => '2023-01-01T00:00:00Z',
                'platform' => 'zenn',
                'scraped_at' => now(),
            ],
        ];

        $savedArticles = $this->scraper->normalizeAndSaveData($articlesData);

        $this->assertCount(1, $savedArticles);
        $this->assertInstanceOf(Article::class, $savedArticles[0]);
        $this->assertEquals('テスト記事', $savedArticles[0]->title);
        $this->assertEquals('https://zenn.dev/articles/test-article', $savedArticles[0]->url);
        $this->assertEquals($company->id, $savedArticles[0]->company_id);
        $this->assertEquals(10, $savedArticles[0]->likes_count);
        $this->assertEquals('/@testcompany', $savedArticles[0]->author);
        $this->assertEquals('https://zenn.dev/@testcompany', $savedArticles[0]->author_url);
        $this->assertEquals('zenn', $savedArticles[0]->platform);

        $this->assertDatabaseHas('articles', [
            'title' => 'テスト記事',
            'url' => 'https://zenn.dev/articles/test-article',
            'company_id' => $company->id,
            'platform' => 'zenn',
        ]);
    }

    public function test_抽出エラーを適切に処理する(): void
    {
        $mockHtml = '<div>Invalid HTML structure</div>';

        Http::fake([
            'https://zenn.dev' => Http::response($mockHtml, 200),
        ]);

        $articles = $this->scraper->scrapeTrendingArticles();

        $this->assertIsArray($articles);
        $this->assertEmpty($articles);
    }

    public function test_ネットワークエラーを処理する(): void
    {
        Http::fake([
            'https://zenn.dev' => Http::response('', 500),
        ]);

        $this->expectException(\Exception::class);
        $this->scraper->scrapeTrendingArticles();
    }

    public function test_タイムアウト設定が正常に動作する(): void
    {
        $this->scraper->setTimeout(60);

        $reflection = new \ReflectionClass($this->scraper);
        $timeoutProperty = $reflection->getProperty('timeout');
        $timeoutProperty->setAccessible(true);

        $this->assertEquals(60, $timeoutProperty->getValue($this->scraper));
    }

    public function test_リトライ設定が正常に動作する(): void
    {
        $this->scraper->setRetryOptions(5, 2);

        $reflection = new \ReflectionClass($this->scraper);

        $maxRetriesProperty = $reflection->getProperty('maxRetries');
        $maxRetriesProperty->setAccessible(true);
        $this->assertEquals(5, $maxRetriesProperty->getValue($this->scraper));

        $delaySecondsProperty = $reflection->getProperty('delaySeconds');
        $delaySecondsProperty->setAccessible(true);
        $this->assertEquals(2, $delaySecondsProperty->getValue($this->scraper));
    }

    public function test_レートリミット設定が正常に動作する(): void
    {
        $this->scraper->setRateLimit(10);

        $reflection = new \ReflectionClass($this->scraper);
        $requestsPerMinuteProperty = $reflection->getProperty('requestsPerMinute');
        $requestsPerMinuteProperty->setAccessible(true);

        $this->assertEquals(10, $requestsPerMinuteProperty->getValue($this->scraper));
    }

    public function test_extract_author_url_zenn形式の_author_ur_lを抽出する(): void
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractAuthorUrl');
        $method->setAccessible(true);

        $html = '<a href="/user123" class="author">user123</a>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $authorUrl = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertEquals('https://zenn.dev/user123', $authorUrl);
    }

    public function test_extract_author_url_相対_ur_lを正しく変換する(): void
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractAuthorUrl');
        $method->setAccessible(true);

        $html = '<div class="ArticleList_userName_abc123"><a href="/@username">@username</a></div>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $authorUrl = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertEquals('https://zenn.dev/@username', $authorUrl);
    }

    public function test_extract_author_url_絶対_ur_lはそのまま返す(): void
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractAuthorUrl');
        $method->setAccessible(true);

        $html = '<div><a href="https://zenn.dev/external-user">external-user</a></div>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $authorUrl = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertEquals('https://zenn.dev/external-user', $authorUrl);
    }

    public function test_extract_author_url_著者が見つからない場合はnullを返す(): void
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractAuthorUrl');
        $method->setAccessible(true);

        $html = '<div>著者リンクなし</div>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $authorUrl = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertNull($authorUrl);
    }

    public function test_extract_author_url_空の要素で処理する(): void
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractAuthorUrl');
        $method->setAccessible(true);

        $html = '<div></div>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $authorUrl = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertNull($authorUrl);
    }

    public function test_extract_author_url_cs_s_modules対応のクラス名を処理する(): void
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractAuthorUrl');
        $method->setAccessible(true);

        // CSS ModulesのuserNameクラスをテスト
        $html = '<div class="userName_abc123"><a href="/newuser">newuser</a></div>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $authorUrl = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertEquals('https://zenn.dev/newuser', $authorUrl);
    }

    public function test_extract_author_url_画像alt属性から著者を取得する(): void
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractAuthorUrl');
        $method->setAccessible(true);

        $html = '<div class="User_profile"><img alt="test-user" src="/avatar.png"></div>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $authorUrl = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertEquals('https://zenn.dev/test-user', $authorUrl);
    }

    public function test_extract_author_url_異常な_htm_lでも処理する(): void
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractAuthorUrl');
        $method->setAccessible(true);

        $html = '<div><a>リンクはあるがhref属性なし</a></div>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $authorUrl = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertNull($authorUrl);
    }

    public function test_extract_author_url_複数のセレクタパターンを処理する(): void
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractAuthorUrl');
        $method->setAccessible(true);

        // data-testid属性でのテスト
        $html = '<div><a href="/testuser" data-testid="author-link">testuser</a></div>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $authorUrl = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertEquals('https://zenn.dev/testuser', $authorUrl);
    }

    public function test_do_m要素が存在しない場合の処理(): void
    {
        $reflection = new \ReflectionClass($this->scraper);

        // 各extractメソッドのnull処理テスト
        $methods = ['extractTitle', 'extractUrl', 'extractLikesCount', 'extractAuthor', 'extractAuthorUrl', 'extractPublishedAt'];

        $html = '<div>empty</div>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        foreach ($methods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $method->setAccessible(true);

            $result = $method->invokeArgs($this->scraper, [$crawler]);

            if ($methodName === 'extractLikesCount') {
                $this->assertEquals(0, $result);
            } else {
                $this->assertNull($result);
            }
        }
    }

    public function test_特殊文字含みデータの処理(): void
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractTitle');
        $method->setAccessible(true);

        // 特殊文字を含むタイトル
        $html = '<h1>【Zenn】React & Next.js の活用 <企業向け></h1>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $title = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertEquals('【Zenn】React & Next.js の活用', $title);
    }

    public function test_extract_likes_count_数値以外の文字列処理(): void
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractLikesCount');
        $method->setAccessible(true);

        // 数値以外を含むテキスト
        $html = '<div aria-label="def いいね ghi">def 456 ghi</div>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $likesCount = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertEquals(456, $likesCount);
    }

    public function test_extract_url_無効な_ur_l形式の処理(): void
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractUrl');
        $method->setAccessible(true);

        // /articles/を含まないURL
        $html = '<h2><a href="/books/other-path">タイトル</a></h2>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $url = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertNull($url);
    }

    public function test_extract_author_長すぎるテキストの処理(): void
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractAuthor');
        $method->setAccessible(true);

        // 50文字超のテキスト（実装では50文字未満のみ受け入れ）
        $longText = str_repeat('a', 60);
        $html = "<div class=\"userName_test\"><span>{$longText}</span></div>";
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $author = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertNull($author);
    }

    public function test_大量記事データでのパフォーマンス(): void
    {
        $largeHtml = '<html><body>';
        for ($i = 1; $i <= 20; $i++) { // Zennは16記事制限があるので20で制限テスト
            $largeHtml .= "
            <article>
                <h2><a href=\"/articles/test-{$i}\">記事タイトル{$i}</a></h2>
                <div data-testid=\"like-count\" aria-label=\"{$i} いいね\">{$i}</div>
                <a href=\"/@user{$i}\">user{$i}</a>
                <time datetime=\"2024-01-01T10:00:00Z\"></time>
            </article>";
        }
        $largeHtml .= '</body></html>';

        Http::fake([
            'https://zenn.dev' => Http::response($largeHtml, 200),
        ]);

        $startTime = microtime(true);
        $result = $this->scraper->scrapeTrendingArticles();
        $endTime = microtime(true);

        $this->assertIsArray($result);
        $this->assertCount(16, $result); // Zennの制限により16記事まで
        $this->assertLessThan(5, $endTime - $startTime); // 5秒以内で完了
    }

    public function test_normalize_and_save_data_例外処理(): void
    {
        // 無効なデータでも処理が継続される
        $articles = [
            [
                'title' => null, // 無効なデータ
                'url' => 'https://zenn.dev/articles/invalid',
                'likes_count' => 'invalid', // 数値以外
                'author' => '',
                'author_url' => null,
                'published_at' => 'invalid-date',
                'platform' => 'zenn',
                'scraped_at' => now(),
            ],
        ];

        $result = $this->scraper->normalizeAndSaveData($articles);

        // エラーがあっても処理は継続し、空の結果を返す
        $this->assertIsArray($result);
    }

    public function test_会社名抽出の境界値テスト(): void
    {
        $articles = [
            [
                'title' => 'テスト記事',
                'url' => 'https://zenn.dev/articles/test',
                'likes_count' => 10,
                'author' => 'ユーザー名in株式会社テスト', // "in会社名"パターン
                'author_url' => 'https://zenn.dev/@test',
                'published_at' => '2024-01-01T00:00:00Z',
                'platform' => 'zenn',
                'scraped_at' => now(),
            ],
        ];

        $result = $this->scraper->normalizeAndSaveData($articles);

        $this->assertCount(1, $result);
        // author_nameが正しく抽出されているかテスト（会社名部分が除去される）
        $this->assertEquals('ユーザー名', $result[0]->author_name);
    }
}
