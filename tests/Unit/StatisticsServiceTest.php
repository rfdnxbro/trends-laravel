<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\Company;
use App\Services\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private StatisticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StatisticsService;
    }

    #[Test]
    public function test_全体統計を正しく取得する()
    {
        // Arrange
        Cache::forget('overall_statistics');
        $this->seed(\Database\Seeders\PlatformSeeder::class);

        Company::factory()->count(5)->create(['is_active' => true]);
        Company::factory()->count(3)->create(['is_active' => false]);

        Article::factory()->count(10)->create(['engagement_count' => 100]);
        Article::factory()->count(5)->create(['engagement_count' => 200, 'deleted_at' => now()]);

        // Act
        $statistics = $this->service->getOverallStatistics();

        // Assert
        $actualActiveCompanies = Company::where('is_active', true)->count();
        $actualArticles = Article::whereNull('deleted_at')->count();
        $actualEngagements = Article::whereNull('deleted_at')->sum('engagement_count');

        $this->assertEquals($actualActiveCompanies, $statistics['total_companies']);
        $this->assertEquals($actualArticles, $statistics['total_articles']);
        $this->assertEquals($actualEngagements, $statistics['total_engagements']);
        $this->assertArrayHasKey('last_updated', $statistics);

        // 最低限の数値確認
        $this->assertGreaterThanOrEqual(5, $statistics['total_companies']);
        $this->assertGreaterThanOrEqual(10, $statistics['total_articles']);
        $this->assertGreaterThanOrEqual(1000, $statistics['total_engagements']);
    }

    #[Test]
    public function test_統計結果がキャッシュされる()
    {
        // Arrange
        Cache::forget('overall_statistics');
        $this->seed(\Database\Seeders\PlatformSeeder::class);

        Company::factory()->count(3)->create(['is_active' => true]);
        Article::factory()->count(5)->create(['engagement_count' => 50]);

        // Act
        // 1回目の呼び出し
        $statistics1 = $this->service->getOverallStatistics();

        // データを追加
        Company::factory()->count(2)->create(['is_active' => true]);
        Article::factory()->count(3)->create(['engagement_count' => 100]);

        // 2回目の呼び出し（キャッシュから取得）
        $statistics2 = $this->service->getOverallStatistics();

        // Assert - キャッシュから取得したため、値は変わらない
        $this->assertEquals($statistics1['total_companies'], $statistics2['total_companies']);
        $this->assertEquals($statistics1['total_articles'], $statistics2['total_articles']);
        $this->assertEquals($statistics1['total_engagements'], $statistics2['total_engagements']);

        // キャッシュをクリア
        Cache::forget('overall_statistics');

        // 3回目の呼び出し（新しいデータを反映）
        $statistics3 = $this->service->getOverallStatistics();

        // Assert - 新しいデータが反映される（実際の値で確認）
        $actualCompanies = Company::where('is_active', true)->count();
        $actualArticles = Article::whereNull('deleted_at')->count();
        $actualEngagements = Article::whereNull('deleted_at')->sum('engagement_count');

        $this->assertEquals($actualCompanies, $statistics3['total_companies']);
        $this->assertEquals($actualArticles, $statistics3['total_articles']);
        $this->assertEquals($actualEngagements, $statistics3['total_engagements']);

        // データが増えていることを確認
        $this->assertGreaterThan($statistics1['total_companies'], $statistics3['total_companies']);
        $this->assertGreaterThan($statistics1['total_articles'], $statistics3['total_articles']);
    }

    #[Test]
    public function test_削除済み記事を除外する()
    {
        // Arrange
        Cache::forget('overall_statistics');
        $this->seed(\Database\Seeders\PlatformSeeder::class);

        $beforeArticles = Article::whereNull('deleted_at')->count();
        $beforeEngagements = Article::whereNull('deleted_at')->sum('engagement_count');

        Article::factory()->count(5)->create(['engagement_count' => 100, 'deleted_at' => null]);
        Article::factory()->count(3)->create(['engagement_count' => 200, 'deleted_at' => now()]);

        // Act
        $statistics = $this->service->getOverallStatistics();

        // Assert
        $expectedArticles = $beforeArticles + 5;
        $expectedEngagements = $beforeEngagements + (5 * 100);

        $this->assertEquals($expectedArticles, $statistics['total_articles']);
        $this->assertEquals($expectedEngagements, $statistics['total_engagements']); // 削除済みを除外
    }

    #[Test]
    public function test_非アクティブ企業を除外する()
    {
        // Arrange
        Cache::forget('overall_statistics');
        $this->seed(\Database\Seeders\PlatformSeeder::class);

        $beforeActive = Company::where('is_active', true)->count();

        Company::factory()->count(7)->create(['is_active' => true]);
        Company::factory()->count(4)->create(['is_active' => false]);

        // Act
        $statistics = $this->service->getOverallStatistics();

        // Assert
        $expectedCompanies = $beforeActive + 7;
        $this->assertEquals($expectedCompanies, $statistics['total_companies']); // 非アクティブを除外
    }
}
