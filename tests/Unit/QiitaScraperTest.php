<?php

namespace Tests\Unit;

use App\Constants\Platform;
use App\Models\Company;
use App\Services\QiitaScraper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QiitaScraperTest extends TestCase
{
    use RefreshDatabase;

    private QiitaScraper $scraper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scraper = new QiitaScraper;
    }

    public function test_スクレイパーの初期化が正常に動作する()
    {
        $this->assertInstanceOf(QiitaScraper::class, $this->scraper);

        $reflection = new \ReflectionClass($this->scraper);
        $property = $reflection->getProperty('requestsPerMinute');
        $property->setAccessible(true);
        $this->assertEquals(Platform::getRateLimit(Platform::QIITA), $property->getValue($this->scraper));
    }

    public function test_qiita_htm_lの解析が正常に動作する()
    {
        $mockHtml = $this->getMockQiitaHtml();

        Http::fake([
            'qiita.com*' => Http::response($mockHtml, 200),
        ]);

        $result = $this->scraper->scrapeTrendingArticles();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $this->assertEquals('Reactの新機能について', $result[0]['title']);
        $this->assertEquals('https://qiita.com/items/12345', $result[0]['url']);
        $this->assertEquals(150, $result[0]['likes_count']);
        $this->assertNull($result[0]['author']);
        $this->assertNull($result[0]['author_url']);
        $this->assertEquals('qiita', $result[0]['platform']);
    }

    public function test_企業アカウントを正しく特定する()
    {
        $company = Company::factory()->create([
            'qiita_username' => 'company_user',
            'name' => 'テスト企業',
        ]);

        $result = $this->scraper->identifyCompanyAccount('https://qiita.com/company_user');

        $this->assertNotNull($result);
        $this->assertEquals('テスト企業', $result->name);
    }

    public function test_企業アカウントが見つからない場合の処理を行う()
    {
        $result = $this->scraper->identifyCompanyAccount('https://qiita.com/unknown_user');

        $this->assertNull($result);
    }

    public function test_nullの_ur_lで企業アカウント特定を処理する()
    {
        $result = $this->scraper->identifyCompanyAccount(null);

        $this->assertNull($result);
    }

    public function test_データの正規化と保存が正常に動作する()
    {
        $company = Company::factory()->create([
            'qiita_username' => 'test_user',
            'name' => 'テスト企業',
        ]);

        $articles = [
            [
                'title' => 'テスト記事',
                'url' => 'https://qiita.com/items/test123',
                'likes_count' => 100,
                'author' => '/test_user',
                'author_url' => 'https://qiita.com/test_user',
                'published_at' => '2024-01-01T10:00:00Z',
                'platform' => 'qiita',
                'scraped_at' => now(),
            ],
        ];

        $result = $this->scraper->normalizeAndSaveData($articles);

        $this->assertCount(1, $result);
        $this->assertDatabaseHas('articles', [
            'title' => 'テスト記事',
            'url' => 'https://qiita.com/items/test123',
            'company_id' => $company->id,
            'likes_count' => 100,
            'platform' => 'qiita',
        ]);
    }

    public function test_タイトルの抽出が正常に動作する()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractTitle');
        $method->setAccessible(true);

        $html = '<article><h2><a>テストタイトル</a></h2></article>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $title = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertEquals('テストタイトル', $title);
    }

    public function test_ur_lの抽出が正常に動作する()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractUrl');
        $method->setAccessible(true);

        $html = '<article><h2><a href="/items/12345">テストタイトル</a></h2></article>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $url = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertEquals('https://qiita.com/items/12345', $url);
    }

    public function test_いいね数の抽出が正常に動作する()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractLikesCount');
        $method->setAccessible(true);

        $html = '<article><div data-testid="like-count">123</div></article>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $likesCount = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertEquals(123, $likesCount);
    }

    public function test_著者の抽出が正常に動作する()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractAuthor');
        $method->setAccessible(true);

        // 新しいHTML構造をテスト
        $html = '<div class="style-1uma8mh"><a href="/user123">User</a></div>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $author = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertEquals('/user123', $author);
    }

    public function test_著者_ur_lの抽出が正常に動作する()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractAuthorUrl');
        $method->setAccessible(true);

        $html = '<article><div data-hyperapp-app="UserIcon"><a href="/user123">User</a></div></article>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $authorUrl = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertEquals('https://qiita.com/user123', $authorUrl);
    }

    public function test_公開日の抽出が正常に動作する()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractPublishedAt');
        $method->setAccessible(true);

        $html = '<article><time datetime="2024-01-01T10:00:00Z"></time></article>';
        $crawler = new \Symfony\Component\DomCrawler\Crawler($html);

        $publishedAt = $method->invokeArgs($this->scraper, [$crawler]);
        $this->assertEquals('2024-01-01T10:00:00Z', $publishedAt);
    }

    public function test_解析エラーを適切に処理する()
    {
        $malformedHtml = '<html><body><div class="invalid">broken</div></body></html>';

        Http::fake([
            'qiita.com*' => Http::response($malformedHtml, 200),
        ]);

        $result = $this->scraper->scrapeTrendingArticles();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_缠尺要素を適切に処理する()
    {
        $incompleteHtml = '
        <html>
        <body>
            <div class="style-1uma8mh">
                <h2><a href="/items/12345">タイトルのみ</a></h2>
            </div>
        </body>
        </html>';

        Http::fake([
            'qiita.com*' => Http::response($incompleteHtml, 200),
        ]);

        $result = $this->scraper->scrapeTrendingArticles();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('タイトルのみ', $result[0]['title']);
        $this->assertEquals(0, $result[0]['likes_count']);
        $this->assertNull($result[0]['author']);
    }

    private function getMockQiitaHtml(): string
    {
        return '
        <html>
        <body>
            <div class="style-1uma8mh">
                <h2>
                    <a href="/items/12345">Reactの新機能について</a>
                </h2>
                <span aria-label="150 LGTM">150</span>
                <div class="author-info">
                    <a href="/user1">ユーザー1</a>
                </div>
                <time datetime="2024-01-01T10:00:00Z"></time>
            </div>
            <div class="style-1uma8mh">
                <h2>
                    <a href="/items/67890">Vue.jsのベストプラクティス</a>
                </h2>
                <span aria-label="80 LGTM">80</span>
                <div class="author-info">
                    <a href="/user2">ユーザー2</a>
                </div>
                <time datetime="2024-01-02T15:30:00Z"></time>
            </div>
        </body>
        </html>';
    }
}
