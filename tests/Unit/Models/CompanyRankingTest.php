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
}
