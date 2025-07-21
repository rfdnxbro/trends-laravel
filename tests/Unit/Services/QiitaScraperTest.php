<?php

namespace Tests\Unit\Services;

use App\Models\Article;
use App\Models\Company;
use App\Models\Platform;
use App\Services\QiitaScraper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class QiitaScraperTest extends TestCase
{
    use RefreshDatabase;

    private QiitaScraper $scraper;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test configuration
        Config::set('constants.qiita.rate_limit_per_minute', 60);
        Config::set('constants.api.timeout_seconds', 30);
        Config::set('constants.api.max_retry_count', 3);
        Config::set('constants.api.retry_delay_seconds', 1);
        Config::set('constants.api.rate_limit_per_minute', 60);
        Config::set('constants.api.rate_limit_window_seconds', 60);

        $this->scraper = new QiitaScraper;
    }

    #[Test]
    public function test_コンストラクタで設定値が正しく初期化される()
    {
        $this->assertInstanceOf(QiitaScraper::class, $this->scraper);

        $reflection = new \ReflectionClass($this->scraper);
        $baseUrlProperty = $reflection->getProperty('baseUrl');
        $baseUrlProperty->setAccessible(true);
        $this->assertEquals('https://qiita.com', $baseUrlProperty->getValue($this->scraper));

        $trendUrlProperty = $reflection->getProperty('trendUrl');
        $trendUrlProperty->setAccessible(true);
        $this->assertEquals('https://qiita.com', $trendUrlProperty->getValue($this->scraper));
    }

    #[Test]
    public function test_scrape_trending_articles_成功時に正しいデータを返す()
    {
        $mockHtml = '<html><body>
            <article>
                <h2><a href="/items/article1">Test Article 1</a></h2>
                <div data-testid="like-count" aria-label="10 likes">10</div>
                <time datetime="2024-01-01T12:00:00Z">2024-01-01</time>
            </article>
            <article>
                <h2><a href="/items/article2">Test Article 2</a></h2>
                <div data-testid="like-count" aria-label="20 likes">20</div>
                <time datetime="2024-01-02T12:00:00Z">2024-01-02</time>
            </article>
        </body></html>';

        Http::fake([
            'https://qiita.com' => Http::response($mockHtml, 200),
        ]);

        $result = $this->scraper->scrapeTrendingArticles();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $this->assertArrayHasKey('title', $result[0]);
        $this->assertArrayHasKey('url', $result[0]);
        $this->assertArrayHasKey('likes_count', $result[0]);
        $this->assertArrayHasKey('platform', $result[0]);

        $this->assertEquals('Test Article 1', $result[0]['title']);
        $this->assertEquals('https://qiita.com/items/article1', $result[0]['url']);
        $this->assertEquals(10, $result[0]['likes_count']);
        $this->assertEquals('qiita', $result[0]['platform']);
    }

    #[Test]
    public function test_find_article_elements_記事要素を正しく検索する()
    {
        $html = '<html><body>
            <article>
                <h2><a href="/items/article1">Test Article 1</a></h2>
            </article>
            <article>
                <h2><a href="/items/article2">Test Article 2</a></h2>
            </article>
        </body></html>';

        $crawler = new Crawler($html);

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('findArticleElements');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $crawler);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Crawler::class, $result);
        $this->assertEquals(2, $result->count());
    }

    #[Test]
    public function test_find_article_elements_記事要素が見つからない場合nullを返す()
    {
        $html = '<html><body>
            <div>No articles here</div>
        </body></html>';

        $crawler = new Crawler($html);

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('findArticleElements');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $crawler);

        $this->assertNull($result);
    }

    #[Test]
    public function test_extract_title_タイトルを正しく抽出する()
    {
        $html = '<article>
            <h2><a href="/items/article1">Test Article Title</a></h2>
        </article>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('article');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractTitle');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertEquals('Test Article Title', $result);
    }

    #[Test]
    public function test_extract_title_タイトルが見つからない場合nullを返す()
    {
        $html = '<article>
            <div>No title here</div>
        </article>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('article');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractTitle');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertNull($result);
    }

    #[Test]
    public function test_extract_url_ur_lを正しく抽出する()
    {
        $html = '<article>
            <h2><a href="/items/article1">Test Article</a></h2>
        </article>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('article');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractUrl');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertEquals('https://qiita.com/items/article1', $result);
    }

    #[Test]
    public function test_extract_url_ur_lが見つからない場合nullを返す()
    {
        $html = '<article>
            <div>No link here</div>
        </article>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('article');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractUrl');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertNull($result);
    }

    #[Test]
    public function test_extract_likes_count_いいね数を正しく抽出する()
    {
        $html = '<article>
            <div data-testid="like-count" aria-label="25 likes">25</div>
        </article>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('article');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractLikesCount');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertEquals(25, $result);
    }

    #[Test]
    public function test_extract_likes_count_いいね数が見つからない場合ゼロを返す()
    {
        $html = '<article>
            <div>No likes count</div>
        </article>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('article');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractLikesCount');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertEquals(0, $result);
    }

    #[Test]
    public function test_extract_author_著者を正しく抽出する()
    {
        $html = '<article>
            <a href="/@test_user">Test User</a>
        </article>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('article');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractAuthor');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertEquals('@test_user', $result);
    }

    #[Test]
    public function test_extract_author_著者が見つからない場合nullを返す()
    {
        $html = '<article>
            <div>No author here</div>
        </article>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('article');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractAuthor');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertNull($result);
    }

    #[Test]
    public function test_extract_author_記事_ur_lは除外される()
    {
        $html = '<article>
            <a href="/items/article1">Article Link</a>
            <a href="/@test_user">User Link</a>
        </article>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('article');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractAuthor');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertEquals('@test_user', $result);
    }

    #[Test]
    public function test_extract_author_url_著者_ur_lを正しく抽出する()
    {
        $html = '<article>
            <a href="/@test_user">Test User</a>
        </article>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('article');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractAuthorUrl');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertEquals('https://qiita.com/@test_user', $result);
    }

    #[Test]
    public function test_extract_author_url_著者が見つからない場合nullを返す()
    {
        $html = '<article>
            <div>No author here</div>
        </article>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('article');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractAuthorUrl');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertNull($result);
    }

    #[Test]
    public function test_extract_published_at_日時情報を正しく抽出する()
    {
        $html = '<article>
            <time datetime="2024-01-01T12:00:00Z">2024-01-01</time>
        </article>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('article');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractPublishedAt');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertEquals('2024-01-01T12:00:00Z', $result);
    }

    #[Test]
    public function test_extract_published_at_日時情報が見つからない場合nullを返す()
    {
        $html = '<article>
            <div>No datetime info</div>
        </article>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('article');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractPublishedAt');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertNull($result);
    }

    #[Test]
    public function test_identify_company_account_企業アカウントを正しく特定する()
    {
        $company = Company::factory()->create([
            'qiita_username' => '@test_user',
            'name' => 'Test Company',
        ]);

        $result = $this->scraper->identifyCompanyAccount('/@test_user');

        $this->assertNotNull($result);
        $this->assertEquals($company->id, $result->id);
        $this->assertEquals('Test Company', $result->name);
    }

    #[Test]
    public function test_identify_company_account_企業が見つからない場合nullを返す()
    {
        $result = $this->scraper->identifyCompanyAccount('https://qiita.com/@unknown_user');

        $this->assertNull($result);
    }

    #[Test]
    public function test_identify_company_account_author_urlがnullの場合nullを返す()
    {
        $result = $this->scraper->identifyCompanyAccount(null);

        $this->assertNull($result);
    }

    #[Test]
    public function test_normalize_and_save_data_データを正しく正規化して保存する()
    {
        $company = Company::factory()->create([
            'qiita_username' => 'test_user',
            'name' => 'Test Company',
        ]);

        $platform = Platform::factory()->create([
            'name' => 'Qiita',
        ]);

        $articles = [
            [
                'title' => 'Test Article',
                'url' => 'https://qiita.com/items/article1',
                'likes_count' => 25,
                'author' => '/@test_user',
                'author_url' => 'https://qiita.com/@test_user',
                'published_at' => '2024-01-01T12:00:00Z',
                'scraped_at' => now(),
                'platform' => 'qiita',
            ],
        ];

        $result = $this->scraper->normalizeAndSaveData($articles);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Article::class, $result[0]);

        $article = $result[0];
        $this->assertEquals('Test Article', $article->title);
        $this->assertEquals('https://qiita.com/items/article1', $article->url);
        $this->assertEquals(25, $article->likes_count);
        $this->assertEquals('test_user', $article->author_name);
        $this->assertEquals($company->id, $article->company_id);
        $this->assertEquals($platform->id, $article->platform_id);
        $this->assertEquals('qiita', $article->platform);
    }

    #[Test]
    public function test_normalize_and_save_data_author_nameを正しく抽出する()
    {
        $platform = Platform::factory()->create([
            'name' => 'Qiita',
        ]);

        $articles = [
            [
                'title' => 'Test Article',
                'url' => 'https://qiita.com/items/article1',
                'likes_count' => 25,
                'author' => '/@test_user',
                'author_url' => 'https://qiita.com/@test_user',
                'published_at' => '2024-01-01T12:00:00Z',
                'scraped_at' => now(),
                'platform' => 'qiita',
            ],
        ];

        $result = $this->scraper->normalizeAndSaveData($articles);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $article = $result[0];
        $this->assertEquals('test_user', $article->author_name);
        $this->assertEquals('/@test_user', $article->author);
    }

    #[Test]
    public function test_normalize_and_save_data_企業が見つからない場合company_idはnull()
    {
        $platform = Platform::factory()->create([
            'name' => 'Qiita',
        ]);

        $articles = [
            [
                'title' => 'Test Article',
                'url' => 'https://qiita.com/items/article1',
                'likes_count' => 25,
                'author' => '/@unknown_user',
                'author_url' => 'https://qiita.com/@unknown_user',
                'published_at' => '2024-01-01T12:00:00Z',
                'scraped_at' => now(),
                'platform' => 'qiita',
            ],
        ];

        $result = $this->scraper->normalizeAndSaveData($articles);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $article = $result[0];
        $this->assertNull($article->company_id);
        $this->assertEquals($platform->id, $article->platform_id);
    }

    #[Test]
    public function test_normalize_and_save_data_既存記事は更新される()
    {
        $company = Company::factory()->create([
            'qiita_username' => 'test_user',
            'name' => 'Test Company',
        ]);

        $platform = Platform::factory()->create([
            'name' => 'Qiita',
        ]);

        // 既存記事を作成
        $existingArticle = Article::factory()->create([
            'url' => 'https://qiita.com/items/article1',
            'title' => 'Old Title',
            'likes_count' => 10,
        ]);

        $articles = [
            [
                'title' => 'Updated Title',
                'url' => 'https://qiita.com/items/article1',
                'likes_count' => 25,
                'author' => '/@test_user',
                'author_url' => 'https://qiita.com/@test_user',
                'published_at' => '2024-01-01T12:00:00Z',
                'scraped_at' => now(),
                'platform' => 'qiita',
            ],
        ];

        $result = $this->scraper->normalizeAndSaveData($articles);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $article = $result[0];
        $this->assertEquals($existingArticle->id, $article->id);
        $this->assertEquals('Updated Title', $article->title);
        $this->assertEquals(25, $article->likes_count);
        $this->assertEquals($company->id, $article->company_id);
    }

    #[Test]
    public function test_normalize_and_save_data_プラットフォームがない場合でも記事は作成される()
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        // プラットフォームが存在しない場合でも記事は作成される
        $articles = [
            [
                'title' => 'Test Article',
                'url' => 'https://qiita.com/items/article1',
                'likes_count' => 25,
                'author' => '/@test_user',
                'author_url' => 'https://qiita.com/@test_user',
                'published_at' => '2024-01-01T12:00:00Z',
                'scraped_at' => now(),
                'platform' => 'qiita',
            ],
        ];

        $result = $this->scraper->normalizeAndSaveData($articles);

        $this->assertIsArray($result);
        // プラットフォームが見つからなくてもplatform_idがnullで記事は作成される
        $this->assertNotEmpty($result);
        $this->assertNull($result[0]->platform_id);
    }

    #[Test]
    public function test_parse_response_レスポンスを正しく解析する()
    {
        $mockHtml = '<html><body>
            <article>
                <h2><a href="/items/article1">Test Article 1</a></h2>
                <div data-testid="like-count" aria-label="10 likes">10</div>
                <a href="/@test_user">Test User</a>
                <time datetime="2024-01-01T12:00:00Z">2024-01-01</time>
            </article>
        </body></html>';

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('body')->andReturn($mockHtml);

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $mockResponse);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('title', $result[0]);
        $this->assertArrayHasKey('url', $result[0]);
        $this->assertArrayHasKey('likes_count', $result[0]);
        $this->assertArrayHasKey('platform', $result[0]);
        $this->assertEquals('Test Article 1', $result[0]['title']);
        $this->assertEquals('https://qiita.com/items/article1', $result[0]['url']);
        $this->assertEquals(10, $result[0]['likes_count']);
        $this->assertEquals('qiita', $result[0]['platform']);
    }

    #[Test]
    public function test_parse_response_タイトルまたは_ur_lが不正な場合は除外される()
    {
        $mockHtml = '<html><body>
            <article>
                <!-- タイトルのみでURLなし -->
                <h2>Title without URL</h2>
                <div data-testid="like-count" aria-label="10 likes">10</div>
            </article>
            <article>
                <h2><a href="/items/article1">Valid Article</a></h2>
                <div data-testid="like-count" aria-label="20 likes">20</div>
            </article>
        </body></html>';

        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('body')->andReturn($mockHtml);

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $mockResponse);

        $this->assertIsArray($result);
        $this->assertCount(1, $result); // 有効な記事のみ
        $this->assertEquals('Valid Article', $result[0]['title']);
        $this->assertEquals('https://qiita.com/items/article1', $result[0]['url']);
    }

    #[Test]
    public function test_log_html_preview_htm_lプレビューを正しくログ出力する()
    {
        $html = '<html><body>Test HTML content</body></html>';

        Log::shouldReceive('debug')->once()->with('Qiita HTML preview', [
            'html_length' => strlen($html),
            'html_preview' => $html,
        ]);

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('logHtmlPreview');
        $method->setAccessible(true);

        $method->invoke($this->scraper, $html);
    }

    #[Test]
    public function test_extract_single_article_data_警告ログを出力する()
    {
        Log::shouldReceive('warning')->once();

        $html = '<article>
            <!-- 不正なデータ -->
            <div>Invalid article structure</div>
        </article>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('article');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractSingleArticleData');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertNull($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
