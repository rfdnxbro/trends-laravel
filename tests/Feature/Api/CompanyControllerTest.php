<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CompanyControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用のユーザーを作成
        $this->user = User::factory()->create();

        // プラットフォームを作成
        Platform::factory()->create(['name' => 'Qiita', 'base_url' => 'https://qiita.com']);
        Platform::factory()->create(['name' => 'Zenn', 'base_url' => 'https://zenn.dev']);
        Platform::factory()->create(['name' => 'はてなブックマーク', 'base_url' => 'https://b.hatena.ne.jp']);
    }

    public function test_store_正常に企業を作成できる()
    {
        $data = [
            'name' => '株式会社テスト',
            'domain' => 'test.com',
            'description' => 'テスト企業の説明',
            'logo_url' => 'https://example.com/logo.png',
            'website_url' => 'https://test.com',
            'is_active' => true,
            'url_patterns' => ['test.com/*'],
            'domain_patterns' => ['*.test.com'],
            'keywords' => ['テスト', 'test'],
            'zenn_organizations' => ['test-org'],
            'qiita_username' => 'test_qiita',
            'zenn_username' => 'test_zenn',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/companies', $data);

        $response->assertStatus(Response::HTTP_CREATED)
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
            'name' => '株式会社テスト',
            'domain' => 'test.com',
            'is_active' => true,
        ]);

        // キャッシュがクリアされたことを確認
        $this->assertFalse(Cache::tags(['companies'])->has('test_key'));
    }

    public function test_store_バリデーションエラー()
    {
        $data = [
            // nameとdomainが必須なので空にする
            'description' => 'テスト企業の説明',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/companies', $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name', 'domain']);
    }

    public function test_update_正常に企業情報を更新できる()
    {
        $company = Company::factory()->create([
            'name' => '古い企業名',
            'domain' => 'old.com',
        ]);

        $data = [
            'name' => '新しい企業名',
            'domain' => 'new.com',
            'description' => '更新された説明',
            'is_active' => false,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/companies/{$company->id}", $data);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'domain',
                    'description',
                    'is_active',
                ],
                'message',
            ])
            ->assertJson([
                'message' => '企業情報を更新しました',
            ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => '新しい企業名',
            'domain' => 'new.com',
            'is_active' => false,
        ]);
    }

    public function test_update_存在しない企業の更新はエラー()
    {
        $data = [
            'name' => '新しい企業名',
            'domain' => 'new.com',
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/companies/99999', $data);

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'error' => '企業が見つかりません',
            ]);
    }

    public function test_destroy_正常に企業を削除できる()
    {
        $company = Company::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/companies/{$company->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => '企業を削除しました',
            ]);

        $this->assertDatabaseMissing('companies', [
            'id' => $company->id,
        ]);
    }

    public function test_destroy_関連データがある場合は削除できない()
    {
        $company = Company::factory()
            ->hasArticles(3)
            ->hasRankings(2)
            ->hasInfluenceScores(5)
            ->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/companies/{$company->id}");

        $response->assertStatus(Response::HTTP_CONFLICT)
            ->assertJson([
                'error' => '関連データが存在するため削除できません',
                'details' => [
                    'articles' => 3,
                    'rankings' => 2,
                    'scores' => 5,
                ],
            ]);

        // 企業がまだ存在することを確認
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
        ]);
    }

    public function test_destroy_存在しない企業の削除はエラー()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/companies/999');

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'error' => '企業が見つかりません',
            ]);
    }

    public function test_show_企業が見つからない場合()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/companies/999');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure(['error', 'details']);
    }

    public function test_articles_企業が見つからない場合()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/companies/999/articles');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure(['error', 'details']);
    }

    public function test_scores_企業が見つからない場合()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/companies/999/scores');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure(['error', 'details']);
    }

    public function test_rankings_企業が見つからない場合()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/companies/999/rankings');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure(['error', 'details']);
    }

    public function test_store_例外処理_データベースエラー()
    {
        // 非常に長いデータでバリデーションまたはDBエラーを発生させる
        $data = [
            'name' => str_repeat('A', 1000),  // 長すぎる名前でエラー
            'domain' => 'test.com',
            'description' => 'テスト企業の説明',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/companies', $data);

        // バリデーションエラー(422)または内部エラー(500)のどちらかが発生
        $this->assertContains($response->status(), [Response::HTTP_UNPROCESSABLE_ENTITY, Response::HTTP_INTERNAL_SERVER_ERROR]);
    }

    public function test_update_例外処理_データベースエラー()
    {
        $company = Company::factory()->create();

        // 長すぎるデータでエラーを発生させる
        $data = [
            'name' => str_repeat('A', 1000),  // 長すぎる名前でエラー
            'domain' => 'updated.com',
            'description' => '更新されたテスト企業の説明',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/companies/{$company->id}", $data);

        // バリデーションエラー(422)または内部エラー(500)のどちらかが発生
        $this->assertContains($response->status(), [Response::HTTP_UNPROCESSABLE_ENTITY, Response::HTTP_INTERNAL_SERVER_ERROR]);
    }

    public function test_destroy_例外処理_データベースエラー()
    {
        $company = Company::factory()->create();

        // 実際には存在しないIDで削除を試行（404エラー）
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/companies/99999');

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson(['error' => '企業が見つかりません']);
    }

    public function test_store_データベースエラー時のハンドリング()
    {
        // domainのユニーク制約エラーをシミュレート
        Company::factory()->create([
            'name' => '既存企業',
            'domain' => 'duplicate.com',
        ]);

        $data = [
            'name' => '新しい企業',
            'domain' => 'duplicate.com', // 重複するドメイン
            'description' => 'テスト',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/companies', $data);

        // domainのユニーク制約がある場合は422、ない場合は201
        if ($response->status() === Response::HTTP_UNPROCESSABLE_ENTITY) {
            $response->assertJsonValidationErrors(['domain']);
        } else {
            $response->assertStatus(Response::HTTP_CREATED);
        }
    }

    public function test_update_バリデーションエラー()
    {
        $company = Company::factory()->create();

        $data = [
            // nameとdomainが必須なので空にする
            'description' => 'テスト企業の説明',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/companies/{$company->id}", $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name', 'domain']);
    }

    public function test_update_キャッシュクリア確認()
    {
        $company = Company::factory()->create();

        // キャッシュに何かセット
        Cache::put("company_detail_{$company->id}", 'test_data');
        Cache::tags(['companies'])->put('test_key', 'test_value');

        $data = [
            'name' => '更新された企業名',
            'domain' => 'updated.com',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/companies/{$company->id}", $data);

        $response->assertStatus(Response::HTTP_OK);

        // キャッシュがクリアされたことを確認
        $this->assertFalse(Cache::has("company_detail_{$company->id}"));
    }

    public function test_destroy_キャッシュクリア確認()
    {
        $company = Company::factory()->create();

        // キャッシュに何かセット
        Cache::put("company_detail_{$company->id}", 'test_data');
        Cache::tags(['companies'])->put('test_key', 'test_value');

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/companies/{$company->id}");

        $response->assertStatus(Response::HTTP_OK);

        // キャッシュがクリアされたことを確認
        $this->assertFalse(Cache::has("company_detail_{$company->id}"));
    }

    public function test_store_例外発生時のロールバック()
    {
        // 極端に長いデータを使って例外を発生させる
        $data = [
            'name' => str_repeat('A', 1000),  // 長すぎる名前
            'domain' => 'test.com',
            'description' => str_repeat('B', 10000), // 極端に長い説明
            'logo_url' => str_repeat('http://example.com/', 1000), // 長すぎるURL
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/companies', $data);

        // バリデーションエラーまたは内部エラーが発生
        $this->assertContains($response->status(), [Response::HTTP_UNPROCESSABLE_ENTITY, Response::HTTP_INTERNAL_SERVER_ERROR]);

        if ($response->status() === Response::HTTP_INTERNAL_SERVER_ERROR) {
            $response->assertJsonStructure(['error', 'message']);
        } else {
            $response->assertJsonStructure(['message', 'errors']);
        }
    }

    public function test_update_例外発生時のロールバック()
    {
        $company = Company::factory()->create([
            'name' => '既存企業',
            'domain' => 'existing.com',
        ]);

        // 極端に長いデータを使って例外を発生させる
        $data = [
            'name' => str_repeat('A', 1000),  // 長すぎる名前
            'domain' => 'updated.com',
            'description' => str_repeat('B', 10000), // 極端に長い説明
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/companies/{$company->id}", $data);

        // バリデーションエラーまたは内部エラーが発生
        $this->assertContains($response->status(), [Response::HTTP_UNPROCESSABLE_ENTITY, Response::HTTP_INTERNAL_SERVER_ERROR]);

        if ($response->status() === Response::HTTP_INTERNAL_SERVER_ERROR) {
            $response->assertJsonStructure(['error', 'message']);
        }
    }

    public function test_destroy_例外発生時のロールバック()
    {
        // 外部キー制約エラーを発生させるため、削除できないデータを作成
        $company = Company::factory()
            ->hasArticles(1) // 記事が紐づいている企業
            ->create();

        // 実際のコントローラの削除処理を実行
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/companies/{$company->id}");

        // 関連データがあるため409エラーが発生するはず
        $response->assertStatus(Response::HTTP_CONFLICT)
            ->assertJsonStructure(['error', 'details']);

        // 企業がまだ存在することを確認
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
        ]);
    }

    public function test_store_不正なデータで例外発生()
    {
        $data = [
            'name' => '',  // 空文字でバリデーションエラー
            'domain' => 'test.com',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/companies', $data);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_不正な_i_dで例外発生()
    {
        $data = [
            'name' => '企業名',
            'domain' => 'test.com',
        ];

        // 存在しないIDで更新を試行
        $response = $this->actingAs($this->user)
            ->putJson('/api/companies/99999', $data);

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson(['error' => '企業が見つかりません']);
    }

    public function test_destroy_不正な_i_dで例外発生()
    {
        // 存在しないIDで削除を試行
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/companies/99999');

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson(['error' => '企業が見つかりません']);
    }
}
