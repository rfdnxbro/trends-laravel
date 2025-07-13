<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\CompanyRankingCollection;
use App\Models\Company;
use App\Models\CompanyRanking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use stdClass;
use Tests\TestCase;

class CompanyRankingCollectionTest extends TestCase
{
    use RefreshDatabase;

    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = Request::create('/test');
    }

    public function test_基本コレクション構造の確認(): void
    {
        $companies = Company::factory()->count(3)->create();
        $rankings = new Collection;

        foreach ($companies as $company) {
            $ranking = CompanyRanking::factory()->create([
                'company_id' => $company->id,
            ]);
            $ranking->company_name = $company->name;
            $ranking->domain = $company->domain;
            $ranking->logo_url = $company->logo_url;
            $rankings->push($ranking);
        }

        $collection = new CompanyRankingCollection($rankings);
        $result = $collection->toArray($this->request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertIsArray($result['meta']);

        $dataArray = $result['data']->toArray($this->request);
        $this->assertIsArray($dataArray);
        $this->assertCount(3, $dataArray);
    }

    public function test_複数ランキングアイテムの変換(): void
    {
        $companies = Company::factory()->count(5)->create();
        $rankings = new Collection;

        for ($i = 0; $i < 5; $i++) {
            $ranking = CompanyRanking::factory()->create([
                'company_id' => $companies[$i]->id,
                'rank_position' => $i + 1,
                'total_score' => (100 - $i * 10),
            ]);
            $ranking->company_name = $companies[$i]->name;
            $ranking->domain = $companies[$i]->domain;
            $ranking->logo_url = $companies[$i]->logo_url;
            $rankings->push($ranking);
        }

        $collection = new CompanyRankingCollection($rankings);
        $result = $collection->toArray($this->request);

        $dataArray = $result['data']->toArray($this->request);
        $this->assertCount(5, $dataArray);
        $this->assertEquals(5, $result['meta']['total']);

        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals($i + 1, $dataArray[$i]['rank_position']);
            $this->assertEquals(100 - $i * 10, $dataArray[$i]['total_score']);
        }
    }

    public function test_メタデータの適切な設定(): void
    {
        $companies = Company::factory()->count(10)->create();
        $rankings = new Collection;

        foreach ($companies as $company) {
            $ranking = CompanyRanking::factory()->create([
                'company_id' => $company->id,
            ]);
            $rankings->push($ranking);
        }

        $collection = new CompanyRankingCollection($rankings);
        $result = $collection->toArray($this->request);

        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('total', $result['meta']);
        $this->assertEquals(10, $result['meta']['total']);
        $this->assertIsInt($result['meta']['total']);
    }

    public function test_空のコレクション処理(): void
    {
        $emptyCollection = new Collection;
        $collection = new CompanyRankingCollection($emptyCollection);
        $result = $collection->toArray($this->request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertEmpty($result['data']);
        $this->assertEquals(0, $result['meta']['total']);
    }

    public function test_単一アイテムでの処理(): void
    {
        $company = Company::factory()->create();
        $ranking = CompanyRanking::factory()->create([
            'company_id' => $company->id,
            'rank_position' => 1,
            'total_score' => 500.0,
        ]);

        $rankings = new Collection([$ranking]);
        $collection = new CompanyRankingCollection($rankings);
        $result = $collection->toArray($this->request);

        $dataArray = $result['data']->toArray($this->request);
        $this->assertCount(1, $dataArray);
        $this->assertEquals(1, $result['meta']['total']);
        $this->assertEquals(1, $dataArray[0]['rank_position']);
        $this->assertEquals(500.0, $dataArray[0]['total_score']);
    }

    public function test_std_classオブジェクトのコレクション処理(): void
    {
        $rankings = new Collection;

        for ($i = 1; $i <= 3; $i++) {
            $data = new stdClass;
            $data->id = $i;
            $data->company_id = $i;
            $data->company_name = "Test Company {$i}";
            $data->domain = "test{$i}.com";
            $data->logo_url = "logo{$i}.png";
            $data->rank_position = $i;
            $data->total_score = 100 * $i;
            $data->article_count = 10 * $i;
            $data->total_bookmarks = 500 * $i;
            $data->rank_change = $i - 1;
            $data->period_start = '2024-01-01';
            $data->period_end = '2024-01-31';
            $data->calculated_at = '2024-02-01 00:00:00';

            $rankings->push($data);
        }

        $collection = new CompanyRankingCollection($rankings);
        $result = $collection->toArray($this->request);

        $dataArray = $result['data']->toArray($this->request);
        $this->assertCount(3, $dataArray);
        $this->assertEquals(3, $result['meta']['total']);

        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals($i + 1, $dataArray[$i]['id']);
            $this->assertEquals('Test Company '.($i + 1), $dataArray[$i]['company']['name']);
        }
    }

    public function test_大量データでのパフォーマンス(): void
    {
        $rankings = new Collection;

        for ($i = 1; $i <= 100; $i++) {
            $data = new stdClass;
            $data->id = $i;
            $data->company_id = $i;
            $data->company_name = "Company {$i}";
            $data->domain = "company{$i}.com";
            $data->logo_url = "logo{$i}.png";
            $data->rank_position = $i;
            $data->total_score = 1000 - $i;
            $data->article_count = 50;
            $data->total_bookmarks = 2500;
            $data->rank_change = 0;
            $data->period_start = '2024-01-01';
            $data->period_end = '2024-01-31';
            $data->calculated_at = '2024-02-01 00:00:00';

            $rankings->push($data);
        }

        $startTime = microtime(true);
        $collection = new CompanyRankingCollection($rankings);
        $result = $collection->toArray($this->request);
        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;

        $dataArray = $result['data']->toArray($this->request);
        $this->assertCount(100, $dataArray);
        $this->assertEquals(100, $result['meta']['total']);
        $this->assertLessThan(1.0, $executionTime, '大量データ処理が1秒を超えています');
    }

    public function test_レスポンス構造の完全性(): void
    {
        $company = Company::factory()->create();
        $ranking = CompanyRanking::factory()->create([
            'company_id' => $company->id,
        ]);

        $rankings = new Collection([$ranking]);
        $collection = new CompanyRankingCollection($rankings);
        $result = $collection->toArray($this->request);

        $expectedKeys = ['data', 'meta'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result, "キー '{$key}' が存在しません");
        }

        $this->assertArrayHasKey('total', $result['meta'], 'meta.total キーが存在しません');
    }

    public function test_コレクションの順序保持(): void
    {
        $rankings = new Collection;
        $expectedOrder = [5, 2, 8, 1, 10];

        foreach ($expectedOrder as $position) {
            $data = new stdClass;
            $data->id = $position;
            $data->company_id = $position;
            $data->company_name = "Company {$position}";
            $data->domain = "company{$position}.com";
            $data->logo_url = "logo{$position}.png";
            $data->rank_position = $position;
            $data->total_score = 100.0;
            $data->article_count = 10;
            $data->total_bookmarks = 500;
            $data->rank_change = 0;
            $data->period_start = '2024-01-01';
            $data->period_end = '2024-01-31';
            $data->calculated_at = '2024-02-01 00:00:00';
            $rankings->push($data);
        }

        $collection = new CompanyRankingCollection($rankings);
        $result = $collection->toArray($this->request);

        $dataArray = $result['data']->toArray($this->request);
        for ($i = 0; $i < count($expectedOrder); $i++) {
            $this->assertEquals($expectedOrder[$i], $dataArray[$i]['rank_position']);
        }
    }

    public function test_nullデータを含むコレクション処理(): void
    {
        $rankings = new Collection;

        $validData = new stdClass;
        $validData->id = 1;
        $validData->company_id = 1;
        $validData->company_name = 'Valid Company';
        $validData->domain = 'valid.com';
        $validData->logo_url = 'logo.png';
        $validData->rank_position = 1;
        $validData->total_score = 100.0;
        $validData->article_count = 10;
        $validData->total_bookmarks = 500;
        $validData->rank_change = 0;
        $validData->period_start = '2024-01-01';
        $validData->period_end = '2024-01-31';
        $validData->calculated_at = '2024-02-01 00:00:00';
        $rankings->push($validData);

        $nullData = new stdClass;
        $nullData->id = null;
        $nullData->company_id = null;
        $nullData->company_name = null;
        $nullData->domain = null;
        $nullData->logo_url = null;
        $nullData->rank_position = 2;
        $nullData->total_score = 50.0;
        $nullData->article_count = 5;
        $nullData->total_bookmarks = 250;
        $nullData->rank_change = null;
        $nullData->period_start = '2024-01-01';
        $nullData->period_end = '2024-01-31';
        $nullData->calculated_at = '2024-02-01 00:00:00';
        $rankings->push($nullData);

        $collection = new CompanyRankingCollection($rankings);
        $result = $collection->toArray($this->request);

        $dataArray = $result['data']->toArray($this->request);
        $this->assertCount(2, $dataArray);
        $this->assertEquals(2, $result['meta']['total']);
        $this->assertEquals(1, $dataArray[0]['id']);
        $this->assertNull($dataArray[1]['id']);
    }

    public function test_混合データ型の処理(): void
    {
        $company = Company::factory()->create();
        $ranking = CompanyRanking::factory()->create([
            'company_id' => $company->id,
        ]);

        $stdData = new stdClass;
        $stdData->id = 100;
        $stdData->company_id = 100;
        $stdData->company_name = 'stdClass Company';
        $stdData->domain = 'std.com';
        $stdData->logo_url = 'std-logo.png';
        $stdData->rank_position = 2;
        $stdData->total_score = 200.0;
        $stdData->article_count = 20;
        $stdData->total_bookmarks = 1000;
        $stdData->rank_change = 1;
        $stdData->period_start = '2024-01-01';
        $stdData->period_end = '2024-01-31';
        $stdData->calculated_at = '2024-02-01 00:00:00';

        $rankings = new Collection([$ranking, $stdData]);
        $collection = new CompanyRankingCollection($rankings);
        $result = $collection->toArray($this->request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertEquals(2, $result['meta']['total']);

        $dataArray = $result['data']->toArray($this->request);
        $this->assertIsArray($dataArray);
        $this->assertCount(2, $dataArray);
    }

    public function test_メタデータカウントの正確性(): void
    {
        $testCases = [0, 1, 5, 25, 50];

        foreach ($testCases as $count) {
            $rankings = new Collection;

            for ($i = 0; $i < $count; $i++) {
                $data = new stdClass;
                $data->id = $i + 1;
                $rankings->push($data);
            }

            $collection = new CompanyRankingCollection($rankings);
            $result = $collection->toArray($this->request);

            $this->assertEquals($count, $result['meta']['total'],
                "コレクション要素数 {$count} でメタデータカウントが一致しません");
        }
    }

    public function test_コレクション変換の不変性(): void
    {
        $company = Company::factory()->create();
        $ranking = CompanyRanking::factory()->create([
            'company_id' => $company->id,
        ]);

        $originalCollection = new Collection([$ranking]);
        $collection = new CompanyRankingCollection($originalCollection);

        $result1 = $collection->toArray($this->request);
        $result2 = $collection->toArray($this->request);

        $this->assertEquals($result1, $result2, 'コレクション変換結果が一貫していません');
        $this->assertCount(1, $originalCollection, '元のコレクションが変更されています');
    }
}
