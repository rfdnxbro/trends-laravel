<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * プラットフォーム一覧取得のテスト
     */
    public function test_プラットフォーム一覧を正常に取得できる()
    {
        // テスト用プラットフォームを作成
        Platform::factory()->create(['name' => 'Zenn', 'base_url' => 'https://zenn.dev']);
        Platform::factory()->create(['name' => 'Qiita', 'base_url' => 'https://qiita.com']);
        Platform::factory()->create(['name' => 'はてなブックマーク', 'base_url' => 'https://b.hatena.ne.jp']);

        $response = $this->getJson('/api/platforms');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'base_url',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        // データが名前順でソートされていることを確認
        $data = $response->json('data');
        $this->assertEquals(3, count($data));
        $this->assertEquals('Qiita', $data[0]['name']);
        $this->assertEquals('Zenn', $data[1]['name']);
        $this->assertEquals('はてなブックマーク', $data[2]['name']);
    }

    /**
     * プラットフォームが存在しない場合のテスト
     */
    public function test_プラットフォームが存在しない場合は空配列を返す()
    {
        $response = $this->getJson('/api/platforms');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }
}
