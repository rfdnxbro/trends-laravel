<?php

namespace Tests\Unit;

use App\Http\Resources\CompanyResource;
use App\Http\Resources\CompanyArticleResource;
use App\Models\Article;
use App\Models\Company;
use App\Models\CompanyRanking;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestData();
    }

    private function setupTestData()
    {
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'domain' => 'test.com',
            'description' => 'Test company description',
        ]);

        $this->platform = Platform::factory()->create([
            'name' => 'Qiita',
            'url' => 'https://qiita.com',
        ]);

        CompanyRanking::factory()->create([
            'company_id' => $this->company->id,
            'ranking_period' => '1m',
            'rank_position' => 1,
            'total_score' => 100.0,
            'article_count' => 10,
            'total_bookmarks' => 1000,
        ]);

        Article::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
            'published_at' => now()->subDays(5),
        ]);
    }

    public function test_company_has_articles_relationship()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $this->company->articles);
        $this->assertCount(3, $this->company->articles);
    }

    public function test_company_has_rankings_relationship()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $this->company->rankings);
        $this->assertCount(1, $this->company->rankings);
    }

    public function test_company_resource_structure()
    {
        $rankings = ['1m' => $this->company->rankings->first()];
        $resource = new CompanyResource($this->company, $rankings);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('domain', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('current_rankings', $array);
        $this->assertArrayHasKey('recent_articles', $array);

        $this->assertEquals($this->company->id, $array['id']);
        $this->assertEquals($this->company->name, $array['name']);
        $this->assertEquals($this->company->domain, $array['domain']);
    }

    public function test_company_resource_current_rankings_format()
    {
        $rankings = ['1m' => $this->company->rankings->first()];
        $resource = new CompanyResource($this->company, $rankings);
        $array = $resource->toArray(request());

        $this->assertIsArray($array['current_rankings']);
        
        if (!empty($array['current_rankings'])) {
            $ranking = $array['current_rankings'][0];
            $this->assertArrayHasKey('period', $ranking);
            $this->assertArrayHasKey('rank_position', $ranking);
            $this->assertArrayHasKey('total_score', $ranking);
            $this->assertArrayHasKey('article_count', $ranking);
            $this->assertArrayHasKey('total_bookmarks', $ranking);
        }
    }

    public function test_article_resource_structure()
    {
        $article = $this->company->articles->first();
        $resource = new CompanyArticleResource($article);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('url', $array);
        $this->assertArrayHasKey('platform', $array);
        $this->assertArrayHasKey('bookmark_count', $array);
        $this->assertArrayHasKey('likes_count', $array);
        $this->assertArrayHasKey('published_at', $array);
        $this->assertArrayHasKey('company', $array);

        $this->assertEquals($article->id, $array['id']);
        $this->assertEquals($article->title, $array['title']);
        $this->assertEquals($article->url, $array['url']);
    }

    public function test_article_resource_company_data()
    {
        $article = $this->company->articles->first();
        $resource = new CompanyArticleResource($article);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('company', $array);
        $this->assertArrayHasKey('id', $array['company']);
        $this->assertEquals($this->company->id, $array['company']['id']);
    }

    public function test_company_active_scope()
    {
        Company::factory()->create(['is_active' => false]);
        Company::factory()->create(['is_active' => true]);

        $activeCompanies = Company::active()->get();
        
        foreach ($activeCompanies as $company) {
            $this->assertTrue($company->is_active);
        }
    }

    public function test_article_recent_scope()
    {
        Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
            'published_at' => now()->subDays(10),
        ]);

        $recentArticles = Article::recent(7)->get();
        
        foreach ($recentArticles as $article) {
            $this->assertGreaterThanOrEqual(
                now()->subDays(7)->toDateString(),
                $article->published_at->toDateString()
            );
        }
    }

    public function test_article_popular_scope()
    {
        Article::factory()->create([
            'company_id' => $this->company->id,
            'platform_id' => $this->platform->id,
            'bookmark_count' => 5,
        ]);

        $popularArticles = Article::popular(10)->get();
        
        foreach ($popularArticles as $article) {
            $this->assertGreaterThanOrEqual(10, $article->bookmark_count);
        }
    }

    public function test_company_article_relationship()
    {
        $article = $this->company->articles->first();
        
        $this->assertInstanceOf(Company::class, $article->company);
        $this->assertEquals($this->company->id, $article->company->id);
    }

    public function test_article_platform_relationship()
    {
        $article = $this->company->articles->first();
        
        $this->assertInstanceOf(Platform::class, $article->platform);
        $this->assertEquals($this->platform->id, $article->platform->id);
    }

    public function test_company_resource_with_loaded_articles()
    {
        $companyWithArticles = Company::with('articles')->find($this->company->id);
        $resource = new CompanyResource($companyWithArticles);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('recent_articles', $array);
        $this->assertIsArray($array['recent_articles']);
    }

    public function test_company_resource_without_rankings()
    {
        $resource = new CompanyResource($this->company);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('current_rankings', $array);
        $this->assertEmpty($array['current_rankings']);
    }

    public function test_company_model_fillable_attributes()
    {
        $fillable = [
            'name',
            'domain',
            'description',
            'logo_url',
            'website_url',
            'is_active',
        ];

        $this->assertEquals($fillable, $this->company->getFillable());
    }

    public function test_company_model_casts()
    {
        $casts = [
            'is_active' => 'boolean',
        ];

        $this->assertEquals($casts, $this->company->getCasts());
    }

    public function test_company_model_default_attributes()
    {
        $newCompany = new Company();
        $this->assertTrue($newCompany->is_active);
    }
}