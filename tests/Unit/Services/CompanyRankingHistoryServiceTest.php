<?php

namespace Tests\Unit\Services;

use App\Services\CompanyRankingHistoryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CompanyRankingHistoryServiceTest extends TestCase
{
    private CompanyRankingHistoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CompanyRankingHistoryService;
    }

    public function test_record_ranking_historyでログが記録される()
    {
        $periodType = '1d';
        $calculatedAt = Carbon::now();

        Log::shouldReceive('info')
            ->with('Recording ranking history', [
                'period_type' => $periodType,
                'calculated_at' => $calculatedAt->toDateTimeString(),
            ])
            ->once();

        Log::shouldReceive('info')
            ->with('Ranking history recorded', [
                'period_type' => $periodType,
                'changes_count' => 0,
            ])
            ->once();

        // Mock DB queries - 空のデータを返すので、transactionは呼ばれない
        DB::shouldReceive('table')
            ->with('company_rankings')
            ->andReturn(\Mockery::mock(\Illuminate\Database\Query\Builder::class, function ($mock) {
                $mock->shouldReceive('where')->andReturnSelf();
                $mock->shouldReceive('orderBy')->andReturnSelf();
                $mock->shouldReceive('get')->andReturn(collect([]));
                $mock->shouldReceive('max')->andReturn(null);
            }));

        // データが空なのでDB::transactionは呼ばれない
        // DB::shouldReceive('beginTransaction')->never();
        // DB::shouldReceive('commit')->never();

        $result = $this->service->recordRankingHistory($periodType, $calculatedAt);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_company_ranking_historyが正しく動作する()
    {
        $companyId = 1;
        $periodType = '1d';
        $days = 30;

        // Mock DB query
        DB::shouldReceive('table')
            ->with('company_ranking_history as crh')
            ->andReturn(\Mockery::mock(\Illuminate\Database\Query\Builder::class, function ($mock) use ($companyId) {
                $mock->shouldReceive('join')->andReturnSelf();
                $mock->shouldReceive('select')->andReturnSelf();
                $mock->shouldReceive('where')->with('crh.company_id', $companyId)->andReturnSelf();
                $mock->shouldReceive('where')->with('crh.period_type', '1d')->andReturnSelf();
                $mock->shouldReceive('whereBetween')->andReturnSelf();
                $mock->shouldReceive('orderBy')->andReturnSelf();
                $mock->shouldReceive('get')->andReturn(collect([
                    (object) [
                        'current_rank' => 1,
                        'previous_rank' => 2,
                        'rank_change' => 1,
                        'calculated_at' => '2023-01-01 00:00:00',
                        'company_name' => 'Test Company',
                    ],
                ]));
            }));

        $result = $this->service->getCompanyRankingHistory($companyId, $periodType, $days);

        $this->assertIsArray($result);
    }

    public function test_cleanup_old_historyが正しく動作する()
    {
        // Mock DB query
        DB::shouldReceive('table')
            ->with('company_ranking_history')
            ->andReturn(\Mockery::mock(\Illuminate\Database\Query\Builder::class, function ($mock) {
                $mock->shouldReceive('where')->andReturnSelf();
                $mock->shouldReceive('delete')->andReturn(10);
            }));

        Log::shouldReceive('info')
            ->with('Old ranking history cleaned up', \Mockery::type('array'))
            ->once();

        $result = $this->service->cleanupOldHistory();

        $this->assertEquals(10, $result);
    }

    public function test_get_ranking_change_statisticsが正しく動作する()
    {
        $periodType = '1d';

        // Mock DB query for max calculated_at
        DB::shouldReceive('table')
            ->with('company_ranking_history')
            ->andReturn(\Mockery::mock(\Illuminate\Database\Query\Builder::class, function ($mock) {
                $mock->shouldReceive('where')->andReturnSelf();
                $mock->shouldReceive('max')->andReturn('2023-01-01 00:00:00');
                $mock->shouldReceive('selectRaw')->andReturnSelf();
                $mock->shouldReceive('first')->andReturn((object) [
                    'total_companies' => 10,
                    'rising_companies' => 3,
                    'falling_companies' => 2,
                    'unchanged_companies' => 5,
                    'avg_change' => 0.5,
                    'max_rise' => 5,
                    'max_fall' => -3,
                ]);
            }));

        $result = $this->service->getRankingChangeStatistics($periodType);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_companies', $result);
        $this->assertArrayHasKey('rising_companies', $result);
        $this->assertArrayHasKey('falling_companies', $result);
    }

    public function test_get_top_ranking_risersが正しく動作する()
    {
        $periodType = '1d';
        $limit = 10;

        // Mock DB query
        DB::shouldReceive('table')
            ->with('company_ranking_history')
            ->andReturn(\Mockery::mock(\Illuminate\Database\Query\Builder::class, function ($mock) {
                $mock->shouldReceive('where')->andReturnSelf();
                $mock->shouldReceive('max')->andReturn('2023-01-01 00:00:00');
            }));

        DB::shouldReceive('table')
            ->with('company_ranking_history as crh')
            ->andReturn(\Mockery::mock(\Illuminate\Database\Query\Builder::class, function ($mock) use ($periodType, $limit) {
                $mock->shouldReceive('join')->andReturnSelf();
                $mock->shouldReceive('select')->andReturnSelf();
                $mock->shouldReceive('where')->with('crh.period_type', $periodType)->andReturnSelf();
                $mock->shouldReceive('where')->andReturnSelf();
                $mock->shouldReceive('orderBy')->andReturnSelf();
                $mock->shouldReceive('limit')->with($limit)->andReturnSelf();
                $mock->shouldReceive('get')->andReturn(collect([
                    (object) [
                        'company_name' => 'Test Company',
                        'domain' => 'test.com',
                        'current_rank' => 1,
                        'previous_rank' => 5,
                        'rank_change' => 4,
                        'calculated_at' => '2023-01-01 00:00:00',
                    ],
                ]));
            }));

        $result = $this->service->getTopRankingRisers($periodType, $limit);

        $this->assertIsArray($result);
    }

    public function test_get_top_ranking_fallersが正しく動作する()
    {
        $periodType = '1d';
        $limit = 10;

        // Mock DB query
        DB::shouldReceive('table')
            ->with('company_ranking_history')
            ->andReturn(\Mockery::mock(\Illuminate\Database\Query\Builder::class, function ($mock) {
                $mock->shouldReceive('where')->andReturnSelf();
                $mock->shouldReceive('max')->andReturn('2023-01-01 00:00:00');
            }));

        DB::shouldReceive('table')
            ->with('company_ranking_history as crh')
            ->andReturn(\Mockery::mock(\Illuminate\Database\Query\Builder::class, function ($mock) use ($periodType, $limit) {
                $mock->shouldReceive('join')->andReturnSelf();
                $mock->shouldReceive('select')->andReturnSelf();
                $mock->shouldReceive('where')->with('crh.period_type', $periodType)->andReturnSelf();
                $mock->shouldReceive('where')->andReturnSelf();
                $mock->shouldReceive('orderBy')->andReturnSelf();
                $mock->shouldReceive('limit')->with($limit)->andReturnSelf();
                $mock->shouldReceive('get')->andReturn(collect([
                    (object) [
                        'company_name' => 'Test Company 2',
                        'domain' => 'test2.com',
                        'current_rank' => 10,
                        'previous_rank' => 3,
                        'rank_change' => -7,
                        'calculated_at' => '2023-01-01 00:00:00',
                    ],
                ]));
            }));

        $result = $this->service->getTopRankingFallers($periodType, $limit);

        $this->assertIsArray($result);
    }

    public function test_record_ranking_historyで_d_b例外が発生した場合に例外が発生する()
    {
        $periodType = '1d';
        $calculatedAt = Carbon::now();

        Log::shouldReceive('info')->once();

        // Mock DB queries to throw exception
        DB::shouldReceive('table')
            ->with('company_rankings')
            ->andReturn(\Mockery::mock(\Illuminate\Database\Query\Builder::class, function ($mock) {
                $mock->shouldReceive('where')->andReturnSelf();
                $mock->shouldReceive('orderBy')->andReturnSelf();
                $mock->shouldReceive('get')->andThrow(new \Exception('Database error'));
            }));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');
        $this->service->recordRankingHistory($periodType, $calculatedAt);
    }
}
