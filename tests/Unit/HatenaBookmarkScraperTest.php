<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Services\HatenaBookmarkScraper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_scraper_initialization()
    {
        $this->assertInstanceOf(HatenaBookmarkScraper::class, $this->scraper);

        $reflection = new \ReflectionClass($this->scraper);
        $property = $reflection->getProperty('requestsPerMinute');
        $property->setAccessible(true);
        $this->assertEquals(20, $property->getValue($this->scraper));
    }

    public function test_parse_hatena_bookmark_html()
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

    public function test_identify_company_domain()
    {
        $company = Company::factory()->create([
            'domain' => 'example.com',
            'name' => 'Example Company',
        ]);

        $result = $this->scraper->identifyCompanyDomain('example.com');

        $this->assertNotNull($result);
        $this->assertEquals('Example Company', $result->name);
    }

    public function test_identify_company_domain_not_found()
    {
        $result = $this->scraper->identifyCompanyDomain('unknown.com');

        $this->assertNull($result);
    }

    public function test_normalize_and_save_data()
    {
        $company = Company::factory()->create([
            'domain' => 'example.com',
            'name' => 'Example Company',
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

    public function test_extract_domain()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractDomain');
        $method->setAccessible(true);

        $domain = $method->invokeArgs($this->scraper, ['https://example.com/path/to/article']);
        $this->assertEquals('example.com', $domain);

        $domain = $method->invokeArgs($this->scraper, ['https://subdomain.example.com/article']);
        $this->assertEquals('subdomain.example.com', $domain);
    }

    public function test_handles_parsing_errors_gracefully()
    {
        $malformedHtml = '<html><body><div class="invalid">broken</div></body></html>';

        Http::fake([
            'b.hatena.ne.jp/*' => Http::response($malformedHtml, 200),
        ]);

        $result = $this->scraper->scrapePopularItEntries();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
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
