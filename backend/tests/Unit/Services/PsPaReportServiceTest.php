<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\Inquiry\Services\PsPaReportService;
use Modules\Inquiry\Repositories\PsPaReportRepository;
use Modules\Shared\Services\PeriodLockService;
use Modules\Shared\Services\AuditService;
use Mockery;

class PsPaReportServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeService(
        mixed $repo = null,
        mixed $periodLock = null,
        mixed $audit = null
    ): PsPaReportService {
        $repo       = $repo       ?? Mockery::mock(PsPaReportRepository::class);
        $periodLock = $periodLock ?? Mockery::mock(PeriodLockService::class);
        $audit      = $audit      ?? Mockery::mock(AuditService::class);

        return new PsPaReportService($repo, $periodLock, $audit);
    }

    // -------------------------------------------------------------------------
    // getReportHeadList
    // -------------------------------------------------------------------------

    public function test_it_returns_report_head_list(): void
    {
        $repoMock = Mockery::mock(PsPaReportRepository::class);
        $expected = [
            ['id_report_head' => 1, 'period' => '2026-06-01', 'status' => 1],
            ['id_report_head' => 2, 'period' => '2026-05-01', 'status' => 3],
        ];

        $repoMock->shouldReceive('getReportHeadList')
            ->once()
            ->with('EG1', 5, '2026-01-01', '2026-06-30')
            ->andReturn($expected);

        $service = $this->makeService($repoMock);
        $result  = $service->getReportHeadList('EG1', 5, '2026-01-01', '2026-06-30');

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_empty_list_when_no_reports(): void
    {
        $repoMock = Mockery::mock(PsPaReportRepository::class);

        $repoMock->shouldReceive('getReportHeadList')
            ->once()
            ->with(null, null, null, null)
            ->andReturn([]);

        $service = $this->makeService($repoMock);
        $result  = $service->getReportHeadList(null, null, null, null);

        $this->assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // getReportDetail
    // -------------------------------------------------------------------------

    public function test_it_returns_report_detail(): void
    {
        $repoMock = Mockery::mock(PsPaReportRepository::class);
        $detail   = [
            'head'  => ['id_report_head' => 7, 'status' => 2],
            'tails' => [
                (object)['id_report_tail' => 1, 'id_material' => 3, 'opening_stock' => 100.0],
            ],
        ];

        $repoMock->shouldReceive('getReportDetail')
            ->once()
            ->with(7)
            ->andReturn($detail);

        $service = $this->makeService($repoMock);
        $result  = $service->getReportDetail(7);

        $this->assertSame($detail, $result);
    }

    public function test_it_returns_null_when_report_not_found(): void
    {
        $repoMock = Mockery::mock(PsPaReportRepository::class);

        $repoMock->shouldReceive('getReportDetail')
            ->once()
            ->with(999)
            ->andReturn(null);

        $service = $this->makeService($repoMock);
        $result  = $service->getReportDetail(999);

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // generateReport — period locked
    // -------------------------------------------------------------------------

    public function test_it_returns_period_locked_response_when_period_is_locked(): void
    {
        $repoMock   = Mockery::mock(PsPaReportRepository::class);
        $lockMock   = Mockery::mock(PeriodLockService::class);
        $auditMock  = Mockery::mock(AuditService::class);

        $lockMock->shouldReceive('isUnlocked')
            ->once()
            ->with('2026-06-01')
            ->andReturn(false);

        $service = new PsPaReportService($repoMock, $lockMock, $auditMock);
        $result  = $service->generateReport('admin', 'EG1', '2026-06-01', []);

        $this->assertSame(99, $result['response']);
        $this->assertSame('Period is locked', $result['message']);
    }

    // -------------------------------------------------------------------------
    // calculateReport — report not found
    // -------------------------------------------------------------------------

    public function test_it_returns_not_found_when_calculating_nonexistent_report(): void
    {
        $repoMock = Mockery::mock(PsPaReportRepository::class);

        $repoMock->shouldReceive('getReportHead')
            ->once()
            ->with(404)
            ->andReturn(null);

        $service = $this->makeService($repoMock);
        $result  = $service->calculateReport('admin', 404);

        $this->assertSame(0, $result['response']);
        $this->assertSame('Report not found', $result['message']);
    }

    public function test_it_returns_already_calculated_when_report_status_is_not_draft(): void
    {
        $repoMock = Mockery::mock(PsPaReportRepository::class);
        $report   = (object)['id_report_head' => 3, 'status' => 3]; // APPROVED

        $repoMock->shouldReceive('getReportHead')
            ->once()
            ->with(3)
            ->andReturn($report);

        $service = $this->makeService($repoMock);
        $result  = $service->calculateReport('admin', 3);

        $this->assertSame(2, $result['response']);
        $this->assertSame('Report already calculated or approved', $result['message']);
    }

    // -------------------------------------------------------------------------
    // approveReport — report not found
    // -------------------------------------------------------------------------

    public function test_it_returns_not_found_when_approving_nonexistent_report(): void
    {
        $repoMock = Mockery::mock(PsPaReportRepository::class);

        $repoMock->shouldReceive('getReportHead')
            ->once()
            ->with(999)
            ->andReturn(null);

        $service = $this->makeService($repoMock);
        $result  = $service->approveReport('admin', 999);

        $this->assertSame(0, $result['response']);
        $this->assertSame('Report not found', $result['message']);
    }

    public function test_it_returns_error_when_approving_report_not_yet_calculated(): void
    {
        $repoMock = Mockery::mock(PsPaReportRepository::class);
        $report   = (object)['id_report_head' => 10, 'status' => 1]; // DRAFT

        $repoMock->shouldReceive('getReportHead')
            ->once()
            ->with(10)
            ->andReturn($report);

        $service = $this->makeService($repoMock);
        $result  = $service->approveReport('admin', 10);

        $this->assertSame(2, $result['response']);
        $this->assertSame('Report must be calculated before approval', $result['message']);
    }

    // -------------------------------------------------------------------------
    // getMaterialStock
    // -------------------------------------------------------------------------

    public function test_it_returns_material_stock_list(): void
    {
        $repoMock = Mockery::mock(PsPaReportRepository::class);
        $filters  = ['plant_id' => 'EG1', 'user_id' => 1, 'material_id' => null];
        $expected = [
            (object)['id_material' => 3, 'description' => 'RBDPO', 'current_stock' => 500.0],
        ];

        $repoMock->shouldReceive('getMaterialStock')
            ->once()
            ->with($filters)
            ->andReturn($expected);

        $service = $this->makeService($repoMock);
        $result  = $service->getMaterialStock($filters);

        $this->assertSame($expected, $result);
    }

    // -------------------------------------------------------------------------
    // getOpeningStock / getClosingStock
    // -------------------------------------------------------------------------

    public function test_it_returns_opening_stock_for_material(): void
    {
        $repoMock = Mockery::mock(PsPaReportRepository::class);
        $expected = ['opening' => 1500.0, 'material_id' => '3'];

        $repoMock->shouldReceive('getOpeningStock')
            ->once()
            ->with('3', '2026-06-01', 'EG1')
            ->andReturn($expected);

        $service = $this->makeService($repoMock);
        $result  = $service->getOpeningStock('3', '2026-06-01', 'EG1');

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_closing_stock_for_material(): void
    {
        $repoMock = Mockery::mock(PsPaReportRepository::class);
        $expected = ['closing' => 2000.0, 'material_id' => '3'];

        $repoMock->shouldReceive('getClosingStock')
            ->once()
            ->with('3', '2026-06-30', 'EG1')
            ->andReturn($expected);

        $service = $this->makeService($repoMock);
        $result  = $service->getClosingStock('3', '2026-06-30', 'EG1');

        $this->assertSame($expected, $result);
    }
}
