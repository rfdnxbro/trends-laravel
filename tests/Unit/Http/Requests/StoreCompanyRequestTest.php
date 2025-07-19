<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\StoreCompanyRequest;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreCompanyRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var StoreCompanyRequest
     */
    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new StoreCompanyRequest;
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
        $data = [
            'name' => 'テスト企業',
            'domain' => 'test.com',
            'description' => 'テストの説明',
            'logo_url' => 'https://example.com/logo.png',
            'website_url' => 'https://test.com',
            'is_active' => true,
            'url_patterns' => ['tech.test.com', 'blog.test.com'],
            'domain_patterns' => ['*.test.com'],
            'keywords' => ['tech', 'ai'],
            'zenn_organizations' => ['test-org'],
            'qiita_username' => 'test_user',
            'zenn_username' => 'test_zenn',
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
     * バリデーションルールのテスト - name フィールド
     */
    public function test_バリデーション_name_最大文字数チェック(): void
    {
        $data = [
            'name' => str_repeat('a', 256), // 256文字
            'domain' => 'test.com',
        ];

        $validator = Validator::make($data, $this->request->rules());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    /**
     * バリデーションルールのテスト - domain 重複チェック
     */
    public function test_バリデーション_domain_重複チェック(): void
    {
        // 既存の企業を作成
        Company::factory()->create(['domain' => 'existing.com']);

        $data = [
            'name' => 'テスト企業',
            'domain' => 'existing.com', // 重複するドメイン
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
     * バリデーションルールのテスト - is_active boolean チェック
     */
    public function test_バリデーション_is_active_boolean_チェック(): void
    {
        $data = [
            'name' => 'テスト企業',
            'domain' => 'test.com',
            'is_active' => 'invalid-boolean',
        ];

        $validator = Validator::make($data, $this->request->rules());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('is_active'));
    }

    /**
     * バリデーションルールのテスト - オプショナルフィールド
     */
    public function test_バリデーション_オプショナルフィールドは空でも有効(): void
    {
        $data = [
            'name' => 'テスト企業',
            'domain' => 'test.com',
            // オプショナルフィールドは省略
        ];

        $validator = Validator::make($data, $this->request->rules());
        $this->assertFalse($validator->fails());
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
     * カスタムメッセージの実際の適用テスト
     */
    public function test_カスタムメッセージが実際に適用されること(): void
    {
        Company::factory()->create(['domain' => 'existing.com']);

        $data = [
            'domain' => 'existing.com',
        ];

        $validator = Validator::make($data, $this->request->rules(), $this->request->messages());
        $this->assertTrue($validator->fails());

        $errors = $validator->errors();
        $this->assertStringContainsString('企業名は必須です。', $errors->first('name'));
        $this->assertStringContainsString('このドメインは既に登録されています。', $errors->first('domain'));
    }

    /**
     * URL最大文字数のテスト
     */
    public function test_バリデーション_ur_l最大文字数チェック(): void
    {
        $longUrl = 'https://example.com/'.str_repeat('a', 500); // 500文字超

        $data = [
            'name' => 'テスト企業',
            'domain' => 'test.com',
            'logo_url' => $longUrl,
            'website_url' => $longUrl,
        ];

        $validator = Validator::make($data, $this->request->rules());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('logo_url'));
        $this->assertTrue($validator->errors()->has('website_url'));
    }

    /**
     * ユーザー名フィールドの最大文字数テスト
     */
    public function test_バリデーション_ユーザー名最大文字数チェック(): void
    {
        $data = [
            'name' => 'テスト企業',
            'domain' => 'test.com',
            'qiita_username' => str_repeat('a', 256), // 256文字
            'zenn_username' => str_repeat('b', 256), // 256文字
        ];

        $validator = Validator::make($data, $this->request->rules());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('qiita_username'));
        $this->assertTrue($validator->errors()->has('zenn_username'));
    }
}
