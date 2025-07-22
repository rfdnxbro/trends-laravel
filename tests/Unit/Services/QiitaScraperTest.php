<?php

namespace Tests\Unit\Services;

use App\Models\Article;
use App\Models\Company;
use App\Models\Platform;
use App\Services\CompanyMatcher;
use App\Services\QiitaScraper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QiitaScraperTest extends TestCase
{
    use RefreshDatabase;

    private QiitaScraper $scraper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scraper = new QiitaScraper();
    }

    #[Test]
    public function test_extractOrganizationNameDirect_組織カード名から正常に抽出()
    {
        $html = '
            <article>
                <div class="organizationCard_name">テスト株式会社</div>
                <h2><a href="/items/123">テスト記事</a></h2>
            </article>
        ';

        Http::fake([
            'qiita.com' => Http::response($html, 200)
        ]);

        $articles = $this->scraper->scrapeTrendingArticles();

        $this->assertNotEmpty($articles);
        $this->assertEquals('テスト株式会社', $articles[0]['organization']);
        $this->assertEquals('テスト株式会社', $articles[0]['organization_name']);
    }

    #[Test] 
    public function test_extractOrganizationNameDirect_組織リンクから抽出()
    {
        $html = '
            <article>
                <a href="/organizations/example-org">Example Organization</a>
                <h2><a href="/items/123">テスト記事</a></h2>
            </article>
        ';

        Http::fake([
            'qiita.com' => Http::response($html, 200)
        ]);

        $articles = $this->scraper->scrapeTrendingArticles();

        $this->assertNotEmpty($articles);
        $this->assertEquals('Example Organization', $articles[0]['organization']);
    }

    #[Test]
    public function test_extractOrganizationNameDirect_組織要素が見つからない場合()
    {
        $html = '
            <article>
                <h2><a href="/items/123">テスト記事</a></h2>
                <div class="author">@test_user</div>
            </article>
        ';

        Http::fake([
            'qiita.com' => Http::response($html, 200)
        ]);

        $articles = $this->scraper->scrapeTrendingArticles();

        $this->assertNotEmpty($articles);
        $this->assertNull($articles[0]['organization']);
        $this->assertNull($articles[0]['organization_name']);
    }

    #[Test]
    public function test_extractOrganizationUrl_HTML組織リンクから直接抽出()
    {
        $html = '
            <article>
                <a href="/organizations/test-org" class="organizationCard">Test Organization</a>
                <h2><a href="/items/123">テスト記事</a></h2>
            </article>
        ';

        Http::fake([
            'qiita.com' => Http::response($html, 200)
        ]);

        $articles = $this->scraper->scrapeTrendingArticles();

        $this->assertNotEmpty($articles);
        $this->assertEquals('https://qiita.com/organizations/test-org', $articles[0]['organization_url']);
    }

    #[Test]
    public function test_extractOrganizationUrl_組織名からスラグ推定URL生成()
    {
        $html = '
            <article>
                <div class="organization-name">test-company</div>
                <h2><a href="/items/123">テスト記事</a></h2>
            </article>
        ';

        Http::fake([
            'qiita.com' => Http::response($html, 200)
        ]);

        $articles = $this->scraper->scrapeTrendingArticles();

        $this->assertNotEmpty($articles);
        $this->assertEquals('https://qiita.com/organizations/test-company', $articles[0]['organization_url']);
    }

    #[Test]
    public function test_extractOrganizationUrl_組織名がnullの場合()
    {
        $html = '
            <article>
                <h2><a href="/items/123">テスト記事</a></h2>
            </article>
        ';

        Http::fake([
            'qiita.com' => Http::response($html, 200)
        ]);

        $articles = $this->scraper->scrapeTrendingArticles();

        $this->assertNotEmpty($articles);
        $this->assertNull($articles[0]['organization_url']);
    }

    #[Test]
    public function test_extractOrganizationFromUrl_正常な組織URL()
    {
        // リフレクションを使用してprivateメソッドをテスト
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractOrganizationFromUrl');
        $method->setAccessible(true);

        $url = 'https://qiita.com/organizations/cybozu/items/sample-article';
        $result = $method->invoke($this->scraper, $url);

        $this->assertEquals('cybozu', $result);
    }

    #[Test]
    public function test_extractOrganizationFromUrl_組織パターンが含まれない場合()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractOrganizationFromUrl');
        $method->setAccessible(true);

        $url = 'https://qiita.com/users/sample-user/items/sample-article';
        $result = $method->invoke($this->scraper, $url);

        $this->assertNull($result);
    }

    #[Test]
    public function test_extractOrganizationFromUrl_空文字列URL()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractOrganizationFromUrl');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, '');

        $this->assertNull($result);
    }

    #[Test]
    public function test_generateOrganizationSlug_正常な英数字処理()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('generateOrganizationSlug');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, 'Test-Company');

        $this->assertEquals('test-company', $result);
    }

    #[Test]
    public function test_generateOrganizationSlug_特殊文字除去()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('generateOrganizationSlug');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, 'テスト株式会社！@#');

        $this->assertEquals('', $result); // 日本語と特殊文字は除去される
    }

    #[Test]
    public function test_generateOrganizationSlug_空文字列()
    {
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('generateOrganizationSlug');
        $method->setAccessible(true);

        $result = $method->invoke($this->scraper, '');

        $this->assertNull($result);
    }

    #[Test]
    public function test_extractLikesCount_4桁以上の数値は除外()
    {
        $html = '
            <article>
                <h2><a href="/items/123">テスト記事</a></h2>
                <footer>
                    <div class="style-likes">12345</div>
                </footer>
            </article>
        ';

        Http::fake([
            'qiita.com' => Http::response($html, 200)
        ]);

        $articles = $this->scraper->scrapeTrendingArticles();

        $this->assertNotEmpty($articles);
        $this->assertEquals(0, $articles[0]['engagement_count']); // 4桁超過で除外
    }

    #[Test]
    public function test_extractLikesCount_非数値文字を含む場合()
    {
        $html = '
            <article>
                <h2><a href="/items/123">テスト記事</a></h2>
                <footer>
                    <div class="style-likes">12a3</div>
                </footer>
            </article>
        ';

        Http::fake([
            'qiita.com' => Http::response($html, 200)
        ]);

        $articles = $this->scraper->scrapeTrendingArticles();

        $this->assertNotEmpty($articles);
        $this->assertEquals(0, $articles[0]['engagement_count']); // 非数値で除外
    }

    #[Test]
    public function test_extractLikesCount_footerが存在しない場合()
    {
        $html = '
            <article>
                <h2><a href="/items/123">テスト記事</a></h2>
            </article>
        ';

        Http::fake([
            'qiita.com' => Http::response($html, 200)
        ]);

        $articles = $this->scraper->scrapeTrendingArticles();

        $this->assertNotEmpty($articles);
        $this->assertEquals(0, $articles[0]['engagement_count']);
    }

    #[Test]
    public function test_extractAuthor_例外処理でnullを返す()
    {
        $html = '
            <article>
                <h2><a href="/items/123">テスト記事</a></h2>
                <!-- author要素なし -->
            </article>
        ';

        Http::fake([
            'qiita.com' => Http::response($html, 200)
        ]);

        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $articles = $this->scraper->scrapeTrendingArticles();

        $this->assertNotEmpty($articles);
        $this->assertNull($articles[0]['author']);
    }

    #[Test]
    public function test_extractAuthorUrl_author取得例外時にnull()
    {
        // モック作成でextractAuthorが例外をスローする状況を作る
        $scraper = $this->createPartialMock(QiitaScraper::class, ['extractAuthor']);
        $scraper->method('extractAuthor')
                ->willThrowException(new \Exception('Test exception'));

        $reflection = new \ReflectionClass($scraper);
        $method = $reflection->getMethod('extractAuthorUrl');
        $method->setAccessible(true);

        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $result = $method->invoke($scraper, new \Symfony\Component\DomCrawler\Crawler());

        $this->assertNull($result);
    }

    #[Test]
    public function test_extractPublishedAt_datetime属性が不正な形式()
    {
        $html = '
            <article>
                <h2><a href="/items/123">テスト記事</a></h2>
                <div class="style-date" datetime="invalid-date">2023年1月1日</div>
            </article>
        ';

        Http::fake([
            'qiita.com' => Http::response($html, 200)
        ]);

        $articles = $this->scraper->scrapeTrendingArticles();

        $this->assertNotEmpty($articles);
        $this->assertEquals('invalid-date', $articles[0]['published_at']); // 不正でもそのまま取得
    }

    #[Test]
    public function test_normalizeAndSaveData_CompanyMatcher新規企業作成()
    {
        // プラットフォーム作成
        $platform = Platform::factory()->create(['name' => 'Qiita']);

        $articles = [
            [
                'title' => '新規組織のテスト記事',
                'url' => 'https://qiita.com/organizations/new-org/items/test',
                'engagement_count' => 10,
                'author' => 'test_user',
                'organization' => 'new-org',
                'organization_name' => '新規テスト組織',
                'organization_url' => 'https://qiita.com/organizations/new-org',
                'author_url' => 'https://qiita.com/test_user',
                'published_at' => '2023-01-01T00:00:00Z',
                'scraped_at' => now(),
                'platform' => 'qiita',
            ]
        ];

        $savedArticles = $this->scraper->normalizeAndSaveData($articles);

        $this->assertCount(1, $savedArticles);
        
        // 新規企業が作成されることを確認
        $company = Company::where('name', '新規テスト組織')->first();
        $this->assertNotNull($company);
        $this->assertFalse($company->is_active);
        $this->assertEquals('new-org', $company->qiita_username);

        // 記事に企業が紐づけられることを確認
        $article = $savedArticles[0];
        $this->assertEquals($company->id, $article->company_id);
        $this->assertEquals('new-org', $article->organization);
        $this->assertEquals('新規テスト組織', $article->organization_name);
    }

    #[Test]
    public function test_normalizeAndSaveData_記事保存時のデータベース例外()
    {
        // プラットフォーム作成
        Platform::factory()->create(['name' => 'Qiita']);

        $articles = [
            [
                'title' => null, // titleがnullで保存エラーを引き起こす
                'url' => 'https://qiita.com/items/test',
                'engagement_count' => 10,
                'author' => 'test_user',
                'published_at' => '2023-01-01T00:00:00Z',
                'scraped_at' => now(),
                'platform' => 'qiita',
            ]
        ];

        Log::shouldReceive('error')->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $savedArticles = $this->scraper->normalizeAndSaveData($articles);

        $this->assertEmpty($savedArticles); // エラーで保存されない
    }

    #[Test]
    public function test_normalizeAndSaveData_organizationありの既存企業マッチング()
    {
        // プラットフォーム作成
        $platform = Platform::factory()->create(['name' => 'Qiita']);
        
        // 既存企業作成
        $company = Company::factory()->create([
            'name' => '既存テスト企業',
            'qiita_username' => 'existing-org',
            'is_active' => true,
        ]);

        $articles = [
            [
                'title' => '既存組織のテスト記事',
                'url' => 'https://qiita.com/organizations/existing-org/items/test',
                'engagement_count' => 15,
                'author' => 'existing_user',
                'organization' => 'existing-org',
                'organization_name' => '既存テスト企業',
                'organization_url' => 'https://qiita.com/organizations/existing-org',
                'author_url' => 'https://qiita.com/existing_user',
                'published_at' => '2023-02-01T00:00:00Z',
                'scraped_at' => now(),
                'platform' => 'qiita',
            ]
        ];

        $savedArticles = $this->scraper->normalizeAndSaveData($articles);

        $this->assertCount(1, $savedArticles);
        
        // 既存企業が使用されることを確認
        $article = $savedArticles[0];
        $this->assertEquals($company->id, $article->company_id);
        $this->assertEquals('existing-org', $article->organization);
        $this->assertEquals('既存テスト企業', $article->organization_name);
    }

    #[Test]
    public function test_normalizeAndSaveData_大量データ処理()
    {
        // プラットフォーム作成
        Platform::factory()->create(['name' => 'Qiita']);

        // 50記事の大量データを作成
        $articles = [];
        for ($i = 1; $i <= 50; $i++) {
            $articles[] = [
                'title' => "テスト記事{$i}",
                'url' => "https://qiita.com/items/test-{$i}",
                'engagement_count' => $i,
                'author' => "user{$i}",
                'published_at' => '2023-01-01T00:00:00Z',
                'scraped_at' => now(),
                'platform' => 'qiita',
            ];
        }

        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        $savedArticles = $this->scraper->normalizeAndSaveData($articles);

        // 大量データの処理が完了することを確認
        $this->assertIsArray($savedArticles);
    }

    #[Test]
    public function test_scrapeTrendingArticles_組織情報込み完全フロー()
    {
        $html = '
            <article class="style-article">
                <h2><a href="/items/complete-test">完全フローテスト</a></h2>
                <div class="organizationCard_name">完全テスト企業</div>
                <a href="/organizations/complete-org" class="organizationCard">完全テスト企業</a>
                <div class="author"><a href="/@complete-user">complete-user</a></div>
                <footer>
                    <div class="style-likes">42</div>
                </footer>
                <time class="style-time" datetime="2023-12-01T10:00:00Z">2023年12月1日</time>
            </article>
        ';

        Http::fake([
            'qiita.com' => Http::response($html, 200)
        ]);

        $articles = $this->scraper->scrapeTrendingArticles();

        $this->assertCount(1, $articles);
        
        $article = $articles[0];
        $this->assertEquals('完全フローテスト', $article['title']);
        $this->assertEquals('https://qiita.com/items/complete-test', $article['url']);
        $this->assertEquals(42, $article['engagement_count']);
        $this->assertEquals('@complete-user', $article['author']);
        $this->assertEquals('complete-user', $article['author_name']);
        $this->assertEquals('完全テスト企業', $article['organization']);
        $this->assertEquals('完全テスト企業', $article['organization_name']);
        $this->assertEquals('https://qiita.com/organizations/complete-org', $article['organization_url']);
        $this->assertEquals('https://qiita.com/@complete-user', $article['author_url']);
        $this->assertEquals('2023-12-01T10:00:00Z', $article['published_at']);
        $this->assertEquals('qiita', $article['platform']);
    }

    #[Test] 
    public function test_extractSingleArticleData_例外処理でnull返却()
    {
        // 空のCrawlerで例外を発生させる
        $crawler = new \Symfony\Component\DomCrawler\Crawler('<div></div>');
        
        $reflection = new \ReflectionClass($this->scraper);
        $method = $reflection->getMethod('extractSingleArticleData');
        $method->setAccessible(true);

        Log::shouldReceive('warning')->once();

        $result = $method->invoke($this->scraper, $crawler);

        $this->assertNull($result);
    }

    #[Test]
    public function test_extractOrganizationUrl_DOM例外処理()
    {
        // 不正なHTMLでDOM例外を発生させる
        $scraper = $this->createPartialMock(QiitaScraper::class, []);
        
        $reflection = new \ReflectionClass($scraper);
        $method = $reflection->getMethod('extractOrganizationUrl');
        $method->setAccessible(true);

        // 不正なCrawlerでDOM例外を発生させる
        $invalidCrawler = new \Symfony\Component\DomCrawler\Crawler();
        
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $result = $method->invoke($scraper, $invalidCrawler, null);

        $this->assertNull($result);
    }
}