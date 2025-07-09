<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Services\HatenaBookmarkScraper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HatenaBookmarkScraperTest extends TestCase
{
    use RefreshDatabase;

    private HatenaBookmarkScraper $scraper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scraper = new HatenaBookmarkScraper;
    }

    /**
     * スクレイパーの初期化をテスト
     */
    #[Test]
    public function test_スクレイパーの初期化が正常に動作する()
    {
        $this->assertInstanceOf(HatenaBookmarkScraper::class, $this->scraper);

        $reflection = new \ReflectionClass($this->scraper);
        $property = $reflection->getProperty('requestsPerMinute');
        $property->setAccessible(true);
        $this->assertEquals(20, $property->getValue($this->scraper));
    }

    /**
     * はてなブックマークHTMLの解析をテスト
     */
    #[Test]
    public function test_はてなブックマーク_htm_lの解析が正常に動作する()
    {
        $mockHtml = $this->getMockHatenaHtml();

        Http::fake([
            'b.hatena.ne.jp/*' => Http::response($mockHtml, 200),
        ]);

        $result = $this->scraper->scrapePopularItEntries();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $this->assertEquals('テストタイトル1', $result[0]['title']);
        $this->assertEquals('https://example.com/article1', $result[0]['url']);
        $this->assertEquals(100, $result[0]['bookmark_count']);
        $this->assertEquals('example.com', $result[0]['domain']);
        $this->assertEquals('hatena_bookmark', $result[0]['platform']);
    }

    /**
     * 企業ドメインの特定をテスト
     */
    #[Test]
    public function test_企業ドメインを正しく特定する()
    {
        $company = Company::factory()->create([
            'domain' => 'example.com',
            'name' => 'サンプル企業',
        ]);

        $result = $this->scraper->identifyCompanyDomain('example.com');

        $this->assertNotNull($result);
        $this->assertEquals('サンプル企業', $result->name);
    }

    /**
     * 企業ドメインが見つからない場合をテスト
     */
    #[Test]
    public function test_企業ドメインが見つからない場合の処理を行う()
    {
        $result = $this->scraper->identifyCompanyDomain('unknown.com');

        $this->assertNull($result);
    }

    /**
     * データの正規化と保存をテスト
     */
    #[Test]
    public function test_データの正規化と保存が正常に動作する()
    {
        $company = Company::factory()->create([
            'domain' => 'example.com',
            'name' => 'サンプル企業',
        ]);

        $entries = [
            [
                'title' => 'テスト記事',
                'url' => 'https://example.com/test',
                'bookmark_count' => 50,
                'domain' => 'example.com',
                'platform' => 'hatena_bookmark',
                'scraped_at' => now(),
            ],
        ];

        $result = $this->scraper->normalizeAndSaveData($entries);

        $this->assertCount(1, $result);
        $this->assertDatabaseHas('articles', [
            'title' => 'テスト記事',
            'url' => 'https://example.com/test',
            'company_id' => $company->id,
            'bookmark_count' => 50,
            'platform' => 'hatena_bookmark',
        ]);
    }

    /**
     * ドメインの抽出をテスト
     */
    #[Test]
    public function test_ドメインの抽出が正常に動作する()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractDomain');
        $method->setAccessible(true);

        $domain = $method->invokeArgs($this->scraper, ['https://example.com/path/to/article']);
        $this->assertEquals('example.com', $domain);

        $domain = $method->invokeArgs($this->scraper, ['https://subdomain.example.com/article']);
        $this->assertEquals('subdomain.example.com', $domain);
    }

    /**
     * 解析エラーの適切な処理をテスト
     */
    #[Test]
    public function test_解析エラーを適切に処理する()
    {
        $malformedHtml = '<html><body><div class="invalid">broken</div></body></html>';

        Http::fake([
            'b.hatena.ne.jp/*' => Http::response($malformedHtml, 200),
        ]);

        $result = $this->scraper->scrapePopularItEntries();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * HTTPエラー時の処理をテスト
     */
    #[Test]
    public function test_http_エラー時の処理が正常に動作する()
    {
        Http::fake([
            'b.hatena.ne.jp/*' => Http::response('', 500),
        ]);

        try {
            $result = $this->scraper->scrapePopularItEntries();
            $this->assertIsArray($result);
        } catch (\Exception $e) {
            $this->assertStringContainsString('HTTP Error', $e->getMessage());
        }
    }

    /**
     * ネットワークエラー時の処理をテスト
     */
    #[Test]
    public function test_ネットワークエラー時の処理が正常に動作する()
    {
        Http::fake([
            'b.hatena.ne.jp/*' => Http::response('Network error', 500),
        ]);

        try {
            $result = $this->scraper->scrapePopularItEntries();
            $this->assertIsArray($result);
        } catch (\Exception $e) {
            $this->assertStringContainsString('HTTP Error', $e->getMessage());
        }
    }

    /**
     * 空のHTMLレスポンス時の処理をテスト
     */
    #[Test]
    public function test_空の_htm_lレスポンス時の処理が正常に動作する()
    {
        Http::fake([
            'b.hatena.ne.jp/*' => Http::response('', 200),
        ]);

        $result = $this->scraper->scrapePopularItEntries();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * リクエストヘッダーの設定をテスト
     */
    #[Test]
    public function test_リクエストヘッダーが正しく設定されている()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $headersProperty = $reflection->getProperty('headers');
        $headersProperty->setAccessible(true);
        $headers = $headersProperty->getValue($this->scraper);

        $this->assertArrayHasKey('Accept', $headers);
        $this->assertArrayHasKey('Accept-Language', $headers);
        $this->assertArrayHasKey('Cache-Control', $headers);
        $this->assertArrayHasKey('Pragma', $headers);
    }

    /**
     * ベースURLの設定をテスト
     */
    #[Test]
    public function test_ベース_ur_lが正しく設定されている()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $baseUrlProperty = $reflection->getProperty('baseUrl');
        $baseUrlProperty->setAccessible(true);
        $baseUrl = $baseUrlProperty->getValue($this->scraper);

        $this->assertEquals('https://b.hatena.ne.jp', $baseUrl);
    }

    private function getMockHatenaHtml(): string
    {
        return '
        <html>
        <body>
            <div class="entrylist-contents">
                <div class="entrylist-contents-title">
                    <a href="https://example.com/article1">テストタイトル1</a>
                </div>
                <div class="entrylist-contents-users">
                    <a href="/entry/s/example.com/article1">100 users</a>
                </div>
            </div>
            <div class="entrylist-contents">
                <div class="entrylist-contents-title">
                    <a href="https://test.com/article2">テストタイトル2</a>
                </div>
                <div class="entrylist-contents-users">
                    <a href="/entry/s/test.com/article2">50 users</a>
                </div>
            </div>
        </body>
        </html>';
    }
}
