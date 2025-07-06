<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyRanking;
use App\Services\CompanyRankingHistoryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanyRankingHistoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private CompanyRankingHistoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CompanyRankingHistoryService;
    }

    public function test_record_ranking_history_with_no_previous_ranking(): void
    {
        $company = Company::factory()->create();
        $calculatedAt = Carbon::now();

        // 現在のランキングを作成
        CompanyRanking::create([
            'company_id' => $company->id,
            'ranking_period' => '1w',
            'rank_position' => 1,
            'total_score' => 100.0,
            'article_count' => 10,
            'total_bookmarks' => 20,
            'period_start' => $calculatedAt->copy()->subDays(7),
            'period_end' => $calculatedAt->copy(),
            'calculated_at' => $calculatedAt,
        ]);

        $changes = $this->service->recordRankingHistory('1w', $calculatedAt);

        $this->assertEmpty($changes);
    }

    public function test_record_ranking_history_with_rank_improvement(): void
    {
        $company = Company::factory()->create();
        $previousCalculatedAt = Carbon::now()->subHour();
        $currentCalculatedAt = Carbon::now();

        // 前回のランキングを作成
        CompanyRanking::create([
            'company_id' => $company->id,
            'ranking_period' => '1w',
            'rank_position' => 5,
            'total_score' => 80.0,
            'article_count' => 8,
            'total_bookmarks' => 15,
            'period_start' => $previousCalculatedAt->copy()->subDays(7),
            'period_end' => $previousCalculatedAt->copy(),
            'calculated_at' => $previousCalculatedAt,
        ]);

        // 現在のランキングを作成
        CompanyRanking::create([
            'company_id' => $company->id,
            'ranking_period' => '1w',
            'rank_position' => 3,
            'total_score' => 100.0,
            'article_count' => 10,
            'total_bookmarks' => 20,
            'period_start' => $currentCalculatedAt->copy()->subDays(7),
            'period_end' => $currentCalculatedAt->copy(),
            'calculated_at' => $currentCalculatedAt,
        ]);

        $changes = $this->service->recordRankingHistory('1w', $currentCalculatedAt);

        $this->assertCount(1, $changes);
        $this->assertEquals($company->id, $changes[0]['company_id']);
        $this->assertEquals('1w', $changes[0]['period_type']);
        $this->assertEquals(3, $changes[0]['current_rank']);
        $this->assertEquals(5, $changes[0]['previous_rank']);
        $this->assertEquals(2, $changes[0]['rank_change']); // 5 - 3 = 2 (improvement)
    }

    public function test_record_ranking_history_with_rank_decline(): void
    {
        $company = Company::factory()->create();
        $previousCalculatedAt = Carbon::now()->subHour();
        $currentCalculatedAt = Carbon::now();

        // 前回のランキングを作成
        CompanyRanking::create([
            'company_id' => $company->id,
            'ranking_period' => '1w',
            'rank_position' => 2,
            'total_score' => 120.0,
            'article_count' => 12,
            'total_bookmarks' => 25,
            'period_start' => $previousCalculatedAt->copy()->subDays(7),
            'period_end' => $previousCalculatedAt->copy(),
            'calculated_at' => $previousCalculatedAt,
        ]);

        // 現在のランキングを作成
        CompanyRanking::create([
            'company_id' => $company->id,
            'ranking_period' => '1w',
            'rank_position' => 7,
            'total_score' => 70.0,
            'article_count' => 7,
            'total_bookmarks' => 14,
            'period_start' => $currentCalculatedAt->copy()->subDays(7),
            'period_end' => $currentCalculatedAt->copy(),
            'calculated_at' => $currentCalculatedAt,
        ]);

        $changes = $this->service->recordRankingHistory('1w', $currentCalculatedAt);

        $this->assertCount(1, $changes);
        $this->assertEquals($company->id, $changes[0]['company_id']);
        $this->assertEquals('1w', $changes[0]['period_type']);
        $this->assertEquals(7, $changes[0]['current_rank']);
        $this->assertEquals(2, $changes[0]['previous_rank']);
        $this->assertEquals(-5, $changes[0]['rank_change']); // 2 - 7 = -5 (decline)
    }

    public function test_record_ranking_history_with_rank_unchanged(): void
    {
        $company = Company::factory()->create();
        $previousCalculatedAt = Carbon::now()->subHour();
        $currentCalculatedAt = Carbon::now();

        // 前回のランキングを作成
        CompanyRanking::create([
            'company_id' => $company->id,
            'ranking_period' => '1w',
            'rank_position' => 5,
            'total_score' => 100.0,
            'article_count' => 10,
            'total_bookmarks' => 20,
            'period_start' => $previousCalculatedAt->copy()->subDays(7),
            'period_end' => $previousCalculatedAt->copy(),
            'calculated_at' => $previousCalculatedAt,
        ]);

        // 現在のランキングを作成（順位変動なし）
        CompanyRanking::create([
            'company_id' => $company->id,
            'ranking_period' => '1w',
            'rank_position' => 5,
            'total_score' => 100.0,
            'article_count' => 10,
            'total_bookmarks' => 20,
            'period_start' => $currentCalculatedAt->copy()->subDays(7),
            'period_end' => $currentCalculatedAt->copy(),
            'calculated_at' => $currentCalculatedAt,
        ]);

        $changes = $this->service->recordRankingHistory('1w', $currentCalculatedAt);

        $this->assertCount(1, $changes);
        $this->assertEquals($company->id, $changes[0]['company_id']);
        $this->assertEquals('1w', $changes[0]['period_type']);
        $this->assertEquals(5, $changes[0]['current_rank']);
        $this->assertEquals(5, $changes[0]['previous_rank']);
        $this->assertEquals(0, $changes[0]['rank_change']); // 5 - 5 = 0 (unchanged)
    }

    public function test_record_ranking_history_with_multiple_companies(): void
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $previousCalculatedAt = Carbon::now()->subHour();
        $currentCalculatedAt = Carbon::now();

        // 前回のランキングを作成
        CompanyRanking::create([
            'company_id' => $company1->id,
            'ranking_period' => '1w',
            'rank_position' => 1,
            'total_score' => 120.0,
            'article_count' => 12,
            'total_bookmarks' => 25,
            'period_start' => $previousCalculatedAt->copy()->subDays(7),
            'period_end' => $previousCalculatedAt->copy(),
            'calculated_at' => $previousCalculatedAt,
        ]);

        CompanyRanking::create([
            'company_id' => $company2->id,
            'ranking_period' => '1w',
            'rank_position' => 2,
            'total_score' => 100.0,
            'article_count' => 10,
            'total_bookmarks' => 20,
            'period_start' => $previousCalculatedAt->copy()->subDays(7),
            'period_end' => $previousCalculatedAt->copy(),
            'calculated_at' => $previousCalculatedAt,
        ]);

        // 現在のランキングを作成（順位が入れ替わる）
        CompanyRanking::create([
            'company_id' => $company1->id,
            'ranking_period' => '1w',
            'rank_position' => 2,
            'total_score' => 110.0,
            'article_count' => 11,
            'total_bookmarks' => 22,
            'period_start' => $currentCalculatedAt->copy()->subDays(7),
            'period_end' => $currentCalculatedAt->copy(),
            'calculated_at' => $currentCalculatedAt,
        ]);

        CompanyRanking::create([
            'company_id' => $company2->id,
            'ranking_period' => '1w',
            'rank_position' => 1,
            'total_score' => 130.0,
            'article_count' => 13,
            'total_bookmarks' => 26,
            'period_start' => $currentCalculatedAt->copy()->subDays(7),
            'period_end' => $currentCalculatedAt->copy(),
            'calculated_at' => $currentCalculatedAt,
        ]);

        $changes = $this->service->recordRankingHistory('1w', $currentCalculatedAt);

        $this->assertCount(2, $changes);

        // Company2が1位に上昇
        $company2Change = collect($changes)->firstWhere('company_id', $company2->id);
        $this->assertEquals(1, $company2Change['current_rank']);
        $this->assertEquals(2, $company2Change['previous_rank']);
        $this->assertEquals(1, $company2Change['rank_change']);

        // Company1が2位に下降
        $company1Change = collect($changes)->firstWhere('company_id', $company1->id);
        $this->assertEquals(2, $company1Change['current_rank']);
        $this->assertEquals(1, $company1Change['previous_rank']);
        $this->assertEquals(-1, $company1Change['rank_change']);
    }

    public function test_get_company_ranking_history(): void
    {
        $company = Company::factory()->create();

        // 履歴データを作成
        DB::table('company_ranking_history')->insert([
            [
                'company_id' => $company->id,
                'period_type' => '1w',
                'current_rank' => 3,
                'previous_rank' => 5,
                'rank_change' => 2,
                'calculated_at' => Carbon::now()->subDays(1),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company->id,
                'period_type' => '1w',
                'current_rank' => 5,
                'previous_rank' => 8,
                'rank_change' => 3,
                'calculated_at' => Carbon::now()->subDays(2),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $history = $this->service->getCompanyRankingHistory($company->id, '1w', 30);

        $this->assertCount(2, $history);
        $this->assertEquals(3, $history[0]->current_rank);
        $this->assertEquals(5, $history[1]->current_rank);
    }

    public function test_get_top_ranking_risers(): void
    {
        $company1 = Company::factory()->create(['name' => 'Company 1']);
        $company2 = Company::factory()->create(['name' => 'Company 2']);
        $calculatedAt = Carbon::now();

        // 履歴データを作成
        DB::table('company_ranking_history')->insert([
            [
                'company_id' => $company1->id,
                'period_type' => '1w',
                'current_rank' => 1,
                'previous_rank' => 10,
                'rank_change' => 9,
                'calculated_at' => $calculatedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company2->id,
                'period_type' => '1w',
                'current_rank' => 3,
                'previous_rank' => 8,
                'rank_change' => 5,
                'calculated_at' => $calculatedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $risers = $this->service->getTopRankingRisers('1w', 5);

        $this->assertCount(2, $risers);
        $this->assertEquals('Company 1', $risers[0]->company_name);
        $this->assertEquals(9, $risers[0]->rank_change);
        $this->assertEquals('Company 2', $risers[1]->company_name);
        $this->assertEquals(5, $risers[1]->rank_change);
    }

    public function test_get_top_ranking_fallers(): void
    {
        $company1 = Company::factory()->create(['name' => 'Company 1']);
        $company2 = Company::factory()->create(['name' => 'Company 2']);
        $calculatedAt = Carbon::now();

        // 履歴データを作成
        DB::table('company_ranking_history')->insert([
            [
                'company_id' => $company1->id,
                'period_type' => '1w',
                'current_rank' => 15,
                'previous_rank' => 3,
                'rank_change' => -12,
                'calculated_at' => $calculatedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company2->id,
                'period_type' => '1w',
                'current_rank' => 10,
                'previous_rank' => 5,
                'rank_change' => -5,
                'calculated_at' => $calculatedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $fallers = $this->service->getTopRankingFallers('1w', 5);

        $this->assertCount(2, $fallers);
        $this->assertEquals('Company 1', $fallers[0]->company_name);
        $this->assertEquals(-12, $fallers[0]->rank_change);
        $this->assertEquals('Company 2', $fallers[1]->company_name);
        $this->assertEquals(-5, $fallers[1]->rank_change);
    }

    public function test_get_ranking_change_statistics(): void
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $company3 = Company::factory()->create();
        $calculatedAt = Carbon::now();

        // 履歴データを作成
        DB::table('company_ranking_history')->insert([
            [
                'company_id' => $company1->id,
                'period_type' => '1w',
                'current_rank' => 1,
                'previous_rank' => 3,
                'rank_change' => 2,
                'calculated_at' => $calculatedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company2->id,
                'period_type' => '1w',
                'current_rank' => 5,
                'previous_rank' => 2,
                'rank_change' => -3,
                'calculated_at' => $calculatedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company3->id,
                'period_type' => '1w',
                'current_rank' => 4,
                'previous_rank' => 4,
                'rank_change' => 0,
                'calculated_at' => $calculatedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $stats = $this->service->getRankingChangeStatistics('1w');

        $this->assertEquals(3, $stats['total_companies']);
        $this->assertEquals(1, $stats['rising_companies']);
        $this->assertEquals(1, $stats['falling_companies']);
        $this->assertEquals(1, $stats['unchanged_companies']);
        $this->assertEquals(2, $stats['max_rise']);
        $this->assertEquals(3, $stats['max_fall']);
    }

    public function test_cleanup_old_history(): void
    {
        $company = Company::factory()->create();
        $oldDate = Carbon::now()->subDays(400);
        $recentDate = Carbon::now()->subDays(30);

        // 古いデータと新しいデータを作成
        DB::table('company_ranking_history')->insert([
            [
                'company_id' => $company->id,
                'period_type' => '1w',
                'current_rank' => 1,
                'previous_rank' => 2,
                'rank_change' => 1,
                'calculated_at' => $oldDate,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company->id,
                'period_type' => '1w',
                'current_rank' => 2,
                'previous_rank' => 3,
                'rank_change' => 1,
                'calculated_at' => $recentDate,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $deletedCount = $this->service->cleanupOldHistory();

        $this->assertEquals(1, $deletedCount);
        $this->assertEquals(1, DB::table('company_ranking_history')->count());
    }

    public function test_get_history_storage_stats(): void
    {
        $company = Company::factory()->create();

        // テストデータを作成
        DB::table('company_ranking_history')->insert([
            [
                'company_id' => $company->id,
                'period_type' => '1w',
                'current_rank' => 1,
                'previous_rank' => 2,
                'rank_change' => 1,
                'calculated_at' => Carbon::now()->subDays(1),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company->id,
                'period_type' => '1m',
                'current_rank' => 2,
                'previous_rank' => 3,
                'rank_change' => 1,
                'calculated_at' => Carbon::now()->subDays(2),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $stats = $this->service->getHistoryStorageStats();

        $this->assertEquals(2, $stats['total_records']);
        $this->assertEquals(1, $stats['unique_companies']);
        $this->assertEquals(2, $stats['period_types']);
        $this->assertEquals(365, $stats['retention_days']);
    }
}
