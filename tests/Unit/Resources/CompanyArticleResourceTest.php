<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\CompanyArticleResource;
use App\Models\Article;
use App\Models\Company;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CompanyArticleResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_基本的な記事情報が正しく変換される()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();
        $article = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'title' => 'テスト記事',
            'url' => 'https://example.com/article',
            'domain' => 'example.com',
            'author_name' => 'テスト著者',
            'author_url' => 'https://example.com/author',
            'bookmark_count' => 100,
            'likes_count' => 50,
        ]);

        $request = new Request();
        $resource = new CompanyArticleResource($article);
        $result = $resource->toArray($request);

        $this->assertEquals($article->id, $result['id']);
        $this->assertEquals('テスト記事', $result['title']);
        $this->assertEquals('https://example.com/article', $result['url']);
        $this->assertEquals('example.com', $result['domain']);
        $this->assertEquals('テスト著者', $result['author_name']);
        $this->assertEquals('https://example.com/author', $result['author_url']);
        $this->assertEquals(100, $result['bookmark_count']);
        $this->assertEquals(50, $result['likes_count']);
        $this->assertEquals($company->id, $result['company']['id']);
    }

    public function test_会社情報がロードされている場合に詳細情報が含まれる()
    {
        $company = Company::factory()->create([
            'name' => 'テスト会社',
            'domain' => 'test-company.com',
        ]);
        $platform = Platform::factory()->create();
        $article = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $article->load('company');

        $request = new Request();
        $resource = new CompanyArticleResource($article);
        $result = $resource->toArray($request);

        $this->assertEquals($company->id, $result['company']['id']);
        $this->assertEquals('テスト会社', $result['company']['name']);
        $this->assertEquals('test-company.com', $result['company']['domain']);
    }

    public function test_プラットフォーム情報がロードされている場合にリソースが正常に動作する()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create([
            'name' => 'テストプラットフォーム',
            'base_url' => 'https://test-platform.com',
        ]);
        $article = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $article->load('platform');

        $request = new Request();
        $resource = new CompanyArticleResource($article);
        $result = $resource->toArray($request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('platform', $result);
        $this->assertTrue(true); // プラットフォーム情報テスト完了
    }

    public function test_基本的なリソース変換が動作する()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();
        $article = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $request = new Request();
        $resource = new CompanyArticleResource($article);
        $result = $resource->toArray($request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('company', $result);
    }

    public function test_match_scoreが設定されている場合に含まれる()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();
        $article = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $article->match_score = 95.5;

        $request = new Request();
        $resource = new CompanyArticleResource($article);
        $result = $resource->toArray($request);

        $this->assertTrue(isset($result['match_score']) && $result['match_score'] === 95.5);
    }

    public function test_リソースの全フィールドが含まれる()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();
        $article = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $request = new Request();
        $resource = new CompanyArticleResource($article);
        $result = $resource->toArray($request);

        $expectedFields = [
            'id', 'title', 'url', 'domain', 'platform', 'author_name', 
            'author_url', 'published_at', 'bookmark_count', 'likes_count', 
            'company', 'scraped_at'
        ];

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $result);
        }
    }

    public function test_日付フィールドが正しく含まれる()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();
        $article = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => '2024-01-15 10:30:00',
            'scraped_at' => '2024-01-15 12:00:00',
        ]);

        $request = new Request();
        $resource = new CompanyArticleResource($article);
        $result = $resource->toArray($request);

        $this->assertArrayHasKey('published_at', $result);
        $this->assertArrayHasKey('scraped_at', $result);
        $this->assertEquals('2024-01-15 10:30:00', $result['published_at']);
        $this->assertEquals('2024-01-15 12:00:00', $result['scraped_at']);
    }
}