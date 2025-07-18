<?php

namespace Tests\Unit\Models;

use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_基本的なモデル作成ができる()
    {
        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        $this->assertInstanceOf(Platform::class, $platform);
        $this->assertTrue($platform->exists);
    }

    public function test_必須フィールドの検証()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Platform::create([]);
    }

    public function test_デフォルト値の動作確認()
    {
        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        $platform->refresh();
        $this->assertTrue($platform->is_active);
    }

    public function test_fillable属性の確認()
    {
        $platform = new Platform;
        $fillable = $platform->getFillable();

        $expected = [
            'name',
            'base_url',
            'is_active',
        ];

        $this->assertEquals($expected, $fillable);
    }

    public function test_型変換の確認()
    {
        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
            'is_active' => '1',
        ]);

        $platform->refresh();
        $this->assertIsBool($platform->is_active);
        $this->assertTrue($platform->is_active);

        $platform->is_active = false;
        $this->assertIsBool($platform->is_active);
        $this->assertFalse($platform->is_active);
    }

    public function test_タイムスタンプの確認()
    {
        $platform = Platform::create([
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
        ]);

        $this->assertNotNull($platform->created_at);
        $this->assertNotNull($platform->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $platform->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $platform->updated_at);
    }

    public function test_activeスコープの動作確認()
    {
        Platform::create([
            'name' => 'アクティブプラットフォーム',
            'base_url' => 'https://active.example.com',
            'is_active' => true,
        ]);

        Platform::create([
            'name' => '非アクティブプラットフォーム',
            'base_url' => 'https://inactive.example.com',
            'is_active' => false,
        ]);

        $activePlatforms = Platform::active()->get();
        $this->assertCount(1, $activePlatforms);
        $this->assertEquals('アクティブプラットフォーム', $activePlatforms->first()->name);
    }

    public function test_mass_assignment_protectionの確認()
    {
        $data = [
            'name' => $this->faker()->company(),
            'base_url' => $this->faker()->url(),
            'created_at' => now()->subDays(10),
        ];

        $platform = Platform::create($data);

        $this->assertNotEquals($data['created_at'], $platform->created_at);
    }

    public function test_articlesリレーションが正しく動作する()
    {
        $platform = Platform::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $platform->articles());
        $this->assertEquals(\App\Models\Article::class, $platform->articles()->getRelated()::class);
    }

    public function test_castsが正しく設定されている()
    {
        $platform = new Platform;
        $casts = $platform->getCasts();

        $this->assertEquals('boolean', $casts['is_active']);
    }

    public function test_name_unique制約のテスト()
    {
        $name = 'Test Platform';

        Platform::create([
            'name' => $name,
            'base_url' => 'https://example.com',
        ]);

        // 同じ名前のプラットフォームは作成できない（unique制約がある）
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Platform::create([
            'name' => $name,
            'base_url' => 'https://example2.com',
        ]);
    }
}
