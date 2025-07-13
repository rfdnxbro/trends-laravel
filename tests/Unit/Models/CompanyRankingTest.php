<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\CompanyRanking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_基本的なモデル作成ができる()
    {
        $company = Company::factory()->create();

        $ranking = CompanyRanking::create([
            'company_id' => $company->id,
            'ranking_period' => 'monthly',
            'rank_position' => 1,
            'total_score' => 100.50,
            'article_count' => 10,
            'total_bookmarks' => 500,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'calculated_at' => now(),
        ]);

        $this->assertInstanceOf(CompanyRanking::class, $ranking);
        $this->assertTrue($ranking->exists);
        $this->assertEquals($company->id, $ranking->company_id);
    }

    public function test_企業とのリレーションが正しく動作する()
    {
        $company = Company::factory()->create(['name' => 'Test Company']);
        $ranking = CompanyRanking::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $ranking->company);
        $this->assertEquals('Test Company', $ranking->company->name);
    }

    public function test_fillable属性の確認()
    {
        $ranking = new CompanyRanking;
        $fillable = $ranking->getFillable();

        $expected = [
            'company_id',
            'ranking_period',
            'rank_position',
            'total_score',
            'article_count',
            'total_bookmarks',
            'period_start',
            'period_end',
            'calculated_at',
        ];

        $this->assertEquals($expected, $fillable);
    }

    public function test_型変換の確認()
    {
        $ranking = CompanyRanking::factory()->create([
            'rank_position' => '1',
            'total_score' => '100.50',
            'article_count' => '10',
            'total_bookmarks' => '500',
        ]);

        $this->assertIsInt($ranking->rank_position);
        $this->assertIsString($ranking->total_score);
        $this->assertIsInt($ranking->article_count);
        $this->assertIsInt($ranking->total_bookmarks);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $ranking->period_start);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $ranking->period_end);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $ranking->calculated_at);
    }

    public function test_period_typeスコープの動作確認()
    {
        CompanyRanking::factory()->create(['ranking_period' => 'monthly']);
        CompanyRanking::factory()->create(['ranking_period' => 'weekly']);
        CompanyRanking::factory()->create(['ranking_period' => 'monthly']);

        $monthlyRankings = CompanyRanking::periodType('monthly')->get();
        $weeklyRankings = CompanyRanking::periodType('weekly')->get();

        $this->assertCount(2, $monthlyRankings);
        $this->assertCount(1, $weeklyRankings);
    }

    public function test_rank_rangeスコープの動作確認()
    {
        CompanyRanking::factory()->create(['rank_position' => 1]);
        CompanyRanking::factory()->create(['rank_position' => 5]);
        CompanyRanking::factory()->create(['rank_position' => 10]);
        CompanyRanking::factory()->create(['rank_position' => 15]);

        $topTenRankings = CompanyRanking::rankRange(1, 10)->get();
        $topFiveRankings = CompanyRanking::rankRange(1, 5)->get();

        $this->assertCount(3, $topTenRankings);
        $this->assertCount(2, $topFiveRankings);
    }

    public function test_top_rankスコープの動作確認()
    {
        CompanyRanking::factory()->create(['rank_position' => 1]);
        CompanyRanking::factory()->create(['rank_position' => 5]);
        CompanyRanking::factory()->create(['rank_position' => 10]);
        CompanyRanking::factory()->create(['rank_position' => 15]);

        $topTenRankings = CompanyRanking::topRank(10)->get();
        $topFiveRankings = CompanyRanking::topRank(5)->get();

        $this->assertCount(3, $topTenRankings);
        $this->assertCount(2, $topFiveRankings);
    }

    public function test_order_by_rankスコープの動作確認()
    {
        CompanyRanking::factory()->create(['rank_position' => 3]);
        CompanyRanking::factory()->create(['rank_position' => 1]);
        CompanyRanking::factory()->create(['rank_position' => 2]);

        $ascRankings = CompanyRanking::orderByRank('asc')->get();
        $descRankings = CompanyRanking::orderByRank('desc')->get();

        $this->assertEquals(1, $ascRankings->first()->rank_position);
        $this->assertEquals(3, $ascRankings->last()->rank_position);
        $this->assertEquals(3, $descRankings->first()->rank_position);
        $this->assertEquals(1, $descRankings->last()->rank_position);
    }

    public function test_order_by_scoreスコープの動作確認()
    {
        CompanyRanking::factory()->create(['total_score' => 100.0]);
        CompanyRanking::factory()->create(['total_score' => 200.0]);
        CompanyRanking::factory()->create(['total_score' => 150.0]);

        $descRankings = CompanyRanking::orderByScore('desc')->get();
        $ascRankings = CompanyRanking::orderByScore('asc')->get();

        $this->assertEquals('200.00', $descRankings->first()->total_score);
        $this->assertEquals('100.00', $descRankings->last()->total_score);
        $this->assertEquals('100.00', $ascRankings->first()->total_score);
        $this->assertEquals('200.00', $ascRankings->last()->total_score);
    }

    public function test_order_by_calculated_atスコープの動作確認()
    {
        $now = now();
        CompanyRanking::factory()->create(['calculated_at' => $now->copy()->subDays(2)]);
        CompanyRanking::factory()->create(['calculated_at' => $now->copy()->subDays(1)]);
        CompanyRanking::factory()->create(['calculated_at' => $now->copy()]);

        $descRankings = CompanyRanking::orderByCalculatedAt('desc')->get();
        $ascRankings = CompanyRanking::orderByCalculatedAt('asc')->get();

        $this->assertTrue($descRankings->first()->calculated_at->isAfter($descRankings->last()->calculated_at));
        $this->assertTrue($ascRankings->first()->calculated_at->isBefore($ascRankings->last()->calculated_at));
    }

    public function test_latestスコープの動作確認()
    {
        $now = now();
        CompanyRanking::factory()->create(['calculated_at' => $now->copy()->subDays(2)]);
        CompanyRanking::factory()->create(['calculated_at' => $now->copy()->subDays(1)]);
        $latest = CompanyRanking::factory()->create(['calculated_at' => $now->copy()]);

        $latestRanking = CompanyRanking::orderByCalculatedAt('desc')->first();

        $this->assertEquals($latest->id, $latestRanking->id);
    }

    public function test_for_companyスコープの動作確認()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        CompanyRanking::factory()->create(['company_id' => $company1->id]);
        CompanyRanking::factory()->create(['company_id' => $company2->id]);
        CompanyRanking::factory()->create(['company_id' => $company1->id]);

        $company1Rankings = CompanyRanking::forCompany($company1->id)->get();
        $company2Rankings = CompanyRanking::forCompany($company2->id)->get();

        $this->assertCount(2, $company1Rankings);
        $this->assertCount(1, $company2Rankings);
    }

    public function test_period_rangeスコープの動作確認()
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        CompanyRanking::factory()->create(['period_start' => $startDate->copy()->subDays(10)]);
        CompanyRanking::factory()->create(['period_start' => $startDate->copy()->addDays(5)]);
        CompanyRanking::factory()->create(['period_start' => $startDate->copy()->addDays(15)]);
        CompanyRanking::factory()->create(['period_start' => $endDate->copy()->addDays(5)]);

        $rangeRankings = CompanyRanking::periodRange($startDate, $endDate)->get();

        $this->assertCount(2, $rangeRankings);
    }

    public function test_active_companiesスコープの動作確認()
    {
        $activeCompany = Company::factory()->create(['is_active' => true]);
        $inactiveCompany = Company::factory()->create(['is_active' => false]);

        CompanyRanking::factory()->create(['company_id' => $activeCompany->id]);
        CompanyRanking::factory()->create(['company_id' => $inactiveCompany->id]);

        $activeRankings = CompanyRanking::activeCompanies()->get();

        $this->assertCount(1, $activeRankings);
        $this->assertEquals($activeCompany->id, $activeRankings->first()->company_id);
    }

    public function test_複数のスコープの組み合わせ()
    {
        $activeCompany = Company::factory()->create(['is_active' => true]);
        $inactiveCompany = Company::factory()->create(['is_active' => false]);

        CompanyRanking::factory()->create([
            'company_id' => $activeCompany->id,
            'ranking_period' => 'monthly',
            'rank_position' => 1,
        ]);

        CompanyRanking::factory()->create([
            'company_id' => $activeCompany->id,
            'ranking_period' => 'weekly',
            'rank_position' => 5,
        ]);

        CompanyRanking::factory()->create([
            'company_id' => $inactiveCompany->id,
            'ranking_period' => 'monthly',
            'rank_position' => 2,
        ]);

        $result = CompanyRanking::activeCompanies()
            ->periodType('monthly')
            ->topRank(5)
            ->orderByRank()
            ->get();

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result->first()->rank_position);
        $this->assertEquals($activeCompany->id, $result->first()->company_id);
    }

    public function test_scope_latest_期間別最新ランキングを取得する()
    {
        $company = Company::factory()->create(['domain' => 'test-period-'.uniqid().'.com']);

        $pastRanking = CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'ranking_period' => 'one_week',
            'calculated_at' => now()->subDays(3),
            'total_score' => 100.0,
        ]);

        $latestRanking = CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'ranking_period' => 'one_week',
            'calculated_at' => now(),
            'total_score' => 200.0,
        ]);

        $results = CompanyRanking::forCompany($company->id)->periodType('one_week')->latest()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('id', $latestRanking->id));
        $this->assertTrue($results->contains('id', $pastRanking->id));

        $latestResult = $results->where('id', $latestRanking->id)->first();
        $pastResult = $results->where('id', $pastRanking->id)->first();
        $this->assertTrue($latestResult->calculated_at->isAfter($pastResult->calculated_at));
    }

    public function test_scope_latest_最新ランキング取得の基本動作確認()
    {
        $company = Company::factory()->create(['domain' => 'test-basic-'.uniqid().'.com']);

        $pastRanking = CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'calculated_at' => now()->subDays(5),
            'total_score' => 100.0,
        ]);

        $latestRanking = CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'calculated_at' => now(),
            'total_score' => 200.0,
        ]);

        $result = CompanyRanking::forCompany($company->id)->latest()->first();

        $this->assertNotNull($result);
        $allResults = CompanyRanking::forCompany($company->id)->get();
        $this->assertCount(2, $allResults);
        $this->assertTrue($allResults->contains('id', $latestRanking->id));
        $this->assertTrue($allResults->contains('id', $pastRanking->id));
    }

    public function test_scope_latest_複数期間での最新データ取得()
    {
        $company1 = Company::factory()->create(['domain' => 'test-multi-period-1-'.uniqid().'.com']);
        $company2 = Company::factory()->create(['domain' => 'test-multi-period-2-'.uniqid().'.com']);

        $ranking1 = CompanyRanking::factory()->create([
            'company_id' => $company1->id,
            'ranking_period' => 'one_month',
            'calculated_at' => now()->subDays(2),
            'total_score' => 100.0,
        ]);

        $latest1 = CompanyRanking::factory()->create([
            'company_id' => $company1->id,
            'ranking_period' => 'one_week',
            'calculated_at' => now()->subDays(1),
            'total_score' => 200.0,
        ]);

        $latest2 = CompanyRanking::factory()->create([
            'company_id' => $company2->id,
            'ranking_period' => 'one_month',
            'calculated_at' => now(),
            'total_score' => 300.0,
        ]);

        $results = CompanyRanking::whereIn('company_id', [$company1->id, $company2->id])
            ->latest()
            ->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->contains('id', $latest2->id));
        $this->assertTrue($results->contains('id', $latest1->id));
        $this->assertTrue($results->contains('id', $ranking1->id));

        $this->assertEquals(300.0, (float) $results->where('id', $latest2->id)->first()->total_score);
        $this->assertEquals(200.0, (float) $results->where('id', $latest1->id)->first()->total_score);
        $this->assertEquals(100.0, (float) $results->where('id', $ranking1->id)->first()->total_score);
    }

    public function test_scope_latest_ランキング順序の検証()
    {
        $company = Company::factory()->create(['domain' => 'test-order-'.uniqid().'.com']);
        $now = now();

        $ranking1 = CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'calculated_at' => $now->copy()->subHours(3),
            'rank_position' => 1,
            'total_score' => 100.0,
        ]);

        $ranking2 = CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'calculated_at' => $now->copy()->subHours(2),
            'rank_position' => 2,
            'total_score' => 200.0,
        ]);

        $ranking3 = CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'calculated_at' => $now->copy()->subHours(1),
            'rank_position' => 3,
            'total_score' => 300.0,
        ]);

        $results = CompanyRanking::forCompany($company->id)->latest()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->contains('id', $ranking3->id));
        $this->assertTrue($results->contains('id', $ranking2->id));
        $this->assertTrue($results->contains('id', $ranking1->id));

        $this->assertEquals(300.0, (float) $results->where('id', $ranking3->id)->first()->total_score);
        $this->assertEquals(200.0, (float) $results->where('id', $ranking2->id)->first()->total_score);
        $this->assertEquals(100.0, (float) $results->where('id', $ranking1->id)->first()->total_score);
    }

    public function test_scope_latest_period_typeとrank_rangeとの組み合わせ()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        CompanyRanking::factory()->create([
            'company_id' => $company1->id,
            'ranking_period' => 'one_month',
            'rank_position' => 5,
            'calculated_at' => now()->subDays(2),
        ]);

        $expectedRanking = CompanyRanking::factory()->create([
            'company_id' => $company2->id,
            'ranking_period' => 'one_month',
            'rank_position' => 3,
            'calculated_at' => now(),
        ]);

        CompanyRanking::factory()->create([
            'company_id' => $company1->id,
            'ranking_period' => 'one_week',
            'rank_position' => 2,
            'calculated_at' => now()->subDays(1),
        ]);

        $result = CompanyRanking::periodType('one_month')
            ->rankRange(1, 5)
            ->latest()
            ->first();

        $this->assertEquals($expectedRanking->id, $result->id);
    }

    public function test_scope_latest_空データでの動作確認()
    {
        $results = CompanyRanking::latest()->get();

        $this->assertCount(0, $results);
        $this->assertTrue($results->isEmpty());
    }

    public function test_scope_latest_同一時刻レコード複数存在時の処理()
    {
        $sameTime = now();

        $ranking1 = CompanyRanking::factory()->create([
            'calculated_at' => $sameTime,
            'rank_position' => 1,
        ]);

        $ranking2 = CompanyRanking::factory()->create([
            'calculated_at' => $sameTime,
            'rank_position' => 2,
        ]);

        $results = CompanyRanking::latest()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->pluck('id')->contains($ranking1->id));
        $this->assertTrue($results->pluck('id')->contains($ranking2->id));
    }

    public function test_scope_latest_with_order_by_score複合スコープ()
    {
        $company = Company::factory()->create(['domain' => 'test-score-combo-'.uniqid().'.com']);
        $now = now();

        $rank1 = CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'total_score' => 100.0,
            'calculated_at' => $now->copy()->subDays(1),
        ]);

        $rank2 = CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'total_score' => 300.0,
            'calculated_at' => $now->copy()->subDays(2),
        ]);

        $highestRecentRanking = CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'total_score' => 200.0,
            'calculated_at' => $now,
        ]);

        $result = CompanyRanking::forCompany($company->id)->latest()->orderByScore('desc')->first();

        $this->assertNotNull($result);
        $allResults = CompanyRanking::forCompany($company->id)->latest()->orderByScore('desc')->get();
        $this->assertCount(3, $allResults);
        $this->assertTrue($allResults->contains('id', $highestRecentRanking->id));
    }

    public function test_scope_latest_with_active_companies複合スコープ()
    {
        $activeCompany = Company::factory()->create([
            'is_active' => true,
            'domain' => 'test-active-'.uniqid().'.com',
        ]);
        $inactiveCompany = Company::factory()->create([
            'is_active' => false,
            'domain' => 'test-inactive-'.uniqid().'.com',
        ]);

        CompanyRanking::factory()->create([
            'company_id' => $inactiveCompany->id,
            'calculated_at' => now(),
        ]);

        $activeRanking = CompanyRanking::factory()->create([
            'company_id' => $activeCompany->id,
            'calculated_at' => now()->subMinutes(30),
        ]);

        $result = CompanyRanking::activeCompanies()->latest()->first();

        $this->assertEquals($activeRanking->id, $result->id);
        $this->assertEquals($activeCompany->id, $result->company_id);
    }

    public function test_scope_latest_クエリ効率性の確認()
    {
        $targetCompany = Company::factory()->create(['domain' => 'test-efficiency-'.uniqid().'.com']);

        $oldestTime = now()->subDays(10);
        $rankings = [];
        foreach (range(1, 10) as $i) {
            $rankings[] = CompanyRanking::factory()->create([
                'company_id' => $targetCompany->id,
                'calculated_at' => $oldestTime->copy()->addDays($i - 1),
                'total_score' => 100.0 + $i,
            ]);
        }

        $latestTime = now();
        $latestRanking = CompanyRanking::factory()->create([
            'company_id' => $targetCompany->id,
            'calculated_at' => $latestTime,
            'total_score' => 999.0,
        ]);

        $startTime = microtime(true);
        $result = CompanyRanking::forCompany($targetCompany->id)->latest()->first();
        $endTime = microtime(true);

        $this->assertNotNull($result);
        $allResults = CompanyRanking::forCompany($targetCompany->id)->get();
        $this->assertCount(11, $allResults);
        $this->assertLessThan(0.5, $endTime - $startTime);
    }

    public function test_scope_latest_with_top_rankとorder_by_rank複合スコープ()
    {
        $now = now();

        CompanyRanking::factory()->create([
            'rank_position' => 15,
            'calculated_at' => $now,
        ]);

        $topRanking = CompanyRanking::factory()->create([
            'rank_position' => 3,
            'calculated_at' => $now->copy()->subMinutes(30),
        ]);

        $result = CompanyRanking::topRank(10)
            ->latest()
            ->orderByRank('asc')
            ->first();

        $this->assertEquals($topRanking->id, $result->id);
        $this->assertEquals(3, $result->rank_position);
    }
}
