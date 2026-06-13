<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\TsTsreport\Services\TsReportService;
use Modules\TsTsreport\Repositories\Contracts\TsReportRepositoryInterface;
use Mockery;

class TsReportServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // getTsReport
    // -------------------------------------------------------------------------

    public function test_it_returns_ts_report_with_status_and_data(): void
    {
        $repoMock = Mockery::mock(TsReportRepositoryInterface::class);
        $expected = [
            (object)['id_balance_head' => 1, 'entry_date' => '2026-06-01', 'description' => 'RBDPO', 'qty' => '500.000'],
            (object)['id_balance_head' => 2, 'entry_date' => '2026-06-01', 'description' => 'PKO',   'qty' => '250.000'],
        ];

        $repoMock->shouldReceive('getTsReport')
            ->once()
            ->with(['entry_date' => '2026-06-01'])
            ->andReturn($expected);

        $service = new TsReportService($repoMock);
        $result  = $service->getTsReport(['entry_date' => '2026-06-01']);

        $this->assertSame(1, $result['status']);
        $this->assertSame($expected, $result['data']);
        $this->assertSame('TS Report retrieved', $result['message']);
    }

    public function test_it_returns_ts_report_with_empty_data_when_no_results(): void
    {
        $repoMock = Mockery::mock(TsReportRepositoryInterface::class);

        $repoMock->shouldReceive('getTsReport')
            ->once()
            ->with([])
            ->andReturn([]);

        $service = new TsReportService($repoMock);
        $result  = $service->getTsReport([]);

        $this->assertSame(1, $result['status']);
        $this->assertSame([], $result['data']);
    }

    // -------------------------------------------------------------------------
    // getTsReportRm
    // -------------------------------------------------------------------------

    public function test_it_returns_ts_report_rm_section(): void
    {
        $repoMock = Mockery::mock(TsReportRepositoryInterface::class);
        $filters  = ['entry_date' => '2026-06-01', 'plant_id' => 'EG1'];
        $rows     = [
            (object)['id_material' => 3, 'description' => 'CPO', 'recv_qty' => '100.000'],
        ];

        $repoMock->shouldReceive('getTsReportRm')
            ->once()
            ->with($filters)
            ->andReturn($rows);

        $service = new TsReportService($repoMock);
        $result  = $service->getTsReportRm($filters);

        $this->assertSame(1, $result['status']);
        $this->assertSame($rows, $result['data']);
        $this->assertSame('TS Report RM section retrieved', $result['message']);
    }

    // -------------------------------------------------------------------------
    // getTsReportPck
    // -------------------------------------------------------------------------

    public function test_it_returns_ts_report_pck_section(): void
    {
        $repoMock = Mockery::mock(TsReportRepositoryInterface::class);
        $filters  = ['entry_date' => '2026-06-01'];
        $rows     = [
            (object)['id_material' => 5, 'description' => 'PKG-A', 'qty' => '200.000'],
        ];

        $repoMock->shouldReceive('getTsReportPck')
            ->once()
            ->with($filters)
            ->andReturn($rows);

        $service = new TsReportService($repoMock);
        $result  = $service->getTsReportPck($filters);

        $this->assertSame(1, $result['status']);
        $this->assertSame($rows, $result['data']);
        $this->assertSame('TS Report PCK section retrieved', $result['message']);
    }

    public function test_it_returns_pck_section_with_empty_filters(): void
    {
        $repoMock = Mockery::mock(TsReportRepositoryInterface::class);

        $repoMock->shouldReceive('getTsReportPck')
            ->once()
            ->with([])
            ->andReturn([]);

        $service = new TsReportService($repoMock);
        $result  = $service->getTsReportPck([]);

        $this->assertSame(1, $result['status']);
        $this->assertSame([], $result['data']);
    }

    // -------------------------------------------------------------------------
    // getTsReportShipment
    // -------------------------------------------------------------------------

    public function test_it_returns_ts_report_shipment_section(): void
    {
        $repoMock = Mockery::mock(TsReportRepositoryInterface::class);
        $filters  = ['entry_date' => '2026-06-01', 'plant_id' => 'EG2'];
        $rows     = [
            (object)['trace_no' => 'SHIP-001', 'qty' => '1000.000'],
        ];

        $repoMock->shouldReceive('getTsReportShipment')
            ->once()
            ->with($filters)
            ->andReturn($rows);

        $service = new TsReportService($repoMock);
        $result  = $service->getTsReportShipment($filters);

        $this->assertSame(1, $result['status']);
        $this->assertSame($rows, $result['data']);
        $this->assertSame('TS Report Shipment section retrieved', $result['message']);
    }

    // -------------------------------------------------------------------------
    // getTsReportTransfer
    // -------------------------------------------------------------------------

    public function test_it_returns_ts_report_transfer_section(): void
    {
        $repoMock = Mockery::mock(TsReportRepositoryInterface::class);
        $filters  = ['entry_date' => '2026-06-01'];
        $rows     = [
            (object)['trace_no' => 'TRF-001', 'source' => 'SL01', 'destination' => 'SL02', 'qty' => '300.000'],
        ];

        $repoMock->shouldReceive('getTsReportTransfer')
            ->once()
            ->with($filters)
            ->andReturn($rows);

        $service = new TsReportService($repoMock);
        $result  = $service->getTsReportTransfer($filters);

        $this->assertSame(1, $result['status']);
        $this->assertSame($rows, $result['data']);
        $this->assertSame('TS Report Transfer section retrieved', $result['message']);
    }

    // -------------------------------------------------------------------------
    // getTsReportWip
    // -------------------------------------------------------------------------

    public function test_it_returns_ts_report_wip_section(): void
    {
        $repoMock = Mockery::mock(TsReportRepositoryInterface::class);
        $filters  = ['entry_date' => '2026-06-01', 'plant_id' => 'EG1'];
        $rows     = [
            (object)['entry_no' => 'WIP-2606001', 'section_id' => 1, 'qty' => '150.000'],
        ];

        $repoMock->shouldReceive('getTsReportWip')
            ->once()
            ->with($filters)
            ->andReturn($rows);

        $service = new TsReportService($repoMock);
        $result  = $service->getTsReportWip($filters);

        $this->assertSame(1, $result['status']);
        $this->assertSame($rows, $result['data']);
        $this->assertSame('TS Report WIP section retrieved', $result['message']);
    }

    public function test_it_returns_wip_section_with_empty_data(): void
    {
        $repoMock = Mockery::mock(TsReportRepositoryInterface::class);

        $repoMock->shouldReceive('getTsReportWip')
            ->once()
            ->with(['entry_date' => '2026-01-01'])
            ->andReturn([]);

        $service = new TsReportService($repoMock);
        $result  = $service->getTsReportWip(['entry_date' => '2026-01-01']);

        $this->assertSame(1, $result['status']);
        $this->assertSame([], $result['data']);
    }

    // -------------------------------------------------------------------------
    // Multiple calls
    // -------------------------------------------------------------------------

    public function test_it_calls_repository_with_exact_filters_passed(): void
    {
        $repoMock = Mockery::mock(TsReportRepositoryInterface::class);
        $filters  = ['entry_date' => '2026-05-15', 'plant_id' => 'EG3', 'user_id' => 7];

        $repoMock->shouldReceive('getTsReport')
            ->once()
            ->with($filters)
            ->andReturn([
                (object)['id_balance_head' => 99, 'qty' => '999.000'],
            ]);

        $service = new TsReportService($repoMock);
        $result  = $service->getTsReport($filters);

        $this->assertCount(1, $result['data']);
    }

    public function test_it_returns_rm_section_with_empty_data_when_no_results(): void
    {
        $repoMock = Mockery::mock(TsReportRepositoryInterface::class);

        $repoMock->shouldReceive('getTsReportRm')
            ->once()
            ->with(['plant_id' => 'EG9'])
            ->andReturn([]);

        $service = new TsReportService($repoMock);
        $result  = $service->getTsReportRm(['plant_id' => 'EG9']);

        $this->assertSame(1, $result['status']);
        $this->assertSame([], $result['data']);
        $this->assertSame('TS Report RM section retrieved', $result['message']);
    }
}
