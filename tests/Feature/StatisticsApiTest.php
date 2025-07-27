<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class StatisticsApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_全体統計_apiが正しいデータを返す()
    {
        // Arrange
        $this->seed(\Database\Seeders\PlatformSeeder::class);

        Company::factory()->count(10)->create(['is_active' => true]);
        Company::factory()->count(5)->create(['is_active' => false]);

        Article::factory()->count(20)->create(['engagement_count' => 150]);
        Article::factory()->count(8)->create(['engagement_count' => 300, 'deleted_at' => now()]);

        // Act
        $response = $this->getJson('/api/statistics/overall');

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'total_companies',
                    'total_articles',
                    'total_engagements',
                    'last_updated',
                ],
            ]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(10, $data['total_companies']);
        $this->assertGreaterThanOrEqual(20, $data['total_articles']);
        $this->assertGreaterThanOrEqual(3000, $data['total_engagements']);
    }

    #[Test]
    public function test_データがない場合も正常にレスポンスを返す()
    {
        // Act
        $response = $this->getJson('/api/statistics/overall');

        // Assert
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'total_companies',
                    'total_articles',
                    'total_engagements',
                    'last_updated',
                ],
            ])
            ->assertJson([
                'data' => [
                    'total_companies' => 0,
                    'total_articles' => 0,
                    'total_engagements' => 0,
                ],
            ]);
    }

    #[Test]
    public function test_エンゲージメント数がnullの記事も正しく処理される()
    {
        // Arrange
        $this->seed(\Database\Seeders\PlatformSeeder::class);

        $beforeArticles = Article::whereNull('deleted_at')->count();
        $beforeEngagements = Article::whereNull('deleted_at')->sum('engagement_count');

        Article::factory()->count(5)->create(['engagement_count' => 100]);
        Article::factory()->count(3)->create(['engagement_count' => 0]);

        // Act
        $response = $this->getJson('/api/statistics/overall');

        // Assert
        $response->assertStatus(Response::HTTP_OK);

        $data = $response->json('data');
        $this->assertEquals($beforeArticles + 8, $data['total_articles']);
        $this->assertEquals($beforeEngagements + 500, $data['total_engagements']); // 5 * 100 + 3 * 0
    }

    #[Test]
    public function test_レスポンスタイムが適切である()
    {
        // Arrange
        $this->seed(\Database\Seeders\PlatformSeeder::class);

        Company::factory()->count(10)->create(['is_active' => true]);
        Article::factory()->count(100)->create(['engagement_count' => 50]);

        // Act
        $startTime = microtime(true);
        $response = $this->getJson('/api/statistics/overall');
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000; // milliseconds

        // Assert
        $response->assertStatus(Response::HTTP_OK);
        $this->assertLessThan(1000, $responseTime, 'Response time exceeds 1 second');
    }
}
