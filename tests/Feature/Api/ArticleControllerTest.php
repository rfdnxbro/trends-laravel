<?php

namespace Tests\Feature\Api;

use App\Models\Article;
use App\Models\Company;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_記事一覧が取得できること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->count(5)->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $response = $this->getJson('/api/articles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'url',
                        'author_name',
                        'published_at',
                        'bookmark_count',
                        'company' => [
                            'id',
                            'name',
                            'domain',
                            'logo_url',
                        ],
                        'platform' => [
                            'id',
                            'name',
                        ],
                    ],
                ],
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);
    }

    public function test_企業_i_dでフィルタリングできること()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->count(3)->create([
            'company_id' => $company1->id,
            'platform_id' => $platform->id,
        ]);

        Article::factory()->count(2)->create([
            'company_id' => $company2->id,
            'platform_id' => $platform->id,
        ]);

        $response = $this->getJson("/api/articles?company_id={$company1->id}");

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));

        foreach ($response->json('data') as $article) {
            $this->assertEquals($company1->id, $article['company']['id']);
        }
    }

    public function test_プラットフォーム_i_dでフィルタリングできること()
    {
        $company = Company::factory()->create();
        $platform1 = Platform::factory()->create();
        $platform2 = Platform::factory()->create();

        Article::factory()->count(3)->create([
            'company_id' => $company->id,
            'platform_id' => $platform1->id,
        ]);

        Article::factory()->count(2)->create([
            'company_id' => $company->id,
            'platform_id' => $platform2->id,
        ]);

        $response = $this->getJson("/api/articles?platform_id={$platform1->id}");

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));

        foreach ($response->json('data') as $article) {
            $this->assertEquals($platform1->id, $article['platform']['id']);
        }
    }

    public function test_記事が日付順でソートされていること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        $article1 = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => now()->subDays(3),
        ]);

        $article2 = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => now()->subDays(1),
        ]);

        $article3 = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
            'published_at' => now()->subDays(2),
        ]);

        $response = $this->getJson('/api/articles');

        $response->assertStatus(200);
        $articles = $response->json('data');

        $this->assertEquals($article2->id, $articles[0]['id']);
        $this->assertEquals($article3->id, $articles[1]['id']);
        $this->assertEquals($article1->id, $articles[2]['id']);
    }

    public function test_ページネーションが正しく動作すること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->count(25)->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $response = $this->getJson('/api/articles');

        $response->assertStatus(200);
        $this->assertCount(20, $response->json('data'));
        $this->assertEquals(25, $response->json('total'));
        $this->assertEquals(2, $response->json('last_page'));
    }
}
