<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Adjustment\Services\AdjustmentPeriodService;
use Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;
use Modules\Shared\Services\AuditService;

/**
 * Unit tests for AdjustmentPeriodService.
 *
 * AuditService uses only static methods that call DB::connection('eudr_ts').
 * We use the real AuditService and mock DB::connection('eudr_ts') via the
 * DB facade to intercept both transaction calls and audit log inserts.
 */
class AdjustmentPeriodServiceTest extends TestCase
{
    protected AdjustmentRepositoryInterface|MockInterface $repoMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoMock = Mockery::mock(AdjustmentRepositoryInterface::class);

        // Suppress AuditService logging side-effects
        Log::shouldReceive('channel')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')->andReturn(null)->byDefault();
        Log::shouldReceive('error')->andReturn(null)->byDefault();
        Log::shouldReceive('debug')->andReturn(null)->byDefault();
        Log::shouldReceive('warning')->andReturn(null)->byDefault();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeService(): AdjustmentPeriodService
    {
        return new AdjustmentPeriodService($this->repoMock, new AuditService());
    }

    private function mockEudrConnection(): MockInterface
    {
        $conn = Mockery::mock('EudrTsConnection');

        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->andReturn($conn);

        return $conn;
    }

    // ——— getPeriodHeaders ———

    public function test_it_delegates_get_period_headers_to_repository(): void
    {
        $expected = [
            ['id_period_head' => 1, 'period_date' => '2026-01-01'],
            ['id_period_head' => 2, 'period_date' => '2026-02-01'],
        ];

        $this->repoMock
            ->shouldReceive('getPeriodHeaders')
            ->once()
            ->andReturn($expected);

        $result = $this->makeService()->getPeriodHeaders();

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_empty_array_from_get_period_headers_when_none_exist(): void
    {
        $this->repoMock
            ->shouldReceive('getPeriodHeaders')
            ->once()
            ->andReturn([]);

        $result = $this->makeService()->getPeriodHeaders();

        $this->assertSame([], $result);
    }

    // ——— getPeriodViewData ———

    public function test_it_delegates_get_period_view_data_to_repository(): void
    {
        $expected = ['id_period_head' => 3, 'details' => [['material' => 'CPO', 'qty' => 500.0]]];

        $this->repoMock
            ->shouldReceive('getPeriodViewData')
            ->once()
            ->with(3)
            ->andReturn($expected);

        $result = $this->makeService()->getPeriodViewData(3);

        $this->assertSame($expected, $result);
    }

    // ——— periodViewOnHand ———

    public function test_it_delegates_period_view_on_hand_to_repository(): void
    {
        $expected = [['material' => 'RBDPO', 'on_hand_qty' => 1200.0]];

        $this->repoMock
            ->shouldReceive('periodViewOnHand')
            ->once()
            ->with('Admin', 5)
            ->andReturn($expected);

        $result = $this->makeService()->periodViewOnHand('Admin', 5);

        $this->assertSame($expected, $result);
    }

    // ——— periodHeadersUpload ———

    public function test_it_wraps_period_headers_upload_in_a_transaction(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $expected = ['response' => 1, 'message' => 'Upload successful'];
        $data     = ['period_date' => '2026-01-01'];
        $file     = 'dummy_file';

        $this->repoMock
            ->shouldReceive('periodHeadersUpload')
            ->once()
            ->with('Admin', $data, $file)
            ->andReturn($expected);

        $result = $this->makeService()->periodHeadersUpload('Admin', $data, $file);

        $this->assertSame($expected, $result);
    }

    // ——— periodViewAdjustment ———

    public function test_it_wraps_period_view_adjustment_in_a_transaction(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $expected = [['material' => 'CPO', 'adjusted_qty' => 800.0]];

        $this->repoMock
            ->shouldReceive('periodViewAdjustment')
            ->once()
            ->with('Admin', 4)
            ->andReturn($expected);

        $result = $this->makeService()->periodViewAdjustment('Admin', 4);

        $this->assertSame($expected, $result);
    }

    // ——— periodHeaderLock ———

    public function test_it_wraps_period_header_lock_in_a_transaction(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $expected = ['response' => 1, 'message' => 'Period locked'];

        $this->repoMock
            ->shouldReceive('periodHeaderLock')
            ->once()
            ->with('Admin', 2)
            ->andReturn($expected);

        $result = $this->makeService()->periodHeaderLock('Admin', 2);

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_failure_from_period_header_lock_when_repository_fails(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $expected = ['response' => 0, 'message' => 'Lock failed'];

        $this->repoMock
            ->shouldReceive('periodHeaderLock')
            ->once()
            ->with('Admin', 99)
            ->andReturn($expected);

        $result = $this->makeService()->periodHeaderLock('Admin', 99);

        $this->assertSame($expected, $result);
    }

    // ——— destroyAdjustmentPeriod ———

    public function test_it_wraps_destroy_adjustment_period_in_a_transaction(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $expected = ['response' => 1, 'message' => 'Period deleted'];

        $this->repoMock
            ->shouldReceive('destroyAdjustmentPeriod')
            ->once()
            ->with(6, 'Admin')
            ->andReturn($expected);

        $result = $this->makeService()->destroyAdjustmentPeriod(6, 'Admin');

        $this->assertSame($expected, $result);
    }

    // ——— getLastAdjustmentRecord ———

    public function test_it_delegates_get_last_adjustment_record_to_repository(): void
    {
        $expected = [['id_adjust_head' => 99, 'adjust_no' => 'ADJ-2026-001']];

        $this->repoMock
            ->shouldReceive('getLastAdjustmentRecord')
            ->once()
            ->with(1002)
            ->andReturn($expected);

        $result = $this->makeService()->getLastAdjustmentRecord(1002);

        $this->assertSame($expected, $result);
    }

    public function test_it_delegates_get_last_adjustment_record_with_null_plant_to_repository(): void
    {
        $expected = [];

        $this->repoMock
            ->shouldReceive('getLastAdjustmentRecord')
            ->once()
            ->with(null)
            ->andReturn($expected);

        $result = $this->makeService()->getLastAdjustmentRecord(null);

        $this->assertSame($expected, $result);
    }

    // ——— storeAdjustmentWhx: success path (triggers audit log) ———

    public function test_it_wraps_store_adjustment_whx_in_a_transaction_and_logs_audit_on_success(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $data     = ['adj_no' => 'ADJ-WHX-001', 'id_material' => 2];
        $expected = ['response' => 1, 'message' => 'WHX adjustment stored'];

        $this->repoMock
            ->shouldReceive('storeAdjustmentWhx')
            ->once()
            ->with('Admin', $data, 1003)
            ->andReturn($expected);

        // AuditService::logAdjustment → AuditService::log → DB::connection('eudr_ts')->insert(...)
        $conn->shouldReceive('insert')->once()->andReturn(true);

        $result = $this->makeService()->storeAdjustmentWhx('Admin', $data, 1003);

        $this->assertSame($expected, $result);
    }

    public function test_it_does_not_log_audit_when_store_adjustment_whx_fails(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $data     = ['adj_no' => 'ADJ-WHX-002', 'id_material' => 3];
        $expected = ['response' => 0, 'message' => 'WHX adjustment failed'];

        $this->repoMock
            ->shouldReceive('storeAdjustmentWhx')
            ->once()
            ->with('Admin', $data, 1003)
            ->andReturn($expected);

        // No audit insert should happen since response is 0
        $conn->shouldNotReceive('insert');

        $result = $this->makeService()->storeAdjustmentWhx('Admin', $data, 1003);

        $this->assertSame($expected, $result);
    }

    // ——— adjustmentInitWhx: success path (triggers audit log) ———

    public function test_it_wraps_adjustment_init_whx_in_a_transaction_and_logs_audit_on_success(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $data     = ['id_material' => 5, 'qty' => 300.0];
        $expected = ['response' => 1, 'message' => 'WHX init done'];

        $this->repoMock
            ->shouldReceive('adjustmentInitWhx')
            ->once()
            ->with('Admin', $data, 1003)
            ->andReturn($expected);

        $conn->shouldReceive('insert')->once()->andReturn(true);

        $result = $this->makeService()->adjustmentInitWhx('Admin', $data, 1003);

        $this->assertSame($expected, $result);
    }

    public function test_it_does_not_log_audit_when_adjustment_init_whx_fails(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $data     = ['id_material' => 6, 'qty' => 0.0];
        $expected = ['response' => 0, 'message' => 'Init failed'];

        $this->repoMock
            ->shouldReceive('adjustmentInitWhx')
            ->once()
            ->with('Admin', $data, 1003)
            ->andReturn($expected);

        $conn->shouldNotReceive('insert');

        $result = $this->makeService()->adjustmentInitWhx('Admin', $data, 1003);

        $this->assertSame($expected, $result);
    }

    // ——— getAdjustStatus ———

    public function test_it_delegates_get_adjust_status_by_adjust_no_to_repository(): void
    {
        $expected = [['adjust_no' => 'ADJ-001', 'status' => 1]];

        $this->repoMock
            ->shouldReceive('getAdjustStatus')
            ->once()
            ->with('ADJ-001', null)
            ->andReturn($expected);

        $result = $this->makeService()->getAdjustStatus('ADJ-001', null);

        $this->assertSame($expected, $result);
    }

    public function test_it_delegates_get_adjust_status_by_id_to_repository(): void
    {
        $expected = [['id_adjust_head' => 42, 'status' => 2]];

        $this->repoMock
            ->shouldReceive('getAdjustStatus')
            ->once()
            ->with(null, 42)
            ->andReturn($expected);

        $result = $this->makeService()->getAdjustStatus(null, 42);

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_empty_from_get_adjust_status_when_not_found(): void
    {
        $this->repoMock
            ->shouldReceive('getAdjustStatus')
            ->once()
            ->with(null, null)
            ->andReturn([]);

        $result = $this->makeService()->getAdjustStatus(null, null);

        $this->assertSame([], $result);
    }
}
