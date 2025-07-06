<?php

namespace Tests\Unit\Controllers;

use App\Constants\RankingPeriod;
use App\Http\Controllers\Api\CompanyRankingController;
use App\Services\CompanyRankingService;
use App\Services\CompanyRankingHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

class CompanyRankingControllerTest extends TestCase
{
    private $rankingService;
    private $historyService;
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->rankingService = Mockery::mock(CompanyRankingService::class);
        $this->historyService = Mockery::mock(CompanyRankingHistoryService::class);
        $this->controller = new CompanyRankingController($this->rankingService, $this->historyService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetRankingByPeriodWithValidPeriod()
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
            ]
        ];

        $this->rankingService->shouldReceive('getRankingForPeriod')
            ->with('1m', 100)
            ->andReturn($mockRankings);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $time, $callback) {
                return $callback();
            });

        $response = $this->controller->getRankingByPeriod($request, '1m');

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
    }

    public function testGetRankingByPeriodWithInvalidPeriod()
    {
        $request = new Request();
        $response = $this->controller->getRankingByPeriod($request, 'invalid');

        $this->assertEquals(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testGetTopRankingWithValidParameters()
    {
        $request = new Request();
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
            ]
        ];

        $this->rankingService->shouldReceive('getRankingForPeriod')
            ->with('1m', 10)
            ->andReturn($mockRankings);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $time, $callback) {
                return $callback();
            });

        $response = $this->controller->getTopRanking($request, '1m', 10);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
    }

    public function testGetTopRankingWithInvalidLimit()
    {
        $request = new Request();
        $response = $this->controller->getTopRanking($request, '1m', 150);

        $this->assertEquals(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testGetCompanyRankingWithValidCompanyId()
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
            ]
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

        $response = $this->controller->getCompanyRanking($request, 1);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals(1, $responseData['data']['company_id']);
    }

    public function testGetStatistics()
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
            ]
        ];

        $this->rankingService->shouldReceive('getRankingStatistics')
            ->andReturn($mockStats);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $time, $callback) {
                return $callback();
            });

        $response = $this->controller->getStatistics();

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
    }

    public function testGetRankingRisersWithValidPeriod()
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
            ]
        ];

        $this->historyService->shouldReceive('getTopRankingRisers')
            ->with('1m', 10)
            ->andReturn($mockRisers);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $time, $callback) {
                return $callback();
            });

        $response = $this->controller->getRankingRisers($request, '1m');

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
    }

    public function testGetRankingFallersWithValidPeriod()
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
            ]
        ];

        $this->historyService->shouldReceive('getTopRankingFallers')
            ->with('1m', 10)
            ->andReturn($mockFallers);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $time, $callback) {
                return $callback();
            });

        $response = $this->controller->getRankingFallers($request, '1m');

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
    }

    public function testGetRankingChangeStatisticsWithValidPeriod()
    {
        $request = new Request();
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

        $response = $this->controller->getRankingChangeStatistics($request, '1m');

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
    }

    public function testGetPeriodTypes()
    {
        $response = $this->controller->getPeriodTypes();

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertIsArray($responseData['data']);
        $this->assertContains('1m', $responseData['data']);
        $this->assertContains('1y', $responseData['data']);
    }
}