<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\CompanyResource;
use App\Models\Article;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CompanyResourceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->request = Request::create('/test');
    }

    public function test_基本的な企業情報が正しく変換される(): void
    {
        $resource = new CompanyResource($this->company);
        $result = $resource->toArray($this->request);

        $this->assertEquals($this->company->id, $result['id']);
        $this->assertEquals($this->company->name, $result['name']);
        $this->assertEquals($this->company->domain, $result['domain']);
        $this->assertEquals($this->company->description, $result['description']);
        $this->assertEquals($this->company->logo_url, $result['logo_url']);
        $this->assertEquals($this->company->website_url, $result['website_url']);
        $this->assertEquals($this->company->is_active, $result['is_active']);
        $this->assertEquals($this->company->created_at, $result['created_at']);
        $this->assertEquals($this->company->updated_at, $result['updated_at']);
    }

    public function test_ランキング情報がない場合は空の配列が返される(): void
    {
        $resource = new CompanyResource($this->company);
        $result = $resource->toArray($this->request);

        $this->assertIsArray($result['current_rankings']);
        $this->assertEmpty($result['current_rankings']);
        $this->assertIsArray($result['ranking_history']);
        $this->assertEmpty($result['ranking_history']);
    }

    public function test_記事情報がロードされていない場合の処理(): void
    {
        $resource = new CompanyResource($this->company);
        $result = $resource->toArray($this->request);

        $this->assertInstanceOf(\Illuminate\Http\Resources\Json\AnonymousResourceCollection::class, $result['recent_articles']);
        $this->assertEquals(0, $result['total_articles']);
    }

    public function test_記事情報がロードされている場合の処理(): void
    {
        $articles = Article::factory(3)->create(['company_id' => $this->company->id]);
        $this->company->load('articles');

        $resource = new CompanyResource($this->company);
        $result = $resource->toArray($this->request);

        $this->assertInstanceOf(\Illuminate\Http\Resources\Json\AnonymousResourceCollection::class, $result['recent_articles']);
        $this->assertEquals(3, $result['total_articles']);
    }

    public function test_match_scoreが設定されている場合に含まれる(): void
    {
        $this->company->match_score = 0.85;
        $resource = new CompanyResource($this->company);
        $result = $resource->toArray($this->request);

        $this->assertArrayHasKey('match_score', $result);
        $this->assertEquals(0.85, $result['match_score']);
    }

    public function test_match_scoreが設定されていない場合は含まれない(): void
    {
        $resource = new CompanyResource($this->company);
        $result = $resource->toArray($this->request);

        $this->assertArrayHasKey('match_score', $result);
        $this->assertInstanceOf(\Illuminate\Http\Resources\MissingValue::class, $result['match_score']);
    }

    public function test_コンストラクタでcurrent_rankingsが設定される(): void
    {
        $rankings = ['weekly' => (object) [
            'rank_position' => 5,
            'total_score' => 250.5,
            'article_count' => 10,
            'total_bookmarks' => 500,
            'calculated_at' => now(),
        ]];

        $resource = new CompanyResource($this->company, $rankings);
        $result = $resource->toArray($this->request);

        $this->assertNotEmpty($result['current_rankings']);
        $this->assertEquals('weekly', $result['current_rankings'][0]['period']);
        $this->assertEquals(5, $result['current_rankings'][0]['rank_position']);
        $this->assertEquals(250.5, $result['current_rankings'][0]['total_score']);
    }

    public function test_format_current_rankings_正常なランキングデータの変換(): void
    {
        $rankings = [
            'weekly' => (object) [
                'rank_position' => 1,
                'total_score' => 999.99,
                'article_count' => 25,
                'total_bookmarks' => 5000,
                'calculated_at' => now(),
            ],
            'monthly' => (object) [
                'rank_position' => 3,
                'total_score' => 750.0,
                'article_count' => 15,
                'total_bookmarks' => 3000,
                'calculated_at' => now(),
            ],
        ];

        $resource = new CompanyResource($this->company, $rankings);
        $result = $resource->toArray($this->request);

        $this->assertCount(2, $result['current_rankings']);

        $weeklyRanking = collect($result['current_rankings'])
            ->firstWhere('period', 'weekly');
        $this->assertEquals(1, $weeklyRanking['rank_position']);
        $this->assertEquals(999.99, $weeklyRanking['total_score']);
        $this->assertEquals(25, $weeklyRanking['article_count']);
        $this->assertEquals(5000, $weeklyRanking['total_bookmarks']);

        $monthlyRanking = collect($result['current_rankings'])
            ->firstWhere('period', 'monthly');
        $this->assertEquals(3, $monthlyRanking['rank_position']);
        $this->assertEquals(750.0, $monthlyRanking['total_score']);
    }

    public function test_format_current_rankings_nullランキングの処理(): void
    {
        $rankings = [
            'weekly' => null,
            'monthly' => (object) [
                'rank_position' => 2,
                'total_score' => 500.0,
                'article_count' => 8,
                'total_bookmarks' => 1500,
                'calculated_at' => now(),
            ],
        ];

        $resource = new CompanyResource($this->company, $rankings);
        $result = $resource->toArray($this->request);

        $this->assertCount(1, $result['current_rankings']);
        $this->assertEquals('monthly', $result['current_rankings'][0]['period']);
    }

    public function test_format_current_rankings_非オブジェクトランキングの処理(): void
    {
        $rankings = [
            'weekly' => 'invalid_data',
            'monthly' => ['invalid_array'],
            'yearly' => (object) [
                'rank_position' => 10,
                'total_score' => 100.0,
                'article_count' => 3,
                'total_bookmarks' => 300,
                'calculated_at' => now(),
            ],
        ];

        $resource = new CompanyResource($this->company, $rankings);
        $result = $resource->toArray($this->request);

        $this->assertCount(1, $result['current_rankings']);
        $this->assertEquals('yearly', $result['current_rankings'][0]['period']);
    }

    public function test_format_current_rankings_配列でないランキングの処理(): void
    {
        $rankings = 'not_an_array';

        $resource = new CompanyResource($this->company, $rankings);
        $result = $resource->toArray($this->request);

        $this->assertIsArray($result['current_rankings']);
        $this->assertEmpty($result['current_rankings']);
    }

    public function test_format_ranking_history_正常なランキング履歴の変換(): void
    {
        $rankings = [
            'weekly' => (object) [
                'rank_position' => 2,
                'total_score' => 800.0,
                'article_count' => 15,
                'total_bookmarks' => 2000,
                'calculated_at' => now()->subDays(1),
            ],
            'monthly' => (object) [
                'rank_position' => 5,
                'total_score' => 600.0,
                'article_count' => 10,
                'total_bookmarks' => 1500,
                'calculated_at' => now()->subDays(7),
            ],
        ];

        $resource = new CompanyResource($this->company, $rankings);
        $result = $resource->toArray($this->request);

        $this->assertCount(2, $result['ranking_history']);

        foreach ($result['ranking_history'] as $history) {
            $this->assertArrayHasKey('date', $history);
            $this->assertArrayHasKey('rank', $history);
            $this->assertArrayHasKey('influence_score', $history);
            $this->assertIsFloat($history['influence_score']);
        }
    }

    public function test_format_ranking_history_calculated_atがnullの場合の処理(): void
    {
        $rankings = [
            'weekly' => (object) [
                'rank_position' => 1,
                'total_score' => 900.0,
                'article_count' => 20,
                'total_bookmarks' => 3000,
                'calculated_at' => null,
            ],
        ];

        $resource = new CompanyResource($this->company, $rankings);
        $result = $resource->toArray($this->request);

        $this->assertCount(1, $result['ranking_history']);
        $this->assertEquals(now()->format('Y-m-d'), $result['ranking_history'][0]['date']);
        $this->assertEquals(1, $result['ranking_history'][0]['rank']);
        $this->assertEquals(900.0, $result['ranking_history'][0]['influence_score']);
    }

    public function test_format_ranking_history_空のランキング配列の処理(): void
    {
        $rankings = [];

        $resource = new CompanyResource($this->company, $rankings);
        $result = $resource->toArray($this->request);

        $this->assertIsArray($result['ranking_history']);
        $this->assertEmpty($result['ranking_history']);
    }

    public function test_リソースのフィールド完全性チェック(): void
    {
        $expectedFields = [
            'id',
            'name',
            'domain',
            'description',
            'logo_url',
            'website_url',
            'is_active',
            'current_rankings',
            'total_articles',
            'ranking_history',
            'created_at',
            'updated_at',
        ];

        $resource = new CompanyResource($this->company);
        $result = $resource->toArray($this->request);

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $result, "フィールド '{$field}' が存在しません");
        }
    }

    public function test_スコア値の型変換が正しく行われる(): void
    {
        $rankings = [
            'weekly' => (object) [
                'rank_position' => 1,
                'total_score' => '500.75',
                'article_count' => 10,
                'total_bookmarks' => 1000,
                'calculated_at' => now(),
            ],
        ];

        $resource = new CompanyResource($this->company, $rankings);
        $result = $resource->toArray($this->request);

        $this->assertIsFloat($result['current_rankings'][0]['total_score']);
        $this->assertEquals(500.75, $result['current_rankings'][0]['total_score']);

        $this->assertIsFloat($result['ranking_history'][0]['influence_score']);
        $this->assertEquals(500.75, $result['ranking_history'][0]['influence_score']);
    }

    public function test_大量ランキングデータでのパフォーマンス(): void
    {
        $rankings = [];
        for ($i = 1; $i <= 100; $i++) {
            $rankings["period_{$i}"] = (object) [
                'rank_position' => $i,
                'total_score' => rand(1, 1000) + rand(0, 99) / 100,
                'article_count' => rand(1, 50),
                'total_bookmarks' => rand(100, 10000),
                'calculated_at' => now()->subDays(rand(1, 365)),
            ];
        }

        $startTime = microtime(true);
        $resource = new CompanyResource($this->company, $rankings);
        $result = $resource->toArray($this->request);
        $endTime = microtime(true);

        $this->assertCount(100, $result['current_rankings']);
        $this->assertCount(100, $result['ranking_history']);
        $this->assertLessThan(1.0, $endTime - $startTime, 'パフォーマンスが期待値を下回っています');
    }
}
