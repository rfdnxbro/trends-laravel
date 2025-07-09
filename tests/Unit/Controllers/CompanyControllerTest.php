<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\Api\CompanyController;
use App\Services\CompanyInfluenceScoreService;
use App\Services\CompanyRankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Validator;
use Mockery;
use Tests\TestCase;

class CompanyControllerTest extends TestCase
{
    /** @var \Mockery\MockInterface&CompanyRankingService */
    private $rankingService;

    /** @var \Mockery\MockInterface&CompanyInfluenceScoreService */
    private $scoreService;

    private $controller;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var \Mockery\MockInterface&CompanyRankingService $rankingService */
        $rankingService = Mockery::mock(CompanyRankingService::class);
        /** @var \Mockery\MockInterface&CompanyInfluenceScoreService $scoreService */
        $scoreService = Mockery::mock(CompanyInfluenceScoreService::class);

        $this->rankingService = $rankingService;
        $this->scoreService = $scoreService;
        $this->controller = new CompanyController($this->rankingService, $this->scoreService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_show_with_invalid_company_id()
    {
        $companyId = 999;

        \Validator::shouldReceive('make')
            ->andReturn(\Mockery::mock(Validator::class, function ($mock) {
                $mock->shouldReceive('fails')->andReturn(true);
                $mock->shouldReceive('errors')->andReturn(['company_id' => ['企業IDが無効です']]);
            }));

        $response = $this->controller->show($companyId);

        $this->assertEquals(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('企業IDが無効です', $responseData['error']);
    }

    public function test_articles_with_invalid_company_id()
    {
        $companyId = 999;
        $request = new Request();

        \Validator::shouldReceive('make')
            ->andReturn(\Mockery::mock(Validator::class, function ($mock) {
                $mock->shouldReceive('fails')->andReturn(true);
                $mock->shouldReceive('errors')->andReturn(['company_id' => ['企業IDが無効です']]);
            }));

        $response = $this->controller->articles($request, $companyId);

        $this->assertEquals(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('企業IDが無効です', $responseData['error']);
    }

    public function test_scores_with_invalid_company_id()
    {
        $companyId = 999;
        $request = new Request();

        \Validator::shouldReceive('make')
            ->andReturn(\Mockery::mock(Validator::class, function ($mock) {
                $mock->shouldReceive('fails')->andReturn(true);
                $mock->shouldReceive('errors')->andReturn(['company_id' => ['企業IDが無効です']]);
            }));

        $response = $this->controller->scores($request, $companyId);

        $this->assertEquals(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('企業IDが無効です', $responseData['error']);
    }

    public function test_rankings_with_invalid_company_id()
    {
        $companyId = 999;
        $request = new Request();

        \Validator::shouldReceive('make')
            ->andReturn(\Mockery::mock(Validator::class, function ($mock) {
                $mock->shouldReceive('fails')->andReturn(true);
                $mock->shouldReceive('errors')->andReturn(['company_id' => ['企業IDが無効です']]);
            }));

        $response = $this->controller->rankings($request, $companyId);

        $this->assertEquals(400, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('企業IDが無効です', $responseData['error']);
    }

    public function test_constructor_properly_sets_dependencies()
    {
        $this->assertInstanceOf(CompanyController::class, $this->controller);
        $this->assertInstanceOf(CompanyRankingService::class, $this->rankingService);
        $this->assertInstanceOf(CompanyInfluenceScoreService::class, $this->scoreService);
    }

    public function test_show_method_exists()
    {
        $this->assertTrue(method_exists($this->controller, 'show'));
    }

    public function test_articles_method_exists()
    {
        $this->assertTrue(method_exists($this->controller, 'articles'));
    }

    public function test_scores_method_exists()
    {
        $this->assertTrue(method_exists($this->controller, 'scores'));
    }

    public function test_rankings_method_exists()
    {
        $this->assertTrue(method_exists($this->controller, 'rankings'));
    }
}