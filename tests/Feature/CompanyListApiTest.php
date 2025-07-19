<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CompanyListApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用企業データを作成
        Company::factory()->create([
            'name' => 'Test Company A',
            'domain' => 'test-a.com',
            'description' => 'テスト企業Aの説明',
            'is_active' => true,
        ]);

        Company::factory()->create([
            'name' => 'Test Company B',
            'domain' => 'test-b.com',
            'description' => 'テスト企業Bの説明',
            'is_active' => true,
        ]);

        Company::factory()->create([
            'name' => 'Inactive Company',
            'domain' => 'inactive.com',
            'description' => '非アクティブ企業',
            'is_active' => false,
        ]);
    }

    public function test_企業一覧が正常に取得できる(): void
    {
        $response = $this->getJson('/api/companies');

        $response->assertStatus(Response::HTTP_OK)
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
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'filters',
                ],
            ]);

        // デフォルトではアクティブな企業のみ表示される
        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals('Test Company A', $data[0]['name']);
        $this->assertEquals('Test Company B', $data[1]['name']);
    }

    public function test_企業名での検索ができる(): void
    {
        $response = $this->getJson('/api/companies?search=Company A');

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Test Company A', $data[0]['name']);
    }

    public function test_ドメインでの検索ができる(): void
    {
        $response = $this->getJson('/api/companies?domain=test-b');

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('test-b.com', $data[0]['domain']);
    }

    public function test_アクティブ状態でのフィルタリングができる(): void
    {
        // 非アクティブ企業も含めて取得
        $response = $this->getJson('/api/companies?is_active=0');

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Inactive Company', $data[0]['name']);
        $this->assertFalse($data[0]['is_active']);
    }

    public function test_ソート機能が正常に動作する(): void
    {
        // 名前の降順でソート
        $response = $this->getJson('/api/companies?sort_by=name&sort_order=desc');

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json('data');
        $this->assertEquals('Test Company B', $data[0]['name']);
        $this->assertEquals('Test Company A', $data[1]['name']);
    }

    public function test_ページネーションが正常に動作する(): void
    {
        // 1ページあたり1件で取得
        $response = $this->getJson('/api/companies?per_page=1&page=1');

        $response->assertStatus(Response::HTTP_OK);
        $meta = $response->json('meta');
        $this->assertEquals(1, $meta['per_page']);
        $this->assertEquals(1, $meta['current_page']);
        $this->assertEquals(2, $meta['total']); // アクティブな企業は2社
        $this->assertEquals(2, $meta['last_page']);

        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_per_pageの上限が適用される(): void
    {
        $response = $this->getJson('/api/companies?per_page=200');

        $response->assertStatus(Response::HTTP_OK);
        $meta = $response->json('meta');
        $this->assertEquals(100, $meta['per_page']); // 上限100に制限される
    }

    public function test_不正なパラメータでバリデーションエラーが返される(): void
    {
        $response = $this->getJson('/api/companies?page=-1&per_page=0&sort_by=invalid&sort_order=invalid');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonStructure([
                'error',
                'details',
            ]);
    }

    public function test_複数条件での検索ができる(): void
    {
        $response = $this->getJson('/api/companies?search=Test&sort_by=name&sort_order=desc&per_page=10');

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json('data');
        $this->assertCount(2, $data);
        // 降順ソートが適用されている
        $this->assertEquals('Test Company B', $data[0]['name']);
        $this->assertEquals('Test Company A', $data[1]['name']);
    }

    public function test_キャッシュが正常に動作する(): void
    {
        // 1回目のリクエスト
        $response1 = $this->getJson('/api/companies');
        $response1->assertStatus(Response::HTTP_OK);

        // 2回目のリクエスト（キャッシュから取得）
        $response2 = $this->getJson('/api/companies');
        $response2->assertStatus(Response::HTTP_OK);

        // 同じレスポンスが返される
        $this->assertEquals($response1->json(), $response2->json());
    }

    public function test_空の結果が適切に処理される(): void
    {
        // 存在しない企業名で検索
        $response = $this->getJson('/api/companies?search=NonExistentCompany');

        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json('data');
        $this->assertCount(0, $data);

        $meta = $response->json('meta');
        $this->assertEquals(0, $meta['total']);
    }
}
