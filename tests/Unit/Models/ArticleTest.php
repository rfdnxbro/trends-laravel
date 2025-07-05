<?php

namespace Tests\Unit\Models;

use App\Models\Article;
use App\Models\Company;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_基本的なモデル作成ができる()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        $article = Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => $this->faker()->sentence(),
            'url' => $this->faker()->url(),
            'author_name' => $this->faker()->name(),
            'published_at' => now(),
            'bookmark_count' => $this->faker()->numberBetween(0, 100),
            'scraped_at' => now(),
        ]);

        $this->assertInstanceOf(Article::class, $article);
        $this->assertTrue($article->exists);
    }

    public function test_必須フィールドの検証()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Article::create([]);
    }

    public function test_fillable属性の確認()
    {
        $article = new Article;
        $fillable = $article->getFillable();

        $expected = [
            'platform_id',
            'company_id',
            'title',
            'url',
            'author_name',
            'published_at',
            'bookmark_count',
            'scraped_at',
        ];

        $this->assertEquals($expected, $fillable);
    }

    public function test_型変換の確認()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        $article = Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => $this->faker()->sentence(),
            'url' => $this->faker()->url(),
            'published_at' => '2023-01-01 12:00:00',
            'scraped_at' => '2023-01-01 13:00:00',
            'bookmark_count' => '50',
        ]);

        $article->refresh();
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $article->published_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $article->scraped_at);
        $this->assertIsInt($article->bookmark_count);
        $this->assertEquals(50, $article->bookmark_count);
    }

    public function test_タイムスタンプの確認()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        $article = Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => $this->faker()->sentence(),
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        $this->assertNotNull($article->created_at);
        $this->assertNotNull($article->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $article->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $article->updated_at);
    }

    public function test_companyリレーションの確認()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        $article = Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => $this->faker()->sentence(),
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        $this->assertInstanceOf(Company::class, $article->company);
        $this->assertEquals($company->id, $article->company->id);
    }

    public function test_platformリレーションの確認()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        $article = Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => $this->faker()->sentence(),
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        $this->assertInstanceOf(Platform::class, $article->platform);
        $this->assertEquals($platform->id, $article->platform->id);
    }

    public function test_recentスコープの動作確認()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => 'Recent Article',
            'url' => $this->faker()->url(),
            'published_at' => now()->subDays(3),
            'scraped_at' => now(),
        ]);

        Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => 'Old Article',
            'url' => $this->faker()->url(),
            'published_at' => now()->subDays(10),
            'scraped_at' => now(),
        ]);

        $recentArticles = Article::recent(7)->get();
        $this->assertCount(1, $recentArticles);
        $this->assertEquals('Recent Article', $recentArticles->first()->title);
    }

    public function test_popularスコープの動作確認()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => 'Popular Article',
            'url' => $this->faker()->url(),
            'bookmark_count' => 50,
            'scraped_at' => now(),
        ]);

        Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => 'Unpopular Article',
            'url' => $this->faker()->url(),
            'bookmark_count' => 5,
            'scraped_at' => now(),
        ]);

        $popularArticles = Article::popular(10)->get();
        $this->assertCount(1, $popularArticles);
        $this->assertEquals('Popular Article', $popularArticles->first()->title);
    }

    public function test_mass_assignment_protectionの確認()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        $data = [
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => $this->faker()->sentence(),
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
            'created_at' => now()->subDays(10),
        ];

        $article = Article::create($data);

        $this->assertNotEquals($data['created_at'], $article->created_at);
    }
}
