<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\Api\CompanyRankingController;
use App\Services\CompanyRankingHistoryService;
use App\Services\CompanyRankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class CompanyRankingControllerTest extends TestCase
{
    /** @var \Mockery\MockInterface&CompanyRankingService */
    private $rankingService;

    /** @var \Mockery\MockInterface&CompanyRankingHistoryService */
    private $historyService;

    private $controller;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var \Mockery\MockInterface&CompanyRankingService $rankingService */
        $rankingService = Mockery::mock(CompanyRankingService::class);
        /** @var \Mockery\MockInterface&CompanyRankingHistoryService $historyService */
        $historyService = Mockery::mock(CompanyRankingHistoryService::class);

        $this->rankingService = $rankingService;
        $this->historyService = $historyService;
        $this->controller = new CompanyRankingController($this->rankingService, $this->historyService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_有効な期間でランキングを期間別に取得する()
    {
        $request = new Request(['page' => 1, 'per_page' => 10]);
        $mockRankings = [
            (object) [
                'rank_position' => 1,
                'company_name' => 'Test Company',
                'domain' => 'test.com',
                'total_score' => 100.0,
                'article_count' => 10,
                'total_bookmarks' => 500,
                'period_start' => '2024-01-01',
                'period_end' => '2024-12-31',
                'calculated_at' => '2024-12-31 23:59:59',
            ],
        ];

        $this->rankingService->shouldReceive('getRankingForPeriod')
            ->with('1m', 100)
            ->andReturn($mockRankings);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $time, $callback) {
                return $callback();
            });

        $response = $this->controller->index($request, '1m');

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
    }

    public function test_無効な期間でランキング取得時にエラーが返される()
    {
        $request = new Request;
        $response = $this->controller->index($request, 'invalid');

        $this->assertEquals(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function test_有効なパラメータでトップランキングを取得する()
    {
        $request = new Request;
        $mockRankings = [
            (object) [
                'rank_position' => 1,
                'company_name' => 'Test Company',
                'domain' => 'test.com',
                'total_score' => 100.0,
                'article_count' => 10,
                'total_bookmarks' => 500,
                'period_start' => '2024-01-01',
                'period_end' => '2024-12-31',
                'calculated_at' => '2024-12-31 23:59:59',
            ],
        ];

        $this->rankingService->shouldReceive('getRankingForPeriod')
            ->with('1m', 10)
            ->andReturn($mockRankings);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $time, $callback) {
                return $callback();
            });

        $response = $this->controller->top($request, '1m', 10);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
    }

    public function test_無効なリミットでトップランキング取得時にエラーが返される()
    {
        $request = new Request;
        $response = $this->controller->top($request, '1m', 150);

        $this->assertEquals(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function test_有効な企業idで企業ランキングを取得する()
    {
        $request = new Request(['include_history' => false]);
        $mockRankings = [
            '1m' => (object) [
                'rank_position' => 1,
                'total_score' => 100.0,
                'article_count' => 10,
                'total_bookmarks' => 500,
                'period_start' => '2024-01-01',
                'period_end' => '2024-12-31',
                'calculated_at' => '2024-12-31 23:59:59',
            ],
        ];

        $this->rankingService->shouldReceive('getCompanyRankings')
            ->with(1)
            ->andReturn($mockRankings);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $time, $callback) {
                return $callback();
            });

        // バリデーションをスキップするためのモック
        \Validator::shouldReceive('make')
            ->andReturn(\Mockery::mock(\Illuminate\Validation\Validator::class, function ($mock) {
                $mock->shouldReceive('fails')->andReturn(false);
            }));

        $response = $this->controller->company($request, 1);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals(1, $responseData['data']['company_id']);
    }

    public function test_ランキング統計情報を取得する()
    {
        $mockStats = [
            '1m' => [
                'total_companies' => 100,
                'average_score' => 50.0,
                'max_score' => 100.0,
                'min_score' => 10.0,
                'total_articles' => 1000,
                'total_bookmarks' => 50000,
                'last_calculated' => '2024-12-31 23:59:59',
            ],
        ];

        $this->rankingService->shouldReceive('getRankingStatistics')
            ->andReturn($mockStats);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $time, $callback) {
                return $callback();
            });

        $response = $this->controller->statistics();

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
    }

    public function test_有効な期間でランキング上昇企業を取得する()
    {
        $request = new Request(['limit' => 10]);
        $mockRisers = [
            (object) [
                'company_name' => 'Rising Company',
                'domain' => 'rising.com',
                'current_rank' => 5,
                'previous_rank' => 10,
                'rank_change' => 5,
                'calculated_at' => '2024-12-31 23:59:59',
            ],
        ];

        $this->historyService->shouldReceive('getTopRankingRisers')
            ->with('1m', 10)
            ->andReturn($mockRisers);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $time, $callback) {
                return $callback();
            });

        $response = $this->controller->risers($request, '1m');

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
    }

    public function test_有効な期間でランキング下降企業を取得する()
    {
        $request = new Request(['limit' => 10]);
        $mockFallers = [
            (object) [
                'company_name' => 'Falling Company',
                'domain' => 'falling.com',
                'current_rank' => 10,
                'previous_rank' => 5,
                'rank_change' => -5,
                'calculated_at' => '2024-12-31 23:59:59',
            ],
        ];

        $this->historyService->shouldReceive('getTopRankingFallers')
            ->with('1m', 10)
            ->andReturn($mockFallers);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $time, $callback) {
                return $callback();
            });

        $response = $this->controller->fallers($request, '1m');

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
    }

    public function test_有効な期間でランキング変動統計を取得する()
    {
        $request = new Request;
        $mockStats = [
            'total_companies' => 100,
            'rising_companies' => 30,
            'falling_companies' => 40,
            'unchanged_companies' => 30,
            'average_change' => 0.5,
            'max_rise' => 10,
            'max_fall' => 8,
            'calculated_at' => '2024-12-31 23:59:59',
        ];

        $this->historyService->shouldReceive('getRankingChangeStatistics')
            ->with('1m')
            ->andReturn($mockStats);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $time, $callback) {
                return $callback();
            });

        $response = $this->controller->changeStatistics($request, '1m');

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
    }

    public function test_期間タイプ一覧を取得する()
    {
        $response = $this->controller->periods();

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertIsArray($responseData['data']);
        $this->assertContains('1m', $responseData['data']);
        $this->assertContains('1y', $responseData['data']);
    }

    public function test_空のランキングデータで正常にレスポンスが返される()
    {
        $request = new Request(['page' => 1, 'per_page' => 10]);

        $this->rankingService->shouldReceive('getRankingForPeriod')
            ->with('1m', 100)
            ->andReturn([]);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $time, $callback) {
                return $callback();
            });

        $response = $this->controller->index($request, '1m');

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
        $this->assertEmpty($responseData['data']);
        $this->assertEquals(0, $responseData['meta']['total']);
    }

    public function test_無効な企業idで企業ランキング取得時にエラーが返される()
    {
        $request = new Request(['include_history' => false]);

        // バリデーションをモック
        \Validator::shouldReceive('make')
            ->andReturn(\Mockery::mock(\Illuminate\Validation\Validator::class, function ($mock) {
                $mock->shouldReceive('fails')->andReturn(true);
                $mock->shouldReceive('errors')->andReturn(new \Illuminate\Support\MessageBag(['company_id' => ['The selected company id is invalid.']]));
            }));

        $response = $this->controller->company($request, 999999);

        $this->assertEquals(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Invalid company ID', $responseData['error']);
    }

    public function test_履歴を含む企業ランキングを取得する()
    {
        $request = new Request(['include_history' => true, 'history_days' => 30]);
        $mockRankings = [
            '1m' => (object) [
                'rank_position' => 1,
                'total_score' => 100.0,
                'article_count' => 10,
                'total_bookmarks' => 500,
                'period_start' => '2024-01-01',
                'period_end' => '2024-12-31',
                'calculated_at' => '2024-12-31 23:59:59',
            ],
        ];

        $mockHistory = [
            [
                'rank_position' => 1,
                'total_score' => 100.0,
                'calculated_at' => '2024-12-31',
            ],
            [
                'rank_position' => 2,
                'total_score' => 90.0,
                'calculated_at' => '2024-12-30',
            ],
        ];

        $this->rankingService->shouldReceive('getCompanyRankings')
            ->with(1)
            ->andReturn($mockRankings);

        $this->historyService->shouldReceive('getCompanyRankingHistory')
            ->andReturn($mockHistory);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $time, $callback) {
                return $callback();
            });

        // バリデーションをスキップするためのモック
        \Validator::shouldReceive('make')
            ->andReturn(\Mockery::mock(\Illuminate\Validation\Validator::class, function ($mock) {
                $mock->shouldReceive('fails')->andReturn(false);
            }));

        $response = $this->controller->company($request, 1);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('history', $responseData['data']);
        $this->assertNotEmpty($responseData['data']['history']);
    }

    public function test_無効な期間でランキング上昇企業取得時にエラーが返される()
    {
        $request = new Request(['limit' => 10]);
        $response = $this->controller->risers($request, 'invalid');

        $this->assertEquals(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function test_無効な期間でランキング下降企業取得時にエラーが返される()
    {
        $request = new Request(['limit' => 10]);
        $response = $this->controller->fallers($request, 'invalid');

        $this->assertEquals(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function test_無効な期間でランキング変動統計取得時にエラーが返される()
    {
        $request = new Request;
        $response = $this->controller->changeStatistics($request, 'invalid');

        $this->assertEquals(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }
}
