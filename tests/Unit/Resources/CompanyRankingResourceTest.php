<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\CompanyRankingResource;
use App\Models\Company;
use App\Models\CompanyRanking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use stdClass;
use Tests\TestCase;

class CompanyRankingResourceTest extends TestCase
{
    use RefreshDatabase;

    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = Request::create('/test');
    }

    public function test_基本的なランキング情報が正しく変換される(): void
    {
        $company = Company::factory()->create();
        $ranking = CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'rank_position' => 5,
            'total_score' => 250.75,
            'article_count' => 15,
            'total_bookmarks' => 1500,
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31',
        ]);

        $ranking->company_name = $company->name;
        $ranking->domain = $company->domain;
        $ranking->logo_url = $company->logo_url;
        $ranking->rank_change = 2;

        $resource = new CompanyRankingResource($ranking);
        $result = $resource->toArray($this->request);

        $this->assertEquals($ranking->id, $result['id']);
        $this->assertEquals($company->id, $result['company']['id']);
        $this->assertEquals($company->name, $result['company']['name']);
        $this->assertEquals($company->domain, $result['company']['domain']);
        $this->assertEquals($company->logo_url, $result['company']['logo_url']);
        $this->assertEquals(5, $result['rank_position']);
        $this->assertEquals(250.75, $result['total_score']);
        $this->assertIsFloat($result['total_score']);
        $this->assertEquals(15, $result['article_count']);
        $this->assertEquals(1500, $result['total_bookmarks']);
        $this->assertEquals(2, $result['rank_change']);
        $this->assertEquals($ranking->period_start, $result['period']['start']);
        $this->assertEquals($ranking->period_end, $result['period']['end']);
        $this->assertNotNull($result['calculated_at']);
    }

    public function test_std_classオブジェクトからの変換が正しく行われる(): void
    {
        $data = new stdClass;
        $data->id = 100;
        $data->company_id = 50;
        $data->company_name = 'Test Company';
        $data->domain = 'test.example.com';
        $data->logo_url = 'https://example.com/logo.png';
        $data->rank_position = 10;
        $data->total_score = '500.25';
        $data->article_count = 25;
        $data->total_bookmarks = 3000;
        $data->rank_change = -3;
        $data->period_start = '2024-02-01';
        $data->period_end = '2024-02-29';
        $data->calculated_at = '2024-03-01 10:00:00';

        $resource = new CompanyRankingResource($data);
        $result = $resource->toArray($this->request);

        $this->assertEquals(100, $result['id']);
        $this->assertEquals(50, $result['company']['id']);
        $this->assertEquals('Test Company', $result['company']['name']);
        $this->assertEquals('test.example.com', $result['company']['domain']);
        $this->assertEquals('https://example.com/logo.png', $result['company']['logo_url']);
        $this->assertEquals(10, $result['rank_position']);
        $this->assertEquals(500.25, $result['total_score']);
        $this->assertIsFloat($result['total_score']);
        $this->assertEquals(25, $result['article_count']);
        $this->assertEquals(3000, $result['total_bookmarks']);
        $this->assertEquals(-3, $result['rank_change']);
        $this->assertEquals('2024-02-01', $result['period']['start']);
        $this->assertEquals('2024-02-29', $result['period']['end']);
        $this->assertEquals('2024-03-01 10:00:00', $result['calculated_at']);
    }

    public function test_null値のフィールドが適切に処理される(): void
    {
        $data = new stdClass;
        $data->id = null;
        $data->company_id = null;
        $data->company_name = null;
        $data->domain = null;
        $data->logo_url = null;
        $data->rank_position = 1;
        $data->total_score = 100.0;
        $data->article_count = 5;
        $data->total_bookmarks = 500;
        $data->rank_change = null;
        $data->period_start = '2024-01-01';
        $data->period_end = '2024-01-31';
        $data->calculated_at = '2024-02-01 00:00:00';

        $resource = new CompanyRankingResource($data);
        $result = $resource->toArray($this->request);

        $this->assertNull($result['id']);
        $this->assertNull($result['company']['id']);
        $this->assertNull($result['company']['name']);
        $this->assertNull($result['company']['domain']);
        $this->assertNull($result['company']['logo_url']);
        $this->assertEquals(1, $result['rank_position']);
        $this->assertEquals(100.0, $result['total_score']);
        $this->assertIsFloat($result['total_score']);
        $this->assertEquals(5, $result['article_count']);
        $this->assertEquals(500, $result['total_bookmarks']);
        $this->assertNull($result['rank_change']);
        $this->assertEquals('2024-01-01', $result['period']['start']);
        $this->assertEquals('2024-01-31', $result['period']['end']);
        $this->assertEquals('2024-02-01 00:00:00', $result['calculated_at']);
    }

    public function test_rank_changeがnullの場合はnullを返す(): void
    {
        $data = new stdClass;
        $data->id = 1;
        $data->company_id = 1;
        $data->company_name = 'Test Company';
        $data->domain = 'test.com';
        $data->logo_url = 'logo.png';
        $data->rank_position = 5;
        $data->total_score = 200.0;
        $data->article_count = 10;
        $data->total_bookmarks = 1000;
        $data->rank_change = null;
        $data->period_start = '2024-01-01';
        $data->period_end = '2024-01-31';
        $data->calculated_at = '2024-02-01 00:00:00';

        $resource = new CompanyRankingResource($data);
        $result = $resource->toArray($this->request);

        $this->assertNull($result['rank_change']);
    }

    public function test_total_scoreの浮動小数点変換が正しく行われる(): void
    {
        $testCases = [
            '100' => 100.0,
            '250.5' => 250.5,
            '999.99' => 999.99,
            '0' => 0.0,
            '0.01' => 0.01,
            500 => 500.0,
            750 => 750.0,
        ];

        foreach ($testCases as $input => $expected) {
            $data = new stdClass;
            $data->id = 1;
            $data->company_id = 1;
            $data->company_name = 'Test Company';
            $data->domain = 'test.com';
            $data->logo_url = 'logo.png';
            $data->rank_position = 1;
            $data->total_score = $input;
            $data->article_count = 10;
            $data->total_bookmarks = 1000;
            $data->rank_change = 0;
            $data->period_start = '2024-01-01';
            $data->period_end = '2024-01-31';
            $data->calculated_at = '2024-02-01 00:00:00';

            $resource = new CompanyRankingResource($data);
            $result = $resource->toArray($this->request);

            $this->assertEquals($expected, $result['total_score'], "入力値 {$input} の変換に失敗");
            $this->assertIsFloat($result['total_score'], "入力値 {$input} がfloat型ではありません");
        }
    }

    public function test_プロパティが存在しない場合のnull処理(): void
    {
        $data = new stdClass;
        $data->rank_position = 1;
        $data->total_score = 100.0;
        $data->article_count = 5;
        $data->total_bookmarks = 500;
        $data->period_start = '2024-01-01';
        $data->period_end = '2024-01-31';
        $data->calculated_at = '2024-02-01 00:00:00';

        $resource = new CompanyRankingResource($data);
        $result = $resource->toArray($this->request);

        $this->assertNull($result['id']);
        $this->assertNull($result['company']['id']);
        $this->assertNull($result['company']['name']);
        $this->assertNull($result['company']['domain']);
        $this->assertNull($result['company']['logo_url']);
        $this->assertNull($result['rank_change']);
    }

    public function test_期間情報の正しいフォーマット(): void
    {
        $data = new stdClass;
        $data->id = 1;
        $data->company_id = 1;
        $data->company_name = 'Test Company';
        $data->domain = 'test.com';
        $data->logo_url = 'logo.png';
        $data->rank_position = 1;
        $data->total_score = 100.0;
        $data->article_count = 5;
        $data->total_bookmarks = 500;
        $data->rank_change = 0;
        $data->period_start = '2024-03-01';
        $data->period_end = '2024-03-31';
        $data->calculated_at = '2024-04-01 12:30:45';

        $resource = new CompanyRankingResource($data);
        $result = $resource->toArray($this->request);

        $this->assertIsArray($result['period']);
        $this->assertArrayHasKey('start', $result['period']);
        $this->assertArrayHasKey('end', $result['period']);
        $this->assertEquals('2024-03-01', $result['period']['start']);
        $this->assertEquals('2024-03-31', $result['period']['end']);
    }

    public function test_数値データの型変換正確性(): void
    {
        $data = new stdClass;
        $data->id = '123';
        $data->company_id = '456';
        $data->company_name = 'Test Company';
        $data->domain = 'test.com';
        $data->logo_url = 'logo.png';
        $data->rank_position = '10';
        $data->total_score = '750.50';
        $data->article_count = '25';
        $data->total_bookmarks = '2500';
        $data->rank_change = '5';
        $data->period_start = '2024-01-01';
        $data->period_end = '2024-01-31';
        $data->calculated_at = '2024-02-01 00:00:00';

        $resource = new CompanyRankingResource($data);
        $result = $resource->toArray($this->request);

        $this->assertIsFloat($result['total_score']);
        $this->assertEquals(750.50, $result['total_score']);
    }

    public function test_レスポンス構造の完全性(): void
    {
        $expectedFields = [
            'id',
            'company',
            'rank_position',
            'total_score',
            'article_count',
            'total_bookmarks',
            'rank_change',
            'period',
            'calculated_at',
        ];

        $expectedCompanyFields = [
            'id',
            'name',
            'domain',
            'logo_url',
        ];

        $expectedPeriodFields = [
            'start',
            'end',
        ];

        $data = new stdClass;
        $data->id = 1;
        $data->company_id = 1;
        $data->company_name = 'Test Company';
        $data->domain = 'test.com';
        $data->logo_url = 'logo.png';
        $data->rank_position = 1;
        $data->total_score = 100.0;
        $data->article_count = 5;
        $data->total_bookmarks = 500;
        $data->rank_change = 0;
        $data->period_start = '2024-01-01';
        $data->period_end = '2024-01-31';
        $data->calculated_at = '2024-02-01 00:00:00';

        $resource = new CompanyRankingResource($data);
        $result = $resource->toArray($this->request);

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $result, "フィールド '{$field}' が存在しません");
        }

        foreach ($expectedCompanyFields as $field) {
            $this->assertArrayHasKey($field, $result['company'], "company.{$field} フィールドが存在しません");
        }

        foreach ($expectedPeriodFields as $field) {
            $this->assertArrayHasKey($field, $result['period'], "period.{$field} フィールドが存在しません");
        }
    }

    public function test_企業情報の埋め込み構造の整合性(): void
    {
        $data = new stdClass;
        $data->id = 1;
        $data->company_id = 100;
        $data->company_name = 'テスト株式会社';
        $data->domain = 'test-company.co.jp';
        $data->logo_url = 'https://cdn.example.com/logo.png';
        $data->rank_position = 1;
        $data->total_score = 1000.0;
        $data->article_count = 50;
        $data->total_bookmarks = 10000;
        $data->rank_change = 1;
        $data->period_start = '2024-01-01';
        $data->period_end = '2024-01-31';
        $data->calculated_at = '2024-02-01 00:00:00';

        $resource = new CompanyRankingResource($data);
        $result = $resource->toArray($this->request);

        $this->assertIsArray($result['company']);
        $this->assertEquals(100, $result['company']['id']);
        $this->assertEquals('テスト株式会社', $result['company']['name']);
        $this->assertEquals('test-company.co.jp', $result['company']['domain']);
        $this->assertEquals('https://cdn.example.com/logo.png', $result['company']['logo_url']);
    }

    public function test_エラー耐性_不正なデータ型の処理(): void
    {
        $data = new stdClass;
        $data->id = [];
        $data->company_id = 'invalid';
        $data->company_name = 123;
        $data->domain = false;
        $data->logo_url = null;
        $data->rank_position = 'first';
        $data->total_score = 'high';
        $data->article_count = 'many';
        $data->total_bookmarks = 'lots';
        $data->rank_change = 'up';
        $data->period_start = '2024-01-01';
        $data->period_end = '2024-01-31';
        $data->calculated_at = '2024-02-01 00:00:00';

        $resource = new CompanyRankingResource($data);
        $result = $resource->toArray($this->request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('company', $result);
        $this->assertArrayHasKey('rank_position', $result);
        $this->assertArrayHasKey('total_score', $result);
        $this->assertArrayHasKey('article_count', $result);
        $this->assertArrayHasKey('total_bookmarks', $result);
        $this->assertArrayHasKey('rank_change', $result);
        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('calculated_at', $result);

        $this->assertIsFloat($result['total_score']);
    }
}
