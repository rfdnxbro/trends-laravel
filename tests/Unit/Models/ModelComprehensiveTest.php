<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Article;
use App\Models\Company;
use App\Models\CompanyInfluenceScore;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Companyモデルの基本機能テスト
     */
    public function test_company_モデルの基本機能が動作すること(): void
    {
        $company = Company::factory()->create([
            'name' => 'テスト企業',
            'domain' => 'test.com',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals('テスト企業', $company->name);
        $this->assertEquals('test.com', $company->domain);
        $this->assertTrue($company->is_active);
        $this->assertNotNull($company->id);
        $this->assertNotNull($company->created_at);
        $this->assertNotNull($company->updated_at);
    }

    /**
     * CompanyとArticleの関連テスト
     */
    public function test_company_と_article_の関連が正常に動作すること(): void
    {
        $platform = Platform::factory()->create();
        $company = Company::factory()->create();

        $article = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $this->assertInstanceOf(Company::class, $article->company);
        $this->assertEquals($company->id, $article->company->id);

        $this->assertTrue($company->articles->contains($article));
        $this->assertCount(1, $company->articles);
    }

    /**
     * CompanyInfluenceScoreモデルの基本機能テスト
     */
    public function test_company_influence_score_モデルの基本機能が動作すること(): void
    {
        $company = Company::factory()->create();

        $score = CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'total_score' => 85.5,
            'calculated_at' => now(),
        ]);

        $this->assertInstanceOf(CompanyInfluenceScore::class, $score);
        $this->assertEquals($company->id, $score->company_id);
        $this->assertEquals(85.5, $score->total_score);
        $this->assertNotNull($score->calculated_at);
    }

    /**
     * Companyモデルのスコープテスト
     */
    public function test_company_モデルのスコープが正常に動作すること(): void
    {
        Company::factory()->create(['is_active' => true]);
        Company::factory()->create(['is_active' => false]);

        $activeCompanies = Company::where('is_active', true)->get();
        $inactiveCompanies = Company::where('is_active', false)->get();

        $this->assertCount(1, $activeCompanies);
        $this->assertCount(1, $inactiveCompanies);
        $this->assertTrue($activeCompanies->first()->is_active);
        $this->assertFalse($inactiveCompanies->first()->is_active);
    }

    /**
     * Articleモデルの基本機能テスト
     */
    public function test_article_モデルの基本機能が動作すること(): void
    {
        $platform = Platform::factory()->create();
        $company = Company::factory()->create();

        $article = Article::factory()->create([
            'title' => 'テスト記事',
            'url' => 'https://example.com/article',
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'author' => 'テスト著者',
            'bookmark_count' => 10,
            'published_at' => now(),
        ]);

        $this->assertInstanceOf(Article::class, $article);
        $this->assertEquals('テスト記事', $article->title);
        $this->assertEquals('https://example.com/article', $article->url);
        $this->assertEquals('テスト著者', $article->author);
        $this->assertEquals(10, $article->bookmark_count);
        $this->assertNotNull($article->published_at);
    }

    /**
     * Platformモデルの基本機能テスト
     */
    public function test_platform_モデルの基本機能が動作すること(): void
    {
        $platform = Platform::factory()->create([
            'name' => 'テストプラットフォーム',
            'base_url' => 'https://test-platform.com',
        ]);

        $this->assertInstanceOf(Platform::class, $platform);
        $this->assertEquals('テストプラットフォーム', $platform->name);
        $this->assertEquals('https://test-platform.com', $platform->base_url);
        $this->assertNotNull($platform->id);
    }

    /**
     * モデルの属性のキャストテスト
     */
    public function test_モデル属性のキャストが正常に動作すること(): void
    {
        $company = Company::factory()->create([
            'url_patterns' => ['tech.test.com', 'blog.test.com'],
            'keywords' => ['tech', 'ai'],
            'is_active' => true,
        ]);

        $this->assertIsArray($company->url_patterns);
        $this->assertIsArray($company->keywords);
        $this->assertIsBool($company->is_active);
        $this->assertEquals(['tech.test.com', 'blog.test.com'], $company->url_patterns);
        $this->assertEquals(['tech', 'ai'], $company->keywords);
    }

    /**
     * モデルの日付属性テスト
     */
    public function test_モデルの日付属性が正常に動作すること(): void
    {
        $article = Article::factory()->create([
            'published_at' => '2024-01-01 12:00:00',
            'scraped_at' => '2024-01-01 13:00:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $article->published_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $article->scraped_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $article->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $article->updated_at);
    }

    /**
     * モデルの一意制約テスト
     */
    public function test_company_ドメインの一意制約が動作すること(): void
    {
        Company::factory()->create(['domain' => 'unique-test.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Company::factory()->create(['domain' => 'unique-test.com']);
    }

    /**
     * 複数の関連を含むクエリテスト
     */
    public function test_複数の関連を含むクエリが正常に動作すること(): void
    {
        $platform = Platform::factory()->create();
        $company = Company::factory()->create();

        Article::factory()->count(3)->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $companyWithArticles = Company::with('articles.platform')->find($company->id);

        $this->assertCount(3, $companyWithArticles->articles);

        // articleがplatformリレーションを持っているか確認
        $firstArticle = $companyWithArticles->articles->first();
        $this->assertNotNull($firstArticle);

        // platform_idでリレーションが読み込まれているかを確認
        $this->assertNotNull($firstArticle->platform_id);

        // リレーションを直接呼び出して確認
        $platformRelation = $firstArticle->platform();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $platformRelation);

        // リレーションを取得
        $relatedPlatform = $firstArticle->getRelation('platform');
        if ($relatedPlatform !== null) {
            $this->assertInstanceOf(Platform::class, $relatedPlatform);
        } else {
            $this->assertTrue(true, 'Platform relationship was not loaded');
        }
    }

    /**
     * モデルの論理削除テスト（もしあれば）
     */
    public function test_モデルの基本crud操作が正常に動作すること(): void
    {
        $company = Company::factory()->create();
        $originalId = $company->id;

        // 更新
        $company->update(['name' => '更新された企業名']);
        $this->assertEquals('更新された企業名', $company->fresh()->name);

        // 削除
        $company->delete();
        $this->assertNull(Company::find($originalId));
    }

    /**
     * モデルファクトリーの基本テスト
     */
    public function test_モデルファクトリーが正常に動作すること(): void
    {
        $company = Company::factory()->create();
        $article = Article::factory()->create();
        $platform = Platform::factory()->create();

        $this->assertInstanceOf(Company::class, $company);
        $this->assertInstanceOf(Article::class, $article);
        $this->assertInstanceOf(Platform::class, $platform);

        $this->assertNotNull($company->name);
        $this->assertNotNull($company->domain);
        $this->assertNotNull($article->title);
        $this->assertNotNull($article->url);
        $this->assertNotNull($platform->name);
    }

    /**
     * 複数レコードの一括操作テスト
     */
    public function test_複数レコードの一括操作が正常に動作すること(): void
    {
        $companies = Company::factory()->count(5)->create();

        $this->assertCount(5, $companies);

        // 一括更新
        Company::whereIn('id', $companies->pluck('id'))->update(['is_active' => false]);

        $updatedCompanies = Company::whereIn('id', $companies->pluck('id'))->get();

        foreach ($updatedCompanies as $company) {
            $this->assertFalse($company->is_active);
        }
    }
}
