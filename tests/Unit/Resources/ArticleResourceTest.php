<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\Company;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArticleResourceTest extends TestCase
{
    use RefreshDatabase;

    private Article $article;

    private Company $company;

    private Platform $platform;

    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->platform = Platform::factory()->create();
        $this->article = Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
        ]);
        $this->request = Request::create('/test');
    }

    #[Test]
    public function test_基本的な記事情報が正しく変換される(): void
    {
        $resource = new ArticleResource($this->article);
        $result = $resource->toArray($this->request);

        $this->assertEquals($this->article->id, $result['id']);
        $this->assertEquals($this->article->title, $result['title']);
        $this->assertEquals($this->article->url, $result['url']);
        $this->assertEquals($this->article->author_name, $result['author_name']);
        $this->assertEquals($this->article->author, $result['author']);
        $this->assertEquals($this->article->author_url, $result['author_url']);
        $this->assertEquals($this->article->published_at?->format('Y-m-d H:i:s'), $result['published_at']);
        $this->assertEquals((int) $this->article->engagement_count, $result['engagement_count']);
        $this->assertEquals($this->article->domain, $result['domain']);
        $this->assertEquals($this->article->platform, $result['platform_name']);
        $this->assertEquals($this->article->scraped_at->format('Y-m-d H:i:s'), $result['scraped_at']);
        $this->assertEquals($this->article->created_at?->format('Y-m-d H:i:s'), $result['created_at']);
        $this->assertEquals($this->article->updated_at?->format('Y-m-d H:i:s'), $result['updated_at']);
    }

    #[Test]
    public function test_プラットフォーム情報がロードされている場合に含まれる(): void
    {
        $this->article->load('platform');
        $resource = new ArticleResource($this->article);
        $result = $resource->toArray($this->request);

        $this->assertArrayHasKey('platform', $result);
        $this->assertEquals($this->platform->id, $result['platform']['id']);
        $this->assertEquals($this->platform->name, $result['platform']['name']);
        $this->assertEquals($this->platform->base_url, $result['platform']['base_url']);
    }

    #[Test]
    public function test_プラットフォーム情報がロードされていない場合は含まれない(): void
    {
        $resource = new ArticleResource($this->article);
        $result = $resource->toArray($this->request);

        $this->assertArrayHasKey('platform', $result);
        $this->assertInstanceOf(\Illuminate\Http\Resources\MissingValue::class, $result['platform']);
    }

    #[Test]
    public function test_企業情報がロードされている場合に含まれる(): void
    {
        $this->article->load('company');
        $resource = new ArticleResource($this->article);
        $result = $resource->toArray($this->request);

        $this->assertArrayHasKey('company', $result);
        $this->assertEquals($this->company->id, $result['company']['id']);
        $this->assertEquals($this->company->name, $result['company']['name']);
        $this->assertEquals($this->company->domain, $result['company']['domain']);
        $this->assertEquals($this->company->logo_url, $result['company']['logo_url']);
        $this->assertEquals($this->company->website_url, $result['company']['website_url']);
    }

    #[Test]
    public function test_企業情報がロードされていない場合は含まれない(): void
    {
        $resource = new ArticleResource($this->article);
        $result = $resource->toArray($this->request);

        $this->assertArrayHasKey('company', $result);
        $this->assertInstanceOf(\Illuminate\Http\Resources\MissingValue::class, $result['company']);
    }

    #[Test]
    public function test_published_atがnullの場合の処理(): void
    {
        $this->article->published_at = null;
        $this->article->save();

        $resource = new ArticleResource($this->article);
        $result = $resource->toArray($this->request);

        $this->assertNull($result['published_at']);
    }

    #[Test]
    public function test_created_atがnullの場合の処理(): void
    {
        $this->article->created_at = null;
        $this->article->save();

        $resource = new ArticleResource($this->article);
        $result = $resource->toArray($this->request);

        $this->assertNull($result['created_at']);
    }

    #[Test]
    public function test_updated_atがnullの場合の処理(): void
    {
        $this->article->updated_at = null;
        $this->article->save();

        $resource = new ArticleResource($this->article);
        $result = $resource->toArray($this->request);

        $this->assertNull($result['updated_at']);
    }

    #[Test]
    public function test_engagement_countの型変換が正しく行われる(): void
    {
        $this->article->engagement_count = '150';
        $this->article->save();

        $resource = new ArticleResource($this->article);
        $result = $resource->toArray($this->request);

        $this->assertIsInt($result['engagement_count']);
        $this->assertEquals(150, $result['engagement_count']);
    }

    #[Test]
    public function test_プラットフォーム情報がnullの場合の処理(): void
    {
        $this->article->platform_id = null;
        $this->article->save();
        $this->article->load('platform');

        $resource = new ArticleResource($this->article);
        $result = $resource->toArray($this->request);

        $this->assertArrayHasKey('platform', $result);
        // platform_idがnullの場合でもrelationがロードされているので配列が返される
        $this->assertIsArray($result['platform']);
        $this->assertNull($result['platform']['id']);
        $this->assertNull($result['platform']['name']);
        $this->assertNull($result['platform']['base_url']);
    }

    #[Test]
    public function test_企業情報がnullの場合の処理(): void
    {
        $this->article->company_id = null;
        $this->article->save();
        $this->article->load('company');

        $resource = new ArticleResource($this->article);
        $result = $resource->toArray($this->request);

        $this->assertArrayHasKey('company', $result);
        // company_idがnullの場合はnullが返される
        $this->assertNull($result['company']);
    }

    #[Test]
    public function test_リソースのフィールド完全性チェック(): void
    {
        $expectedFields = [
            'id',
            'title',
            'url',
            'author_name',
            'author',
            'author_url',
            'published_at',
            'engagement_count',
            'platform',
            'company',
            'domain',
            'platform_name',
            'scraped_at',
            'created_at',
            'updated_at',
        ];

        $resource = new ArticleResource($this->article);
        $result = $resource->toArray($this->request);

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $result, "フィールド '{$field}' が存在しません");
        }
    }

    #[Test]
    public function test_関連データが全てロードされている場合の処理(): void
    {
        $this->article->load(['platform', 'company']);
        $resource = new ArticleResource($this->article);
        $result = $resource->toArray($this->request);

        // プラットフォーム情報の確認
        $this->assertIsArray($result['platform']);
        $this->assertEquals($this->platform->id, $result['platform']['id']);
        $this->assertEquals($this->platform->name, $result['platform']['name']);
        $this->assertEquals($this->platform->base_url, $result['platform']['base_url']);

        // 企業情報の確認
        $this->assertIsArray($result['company']);
        $this->assertEquals($this->company->id, $result['company']['id']);
        $this->assertEquals($this->company->name, $result['company']['name']);
        $this->assertEquals($this->company->domain, $result['company']['domain']);
        $this->assertEquals($this->company->logo_url, $result['company']['logo_url']);
        $this->assertEquals($this->company->website_url, $result['company']['website_url']);
    }

    #[Test]
    public function test_match_scoreが設定されている場合の処理(): void
    {
        $this->article->match_score = 0.92;
        $resource = new ArticleResource($this->article);
        $result = $resource->toArray($this->request);

        // ArticleResourceにはmatch_scoreフィールドが定義されていないが、
        // モデルの属性として存在することを確認
        $this->assertEquals(0.92, $this->article->match_score);
    }

    #[Test]
    public function test_数値フィールドの境界値テスト(): void
    {
        $this->article->engagement_count = 999999;
        $this->article->save();

        $resource = new ArticleResource($this->article);
        $result = $resource->toArray($this->request);

        $this->assertEquals(999999, $result['engagement_count']);
        $this->assertIsInt($result['engagement_count']);
    }

    #[Test]
    public function test_日付フィールドのフォーマットが正しい(): void
    {
        $testDate = now()->setMicrosecond(0);
        $this->article->published_at = $testDate;
        $this->article->scraped_at = $testDate;
        $this->article->created_at = $testDate;
        $this->article->updated_at = $testDate;
        $this->article->save();

        $resource = new ArticleResource($this->article);
        $result = $resource->toArray($this->request);

        $expectedFormat = $testDate->format('Y-m-d H:i:s');
        $this->assertEquals($expectedFormat, $result['published_at']);
        $this->assertEquals($expectedFormat, $result['scraped_at']);
        $this->assertEquals($expectedFormat, $result['created_at']);
        $this->assertEquals($expectedFormat, $result['updated_at']);
    }

    #[Test]
    public function test_大量データでのパフォーマンス(): void
    {
        // 大量の記事データでリソース変換のパフォーマンスをテスト
        $articles = Article::factory(100)->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
        ]);

        $startTime = microtime(true);
        foreach ($articles as $article) {
            $resource = new ArticleResource($article);
            $resource->toArray($this->request);
        }
        $endTime = microtime(true);

        $this->assertLessThan(2.0, $endTime - $startTime, 'パフォーマンスが期待値を下回っています');
    }
}
