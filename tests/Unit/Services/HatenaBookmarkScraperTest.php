<?php

namespace Tests\Unit\Services;

use App\Models\Article;
use App\Models\Company;
use App\Models\Platform;
use App\Services\HatenaBookmarkScraper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class HatenaBookmarkScraperTest extends TestCase
{
    use RefreshDatabase;

    private HatenaBookmarkScraper $scraper;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test configuration
        Config::set('constants.hatena.rate_limit_per_minute', 60);
        Config::set('constants.api.timeout_seconds', 30);
        Config::set('constants.api.max_retry_count', 3);
        Config::set('constants.api.retry_delay_seconds', 1);
        Config::set('constants.api.rate_limit_per_minute', 60);
        Config::set('constants.api.rate_limit_window_seconds', 60);

        $this->scraper = new HatenaBookmarkScraper;
    }

    #[Test]
    public function test_コンストラクタで設定値が正しく初期化される()
    {
        $this->assertInstanceOf(HatenaBookmarkScraper::class, $this->scraper);

        $reflection = new \ReflectionClass($this->scraper);
        $baseUrlProperty = $reflection->getProperty('baseUrl');
        $baseUrlProperty->setAccessible(true);
        $this->assertEquals('https://b.hatena.ne.jp', $baseUrlProperty->getValue($this->scraper));

        $itCategoryUrlProperty = $reflection->getProperty('itCategoryUrl');
        $itCategoryUrlProperty->setAccessible(true);
        $this->assertEquals('https://b.hatena.ne.jp/hotentry/it', $itCategoryUrlProperty->getValue($this->scraper));
    }

    #[Test]
    public function test_scrape_popular_it_entries_成功時に正しいデータを返す()
    {
        $mockHtml = '<html><body>
            <div class="entrylist-contents">
                <h3 class="entrylist-contents-title">
                    <a href="https://example.com/article1">Test Article 1</a>
                </h3>
                <div class="entrylist-contents-users">
                    <a href="#" class="entrylist-contents-users-link">100</a>
                </div>
            </div>
            <div class="entrylist-contents">
                <h3 class="entrylist-contents-title">
                    <a href="https://example.com/article2">Test Article 2</a>
                </h3>
                <div class="entrylist-contents-users">
                    <a href="#" class="entrylist-contents-users-link">50</a>
                </div>
            </div>
        </body></html>';

        Http::fake([
            'https://b.hatena.ne.jp/hotentry/it' => Http::response($mockHtml, 200),
        ]);

        $result = $this->scraper->scrapePopularItEntries();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $this->assertArrayHasKey('title', $result[0]);
        $this->assertArrayHasKey('url', $result[0]);
        $this->assertArrayHasKey('engagement_count', $result[0]);
        $this->assertArrayHasKey('platform', $result[0]);

        $this->assertEquals('Test Article 1', $result[0]['title']);
        $this->assertEquals('https://example.com/article1', $result[0]['url']);
        $this->assertEquals(100, $result[0]['engagement_count']);
        $this->assertEquals('hatena_bookmark', $result[0]['platform']);
    }

    #[Test]
    public function test_extract_title_タイトルを正しく抽出する()
    {
        $html = '<div class="entrylist-contents">
            <h3 class="entrylist-contents-title">
                <a href="https://example.com/article">Test Article Title</a>
            </h3>
        </div>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('.entrylist-contents');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractTitle');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertEquals('Test Article Title', $result);
    }

    #[Test]
    public function test_extract_title_タイトルが見つからない場合nullを返す()
    {
        $html = '<div class="entrylist-contents">
            <div>No title here</div>
        </div>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('.entrylist-contents');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractTitle');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertNull($result);
    }

    #[Test]
    public function test_extract_url_ur_lを正しく抽出する()
    {
        $html = '<div class="entrylist-contents">
            <h3 class="entrylist-contents-title">
                <a href="https://example.com/article">Test Article</a>
            </h3>
        </div>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('.entrylist-contents');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractUrl');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertEquals('https://example.com/article', $result);
    }

    #[Test]
    public function test_extract_url_ur_lが見つからない場合nullを返す()
    {
        $html = '<div class="entrylist-contents">
            <div>No link here</div>
        </div>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('.entrylist-contents');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractUrl');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertNull($result);
    }

    #[Test]
    public function test_extract_engagement_count_エンゲージメント数を正しく抽出する()
    {
        $html = '<div class="entrylist-contents">
            <div class="entrylist-contents-users">
                <a href="#" class="entrylist-contents-users-link">123</a>
            </div>
        </div>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('.entrylist-contents');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractBookmarkCount');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertEquals(123, $result);
    }

    #[Test]
    public function test_extract_engagement_count_エンゲージメント数が見つからない場合ゼロを返す()
    {
        $html = '<div class="entrylist-contents">
            <div>No bookmark count</div>
        </div>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('.entrylist-contents');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractBookmarkCount');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertEquals(0, $result);
    }

    #[Test]
    public function test_extract_engagement_count_文字列混在でも数値を抽出する()
    {
        $html = '<div class="entrylist-contents">
            <div class="entrylist-contents-users">
                <a href="#" class="entrylist-contents-users-link">456 users</a>
            </div>
        </div>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('.entrylist-contents');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractBookmarkCount');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertEquals(456, $result);
    }

    #[Test]
    public function test_extract_domain_ドメインを正しく抽出する()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractDomain');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, 'https://example.com/path/to/article');

        $this->assertEquals('example.com', $result);
    }

    #[Test]
    public function test_extract_domain_不正な_ur_lの場合空文字を返す()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractDomain');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, 'invalid-url');

        $this->assertEquals('', $result);
    }

    #[Test]
    public function test_extract_published_at_日時情報を正しく抽出する()
    {
        $html = '<div class="entrylist-contents">
            <time datetime="2024-01-01T12:00:00Z">2024-01-01</time>
        </div>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('.entrylist-contents');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractPublishedAt');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertEquals('2024-01-01T12:00:00Z', $result);
    }

    #[Test]
    public function test_extract_published_at_相対時間を現在時刻ベースで変換する()
    {
        $html = '<div class="entrylist-contents">
            <div class="entrylist-contents-date">2時間前</div>
        </div>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('.entrylist-contents');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractPublishedAt');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertNotNull($result);
        $this->assertIsString($result);
        // 2時間前の時刻が正しく計算されていることを確認
        $expectedTime = now()->subHours(2);
        $actualTime = \Carbon\Carbon::parse($result);
        $this->assertTrue($actualTime->diffInMinutes($expectedTime) < 5); // 5分以内の誤差を許容
    }

    #[Test]
    public function test_extract_published_at_分単位の相対時間を変換する()
    {
        $html = '<div class="entrylist-contents">
            <div class="entrylist-contents-date">30分前</div>
        </div>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('.entrylist-contents');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractPublishedAt');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertNotNull($result);
        $this->assertIsString($result);
        // 30分前の時刻が正しく計算されていることを確認
        $expectedTime = now()->subMinutes(30);
        $actualTime = \Carbon\Carbon::parse($result);
        $this->assertTrue($actualTime->diffInMinutes($expectedTime) < 5); // 5分以内の誤差を許容
    }

    #[Test]
    public function test_extract_published_at_日時情報が見つからない場合nullを返す()
    {
        $html = '<div class="entrylist-contents">
            <div>No datetime info</div>
        </div>';

        $crawler = new Crawler($html);
        $node = $crawler->filter('.entrylist-contents');

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractPublishedAt');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $node);

        $this->assertNull($result);
    }

    #[Test]
    public function test_identify_company_domain_企業ドメインを正しく特定する()
    {
        $company = Company::factory()->create([
            'domain' => 'example.com',
            'name' => 'Example Company',
        ]);

        $result = $this->scraper->identifyCompanyDomain('example.com');

        $this->assertNotNull($result);
        $this->assertEquals($company->id, $result->id);
        $this->assertEquals('Example Company', $result->name);
    }

    #[Test]
    public function test_identify_company_domain_企業が見つからない場合nullを返す()
    {
        $result = $this->scraper->identifyCompanyDomain('unknown.com');

        $this->assertNull($result);
    }

    #[Test]
    public function test_normalize_and_save_data_データを正しく正規化して保存する()
    {
        $company = Company::factory()->create([
            'domain' => 'example.com',
            'name' => 'Example Company',
        ]);

        $platform = Platform::factory()->create([
            'name' => 'はてなブックマーク',
        ]);

        $entries = [
            [
                'title' => 'Test Article',
                'url' => 'https://example.com/article1',
                'engagement_count' => 100,
                'domain' => 'example.com',
                'published_at' => '2024-01-01T12:00:00Z',
                'scraped_at' => now(),
                'platform' => 'hatena_bookmark',
            ],
        ];

        $result = $this->scraper->normalizeAndSaveData($entries);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Article::class, $result[0]);

        $article = $result[0];
        $this->assertEquals('Test Article', $article->title);
        $this->assertEquals('https://example.com/article1', $article->url);
        $this->assertEquals(100, $article->engagement_count);
        $this->assertEquals($company->id, $article->company_id);
        $this->assertEquals($platform->id, $article->platform_id);
        $this->assertEquals('hatena_bookmark', $article->platform);
    }

    #[Test]
    public function test_normalize_and_save_data_企業が見つからない場合company_idはnull()
    {
        $platform = Platform::factory()->create([
            'name' => 'はてなブックマーク',
        ]);

        $entries = [
            [
                'title' => 'Test Article',
                'url' => 'https://unknown.com/article1',
                'engagement_count' => 50,
                'domain' => 'unknown.com',
                'published_at' => '2024-01-01T12:00:00Z',
                'scraped_at' => now(),
                'platform' => 'hatena_bookmark',
            ],
        ];

        $result = $this->scraper->normalizeAndSaveData($entries);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Article::class, $result[0]);

        $article = $result[0];
        $this->assertNull($article->company_id);
        $this->assertEquals($platform->id, $article->platform_id);
    }

    #[Test]
    public function test_normalize_and_save_data_既存記事は更新される()
    {
        $company = Company::factory()->create([
            'domain' => 'example.com',
            'name' => 'Example Company',
        ]);

        $platform = Platform::factory()->create([
            'name' => 'はてなブックマーク',
        ]);

        // 既存記事を作成
        $existingArticle = Article::factory()->create([
            'url' => 'https://example.com/article1',
            'title' => 'Old Title',
            'engagement_count' => 50,
        ]);

        $entries = [
            [
                'title' => 'Updated Title',
                'url' => 'https://example.com/article1',
                'engagement_count' => 100,
                'domain' => 'example.com',
                'published_at' => '2024-01-01T12:00:00Z',
                'scraped_at' => now(),
                'platform' => 'hatena_bookmark',
            ],
        ];

        $result = $this->scraper->normalizeAndSaveData($entries);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $article = $result[0];
        $this->assertEquals($existingArticle->id, $article->id);
        $this->assertEquals('Updated Title', $article->title);
        $this->assertEquals(100, $article->engagement_count);
        $this->assertEquals($company->id, $article->company_id);
    }

    #[Test]
    public function test_normalize_and_save_data_プラットフォームがない場合でも記事は作成される()
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        // プラットフォームが存在しない場合でも記事は作成される
        $entries = [
            [
                'title' => 'Test Article',
                'url' => 'https://example.com/article1',
                'engagement_count' => 100,
                'domain' => 'example.com',
                'published_at' => '2024-01-01T12:00:00Z',
                'scraped_at' => now(),
                'platform' => 'hatena_bookmark',
            ],
        ];

        $result = $this->scraper->normalizeAndSaveData($entries);

        $this->assertIsArray($result);
        // プラットフォームが見つからなくてもplatform_idがnullで記事は作成される
        $this->assertNotEmpty($result);
        $this->assertNull($result[0]->platform_id);
    }

    #[Test]
    public function test_parse_response_レスポンスを正しく解析する()
    {
        $mockHtml = '<html><body>
            <div class="entrylist-contents">
                <h3 class="entrylist-contents-title">
                    <a href="https://example.com/article1">Test Article 1</a>
                </h3>
                <div class="entrylist-contents-users">
                    <a href="#" class="entrylist-contents-users-link">100</a>
                </div>
            </div>
        </body></html>';

        // レスポンスオブジェクトを直接作成
        $response = new \Illuminate\Http\Client\Response(
            new \GuzzleHttp\Psr7\Response(200, [], $mockHtml)
        );

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $response);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('title', $result[0]);
        $this->assertArrayHasKey('url', $result[0]);
        $this->assertArrayHasKey('engagement_count', $result[0]);
        $this->assertArrayHasKey('platform', $result[0]);
        $this->assertEquals('Test Article 1', $result[0]['title']);
        $this->assertEquals('https://example.com/article1', $result[0]['url']);
        $this->assertEquals(100, $result[0]['engagement_count']);
        $this->assertEquals('hatena_bookmark', $result[0]['platform']);
    }

    #[Test]
    public function test_parse_response_部分的に不正なデータがあっても正常処理される()
    {
        $mockHtml = '<html><body>
            <div class="entrylist-contents">
                <h3 class="entrylist-contents-title">
                    <a href="https://example.com/article1">Test Article</a>
                </h3>
                <!-- ブックマーク数が不正な形式 -->
                <div class="entrylist-contents-users">
                    <span>Invalid bookmark count</span>
                </div>
            </div>
        </body></html>';

        // レスポンスオブジェクトを直接作成
        $response = new \Illuminate\Http\Client\Response(
            new \GuzzleHttp\Psr7\Response(200, [], $mockHtml)
        );

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $response);

        $this->assertIsArray($result);
        // エラーがあっても部分的にパースが成功する
        $this->assertCount(1, $result);
    }

    #[Test]
    public function test_parse_response_タイトルまたは_ur_lが不正な場合は除外される()
    {
        $mockHtml = '<html><body>
            <div class="entrylist-contents">
                <h3 class="entrylist-contents-title">
                    <!-- タイトルのみでURLなし -->
                    <span>Title without URL</span>
                </h3>
                <div class="entrylist-contents-users">
                    <a href="#" class="entrylist-contents-users-link">100</a>
                </div>
            </div>
            <div class="entrylist-contents">
                <h3 class="entrylist-contents-title">
                    <a href="https://example.com/article1">Valid Article</a>
                </h3>
                <div class="entrylist-contents-users">
                    <a href="#" class="entrylist-contents-users-link">50</a>
                </div>
            </div>
        </body></html>';

        // レスポンスオブジェクトを直接作成
        $response = new \Illuminate\Http\Client\Response(
            new \GuzzleHttp\Psr7\Response(200, [], $mockHtml)
        );

        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, $response);

        $this->assertIsArray($result);
        $this->assertCount(1, $result); // 有効な記事のみ
        $this->assertEquals('Valid Article', $result[0]['title']);
        $this->assertEquals('https://example.com/article1', $result[0]['url']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
