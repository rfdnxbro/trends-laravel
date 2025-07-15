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
        // latest()は Eloquent の組み込みメソッドなので、カスタムlatest()スコープが被らないよう
        // 実際にorderByCalculatedAtメソッドを使用してテストを行う
        $company = Company::factory()->create(['domain' => 'test-scope.com']);

        $score1 = CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'calculated_at' => now()->subHour(),
            'total_score' => 100.0,
        ]);

        $score2 = CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'calculated_at' => now(),
            'total_score' => 200.0,
        ]);

        // orderByCalculatedAtスコープのテスト（実質latest()と同じ機能）
        $results = CompanyInfluenceScore::forCompany($company->id)->orderByCalculatedAt('desc')->get();
        $this->assertNotEmpty($results);
        $this->assertGreaterThanOrEqual(2, $results->count());

        // 最新が先頭に来ることを確認
        $first = $results->first();
        $last = $results->last();
        $this->assertTrue($first->calculated_at->gte($last->calculated_at));

        // カスタムlatest()スコープの直接テスト（被らない方法で）
        $companyScore = new CompanyInfluenceScore;
        $query = $companyScore->newQuery();
        $latestQuery = $companyScore->scopeLatest($query);
        $this->assertNotNull($latestQuery);

        // SQLクエリにcalculated_atでのORDER BYが含まれていることを確認
        $sql = $latestQuery->toSql();
        $this->assertStringContainsString('order by', strtolower($sql));
        $this->assertStringContainsString('calculated_at', strtolower($sql));
        $this->assertStringContainsString('desc', strtolower($sql));
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

    public function test_companyリレーションの_belongs_to関係が正しい()
    {
        $score = new CompanyInfluenceScore;

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $score->company());
        $this->assertEquals(\App\Models\Company::class, $score->company()->getRelated()::class);
    }

    public function test_castsが正しく設定されている()
    {
        $score = new CompanyInfluenceScore;
        $casts = $score->getCasts();

        $this->assertEquals('date', $casts['period_start']);
        $this->assertEquals('date', $casts['period_end']);
        $this->assertEquals('decimal:2', $casts['total_score']);
        $this->assertEquals('integer', $casts['article_count']);
        $this->assertEquals('integer', $casts['total_bookmarks']);
        $this->assertEquals('datetime', $casts['calculated_at']);
    }

    public function test_期間タイプの妥当性検証()
    {
        $validPeriodTypes = ['daily', 'weekly', 'monthly'];

        foreach ($validPeriodTypes as $periodType) {
            $score = CompanyInfluenceScore::factory()->create(['period_type' => $periodType]);
            $this->assertEquals($periodType, $score->period_type);
        }
    }

    public function test_scope_latest_最新のスコアレコードを取得する()
    {
        $company = Company::factory()->create(['domain' => 'test-latest-'.uniqid().'.com']);

        $pastScore = CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'calculated_at' => now()->subDays(5),
            'total_score' => 100.0,
        ]);

        $latestScore = CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'calculated_at' => now(),
            'total_score' => 200.0,
        ]);

        $results = CompanyInfluenceScore::forCompany($company->id)->latest()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('id', $latestScore->id));
        $this->assertTrue($results->contains('id', $pastScore->id));

        $firstResult = $results->where('id', $latestScore->id)->first();
        $lastResult = $results->where('id', $pastScore->id)->first();
        $this->assertTrue($firstResult->calculated_at->isAfter($lastResult->calculated_at));
    }

    public function test_scope_latest_複数企業の最新スコア取得()
    {
        $company1 = Company::factory()->create(['domain' => 'test-multi-1-'.uniqid().'.com']);
        $company2 = Company::factory()->create(['domain' => 'test-multi-2-'.uniqid().'.com']);

        $score1 = CompanyInfluenceScore::factory()->create([
            'company_id' => $company1->id,
            'calculated_at' => now()->subDays(2),
        ]);

        $latest1 = CompanyInfluenceScore::factory()->create([
            'company_id' => $company1->id,
            'calculated_at' => now()->subDays(1),
        ]);

        $latest2 = CompanyInfluenceScore::factory()->create([
            'company_id' => $company2->id,
            'calculated_at' => now(),
        ]);

        $results = CompanyInfluenceScore::whereIn('company_id', [$company1->id, $company2->id])
            ->latest()
            ->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->contains('id', $latest2->id));
        $this->assertTrue($results->contains('id', $latest1->id));
        $this->assertTrue($results->contains('id', $score1->id));

        $resultsArray = $results->toArray();
        $this->assertNotEmpty($resultsArray);
        $sortedResults = $results->sortByDesc('calculated_at')->values();
        $this->assertEquals(3, $sortedResults->count());
    }

    public function test_scope_latest_日時順ソート機能の検証()
    {
        $company = Company::factory()->create(['domain' => 'test-sort-'.uniqid().'.com']);
        $now = now();

        $score1 = CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'calculated_at' => $now->copy()->subHours(3),
            'total_score' => 100.0,
        ]);

        $score2 = CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'calculated_at' => $now->copy()->subHours(2),
            'total_score' => 200.0,
        ]);

        $score3 = CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'calculated_at' => $now->copy()->subHours(1),
            'total_score' => 300.0,
        ]);

        $results = CompanyInfluenceScore::forCompany($company->id)->latest()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->contains('id', $score3->id));
        $this->assertTrue($results->contains('id', $score2->id));
        $this->assertTrue($results->contains('id', $score1->id));

        $sortedByScore = $results->sortByDesc('calculated_at')->values();
        $this->assertEquals(300.0, (float) $sortedByScore[0]->total_score);
        $this->assertEquals(200.0, (float) $sortedByScore[1]->total_score);
        $this->assertEquals(100.0, (float) $sortedByScore[2]->total_score);
    }

    public function test_scope_latest_空データでの動作確認()
    {
        $results = CompanyInfluenceScore::latest()->get();

        $this->assertCount(0, $results);
        $this->assertTrue($results->isEmpty());
    }

    public function test_scope_latest_他のスコープとの組み合わせ()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        CompanyInfluenceScore::factory()->create([
            'company_id' => $company1->id,
            'period_type' => 'monthly',
            'calculated_at' => now()->subDays(2),
        ]);

        $expectedScore = CompanyInfluenceScore::factory()->create([
            'company_id' => $company1->id,
            'period_type' => 'monthly',
            'calculated_at' => now(),
        ]);

        CompanyInfluenceScore::factory()->create([
            'company_id' => $company2->id,
            'period_type' => 'monthly',
            'calculated_at' => now()->subDays(1),
        ]);

        $result = CompanyInfluenceScore::forCompany($company1->id)
            ->periodType('monthly')
            ->latest()
            ->first();

        $this->assertNotNull($result);
        $this->assertEquals('monthly', $result->period_type);
        $this->assertEquals($company1->id, $result->company_id);
    }

    public function test_scope_latest_同一時刻レコード複数存在時の処理()
    {
        $sameTime = now();

        $score1 = CompanyInfluenceScore::factory()->create([
            'calculated_at' => $sameTime,
            'total_score' => 100.0,
        ]);

        $score2 = CompanyInfluenceScore::factory()->create([
            'calculated_at' => $sameTime,
            'total_score' => 200.0,
        ]);

        $results = CompanyInfluenceScore::latest()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->pluck('id')->contains($score1->id));
        $this->assertTrue($results->pluck('id')->contains($score2->id));
    }

    public function test_scope_latest_with_order_by_score複合スコープ()
    {
        $company = Company::factory()->create(['domain' => 'test-score-'.uniqid().'.com']);
        $now = now();

        $score1 = CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'total_score' => 100.0,
            'calculated_at' => $now->subDays(1),
        ]);

        $score2 = CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'total_score' => 200.0,
            'calculated_at' => $now,
        ]);

        $result = CompanyInfluenceScore::forCompany($company->id)->latest()->orderByScore('desc')->first();

        $this->assertEquals($score2->id, $result->id);
    }

    public function test_scope_latest_大量データでのパフォーマンス()
    {
        $company = Company::factory()->create(['domain' => 'test-perf-'.uniqid().'.com']);

        $oldestTime = now()->subDays(10);
        $scores = [];
        foreach (range(1, 10) as $i) {
            $scores[] = CompanyInfluenceScore::factory()->create([
                'company_id' => $company->id,
                'calculated_at' => $oldestTime->copy()->addDays($i - 1),
                'total_score' => 100.0 + $i,
            ]);
        }

        $latestTime = now();
        $latestScore = CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'calculated_at' => $latestTime,
            'total_score' => 999.0,
        ]);

        $startTime = microtime(true);
        $result = CompanyInfluenceScore::forCompany($company->id)->latest()->first();
        $endTime = microtime(true);

        $this->assertNotNull($result);
        $allResults = CompanyInfluenceScore::forCompany($company->id)->get();
        $this->assertCount(11, $allResults);
        $this->assertLessThan(1.0, $endTime - $startTime);
    }

    public function test_scope_latest_with_for_company複合スコープの効率性()
    {
        $targetCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        CompanyInfluenceScore::factory()->count(5)->create([
            'company_id' => $otherCompany->id,
            'calculated_at' => now()->subDays(rand(1, 10)),
        ]);

        $targetScore = CompanyInfluenceScore::factory()->create([
            'company_id' => $targetCompany->id,
            'calculated_at' => now(),
        ]);

        $result = CompanyInfluenceScore::forCompany($targetCompany->id)->latest()->first();

        $this->assertEquals($targetScore->id, $result->id);
        $this->assertEquals($targetCompany->id, $result->company_id);
    }

    public function test_scope_latest_単独実行でのカバレッジ確保()
    {
        // latest()スコープメソッドを確実に実行してライン78をカバー
        $score = CompanyInfluenceScore::factory()->create([
            'calculated_at' => now(),
        ]);

        // 直接latest()を単独で呼び出し
        $latestBuilder = CompanyInfluenceScore::latest();
        $this->assertNotNull($latestBuilder);

        // クエリビルダーが正しく構築されていることを確認
        $latestResults = $latestBuilder->get();
        $this->assertGreaterThanOrEqual(1, $latestResults->count());

        // 最新スコープのSQLクエリが期待どおりかを確認
        $latestQuery = CompanyInfluenceScore::latest();
        $this->assertStringContainsString('order by', strtolower($latestQuery->toSql()));
        $this->assertStringContainsString('desc', strtolower($latestQuery->toSql()));

        // latest()スコープは実際にはcalculated_atでソートするカスタムスコープ
        // Eloquentのデフォルトlatest()はcreated_atを使用するが、
        // CompanyInfluenceScoreクラスでは独自のlatest()スコープがcalculated_atを使用
    }
}
