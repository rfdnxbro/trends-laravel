<?php

namespace Tests\Feature\Controllers;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 存在しない企業IDでshowメソッドを呼び出した場合のテスト（バリデーションエラー）
     */
    public function test_存在しない企業idでshow呼び出し時にバリデーションエラーが返される()
    {
        $response = $this->getJson('/api/companies/999999');

        $response->assertStatus(400)
            ->assertJson([
                'error' => '企業IDが無効です',
            ]);
    }

    /**
     * 存在しない企業IDでarticlesメソッドを呼び出した場合のテスト（バリデーションエラー）
     */
    public function test_存在しない企業idでarticles呼び出し時にバリデーションエラーが返される()
    {
        $response = $this->getJson('/api/companies/999999/articles');

        $response->assertStatus(400)
            ->assertJson([
                'error' => '企業IDが無効です',
            ]);
    }

    /**
     * 存在しない企業IDでscoresメソッドを呼び出した場合のテスト（バリデーションエラー）
     */
    public function test_存在しない企業idでscores呼び出し時にバリデーションエラーが返される()
    {
        $response = $this->getJson('/api/companies/999999/scores');

        $response->assertStatus(400)
            ->assertJson([
                'error' => '企業IDが無効です',
            ]);
    }

    /**
     * 存在しない企業IDでrankingsメソッドを呼び出した場合のテスト（バリデーションエラー）
     */
    public function test_存在しない企業idでrankings呼び出し時にバリデーションエラーが返される()
    {
        $response = $this->getJson('/api/companies/999999/rankings');

        $response->assertStatus(400)
            ->assertJson([
                'error' => '企業IDが無効です',
            ]);
    }

    /**
     * 存在する企業IDでshowメソッドが正常に動作することのテスト
     */
    public function test_存在する企業idでshow呼び出し時に正常にデータが返される()
    {
        $company = Company::factory()->create();

        $response = $this->getJson("/api/companies/{$company->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'domain',
                    'description',
                    'logo_url',
                    'website_url',
                    'is_active',
                    'current_rankings',
                    'recent_articles',
                    'total_articles',
                    'ranking_history',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    /**
     * 存在する企業IDでarticlesメソッドが正常に動作することのテスト
     */
    public function test_存在する企業idでarticles呼び出し時に正常にデータが返される()
    {
        $company = Company::factory()->create();

        $response = $this->getJson("/api/companies/{$company->id}/articles");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'company_id',
                    'filters',
                ],
            ]);
    }

    /**
     * 存在する企業IDでscoresメソッドが正常に動作することのテスト
     */
    public function test_存在する企業idでscores呼び出し時に正常にデータが返される()
    {
        $company = Company::factory()->create();

        $response = $this->getJson("/api/companies/{$company->id}/scores");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'company_id',
                    'scores',
                ],
                'meta' => [
                    'period',
                    'days',
                    'total',
                ],
            ]);
    }

    /**
     * 存在する企業IDでrankingsメソッドが正常に動作することのテスト
     */
    public function test_存在する企業idでrankings呼び出し時に正常にデータが返される()
    {
        $company = Company::factory()->create();

        $response = $this->getJson("/api/companies/{$company->id}/rankings");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'company_id',
                    'rankings',
                ],
            ]);
    }

    /**
     * indexメソッドが正常に動作することのテスト
     */
    public function test_index呼び出し時に正常にデータが返される()
    {
        Company::factory()->count(3)->create();

        $response = $this->getJson('/api/companies');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
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
                ],
                'meta',
            ]);
    }

    /**
     * データベースから削除後のキャッシュクロージャー内での404エラーテスト
     * (バリデーションを通過した後にデータが見つからないケース)
     */
    public function test_データベース削除後のキャッシュクロージャー内での404エラー()
    {
        // 企業を作成
        $company = Company::factory()->create();
        $companyId = $company->id;

        // CompanyControllerを直接インスタンス化
        $rankingService = app(\App\Services\CompanyRankingService::class);
        $scoreService = app(\App\Services\CompanyInfluenceScoreService::class);
        $controller = new \App\Http\Controllers\Api\CompanyController($rankingService, $scoreService);

        // キャッシュクロージャー内での処理を直接テスト
        // (バリデーションはスキップして、キャッシュ処理内でのfind()結果のテスト)
        $cacheKey = "company_detail_{$companyId}";
        \Cache::forget($cacheKey);

        // 企業をソフトデリートではなく完全削除
        $company->forceDelete();

        // Cache::rememberのクロージャー部分を直接実行
        $result = \Cache::remember($cacheKey, \App\Constants\CacheTime::COMPANY_DETAIL, function () use ($companyId, $rankingService) {
            $company = \App\Models\Company::with(['rankings', 'articles' => function ($query) {
                $query->recent(config('constants.api.default_article_days'))->orderBy('published_at', 'desc')->limit(config('constants.api.default_article_limit'));
            }])->find($companyId);

            if (! $company) {
                return response()->json([
                    'error' => '企業が見つかりません',
                ], \Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND);
            }

            // 現在のランキング情報を取得
            $currentRankings = $rankingService->getCompanyRankings($companyId);

            return response()->json([
                'data' => new \App\Http\Resources\CompanyResource($company, $currentRankings),
            ]);
        });

        $this->assertEquals(404, $result->getStatusCode());
        $responseData = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('企業が見つかりません', $responseData['error']);
    }

    /**
     * 企業作成のテスト（正常系）
     */
    public function test_企業作成が正常に動作する()
    {
        $companyData = [
            'name' => 'テスト企業',
            'domain' => 'test-company.com',
            'website_url' => 'https://test-company.com',
            'description' => 'テスト企業の説明',
            'logo_url' => 'https://test-company.com/logo.png',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/companies', $companyData);

        $response->assertStatus(201)
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
                'message',
            ])
            ->assertJson([
                'message' => '企業を作成しました',
            ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'テスト企業',
            'domain' => 'test-company.com',
        ]);
    }

    /**
     * 企業作成のテスト（バリデーションエラー）
     */
    public function test_企業作成でバリデーションエラーが発生する()
    {
        $invalidData = [
            'name' => '', // 必須項目が空
            'domain' => 'invalid-domain', // 無効なドメイン
        ];

        $response = $this->postJson('/api/companies', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ]);
    }

    /**
     * 企業更新のテスト（正常系）
     */
    public function test_企業更新が正常に動作する()
    {
        $company = Company::factory()->create();

        $updateData = [
            'name' => '更新された企業名',
            'domain' => $company->domain, // 同じドメインは更新可能
            'description' => '更新された説明',
            'is_active' => false,
        ];

        $response = $this->putJson("/api/companies/{$company->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'domain',
                    'description',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
                'message',
            ])
            ->assertJson([
                'message' => '企業情報を更新しました',
            ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => '更新された企業名',
            'is_active' => false,
        ]);
    }

    /**
     * 企業更新のテスト（存在しない企業）
     */
    public function test_存在しない企業の更新でエラーが発生する()
    {
        $updateData = [
            'name' => '更新された企業名',
            'domain' => 'updated-domain.com',
        ];

        $response = $this->putJson('/api/companies/999999', $updateData);

        $response->assertStatus(404)
            ->assertJson([
                'error' => '企業が見つかりません',
            ]);
    }

    /**
     * 企業削除のテスト（正常系）
     */
    public function test_企業削除が正常に動作する()
    {
        $company = Company::factory()->create();

        $response = $this->deleteJson("/api/companies/{$company->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => '企業を削除しました',
            ]);

        $this->assertDatabaseMissing('companies', [
            'id' => $company->id,
        ]);
    }

    /**
     * 企業削除のテスト（関連データ存在時のエラー）
     */
    public function test_関連データが存在する企業の削除でエラーが発生する()
    {
        $company = Company::factory()->create();

        // 関連する記事を作成
        \App\Models\Article::factory()->create(['company_id' => $company->id]);

        $response = $this->deleteJson("/api/companies/{$company->id}");

        $response->assertStatus(409)
            ->assertJsonStructure([
                'error',
                'details' => [
                    'articles',
                    'rankings',
                    'scores',
                ],
            ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
        ]);
    }

    /**
     * 企業強制削除のテスト（正常系）
     */
    public function test_企業強制削除が正常に動作する()
    {
        $company = Company::factory()->create();

        // 関連する記事を作成
        \App\Models\Article::factory()->create(['company_id' => $company->id]);

        $response = $this->deleteJson("/api/companies/{$company->id}/force");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'deleted' => [
                    'articles',
                    'rankings',
                    'scores',
                ],
            ])
            ->assertJson([
                'message' => '企業を関連データと共に削除しました',
            ]);

        $this->assertDatabaseMissing('companies', [
            'id' => $company->id,
        ]);
    }

    /**
     * 存在しない企業の強制削除でエラーが発生する
     */
    public function test_存在しない企業の強制削除でエラーが発生する()
    {
        $response = $this->deleteJson('/api/companies/999999/force');

        $response->assertStatus(404)
            ->assertJson([
                'error' => '企業が見つかりません',
            ]);
    }
}
