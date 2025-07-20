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
            'domain',
            'platform',
            'author_name',
            'author',
            'author_url',
            'published_at',
            'bookmark_count',
            'likes_count',
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
            'likes_count' => '25',
        ]);

        $article->refresh();
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $article->published_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $article->scraped_at);
        $this->assertIsInt($article->bookmark_count);
        $this->assertEquals(50, $article->bookmark_count);
        $this->assertIsInt($article->likes_count);
        $this->assertEquals(25, $article->likes_count);
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
            'title' => '最新記事',
            'url' => $this->faker()->url(),
            'published_at' => now()->subDays(3),
            'scraped_at' => now(),
        ]);

        Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => '古い記事',
            'url' => $this->faker()->url(),
            'published_at' => now()->subDays(10),
            'scraped_at' => now(),
        ]);

        $recentArticles = Article::recent(7)->get();
        $this->assertCount(1, $recentArticles);
        $this->assertEquals('最新記事', $recentArticles->first()->title);
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
            'title' => '人気記事',
            'url' => $this->faker()->url(),
            'bookmark_count' => 50,
            'scraped_at' => now(),
        ]);

        Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => '人気のない記事',
            'url' => $this->faker()->url(),
            'bookmark_count' => 5,
            'scraped_at' => now(),
        ]);

        $popularArticles = Article::popular(10)->get();
        $this->assertCount(1, $popularArticles);
        $this->assertEquals('人気記事', $popularArticles->first()->title);
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

    public function test_withFiltersスコープでタイトル検索ができる()
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
            'title' => 'Laravel PHP Framework',
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => 'React JavaScript Library',
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        $filters = ['search' => 'Laravel'];
        $result = Article::withFilters($filters)->get();

        $this->assertCount(1, $result);
        $this->assertStringContainsString('Laravel', $result->first()->title);
    }

    public function test_withFiltersスコープで著者名検索ができる()
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
            'title' => 'テスト記事1',
            'author_name' => 'John Smith',
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => 'テスト記事2',
            'author_name' => 'Jane Doe',
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        $filters = ['search' => 'John'];
        $result = Article::withFilters($filters)->get();

        $this->assertCount(1, $result);
        $this->assertStringContainsString('John', $result->first()->author_name);
    }

    public function test_withFiltersスコープで企業フィルタリングができる()
    {
        $company1 = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $company2 = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company1->id,
            'title' => 'Company1の記事',
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company2->id,
            'title' => 'Company2の記事',
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        $filters = ['company_id' => $company1->id];
        $result = Article::withFilters($filters)->get();

        $this->assertCount(1, $result);
        $this->assertEquals($company1->id, $result->first()->company_id);
    }

    public function test_withFiltersスコープでプラットフォームフィルタリングができる()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $platform1 = Platform::create([
            'name' => 'Platform1',
            'base_url' => $this->faker()->url(),
        ]);

        $platform2 = Platform::create([
            'name' => 'Platform2',
            'base_url' => $this->faker()->url(),
        ]);

        Article::create([
            'platform_id' => $platform1->id,
            'company_id' => $company->id,
            'title' => 'Platform1の記事',
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        Article::create([
            'platform_id' => $platform2->id,
            'company_id' => $company->id,
            'title' => 'Platform2の記事',
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        $filters = ['platform_id' => $platform1->id];
        $result = Article::withFilters($filters)->get();

        $this->assertCount(1, $result);
        $this->assertEquals($platform1->id, $result->first()->platform_id);
    }

    public function test_withFiltersスコープで日付範囲フィルタリングができる()
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
            'title' => '新しい記事',
            'published_at' => '2024-01-15 00:00:00',
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => '古い記事',
            'published_at' => '2023-12-15 00:00:00',
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        $filters = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ];
        $result = Article::withFilters($filters)->get();

        $this->assertCount(1, $result);
        $this->assertEquals('新しい記事', $result->first()->title);
    }

    public function test_withFiltersスコープで複数フィルタを組み合わせできる()
    {
        $company1 = Company::create([
            'name' => 'Target Company',
            'domain' => $this->faker()->domainName(),
        ]);

        $company2 = Company::create([
            'name' => 'Other Company',
            'domain' => $this->faker()->domainName(),
        ]);

        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company1->id,
            'title' => 'Laravel マッチする記事',
            'published_at' => '2024-01-15 00:00:00',
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company2->id,
            'title' => 'Laravel 違う企業の記事',
            'published_at' => '2024-01-15 00:00:00',
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company1->id,
            'title' => 'React 違うタイトルの記事',
            'published_at' => '2024-01-15 00:00:00',
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        $filters = [
            'search' => 'Laravel',
            'company_id' => $company1->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ];
        $result = Article::withFilters($filters)->get();

        $this->assertCount(1, $result);
        $this->assertEquals('Laravel マッチする記事', $result->first()->title);
    }

    public function test_withSortスコープでpublished_atソートができる()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        $article1 = Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => '新しい記事',
            'published_at' => '2024-01-20 00:00:00',
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        $article2 = Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => '古い記事',
            'published_at' => '2024-01-10 00:00:00',
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        $result = Article::withSort('published_at', 'desc')->get();

        $this->assertEquals($article1->id, $result->first()->id);
        $this->assertEquals($article2->id, $result->last()->id);
    }

    public function test_withSortスコープでlikes_countソートができる()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        $article1 = Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => '人気記事',
            'likes_count' => 100,
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        $article2 = Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => '普通の記事',
            'likes_count' => 50,
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        $result = Article::withSort('likes_count', 'desc')->get();

        $this->assertEquals($article1->id, $result->first()->id);
        $this->assertEquals($article2->id, $result->last()->id);
    }

    public function test_withSortスコープでbookmark_countソートができる()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        $article1 = Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => 'ブックマーク多い記事',
            'bookmark_count' => 200,
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        $article2 = Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => 'ブックマーク少ない記事',
            'bookmark_count' => 50,
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        $result = Article::withSort('bookmark_count', 'desc')->get();

        $this->assertEquals($article1->id, $result->first()->id);
        $this->assertEquals($article2->id, $result->last()->id);
    }

    public function test_withSortスコープで無効なカラム指定時はデフォルトソートになる()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        $article1 = Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => '新しい記事',
            'published_at' => '2024-01-20 00:00:00',
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        $article2 = Article::create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
            'title' => '古い記事',
            'published_at' => '2024-01-10 00:00:00',
            'url' => $this->faker()->url(),
            'scraped_at' => now(),
        ]);

        $result = Article::withSort('invalid_column', 'desc')->get();

        // デフォルトはpublished_at descなので新しい記事が最初に来る
        $this->assertEquals($article1->id, $result->first()->id);
    }

    public function test_withFiltersスコープで空のフィルタは影響しない()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        Article::factory()->count(3)->create([
            'platform_id' => $platform->id,
            'company_id' => $company->id,
        ]);

        $filters = [
            'search' => '',
            'company_id' => null,
            'platform_id' => '',
            'start_date' => null,
            'end_date' => '',
        ];
        $result = Article::withFilters($filters)->get();

        $this->assertCount(3, $result);
    }
}
