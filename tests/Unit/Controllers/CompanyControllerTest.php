<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\Api\CompanyController;
use App\Services\CompanyInfluenceScoreService;
use App\Services\CompanyRankingService;
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

    /**
     * コンストラクタでの依存性注入が正しく動作することのテスト
     */
    public function test_コンストラクタで依存関係が正しく設定される()
    {
        $this->assertInstanceOf(CompanyController::class, $this->controller);
        $this->assertInstanceOf(CompanyRankingService::class, $this->rankingService);
        $this->assertInstanceOf(CompanyInfluenceScoreService::class, $this->scoreService);
    }
}
