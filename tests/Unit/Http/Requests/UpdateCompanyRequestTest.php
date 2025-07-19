<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateCompanyRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var UpdateCompanyRequest
     */
    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UpdateCompanyRequest;
    }

    /**
     * authorize メソッドのテスト
     */
    public function test_authorize_常にtrueを返すこと(): void
    {
        $this->assertTrue($this->request->authorize());
    }

    /**
     * バリデーションルールのテスト - 正常系
     */
    public function test_バリデーション_有効なデータで成功すること(): void
    {
        $company = Company::factory()->create();

        // ルートパラメータを設定
        $this->request->setRouteResolver(function () use ($company) {
            $route = $this->createMock(\Illuminate\Routing\Route::class);
            $route->expects($this->any())
                ->method('parameter')
                ->with('company_id')
                ->willReturn($company->id);

            return $route;
        });

        $data = [
            'name' => '更新後企業名',
            'domain' => 'updated.com',
            'description' => '更新後の説明',
            'logo_url' => 'https://example.com/updated-logo.png',
            'website_url' => 'https://updated.com',
            'is_active' => false,
            'url_patterns' => ['tech.updated.com'],
            'domain_patterns' => ['*.updated.com'],
            'keywords' => ['tech', 'update'],
            'zenn_organizations' => ['updated-org'],
            'qiita_username' => 'updated_user',
            'zenn_username' => 'updated_zenn',
        ];

        $validator = Validator::make($data, $this->request->rules());
        $this->assertFalse($validator->fails());
    }

    /**
     * バリデーションルールのテスト - 必須項目エラー
     */
    public function test_バリデーション_必須項目が空の場合エラーになること(): void
    {
        $data = [];

        $validator = Validator::make($data, $this->request->rules());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
        $this->assertTrue($validator->errors()->has('domain'));
    }

    /**
     * バリデーションルールのテスト - 自分のドメインは重複チェック対象外
     */
    public function test_バリデーション_自分のドメインは重複チェック対象外(): void
    {
        $company = Company::factory()->create(['domain' => 'existing.com']);

        // ルートパラメータを設定
        $this->request->setRouteResolver(function () use ($company) {
            $route = $this->createMock(\Illuminate\Routing\Route::class);
            $route->expects($this->any())
                ->method('parameter')
                ->with('company_id')
                ->willReturn($company->id);

            return $route;
        });

        $data = [
            'name' => '更新後企業名',
            'domain' => 'existing.com', // 自分のドメインと同じ
        ];

        $validator = Validator::make($data, $this->request->rules());
        $this->assertFalse($validator->fails());
    }

    /**
     * バリデーションルールのテスト - 他の企業のドメインは重複エラー
     */
    public function test_バリデーション_他企業のドメインは重複エラー(): void
    {
        $company1 = Company::factory()->create(['domain' => 'company1.com']);
        $company2 = Company::factory()->create(['domain' => 'company2.com']);

        // company2を更新しようとしている設定
        $this->request->setRouteResolver(function () use ($company2) {
            $route = $this->createMock(\Illuminate\Routing\Route::class);
            $route->expects($this->any())
                ->method('parameter')
                ->with('company_id')
                ->willReturn($company2->id);

            return $route;
        });

        $data = [
            'name' => '更新後企業名',
            'domain' => 'company1.com', // company1のドメインと重複
        ];

        $validator = Validator::make($data, $this->request->rules());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('domain'));
    }

    /**
     * バリデーションルールのテスト - URL形式チェック
     */
    public function test_バリデーション_ur_l形式チェック(): void
    {
        $data = [
            'name' => 'テスト企業',
            'domain' => 'test.com',
            'logo_url' => 'invalid-url',
            'website_url' => 'also-invalid',
        ];

        $validator = Validator::make($data, $this->request->rules());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('logo_url'));
        $this->assertTrue($validator->errors()->has('website_url'));
    }

    /**
     * バリデーションルールのテスト - 配列フィールド
     */
    public function test_バリデーション_配列フィールドが正しく検証されること(): void
    {
        $data = [
            'name' => 'テスト企業',
            'domain' => 'test.com',
            'url_patterns' => ['valid.com', 123], // 数値が含まれている
            'keywords' => ['valid', ['nested']], // ネストした配列
        ];

        $validator = Validator::make($data, $this->request->rules());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('url_patterns.1'));
        $this->assertTrue($validator->errors()->has('keywords.1'));
    }

    /**
     * カスタムメッセージのテスト
     */
    public function test_カスタムメッセージが正しく設定されていること(): void
    {
        $messages = $this->request->messages();

        $this->assertArrayHasKey('name.required', $messages);
        $this->assertEquals('企業名は必須です。', $messages['name.required']);

        $this->assertArrayHasKey('domain.required', $messages);
        $this->assertEquals('ドメインは必須です。', $messages['domain.required']);

        $this->assertArrayHasKey('domain.unique', $messages);
        $this->assertEquals('このドメインは既に登録されています。', $messages['domain.unique']);
    }

    /**
     * ルートパラメータ取得のテスト
     */
    public function test_ルートパラメータから企業_i_dが取得できること(): void
    {
        $company = Company::factory()->create();

        // ルートパラメータを設定
        $this->request->setRouteResolver(function () use ($company) {
            $route = $this->createMock(\Illuminate\Routing\Route::class);
            $route->expects($this->any())
                ->method('parameter')
                ->with('company_id')
                ->willReturn($company->id);

            return $route;
        });

        $rules = $this->request->rules();

        // domainルールに企業IDが含まれることを確認
        $this->assertStringContainsString($company->id, $rules['domain']);
        $this->assertStringContainsString('unique:companies,domain,', $rules['domain']);
    }

    /**
     * カスタムメッセージの実際の適用テスト
     */
    public function test_カスタムメッセージが実際に適用されること(): void
    {
        $company1 = Company::factory()->create(['domain' => 'existing.com']);
        $company2 = Company::factory()->create(['domain' => 'company2.com']);

        // company2を更新しようとしている設定
        $this->request->setRouteResolver(function () use ($company2) {
            $route = $this->createMock(\Illuminate\Routing\Route::class);
            $route->expects($this->any())
                ->method('parameter')
                ->with('company_id')
                ->willReturn($company2->id);

            return $route;
        });

        $data = [
            'domain' => 'existing.com', // company1のドメインと重複
        ];

        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());
        $this->assertTrue($validator->fails());

        $errors = $validator->errors();
        $this->assertStringContainsString('企業名は必須です。', $errors->first('name'));
        $this->assertStringContainsString('このドメインは既に登録されています。', $errors->first('domain'));
    }

    /**
     * オプショナルフィールドのテスト
     */
    public function test_バリデーション_オプショナルフィールドは空でも有効(): void
    {
        $company = Company::factory()->create();

        // ルートパラメータを設定
        $this->request->setRouteResolver(function () use ($company) {
            $route = $this->createMock(\Illuminate\Routing\Route::class);
            $route->expects($this->any())
                ->method('parameter')
                ->with('company_id')
                ->willReturn($company->id);

            return $route;
        });

        $data = [
            'name' => 'テスト企業',
            'domain' => 'test.com',
            // オプショナルフィールドは省略
        ];

        $validator = Validator::make($data, $this->request->rules());
        $this->assertFalse($validator->fails());
    }

    /**
     * 文字数制限のテスト
     */
    public function test_バリデーション_文字数制限チェック(): void
    {
        $data = [
            'name' => str_repeat('a', 256), // 256文字
            'domain' => 'test.com',
            'logo_url' => 'https://example.com/'.str_repeat('a', 500), // 500文字超
            'qiita_username' => str_repeat('b', 256), // 256文字
        ];

        $validator = Validator::make($data, $this->request->rules());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
        $this->assertTrue($validator->errors()->has('logo_url'));
        $this->assertTrue($validator->errors()->has('qiita_username'));
    }
}
