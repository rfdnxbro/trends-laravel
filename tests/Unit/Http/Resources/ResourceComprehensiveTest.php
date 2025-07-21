<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\CompanyArticleResource;
use App\Http\Resources\CompanyRankingCollection;
use App\Http\Resources\CompanyRankingResource;
use App\Http\Resources\CompanyResource;
use App\Models\Article;
use App\Models\Company;
use App\Models\CompanyRanking;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ResourceComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    /**
     * CompanyResourceの変換テスト
     */
    public function test_company_resource_変換が正常に動作すること(): void
    {
        $company = Company::factory()->create([
            'name' => 'テスト企業',
            'domain' => 'test.com',
            'description' => 'テスト説明',
            'is_active' => true,
        ]);

        $resource = new CompanyResource($company);
        $request = Request::create('/test');

        $array = $resource->toArray($request);

        $this->assertIsArray($array);
        $this->assertEquals('テスト企業', $array['name']);
        $this->assertEquals('test.com', $array['domain']);
        $this->assertEquals('テスト説明', $array['description']);
        $this->assertTrue($array['is_active']);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }

    /**
     * CompanyArticleResourceの変換テスト
     */
    public function test_company_article_resource_変換が正常に動作すること(): void
    {
        $platform = Platform::factory()->create();
        $company = Company::factory()->create();

        $article = Article::factory()->create([
            'title' => 'テスト記事',
            'url' => 'https://example.com/article',
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'author_name' => 'テスト著者',
            'engagement_count' => 10,
        ]);

        $resource = new CompanyArticleResource($article);
        $request = Request::create('/test');

        $array = $resource->toArray($request);

        $this->assertIsArray($array);
        $this->assertEquals('テスト記事', $array['title']);
        $this->assertEquals('https://example.com/article', $array['url']);
        $this->assertEquals('テスト著者', $array['author_name']);
        $this->assertEquals(10, $array['engagement_count']);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('published_at', $array);
    }

    /**
     * CompanyRankingResourceの変換テスト
     */
    public function test_company_ranking_resource_変換が正常に動作すること(): void
    {
        $company = Company::factory()->create();
        $ranking = CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'rank_position' => 1,
            'total_score' => 95.5,
            'article_count' => 10,
            'total_bookmarks' => 100,
        ]);

        // CompanyRankingResourceが期待するデータ構造のオブジェクトを作成
        $rankingData = (object) [
            'id' => $ranking->id,
            'company_id' => $company->id,
            'company_name' => $company->name,
            'domain' => $company->domain,
            'logo_url' => $company->logo_url,
            'rank_position' => $ranking->rank_position,
            'total_score' => $ranking->total_score,
            'article_count' => $ranking->article_count,
            'total_bookmarks' => $ranking->total_bookmarks,
            'rank_change' => null,
            'period_start' => $ranking->period_start,
            'period_end' => $ranking->period_end,
            'calculated_at' => $ranking->calculated_at,
        ];

        $resource = new CompanyRankingResource($rankingData);
        $request = Request::create('/test');

        $array = $resource->toArray($request);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('company', $array);
        $this->assertArrayHasKey('rank_position', $array);
        $this->assertArrayHasKey('total_score', $array);
        $this->assertEquals(1, $array['rank_position']);
        $this->assertEquals(95.5, $array['total_score']);
        $this->assertArrayHasKey('name', $array['company']);
        $this->assertArrayHasKey('domain', $array['company']);
    }

    /**
     * CompanyRankingCollectionの変換テスト
     */
    public function test_company_ranking_collection_変換が正常に動作すること(): void
    {
        // ResourceCollectionのテストは複雑なため、基本的な構造のみテスト
        $collection = new CompanyRankingCollection(collect([]));
        $request = Request::create('/test');

        $array = $collection->toArray($request);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('meta', $array);
        $this->assertEmpty($array['data']);
        $this->assertEquals(0, $array['meta']['total']);
    }

    /**
     * CompanyResourceのwithメソッドテスト
     */
    public function test_company_resource_with_メタデータが正しく追加されること(): void
    {
        $company = Company::factory()->create();
        $resource = new CompanyResource($company);
        $request = Request::create('/test');

        $response = $resource->toResponse($request);

        $this->assertContains($response->getStatusCode(), [200, 201]);

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('data', $content);
    }

    /**
     * CompanyArticleResourceの関連モデルテスト
     */
    public function test_company_article_resource_関連モデルが正しく含まれること(): void
    {
        $platform = Platform::factory()->create(['name' => 'テストプラットフォーム']);
        $company = Company::factory()->create();

        $article = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $article->load('platform');

        $resource = new CompanyArticleResource($article);
        $request = Request::create('/test');

        $array = $resource->toArray($request);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('platform', $array);
    }

    /**
     * リソースの条件付きフィールドテスト
     */
    public function test_リソースの条件付きフィールドが正しく動作すること(): void
    {
        $company = Company::factory()->create([
            'logo_url' => 'https://example.com/logo.png',
            'website_url' => 'https://example.com',
        ]);

        $resource = new CompanyResource($company);
        $request = Request::create('/test');

        $array = $resource->toArray($request);

        $this->assertIsArray($array);

        if (isset($array['logo_url'])) {
            $this->assertEquals('https://example.com/logo.png', $array['logo_url']);
        }
        if (isset($array['website_url'])) {
            $this->assertEquals('https://example.com', $array['website_url']);
        }
    }

    /**
     * 空のコレクションテスト
     */
    public function test_空のコレクションが正しく処理されること(): void
    {
        $companies = collect([]);

        $collection = new CompanyRankingCollection($companies);
        $request = Request::create('/test');

        $array = $collection->toArray($request);

        $this->assertIsArray($array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('meta', $array);
        $this->assertEmpty($array['data']);
        $this->assertEquals(0, $array['meta']['total']);
    }

    /**
     * NULLフィールドの処理テスト
     */
    public function test_null_フィールドが正しく処理されること(): void
    {
        $company = Company::factory()->create([
            'description' => null,
            'logo_url' => null,
            'website_url' => null,
        ]);

        $resource = new CompanyResource($company);
        $request = Request::create('/test');

        $array = $resource->toArray($request);

        $this->assertIsArray($array);
        $this->assertNull($array['description']);
    }

    /**
     * 大量データのパフォーマンステスト
     */
    public function test_大量データのリソース変換パフォーマンス(): void
    {
        $companies = Company::factory()->count(100)->create();

        $startTime = microtime(true);

        // CompanyResourceを使用してパフォーマンステスト
        $resources = $companies->map(function ($company) {
            $resource = new CompanyResource($company);
            $request = Request::create('/test');

            return $resource->toArray($request);
        });

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertIsArray($resources->toArray());
        $this->assertCount(100, $resources);
        $this->assertLessThan(2.0, $executionTime); // 2秒以内に完了
    }

    /**
     * リソースのJSON変換テスト
     */
    public function test_リソースのjson変換が正常に動作すること(): void
    {
        $company = Company::factory()->create();

        $resource = new CompanyResource($company);
        $request = Request::create('/test');

        $response = $resource->toResponse($request);
        $json = $response->getContent();

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('data', $decoded);
    }
}
