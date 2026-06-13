<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\TsStock\Services\StockService;
use Modules\TsStock\Repositories\Contracts\StockRepositoryInterface;
use Mockery;
use Mockery\MockInterface;

class StockServiceTest extends TestCase
{
    protected MockInterface $repoMock;
    protected StockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoMock = Mockery::mock(StockRepositoryInterface::class);
        $this->service  = new StockService($this->repoMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========== getStockList ==========

    public function test_it_returns_stock_list_with_status_1_on_success(): void
    {
        $repoData = [
            ['id_balance_head' => 1, 'material' => 'CRUDE PALM OIL', 'qty' => 5000.0],
            ['id_balance_head' => 2, 'material' => 'PALM STEARIN', 'qty' => 3000.0],
        ];

        $this->repoMock->shouldReceive('getStockList')
            ->once()
            ->with([])
            ->andReturn($repoData);

        $result = $this->service->getStockList();

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
        $this->assertEquals('Stock list retrieved successfully', $result['message']);
    }

    public function test_it_returns_empty_data_array_when_no_stock_records(): void
    {
        $this->repoMock->shouldReceive('getStockList')
            ->once()
            ->with([])
            ->andReturn([]);

        $result = $this->service->getStockList();

        $this->assertEquals(1, $result['status']);
        $this->assertEmpty($result['data']);
    }

    public function test_it_passes_filters_to_repository_when_getting_stock_list(): void
    {
        $filters  = ['plant_code' => 'EOB', 'material_type' => 'RAW'];
        $repoData = [['id_balance_head' => 1, 'material' => 'CRUDE PALM OIL', 'qty' => 5000.0]];

        $this->repoMock->shouldReceive('getStockList')
            ->once()
            ->with($filters)
            ->andReturn($repoData);

        $result = $this->service->getStockList($filters);

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
    }

    // ========== getStockDetail ==========

    public function test_it_returns_stock_detail_with_status_1_when_record_exists(): void
    {
        $repoData = ['id_balance_head' => 1, 'material' => 'CRUDE PALM OIL', 'qty' => 5000.0];

        $this->repoMock->shouldReceive('getStockDetail')
            ->once()
            ->with(1)
            ->andReturn($repoData);

        $result = $this->service->getStockDetail(1);

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
        $this->assertEquals('Stock detail retrieved successfully', $result['message']);
    }

    public function test_it_returns_status_0_when_stock_detail_not_found(): void
    {
        $this->repoMock->shouldReceive('getStockDetail')
            ->once()
            ->with(99)
            ->andReturn(null);

        $result = $this->service->getStockDetail(99);

        $this->assertEquals(0, $result['status']);
        $this->assertNull($result['data']);
        $this->assertEquals('Stock detail not found', $result['message']);
    }

    // ========== getActiveMaterialStock ==========

    public function test_it_returns_active_material_stock_with_status_1(): void
    {
        $repoData = [
            ['id_material' => 1, 'material' => 'CRUDE PALM OIL', 'material_type' => 'RAW'],
        ];

        $this->repoMock->shouldReceive('getActiveMaterialStock')
            ->once()
            ->with(null, null)
            ->andReturn($repoData);

        $result = $this->service->getActiveMaterialStock();

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
        $this->assertEquals('Active materials retrieved', $result['message']);
    }

    public function test_it_passes_search_and_type_filters_to_repository(): void
    {
        $repoData = [
            ['id_material' => 1, 'material' => 'CRUDE PALM OIL', 'material_type' => 'RAW'],
        ];

        $this->repoMock->shouldReceive('getActiveMaterialStock')
            ->once()
            ->with('palm', 'RAW')
            ->andReturn($repoData);

        $result = $this->service->getActiveMaterialStock('palm', 'RAW');

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
    }

    public function test_it_returns_empty_data_array_when_no_active_material_stock(): void
    {
        $this->repoMock->shouldReceive('getActiveMaterialStock')
            ->once()
            ->with('nonexistent', null)
            ->andReturn([]);

        $result = $this->service->getActiveMaterialStock('nonexistent');

        $this->assertEquals(1, $result['status']);
        $this->assertEmpty($result['data']);
    }

    // ========== getStockMovement ==========

    public function test_it_returns_stock_movements_with_status_1(): void
    {
        $repoData = [
            ['id_movement' => 1, 'movement_type' => 'IN', 'qty' => 1000.0],
            ['id_movement' => 2, 'movement_type' => 'OUT', 'qty' => 500.0],
        ];

        $this->repoMock->shouldReceive('getStockMovement')
            ->once()
            ->with([])
            ->andReturn($repoData);

        $result = $this->service->getStockMovement();

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
        $this->assertEquals('Stock movements retrieved', $result['message']);
    }

    public function test_it_passes_filters_to_repository_when_getting_stock_movement(): void
    {
        $filters  = ['plant_code' => 'EOB', 'date_from' => '2026-01-01'];
        $repoData = [['id_movement' => 1, 'movement_type' => 'IN', 'qty' => 1000.0]];

        $this->repoMock->shouldReceive('getStockMovement')
            ->once()
            ->with($filters)
            ->andReturn($repoData);

        $result = $this->service->getStockMovement($filters);

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
    }

    public function test_it_returns_empty_data_when_no_stock_movements_found(): void
    {
        $this->repoMock->shouldReceive('getStockMovement')
            ->once()
            ->with(['plant_code' => 'NONEXISTENT'])
            ->andReturn([]);

        $result = $this->service->getStockMovement(['plant_code' => 'NONEXISTENT']);

        $this->assertEquals(1, $result['status']);
        $this->assertEmpty($result['data']);
    }

    // ========== getActiveSlocs ==========

    public function test_it_returns_active_slocs_with_status_1(): void
    {
        $repoData = [
            ['id_sloc' => 1, 'sloc_code' => 'EOB-TANK', 'sloc_name' => 'EOB Tank Farm'],
            ['id_sloc' => 2, 'sloc_code' => 'EOB-WH', 'sloc_name' => 'EOB Warehouse'],
        ];

        $this->repoMock->shouldReceive('getActiveSlocs')
            ->once()
            ->withNoArgs()
            ->andReturn($repoData);

        $result = $this->service->getActiveSlocs();

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
        $this->assertEquals('Active slocs retrieved', $result['message']);
    }

    public function test_it_returns_empty_data_when_no_active_slocs(): void
    {
        $this->repoMock->shouldReceive('getActiveSlocs')
            ->once()
            ->andReturn([]);

        $result = $this->service->getActiveSlocs();

        $this->assertEquals(1, $result['status']);
        $this->assertEmpty($result['data']);
    }
}
