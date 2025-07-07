<?php

namespace Tests\Unit;

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

    public function test_scraper_implements_base_scraper_interface(): void
    {
        $this->assertInstanceOf(\App\Services\BaseScraper::class, $this->scraper);
    }

    public function test_scraper_has_correct_configuration(): void
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
        $this->assertEquals(20, $requestsPerMinuteProperty->getValue($this->scraper));
    }

    public function test_scrape_trending_articles_with_mocked_response(): void
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

    public function test_identify_company_account(): void
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

    public function test_normalize_and_save_data(): void
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

    public function test_handle_extraction_errors_gracefully(): void
    {
        $mockHtml = '<div>Invalid HTML structure</div>';

        Http::fake([
            'https://zenn.dev' => Http::response($mockHtml, 200),
        ]);

        $articles = $this->scraper->scrapeTrendingArticles();

        $this->assertIsArray($articles);
        $this->assertEmpty($articles);
    }

    public function test_handle_network_errors(): void
    {
        Http::fake([
            'https://zenn.dev' => Http::response('', 500),
        ]);

        $this->expectException(\Exception::class);
        $this->scraper->scrapeTrendingArticles();
    }

    public function test_timeout_configuration(): void
    {
        $this->scraper->setTimeout(60);

        $reflection = new \ReflectionClass($this->scraper);
        $timeoutProperty = $reflection->getProperty('timeout');
        $timeoutProperty->setAccessible(true);

        $this->assertEquals(60, $timeoutProperty->getValue($this->scraper));
    }

    public function test_retry_configuration(): void
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

    public function test_rate_limit_configuration(): void
    {
        $this->scraper->setRateLimit(10);

        $reflection = new \ReflectionClass($this->scraper);
        $requestsPerMinuteProperty = $reflection->getProperty('requestsPerMinute');
        $requestsPerMinuteProperty->setAccessible(true);

        $this->assertEquals(10, $requestsPerMinuteProperty->getValue($this->scraper));
    }
}
