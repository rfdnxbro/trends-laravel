<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyRanking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
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
        $companies = Company::factory()->count(10)->create(['is_active' => true]);

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

    public function test_期間タイプ一覧を取得する()
    {
        $response = $this->getJson('/api/rankings/periods');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [],
            ]);
    }

    public function test_期間別でランキングを取得する()
    {
        $response = $this->getJson('/api/rankings/1m');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'company' => [
                            'id',
                            'name',
                            'domain',
                            'logo_url',
                        ],
                        'rank_position',
                        'total_score',
                        'article_count',
                        'total_bookmarks',
                        'rank_change',
                        'period' => [
                            'start',
                            'end',
                        ],
                        'calculated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    public function test_期間別ランキングでページングが動作する()
    {
        $response = $this->getJson('/api/rankings/1m?page=1&per_page=5');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('meta.per_page', '5')
            ->assertJsonPath('meta.current_page', '1');
    }

    public function test_期間別ランキングでソートが動作する()
    {
        $response = $this->getJson('/api/rankings/1m?sort_by=total_score&sort_order=desc');

        $response->assertStatus(Response::HTTP_OK);

        $data = $response->json('data');
        $this->assertTrue($data[0]['total_score'] >= $data[1]['total_score']);
    }

    public function test_無効な期間でランキング取得するとエラーが返る()
    {
        $response = $this->getJson('/api/rankings/invalid');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure([
                'error',
            ]);
    }

    public function test_トップランキングを取得する()
    {
        $response = $this->getJson('/api/rankings/1m/top/5');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'company' => [
                            'id',
                            'name',
                            'domain',
                            'logo_url',
                        ],
                        'rank_position',
                        'total_score',
                        'article_count',
                        'total_bookmarks',
                        'rank_change',
                        'period' => [
                            'start',
                            'end',
                        ],
                        'calculated_at',
                    ],
                ],
                'meta' => [
                    'period',
                    'limit',
                    'total',
                ],
            ])
            ->assertJsonPath('meta.limit', 5);
    }

    public function test_無効なリミットでトップランキング取得するとエラーが返る()
    {
        $response = $this->getJson('/api/rankings/1m/top/200');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure([
                'error',
                'details',
            ]);
    }

    public function test_企業別ランキングを取得する()
    {
        $company = Company::first();
        $response = $this->getJson("/api/rankings/company/{$company->id}");

        $response->assertStatus(Response::HTTP_OK)
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
                            'calculated_at',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.company_id', $company->id);
    }

    public function test_履歴付きで企業別ランキングを取得する()
    {
        $company = Company::first();
        $response = $this->getJson("/api/rankings/company/{$company->id}?include_history=true");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'company_id',
                    'rankings',
                    'history',
                ],
            ]);
    }

    public function test_無効な企業idでランキング取得するとエラーが返る()
    {
        $response = $this->getJson('/api/rankings/company/99999');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure([
                'error',
                'details',
            ]);
    }

    public function test_統計情報を取得する()
    {
        $response = $this->getJson('/api/rankings/statistics');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [],
            ]);
    }

    public function test_ランキング上昇企業を取得する()
    {
        $response = $this->getJson('/api/rankings/1m/risers');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [],
                'meta' => [
                    'period',
                    'limit',
                    'total',
                ],
            ]);
    }

    public function test_リミット付きでランキング上昇企業を取得する()
    {
        $response = $this->getJson('/api/rankings/1m/risers?limit=5');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('meta.limit', '5');
    }

    public function test_ランキング下降企業を取得する()
    {
        $response = $this->getJson('/api/rankings/1m/fallers');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [],
                'meta' => [
                    'period',
                    'limit',
                    'total',
                ],
            ]);
    }

    public function test_ランキング変動統計を取得する()
    {
        $response = $this->getJson('/api/rankings/1m/statistics');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [],
            ]);
    }

    public function test_apiレート制限が動作する()
    {
        for ($i = 0; $i < 65; $i++) {
            $response = $this->getJson('/api/rankings/periods');

            if ($i < 60) {
                $response->assertStatus(Response::HTTP_OK);
            } else {
                $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
                break;
            }
        }
    }

    public function test_apiキャッシュが動作する()
    {
        $response1 = $this->getJson('/api/rankings/1m');
        $response2 = $this->getJson('/api/rankings/1m');

        $response1->assertStatus(Response::HTTP_OK);
        $response2->assertStatus(Response::HTTP_OK);

        $this->assertEquals($response1->json(), $response2->json());
    }

    public function test_apiページングメタデータが正常に返される()
    {
        $response = $this->getJson('/api/rankings/1m?per_page=3&page=2');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('meta.current_page', '2')
            ->assertJsonPath('meta.per_page', '3')
            ->assertJsonPath('meta.total', 10);
    }

    public function test_apiレスポンス構造が正常である()
    {
        $response = $this->getJson('/api/rankings/1m');

        $response->assertStatus(Response::HTTP_OK);

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

    public function test_週次ランキング_apiが正常に動作する()
    {
        $response = $this->getJson('/api/rankings/1w');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'company' => [
                            'id',
                            'name',
                            'domain',
                            'logo_url',
                        ],
                        'rank_position',
                        'total_score',
                        'article_count',
                        'total_bookmarks',
                        'rank_change',
                        'period' => [
                            'start',
                            'end',
                        ],
                        'calculated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);

        // レスポンス内容の型チェック
        $data = $response->json('data');
        if (! empty($data)) {
            $firstItem = $data[0];
            $this->assertIsInt($firstItem['rank_position']);
            $this->assertIsFloat($firstItem['total_score']);
            $this->assertIsInt($firstItem['article_count']);
            $this->assertIsInt($firstItem['total_bookmarks']);
        }
    }

    public function test_企業詳細_apiとの統合が正常に動作する()
    {
        // テストデータが存在することを確認
        $company = Company::first();
        $this->assertNotNull($company, 'テスト用の企業データが存在しません');

        // 企業詳細APIを呼び出し
        $detailResponse = $this->getJson("/api/companies/{$company->id}");

        $detailResponse->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'domain',
                    'description',
                    'logo_url',
                    'website_url',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $detailData = $detailResponse->json();
        $this->assertEquals($company->id, $detailData['data']['id']);
        $this->assertIsString($detailData['data']['name']);
    }
}
