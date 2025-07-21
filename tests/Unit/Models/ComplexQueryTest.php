<?php

namespace Tests\Unit\Models;

use App\Models\Article;
use App\Models\Company;
use App\Models\CompanyInfluenceScore;
use App\Models\CompanyRanking;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplexQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_企業ランキングと影響力スコアの結合クエリ()
    {
        $company = Company::factory()->create();

        CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'ranking_period' => 'monthly',
            'rank_position' => 1,
            'total_score' => 100.0,
        ]);

        CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'period_type' => 'monthly',
            'total_score' => 100.0,
        ]);

        $result = CompanyRanking::query()
            ->join('company_influence_scores', function ($join) {
                $join->on('company_rankings.company_id', '=', 'company_influence_scores.company_id')
                    ->where('company_rankings.ranking_period', '=', 'monthly')
                    ->where('company_influence_scores.period_type', '=', 'monthly');
            })
            ->select('company_rankings.*', 'company_influence_scores.total_score as influence_score')
            ->get();

        $this->assertCount(1, $result);
        $this->assertEquals('100.00', $result->first()->influence_score);
    }

    public function test_企業記事数とランキングの相関分析()
    {
        $platform = Platform::factory()->create();
        $company = Company::factory()->create();

        Article::factory()->count(5)->create([
            'company_id' => $company->id,
            'platform_id' => $platform->id,
        ]);

        CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'article_count' => 5,
            'rank_position' => 1,
        ]);

        $result = CompanyRanking::query()
            ->join('companies', 'company_rankings.company_id', '=', 'companies.id')
            ->leftJoin('articles', 'companies.id', '=', 'articles.company_id')
            ->select('company_rankings.*', 'companies.name as company_name')
            ->selectRaw('COUNT(articles.id) as actual_article_count')
            ->groupBy('company_rankings.id', 'companies.name')
            ->having('actual_article_count', '>', 0)
            ->get();

        $this->assertCount(1, $result);
        $this->assertEquals(5, $result->first()->actual_article_count);
    }

    public function test_期間別トップ企業の推移分析()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        CompanyRanking::factory()->create([
            'company_id' => $company1->id,
            'ranking_period' => 'monthly',
            'rank_position' => 1,
            'calculated_at' => now()->subMonths(2),
        ]);

        CompanyRanking::factory()->create([
            'company_id' => $company2->id,
            'ranking_period' => 'monthly',
            'rank_position' => 1,
            'calculated_at' => now()->subMonths(1),
        ]);

        CompanyRanking::factory()->create([
            'company_id' => $company1->id,
            'ranking_period' => 'monthly',
            'rank_position' => 1,
            'calculated_at' => now(),
        ]);

        $topCompanyHistory = CompanyRanking::query()
            ->join('companies', 'company_rankings.company_id', '=', 'companies.id')
            ->where('company_rankings.ranking_period', 'monthly')
            ->where('company_rankings.rank_position', 1)
            ->orderBy('company_rankings.calculated_at', 'desc')
            ->select('companies.name', 'company_rankings.calculated_at', 'company_rankings.total_score')
            ->get();

        $this->assertCount(3, $topCompanyHistory);
        $this->assertEquals($company1->name, $topCompanyHistory->first()->name);
    }

    public function test_企業の成長率計算クエリ()
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

        $growthAnalysis = CompanyInfluenceScore::query()
            ->where('company_id', $company->id)
            ->where('period_type', 'monthly')
            ->orderBy('calculated_at', 'asc')
            ->get();

        $this->assertCount(3, $growthAnalysis);

        $firstScore = $growthAnalysis->first()->total_score;
        $lastScore = $growthAnalysis->last()->total_score;
        $growthRate = (($lastScore - $firstScore) / $firstScore) * 100;

        $this->assertEquals(100.0, $growthRate);
    }

    public function test_プラットフォーム別企業影響力集計()
    {
        $platform1 = Platform::factory()->create(['name' => 'Qiita']);
        $platform2 = Platform::factory()->create(['name' => 'Zenn']);
        $company = Company::factory()->create();

        Article::factory()->count(3)->create([
            'company_id' => $company->id,
            'platform_id' => $platform1->id,
            'engagement_count' => 100,
        ]);

        Article::factory()->count(2)->create([
            'company_id' => $company->id,
            'platform_id' => $platform2->id,
            'engagement_count' => 200,
        ]);

        $platformInfluence = Article::query()
            ->join('platforms', 'articles.platform_id', '=', 'platforms.id')
            ->join('companies', 'articles.company_id', '=', 'companies.id')
            ->where('articles.company_id', $company->id)
            ->select('platforms.name as platform_name')
            ->selectRaw('COUNT(articles.id) as article_count')
            ->selectRaw('SUM(articles.engagement_count) as total_engagement')
            ->selectRaw('AVG(articles.engagement_count) as avg_engagement')
            ->groupBy('platforms.id', 'platforms.name')
            ->orderBy('total_engagement', 'desc')
            ->get();

        $this->assertCount(2, $platformInfluence);
        $this->assertEquals('Zenn', $platformInfluence->first()->platform_name);
        $this->assertEquals(400, $platformInfluence->first()->total_engagement);
    }

    public function test_大量データでのパフォーマンステスト()
    {
        $companies = Company::factory()->count(10)->create();
        $platform = Platform::factory()->create();

        foreach ($companies as $company) {
            Article::factory()->count(20)->create([
                'company_id' => $company->id,
                'platform_id' => $platform->id,
            ]);

            CompanyRanking::factory()->count(5)->create([
                'company_id' => $company->id,
            ]);
        }

        $startTime = microtime(true);

        $result = CompanyRanking::query()
            ->join('companies', 'company_rankings.company_id', '=', 'companies.id')
            ->select('companies.name', 'company_rankings.total_score')
            ->orderBy('company_rankings.total_score', 'desc')
            ->limit(10)
            ->get();

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertCount(10, $result);
        $this->assertLessThan(1.0, $executionTime);
    }

    public function test_複雑な条件での企業検索()
    {
        $activeCompany = Company::factory()->create(['is_active' => true]);
        $inactiveCompany = Company::factory()->create(['is_active' => false]);
        $platform = Platform::factory()->create();

        Article::factory()->count(5)->create([
            'company_id' => $activeCompany->id,
            'platform_id' => $platform->id,
            'engagement_count' => 100,
        ]);

        CompanyRanking::factory()->create([
            'company_id' => $activeCompany->id,
            'ranking_period' => 'monthly',
            'rank_position' => 1,
            'total_score' => 200.0,
        ]);

        CompanyRanking::factory()->create([
            'company_id' => $inactiveCompany->id,
            'ranking_period' => 'monthly',
            'rank_position' => 2,
            'total_score' => 150.0,
        ]);

        $searchResult = CompanyRanking::query()
            ->join('companies', 'company_rankings.company_id', '=', 'companies.id')
            ->leftJoin('articles', 'companies.id', '=', 'articles.company_id')
            ->where('companies.is_active', true)
            ->where('company_rankings.ranking_period', 'monthly')
            ->where('company_rankings.rank_position', '<=', 5)
            ->where('company_rankings.total_score', '>=', 100)
            ->select('companies.name', 'company_rankings.rank_position', 'company_rankings.total_score')
            ->selectRaw('COUNT(articles.id) as article_count')
            ->selectRaw('SUM(articles.engagement_count) as total_engagement')
            ->groupBy('companies.id', 'companies.name', 'company_rankings.rank_position', 'company_rankings.total_score')
            ->orderBy('company_rankings.rank_position')
            ->get();

        $this->assertCount(1, $searchResult);
        $this->assertEquals(1, $searchResult->first()->rank_position);
        $this->assertEquals(5, $searchResult->first()->article_count);
    }

    public function test_インデックスを使用した効率的なクエリ()
    {
        $companies = Company::factory()->count(5)->create();
        $platform = Platform::factory()->create();

        foreach ($companies as $company) {
            CompanyRanking::factory()->create([
                'company_id' => $company->id,
                'ranking_period' => 'monthly',
                'rank_position' => $company->id,
                'calculated_at' => now()->subDays($company->id),
            ]);
        }

        $queries = [];

        \DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $result = CompanyRanking::query()
            ->where('ranking_period', 'monthly')
            ->where('rank_position', '<=', 3)
            ->orderBy('rank_position')
            ->get();

        $this->assertCount(3, $result);
        $this->assertNotEmpty($queries);
    }

    public function test_集計関数を使用した統計クエリ()
    {
        $company = Company::factory()->create();

        CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'period_type' => 'monthly',
            'total_score' => 100.0,
        ]);

        CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'period_type' => 'monthly',
            'total_score' => 200.0,
        ]);

        CompanyInfluenceScore::factory()->create([
            'company_id' => $company->id,
            'period_type' => 'monthly',
            'total_score' => 150.0,
        ]);

        $statistics = CompanyInfluenceScore::query()
            ->where('company_id', $company->id)
            ->where('period_type', 'monthly')
            ->selectRaw('
                COUNT(*) as score_count,
                MIN(total_score) as min_score,
                MAX(total_score) as max_score,
                AVG(total_score) as avg_score
            ')
            ->first();

        $this->assertEquals(3, $statistics->score_count);
        $this->assertEquals('100.00', $statistics->min_score);
        $this->assertEquals('200.00', $statistics->max_score);
        $this->assertEquals('150.00', $statistics->avg_score);
    }
}
