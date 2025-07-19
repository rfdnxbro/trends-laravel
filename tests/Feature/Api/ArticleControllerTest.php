<?php

namespace Tests\Feature\Api;

use App\Models\Article;
use App\Models\Company;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_記事一覧が取得できること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->count(5)->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $response = $this->getJson('/api/articles');

        $response->assertStatus(Response::HTTP_OK)
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

    #[Test]
    public function test_企業idでフィルタリングできること()
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

        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(3, $response->json('data'));

        foreach ($response->json('data') as $article) {
            $this->assertEquals($company1->id, $article['company']['id']);
        }
    }

    #[Test]
    public function test_プラットフォームidでフィルタリングできること()
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

        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(3, $response->json('data'));

        foreach ($response->json('data') as $article) {
            $this->assertEquals($platform1->id, $article['platform']['id']);
        }
    }

    #[Test]
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

        $response->assertStatus(Response::HTTP_OK);
        $articles = $response->json('data');

        $this->assertEquals($article2->id, $articles[0]['id']);
        $this->assertEquals($article3->id, $articles[1]['id']);
        $this->assertEquals($article1->id, $articles[2]['id']);
    }

    #[Test]
    public function test_ページネーションが正しく動作すること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->count(25)->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $response = $this->getJson('/api/articles');

        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(20, $response->json('data'));
        $this->assertEquals(25, $response->json('total'));
        $this->assertEquals(2, $response->json('last_page'));
    }

    #[Test]
    public function test_記事詳細が取得できること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        $article = Article::factory()->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $response = $this->getJson("/api/articles/{$article->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
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
            ])
            ->assertJson([
                'id' => $article->id,
                'title' => $article->title,
                'url' => $article->url,
            ]);
    }

    #[Test]
    public function test_存在しない記事id指定時は404が返される()
    {
        $response = $this->getJson('/api/articles/99999');

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'message' => 'Article not found',
            ]);
    }

    #[Test]
    public function test_limitパラメータが正常に動作すること()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->count(10)->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        $response = $this->getJson('/api/articles?limit=5');

        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(5, $response->json('data'));
    }

    #[Test]
    public function test_複数パラメータでのフィルタリングが正常に動作すること()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $platform1 = Platform::factory()->create();
        $platform2 = Platform::factory()->create();

        // company1 + platform1 の記事を3件作成
        Article::factory()->count(3)->create([
            'company_id' => $company1->id,
            'platform_id' => $platform1->id,
        ]);

        // company1 + platform2 の記事を2件作成
        Article::factory()->count(2)->create([
            'company_id' => $company1->id,
            'platform_id' => $platform2->id,
        ]);

        // company2 + platform1 の記事を1件作成
        Article::factory()->create([
            'company_id' => $company2->id,
            'platform_id' => $platform1->id,
        ]);

        $response = $this->getJson("/api/articles?company_id={$company1->id}&platform_id={$platform1->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(3, $response->json('data'));

        foreach ($response->json('data') as $article) {
            $this->assertEquals($company1->id, $article['company']['id']);
            $this->assertEquals($platform1->id, $article['platform']['id']);
        }
    }

    #[Test]
    public function test_記事が存在しない場合は空の配列が返される()
    {
        $response = $this->getJson('/api/articles');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
                'current_page',
                'last_page',
                'per_page',
                'total',
            ])
            ->assertJson([
                'data' => [],
                'total' => 0,
            ]);
    }

    #[Test]
    public function test_無効なパラメータでもエラーにならないこと()
    {
        $company = Company::factory()->create();
        $platform = Platform::factory()->create();

        Article::factory()->count(3)->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        // 存在しないcompany_idを指定
        $response = $this->getJson('/api/articles?company_id=99999');
        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(0, $response->json('data'));

        // 存在しないplatform_idを指定
        $response = $this->getJson('/api/articles?platform_id=99999');
        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(0, $response->json('data'));
    }
}
