<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\CompanyInfluenceScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyInfluenceScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_基本的なモデル作成ができる()
    {
        $company = Company::factory()->create();

        $score = CompanyInfluenceScore::create([
            'company_id' => $company->id,
            'period_type' => 'monthly',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'total_score' => 100.50,
            'article_count' => 10,
            'total_bookmarks' => 500,
            'calculated_at' => now(),
        ]);

        $this->assertInstanceOf(CompanyInfluenceScore::class, $score);
        $this->assertTrue($score->exists);
        $this->assertEquals($company->id, $score->company_id);
    }

    public function test_企業とのリレーションが正しく動作する()
    {
        $company = Company::factory()->create(['name' => 'Test Company']);
        $score = CompanyInfluenceScore::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $score->company);
        $this->assertEquals('Test Company', $score->company->name);
    }

    public function test_fillable属性の確認()
    {
        $score = new CompanyInfluenceScore;
        $fillable = $score->getFillable();

        $expected = [
            'company_id',
            'period_type',
            'period_start',
            'period_end',
            'total_score',
            'article_count',
            'total_bookmarks',
            'calculated_at',
        ];

        $this->assertEquals($expected, $fillable);
    }

    public function test_型変換の確認()
    {
        $score = CompanyInfluenceScore::factory()->create([
            'total_score' => '100.50',
            'article_count' => '10',
            'total_bookmarks' => '500',
        ]);

        $this->assertIsString($score->total_score);
        $this->assertIsInt($score->article_count);
        $this->assertIsInt($score->total_bookmarks);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $score->period_start);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $score->period_end);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $score->calculated_at);
    }

    public function test_period_typeスコープの動作確認()
    {
        CompanyInfluenceScore::factory()->create(['period_type' => 'monthly']);
        CompanyInfluenceScore::factory()->create(['period_type' => 'weekly']);
        CompanyInfluenceScore::factory()->create(['period_type' => 'monthly']);

        $monthlyScores = CompanyInfluenceScore::periodType('monthly')->get();
        $weeklyScores = CompanyInfluenceScore::periodType('weekly')->get();

        $this->assertCount(2, $monthlyScores);
        $this->assertCount(1, $weeklyScores);
    }

    public function test_period_rangeスコープの動作確認()
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        CompanyInfluenceScore::factory()->create(['period_start' => $startDate->copy()->subDays(10)]);
        CompanyInfluenceScore::factory()->create(['period_start' => $startDate->copy()->addDays(5)]);
        CompanyInfluenceScore::factory()->create(['period_start' => $startDate->copy()->addDays(15)]);
        CompanyInfluenceScore::factory()->create(['period_start' => $endDate->copy()->addDays(5)]);

        $rangeScores = CompanyInfluenceScore::periodRange($startDate, $endDate)->get();

        $this->assertCount(2, $rangeScores);
    }

    public function test_order_by_scoreスコープの動作確認()
    {
        CompanyInfluenceScore::factory()->create(['total_score' => 100.0]);
        CompanyInfluenceScore::factory()->create(['total_score' => 200.0]);
        CompanyInfluenceScore::factory()->create(['total_score' => 150.0]);

        $descScores = CompanyInfluenceScore::orderByScore('desc')->get();
        $ascScores = CompanyInfluenceScore::orderByScore('asc')->get();

        $this->assertEquals('200.00', $descScores->first()->total_score);
        $this->assertEquals('100.00', $descScores->last()->total_score);
        $this->assertEquals('100.00', $ascScores->first()->total_score);
        $this->assertEquals('200.00', $ascScores->last()->total_score);
    }

    public function test_order_by_calculated_atスコープの動作確認()
    {
        $now = now();
        CompanyInfluenceScore::factory()->create(['calculated_at' => $now->copy()->subDays(2)]);
        CompanyInfluenceScore::factory()->create(['calculated_at' => $now->copy()->subDays(1)]);
        CompanyInfluenceScore::factory()->create(['calculated_at' => $now->copy()]);

        $descScores = CompanyInfluenceScore::orderByCalculatedAt('desc')->get();
        $ascScores = CompanyInfluenceScore::orderByCalculatedAt('asc')->get();

        $this->assertTrue($descScores->first()->calculated_at->isAfter($descScores->last()->calculated_at));
        $this->assertTrue($ascScores->first()->calculated_at->isBefore($ascScores->last()->calculated_at));
    }

    public function test_latestスコープの動作確認()
    {
        $now = now();
        CompanyInfluenceScore::factory()->create(['calculated_at' => $now->copy()->subDays(2)]);
        CompanyInfluenceScore::factory()->create(['calculated_at' => $now->copy()->subDays(1)]);
        $latest = CompanyInfluenceScore::factory()->create(['calculated_at' => $now->copy()]);

        $latestScore = CompanyInfluenceScore::orderByCalculatedAt('desc')->first();

        $this->assertEquals($latest->id, $latestScore->id);
    }

    public function test_for_companyスコープの動作確認()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        CompanyInfluenceScore::factory()->create(['company_id' => $company1->id]);
        CompanyInfluenceScore::factory()->create(['company_id' => $company2->id]);
        CompanyInfluenceScore::factory()->create(['company_id' => $company1->id]);

        $company1Scores = CompanyInfluenceScore::forCompany($company1->id)->get();
        $company2Scores = CompanyInfluenceScore::forCompany($company2->id)->get();

        $this->assertCount(2, $company1Scores);
        $this->assertCount(1, $company2Scores);
    }

    public function test_複数のスコープの組み合わせ()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $now = now();

        CompanyInfluenceScore::factory()->create([
            'company_id' => $company1->id,
            'period_type' => 'monthly',
            'total_score' => 100.0,
            'calculated_at' => $now->subDays(1),
        ]);

        CompanyInfluenceScore::factory()->create([
            'company_id' => $company1->id,
            'period_type' => 'weekly',
            'total_score' => 150.0,
            'calculated_at' => $now,
        ]);

        CompanyInfluenceScore::factory()->create([
            'company_id' => $company2->id,
            'period_type' => 'monthly',
            'total_score' => 200.0,
            'calculated_at' => $now->subDays(2),
        ]);

        $result = CompanyInfluenceScore::forCompany($company1->id)
            ->periodType('monthly')
            ->orderByScore('desc')
            ->latest()
            ->get();

        $this->assertCount(1, $result);
        $this->assertEquals('100.00', $result->first()->total_score);
        $this->assertEquals($company1->id, $result->first()->company_id);
    }

    public function test_同じ期間の複数スコア計算()
    {
        $company = Company::factory()->create();
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        $score1 = CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'period_type' => 'monthly',
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_score' => 100.0,
            'calculated_at' => now()->subHours(2),
        ]);

        $score2 = CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'period_type' => 'monthly',
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_score' => 150.0,
            'calculated_at' => now(),
        ]);

        $latestScore = CompanyInfluenceScore::forCompany($company->id)
            ->periodType('monthly')
            ->orderByCalculatedAt('desc')
            ->first();

        $this->assertEquals($score2->id, $latestScore->id);
        $this->assertEquals('150.00', $latestScore->total_score);
    }

    public function test_期間別スコア履歴の取得()
    {
        $company = Company::factory()->create();

        CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'period_type' => 'monthly',
            'total_score' => 100.0,
            'calculated_at' => now()->subMonths(2),
        ]);

        CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'period_type' => 'monthly',
            'total_score' => 150.0,
            'calculated_at' => now()->subMonths(1),
        ]);

        CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'period_type' => 'monthly',
            'total_score' => 200.0,
            'calculated_at' => now(),
        ]);

        $history = CompanyInfluenceScore::forCompany($company->id)
            ->periodType('monthly')
            ->orderByCalculatedAt('asc')
            ->get();

        $this->assertCount(3, $history);
        $this->assertEquals('100.00', $history->first()->total_score);
        $this->assertEquals('200.00', $history->last()->total_score);
    }
}
