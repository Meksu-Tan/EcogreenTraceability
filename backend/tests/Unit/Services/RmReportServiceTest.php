<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\TsRmreport\Services\RmReportService;
use Modules\TsRmreport\Repositories\Contracts\RmReportRepositoryInterface;
use Mockery;
use Mockery\MockInterface;

class RmReportServiceTest extends TestCase
{
    protected MockInterface $repoMock;
    protected RmReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoMock = Mockery::mock(RmReportRepositoryInterface::class);
        $this->service  = new RmReportService($this->repoMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========== getRmReport ==========

    public function test_it_returns_rm_report_with_status_1_on_success(): void
    {
        $repoData = [
            ['batch_sap' => 'BATCH001', 'material' => 'CRUDE PALM OIL', 'qty_in' => 5000.0],
            ['batch_sap' => 'BATCH002', 'material' => 'PALM STEARIN', 'qty_in' => 3000.0],
        ];

        $this->repoMock->shouldReceive('getRmReport')
            ->once()
            ->with([])
            ->andReturn($repoData);

        $result = $this->service->getRmReport();

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
        $this->assertEquals('RM Report retrieved', $result['message']);
    }

    public function test_it_returns_empty_data_when_no_rm_report_records(): void
    {
        $this->repoMock->shouldReceive('getRmReport')
            ->once()
            ->with([])
            ->andReturn([]);

        $result = $this->service->getRmReport();

        $this->assertEquals(1, $result['status']);
        $this->assertEmpty($result['data']);
    }

    public function test_it_passes_filters_to_repository_when_getting_rm_report(): void
    {
        $filters  = ['plant_code' => 'EOB', 'period' => '2026-06'];
        $repoData = [['batch_sap' => 'BATCH001', 'material' => 'CRUDE PALM OIL']];

        $this->repoMock->shouldReceive('getRmReport')
            ->once()
            ->with($filters)
            ->andReturn($repoData);

        $result = $this->service->getRmReport($filters);

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
    }

    // ========== getRmListDetail ==========

    public function test_it_returns_rm_list_detail_with_status_1(): void
    {
        $repoData = [
            ['id_detail' => 1, 'batch_sap' => 'BATCH001', 'movement_type' => 'GR'],
        ];

        $this->repoMock->shouldReceive('getRmListDetail')
            ->once()
            ->with([])
            ->andReturn($repoData);

        $result = $this->service->getRmListDetail();

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
        $this->assertEquals('RM detail list retrieved', $result['message']);
    }

    public function test_it_returns_empty_data_when_no_rm_list_detail(): void
    {
        $this->repoMock->shouldReceive('getRmListDetail')
            ->once()
            ->with([])
            ->andReturn([]);

        $result = $this->service->getRmListDetail();

        $this->assertEquals(1, $result['status']);
        $this->assertEmpty($result['data']);
    }

    public function test_it_passes_filters_to_repository_when_getting_rm_list_detail(): void
    {
        $filters  = ['batch_sap' => 'BATCH001'];
        $repoData = [['id_detail' => 1, 'movement_type' => 'GR']];

        $this->repoMock->shouldReceive('getRmListDetail')
            ->once()
            ->with($filters)
            ->andReturn($repoData);

        $result = $this->service->getRmListDetail($filters);

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
    }

    // ========== getRmListTransfer ==========

    public function test_it_returns_rm_list_transfer_with_status_1(): void
    {
        $repoData = [
            ['id_transfer' => 1, 'batch_sap' => 'BATCH001', 'qty' => 500.0],
        ];

        $this->repoMock->shouldReceive('getRmListTransfer')
            ->once()
            ->with([])
            ->andReturn($repoData);

        $result = $this->service->getRmListTransfer();

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
        $this->assertEquals('RM transfer list retrieved', $result['message']);
    }

    public function test_it_returns_empty_data_when_no_rm_list_transfer(): void
    {
        $this->repoMock->shouldReceive('getRmListTransfer')
            ->once()
            ->with([])
            ->andReturn([]);

        $result = $this->service->getRmListTransfer();

        $this->assertEquals(1, $result['status']);
        $this->assertEmpty($result['data']);
    }

    // ========== getRmSummaryRmPrd ==========

    public function test_it_returns_rm_summary_rm_prd_with_status_1(): void
    {
        $repoData = [
            ['batch_sap' => 'BATCH001', 'total_in' => 5000.0, 'total_out' => 2000.0],
        ];

        $this->repoMock->shouldReceive('getRmSummaryRmPrd')
            ->once()
            ->with([])
            ->andReturn($repoData);

        $result = $this->service->getRmSummaryRmPrd();

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
        $this->assertEquals('RM summary retrieved', $result['message']);
    }

    public function test_it_passes_filters_to_repository_when_getting_rm_summary(): void
    {
        $filters  = ['plant_code' => 'EOB', 'batch_sap' => 'BATCH001'];
        $repoData = [['batch_sap' => 'BATCH001', 'total_in' => 5000.0]];

        $this->repoMock->shouldReceive('getRmSummaryRmPrd')
            ->once()
            ->with($filters)
            ->andReturn($repoData);

        $result = $this->service->getRmSummaryRmPrd($filters);

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
    }

    // ========== getRmDetailRmPrdOnTank ==========

    public function test_it_returns_rm_detail_on_tank_with_status_1(): void
    {
        $repoData = [
            ['id_tank' => 1, 'tank_code' => 'T01', 'batch_sap' => 'BATCH001', 'qty' => 3000.0],
        ];

        $this->repoMock->shouldReceive('getRmDetailRmPrdOnTank')
            ->once()
            ->with('BATCH001')
            ->andReturn($repoData);

        $result = $this->service->getRmDetailRmPrdOnTank('BATCH001');

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
        $this->assertEquals('RM detail on tank retrieved', $result['message']);
    }

    public function test_it_returns_empty_data_when_no_rm_detail_on_tank_for_batch(): void
    {
        $this->repoMock->shouldReceive('getRmDetailRmPrdOnTank')
            ->once()
            ->with('UNKNOWN_BATCH')
            ->andReturn([]);

        $result = $this->service->getRmDetailRmPrdOnTank('UNKNOWN_BATCH');

        $this->assertEquals(1, $result['status']);
        $this->assertEmpty($result['data']);
    }

    // ========== getRmDetailRmPrdOnAdjOut ==========

    public function test_it_returns_rm_detail_on_adj_out_with_status_1(): void
    {
        $repoData = [
            ['id_adj' => 1, 'adj_type' => 'ADJ-OUT', 'batch_sap' => 'BATCH001', 'qty' => 200.0],
        ];

        $this->repoMock->shouldReceive('getRmDetailRmPrdOnAdjOut')
            ->once()
            ->with('BATCH001')
            ->andReturn($repoData);

        $result = $this->service->getRmDetailRmPrdOnAdjOut('BATCH001');

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
        $this->assertEquals('RM detail on adj out retrieved', $result['message']);
    }

    public function test_it_returns_empty_data_when_no_rm_detail_on_adj_out_for_batch(): void
    {
        $this->repoMock->shouldReceive('getRmDetailRmPrdOnAdjOut')
            ->once()
            ->with('UNKNOWN_BATCH')
            ->andReturn([]);

        $result = $this->service->getRmDetailRmPrdOnAdjOut('UNKNOWN_BATCH');

        $this->assertEquals(1, $result['status']);
        $this->assertEmpty($result['data']);
    }

    // ========== getRmDetailRmPrdOnWarehouse ==========

    public function test_it_returns_rm_detail_on_warehouse_with_status_1(): void
    {
        $repoData = [
            ['id_wh' => 1, 'warehouse_code' => 'WH01', 'batch_sap' => 'BATCH001', 'qty' => 1500.0],
        ];

        $this->repoMock->shouldReceive('getRmDetailRmPrdOnWarehouse')
            ->once()
            ->with('BATCH001')
            ->andReturn($repoData);

        $result = $this->service->getRmDetailRmPrdOnWarehouse('BATCH001');

        $this->assertEquals(1, $result['status']);
        $this->assertSame($repoData, $result['data']);
        $this->assertEquals('RM detail on warehouse retrieved', $result['message']);
    }

    public function test_it_returns_empty_data_when_no_rm_detail_on_warehouse_for_batch(): void
    {
        $this->repoMock->shouldReceive('getRmDetailRmPrdOnWarehouse')
            ->once()
            ->with('UNKNOWN_BATCH')
            ->andReturn([]);

        $result = $this->service->getRmDetailRmPrdOnWarehouse('UNKNOWN_BATCH');

        $this->assertEquals(1, $result['status']);
        $this->assertEmpty($result['data']);
    }
}
