<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\SearchController;
use App\Models\Article;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    private SearchController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new SearchController;
    }

    /**
     * @test
     */
    public function 企業の関連度スコア計算が正常に動作する()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'domain' => 'test-company.com',
            'description' => 'テスト用企業です',
        ]);

        $query = 'Test';

        // プライベートメソッドを実行するためのリフレクション
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $company, $query);

        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(2.0, $score);
    }

    /**
     * @test
     */
    public function 記事の関連度スコア計算が正常に動作する()
    {
        $article = Article::factory()->create([
            'title' => 'Laravel テストに関する記事',
            'author_name' => 'test_author',
            'bookmark_count' => 50,
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $query = 'Laravel';

        // プライベートメソッドを実行するためのリフレクション
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, $query);

        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(2.0, $score);
    }

    /**
     * @test
     */
    public function 企業名完全一致の場合最高スコアが返される()
    {
        $company = Company::factory()->create([
            'name' => 'TestExact',
            'domain' => 'testexact.com',
            'description' => 'テスト用企業です',
        ]);

        $query = 'TestExact';

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $company, $query);

        $this->assertGreaterThanOrEqual(1.0, $score);
    }

    /**
     * @test
     */
    public function 記事のブックマーク数が多いほど高スコアが返される()
    {
        $highBookmarkArticle = Article::factory()->create([
            'title' => 'Laravel テスト記事',
            'bookmark_count' => 150,
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $lowBookmarkArticle = Article::factory()->create([
            'title' => 'Laravel テスト記事',
            'bookmark_count' => 5,
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $query = 'Laravel';

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $highScore = $method->invoke($this->controller, $highBookmarkArticle, $query);
        $lowScore = $method->invoke($this->controller, $lowBookmarkArticle, $query);

        $this->assertGreaterThan($lowScore, $highScore);
    }

    /**
     * @test
     */
    public function 新しい記事ほど高スコアが返される()
    {
        $recentArticle = Article::factory()->create([
            'title' => 'Laravel テスト記事',
            'bookmark_count' => 50,
            'published_at' => Carbon::now()->subDays(3),
        ]);

        $oldArticle = Article::factory()->create([
            'title' => 'Laravel テスト記事',
            'bookmark_count' => 50,
            'published_at' => Carbon::now()->subDays(150),
        ]);

        $query = 'Laravel';

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $recentScore = $method->invoke($this->controller, $recentArticle, $query);
        $oldScore = $method->invoke($this->controller, $oldArticle, $query);

        $this->assertGreaterThan($oldScore, $recentScore);
    }

    /**
     * @test
     */
    public function 企業のドメイン一致でスコアが加算される()
    {
        $company = Company::factory()->create([
            'name' => 'Sample Company',
            'domain' => 'test-company.com',
            'description' => 'サンプル企業です',
        ]);

        $query = 'test';

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $company, $query);

        $this->assertGreaterThan(0, $score);
    }

    /**
     * @test
     */
    public function 記事の著者名一致でスコアが加算される()
    {
        $article = Article::factory()->create([
            'title' => 'サンプル記事',
            'author_name' => 'test_author',
            'bookmark_count' => 50,
            'published_at' => Carbon::now()->subDays(5),
        ]);

        $query = 'test';

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('calculateArticleRelevanceScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->controller, $article, $query);

        $this->assertGreaterThan(0, $score);
    }
}
