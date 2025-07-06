<?php

namespace Tests\Feature;

use App\Constants\RankingPeriod;
use App\Models\Company;
use App\Models\CompanyRanking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyRankingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestData();
    }

    private function setupTestData()
    {
        $companies = Company::factory()->count(10)->create();
        
        foreach ($companies as $index => $company) {
            CompanyRanking::factory()->create([
                'company_id' => $company->id,
                'ranking_period' => '1m',
                'rank_position' => $index + 1,
                'total_score' => 100 - ($index * 10),
                'article_count' => 10 - $index,
                'total_bookmarks' => 1000 - ($index * 100),
                'period_start' => now()->subMonth()->startOfDay(),
                'period_end' => now()->endOfDay(),
                'calculated_at' => now(),
            ]);
        }
    }

    public function testGetPeriodTypes()
    {
        $response = $this->getJson('/api/rankings/periods');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => []
                 ]);
    }

    public function testGetRankingByPeriod()
    {
        $response = $this->getJson('/api/rankings/1m');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'company' => [
                                 'id',
                                 'name',
                                 'domain',
                                 'logo_url'
                             ],
                             'rank_position',
                             'total_score',
                             'article_count',
                             'total_bookmarks',
                             'rank_change',
                             'period' => [
                                 'start',
                                 'end'
                             ],
                             'calculated_at'
                         ]
                     ],
                     'meta' => [
                         'current_page',
                         'per_page',
                         'total',
                         'last_page'
                     ]
                 ]);
    }

    public function testGetRankingByPeriodWithPagination()
    {
        $response = $this->getJson('/api/rankings/1m?page=1&per_page=5');

        $response->assertStatus(200)
                 ->assertJsonPath('meta.per_page', '5')
                 ->assertJsonPath('meta.current_page', '1');
    }

    public function testGetRankingByPeriodWithSorting()
    {
        $response = $this->getJson('/api/rankings/1m?sort_by=total_score&sort_order=desc');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertTrue($data[0]['total_score'] >= $data[1]['total_score']);
    }

    public function testGetRankingByPeriodWithInvalidPeriod()
    {
        $response = $this->getJson('/api/rankings/invalid');

        $response->assertStatus(400)
                 ->assertJsonStructure([
                     'error'
                 ]);
    }

    public function testGetTopRanking()
    {
        $response = $this->getJson('/api/rankings/1m/top/5');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'company' => [
                                 'id',
                                 'name',
                                 'domain',
                                 'logo_url'
                             ],
                             'rank_position',
                             'total_score',
                             'article_count',
                             'total_bookmarks',
                             'rank_change',
                             'period' => [
                                 'start',
                                 'end'
                             ],
                             'calculated_at'
                         ]
                     ],
                     'meta' => [
                         'period',
                         'limit',
                         'total'
                     ]
                 ])
                 ->assertJsonPath('meta.limit', 5);
    }

    public function testGetTopRankingWithInvalidLimit()
    {
        $response = $this->getJson('/api/rankings/1m/top/200');

        $response->assertStatus(400)
                 ->assertJsonStructure([
                     'error',
                     'details'
                 ]);
    }

    public function testGetCompanyRanking()
    {
        $company = Company::first();
        $response = $this->getJson("/api/rankings/company/{$company->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'company_id',
                         'rankings' => [
                             '1m' => [
                                 'rank_position',
                                 'total_score',
                                 'article_count',
                                 'total_bookmarks',
                                 'period_start',
                                 'period_end',
                                 'calculated_at'
                             ]
                         ]
                     ]
                 ])
                 ->assertJsonPath('data.company_id', $company->id);
    }

    public function testGetCompanyRankingWithHistory()
    {
        $company = Company::first();
        $response = $this->getJson("/api/rankings/company/{$company->id}?include_history=true");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'company_id',
                         'rankings',
                         'history'
                     ]
                 ]);
    }

    public function testGetCompanyRankingWithInvalidCompanyId()
    {
        $response = $this->getJson('/api/rankings/company/99999');

        $response->assertStatus(400)
                 ->assertJsonStructure([
                     'error',
                     'details'
                 ]);
    }

    public function testGetStatistics()
    {
        $response = $this->getJson('/api/rankings/statistics');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => []
                 ]);
    }

    public function testGetRankingRisers()
    {
        $response = $this->getJson('/api/rankings/1m/risers');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [],
                     'meta' => [
                         'period',
                         'limit',
                         'total'
                     ]
                 ]);
    }

    public function testGetRankingRisersWithLimit()
    {
        $response = $this->getJson('/api/rankings/1m/risers?limit=5');

        $response->assertStatus(200)
                 ->assertJsonPath('meta.limit', '5');
    }

    public function testGetRankingFallers()
    {
        $response = $this->getJson('/api/rankings/1m/fallers');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [],
                     'meta' => [
                         'period',
                         'limit',
                         'total'
                     ]
                 ]);
    }

    public function testGetRankingChangeStatistics()
    {
        $response = $this->getJson('/api/rankings/1m/statistics');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => []
                 ]);
    }

    public function testApiRateLimiting()
    {
        for ($i = 0; $i < 65; $i++) {
            $response = $this->getJson('/api/rankings/periods');
            
            if ($i < 60) {
                $response->assertStatus(200);
            } else {
                $response->assertStatus(429);
                break;
            }
        }
    }

    public function testApiCaching()
    {
        $response1 = $this->getJson('/api/rankings/1m');
        $response2 = $this->getJson('/api/rankings/1m');

        $response1->assertStatus(200);
        $response2->assertStatus(200);
        
        $this->assertEquals($response1->json(), $response2->json());
    }

    public function testApiPaginationMetadata()
    {
        $response = $this->getJson('/api/rankings/1m?per_page=3&page=2');

        $response->assertStatus(200)
                 ->assertJsonPath('meta.current_page', '2')
                 ->assertJsonPath('meta.per_page', '3')
                 ->assertJsonPath('meta.total', '10');
    }

    public function testApiResponseStructure()
    {
        $response = $this->getJson('/api/rankings/1m');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        
        foreach ($data as $item) {
            $this->assertArrayHasKey('company', $item);
            $this->assertArrayHasKey('rank_position', $item);
            $this->assertArrayHasKey('total_score', $item);
            $this->assertArrayHasKey('article_count', $item);
            $this->assertArrayHasKey('total_bookmarks', $item);
            $this->assertArrayHasKey('period', $item);
            $this->assertArrayHasKey('calculated_at', $item);
        }
    }
}