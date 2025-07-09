<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_基本的なモデル作成ができる()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
            'description' => $this->faker()->text(200),
            'logo_url' => $this->faker()->imageUrl(),
            'website_url' => $this->faker()->url(),
        ]);

        $this->assertInstanceOf(Company::class, $company);
        $this->assertTrue($company->exists);
    }

    public function test_必須フィールドの検証()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Company::create([]);
    }

    public function test_domain_unique制約のテスト()
    {
        $domain = $this->faker()->domainName();

        Company::create([
            'name' => $this->faker()->company(),
            'domain' => $domain,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Company::create([
            'name' => $this->faker()->company(),
            'domain' => $domain,
        ]);
    }

    public function test_デフォルト値の動作確認()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $this->assertTrue($company->is_active);
    }

    public function test_fillable属性の確認()
    {
        $company = new Company;
        $fillable = $company->getFillable();

        $expected = [
            'name',
            'domain',
            'description',
            'logo_url',
            'website_url',
            'is_active',
        ];

        $this->assertEquals($expected, $fillable);
    }

    public function test_型変換の確認()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
            'is_active' => '1',
        ]);

        $this->assertIsBool($company->is_active);
        $this->assertTrue($company->is_active);

        $company->is_active = false;
        $this->assertIsBool($company->is_active);
        $this->assertFalse($company->is_active);
    }

    public function test_タイムスタンプの確認()
    {
        $company = Company::create([
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
        ]);

        $this->assertNotNull($company->created_at);
        $this->assertNotNull($company->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $company->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $company->updated_at);
    }

    public function test_activeスコープの動作確認()
    {
        Company::create([
            'name' => 'アクティブ企業',
            'domain' => 'active.example.com',
            'is_active' => true,
        ]);

        Company::create([
            'name' => '非アクティブ企業',
            'domain' => 'inactive.example.com',
            'is_active' => false,
        ]);

        $activeCompanies = Company::active()->get();
        $this->assertCount(1, $activeCompanies);
        $this->assertEquals('アクティブ企業', $activeCompanies->first()->name);
    }

    public function test_mass_assignment_protectionの確認()
    {
        $data = [
            'name' => $this->faker()->company(),
            'domain' => $this->faker()->domainName(),
            'created_at' => now()->subDays(10),
        ];

        $company = Company::create($data);

        $this->assertNotEquals($data['created_at'], $company->created_at);
    }

    public function test_rankingsリレーションが正しく動作する()
    {
        $company = Company::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $company->rankings());
        $this->assertEquals(\App\Models\CompanyRanking::class, $company->rankings()->getRelated()::class);
    }

    public function test_influenceScoresリレーションが正しく動作する()
    {
        $company = Company::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $company->influenceScores());
        $this->assertEquals(\App\Models\CompanyInfluenceScore::class, $company->influenceScores()->getRelated()::class);
    }

    public function test_articlesリレーションが正しく動作する()
    {
        $company = Company::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $company->articles());
        $this->assertEquals(\App\Models\Article::class, $company->articles()->getRelated()::class);
    }

    public function test_default_attributesが正しく設定されている()
    {
        $company = new Company();
        $attributes = $company->getAttributes();

        $this->assertTrue($attributes['is_active']);
    }

    public function test_castsが正しく設定されている()
    {
        $company = new Company();
        $casts = $company->getCasts();

        $this->assertEquals('boolean', $casts['is_active']);
    }
}
