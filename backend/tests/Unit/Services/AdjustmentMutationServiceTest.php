<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;
use Modules\Adjustment\Services\AdjustmentMutationService;
use Modules\Shared\Helpers\ResponseCode;
use Modules\Shared\Services\AuditService;
use Tests\TestCase;

/**
 * Unit tests for AdjustmentMutationService.
 *
 * AuditService methods are all static and call DB::connection('eudr_ts') internally.
 * We mock DB::connection('eudr_ts') via the DB facade to intercept both
 * the service's own transaction calls AND AuditService::log DB inserts.
 */
class AdjustmentMutationServiceTest extends TestCase
{
    protected AdjustmentRepositoryInterface|MockInterface $repoMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoMock = Mockery::mock(AdjustmentRepositoryInterface::class);

        // Suppress logging from AuditService::log
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

    private function makeService(): AdjustmentMutationService
    {
        // Use real AuditService — its static methods call DB::connection('eudr_ts')
        // which is already mocked via the DB facade in each test.
        return new AdjustmentMutationService($this->repoMock, new AuditService);
    }

    /**
     * Build a mock for DB::connection('eudr_ts') and return it.
     * The caller stacks further expectations on the returned mock.
     */
    private function mockEudrConnection(): MockInterface
    {
        $conn = Mockery::mock('EudrTsConnection');

        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->andReturn($conn);

        return $conn;
    }

    // ——— createAdjustmentHeader: period locked ———

    public function test_it_returns_period_locked_when_period_is_locked(): void
    {
        $conn = $this->mockEudrConnection();

        // PeriodLockService::isLocked queries m_period_lock first — return locked
        $conn->shouldReceive('select')
            ->once()
            ->andReturn([(object) ['lock_status' => '1']]);

        $service = $this->makeService();
        $result = $service->createAdjustmentHeader('Admin', [
            'entry_date' => '2024-01-15',
            'adjust_no' => 'ADJ-001',
            'id_material' => 1,
            'after_adjust' => 500.0,
        ], 1002);

        $this->assertEquals(ResponseCode::PERIOD_LOCKED, $result['response']);
        $this->assertEquals('Period is locked', $result['message']);
    }

    // ——— createAdjustmentHeader: period unlocked, successful creation ———

    public function test_it_creates_adjustment_header_when_period_is_unlocked(): void
    {
        $conn = $this->mockEudrConnection();

        // PeriodLockService: both table queries return empty → not locked
        $conn->shouldReceive('select')->twice()->andReturn([]);

        // Transaction executes the callback immediately
        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        // Repository succeeds
        $this->repoMock
            ->shouldReceive('createAdjustmentHeader')
            ->once()
            ->with('Admin', Mockery::type('array'), 1002)
            ->andReturn(['response' => 1, 'id' => 42]);

        // AuditService::logAdjustment → AuditService::log → DB::connection('eudr_ts')->insert(...)
        $conn->shouldReceive('insert')->once()->andReturn(true);

        $service = $this->makeService();
        $result = $service->createAdjustmentHeader('Admin', [
            'entry_date' => '2024-01-15',
            'adjust_no' => 'ADJ-001',
            'id_material' => 1,
            'after_adjust' => 500.0,
        ], 1002);

        $this->assertEquals(1, $result['response']);
        $this->assertEquals(42, $result['id']);
    }

    // ——— approveAdjustment: header not found returns response 0 ———

    public function test_it_returns_failure_when_adjustment_header_not_found_on_approve(): void
    {
        $this->repoMock
            ->shouldReceive('getAdjustmentHeader')
            ->once()
            ->with(999)
            ->andReturn(null);

        $service = $this->makeService();
        $result = $service->approveAdjustment('Admin', 999, 2);

        $this->assertEquals(0, $result['response']);
        $this->assertEquals('Adjustment not found', $result['message']);
    }

    // ——— approveAdjustment: header already processed returns response 2 ———

    public function test_it_returns_already_processed_when_status_not_pending_on_approve(): void
    {
        $this->repoMock
            ->shouldReceive('getAdjustmentHeader')
            ->once()
            ->with(10)
            ->andReturn((object) ['status' => 2]); // already approved

        $service = $this->makeService();
        $result = $service->approveAdjustment('Admin', 10, 2);

        $this->assertEquals(2, $result['response']);
        $this->assertEquals('Adjustment already processed', $result['message']);
    }

    // ——— approveAdjustment: successful approval ———

    public function test_it_approves_adjustment_successfully(): void
    {
        $this->repoMock
            ->shouldReceive('getAdjustmentHeader')
            ->once()
            ->with(10)
            ->andReturn((object) ['status' => 1]); // pending

        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $this->repoMock
            ->shouldReceive('approveAdjustment')
            ->once()
            ->with(10, 2, 'Admin')
            ->andReturn(['response' => 1]);

        // AuditService::logAdjustment triggers AuditService::log → insert
        $conn->shouldReceive('insert')->once()->andReturn(true);

        $service = $this->makeService();
        $result = $service->approveAdjustment('Admin', 10, 2);

        $this->assertEquals(1, $result['response']);
    }

    // ——— executeAdjustment: header not found returns response 0 ———

    public function test_it_returns_failure_when_adjustment_header_not_found_on_execute(): void
    {
        $this->repoMock
            ->shouldReceive('getAdjustmentHeader')
            ->once()
            ->with(999)
            ->andReturn(null);

        $service = $this->makeService();
        $result = $service->executeAdjustment('Admin', 999);

        $this->assertEquals(0, $result['response']);
        $this->assertEquals('Adjustment not found', $result['message']);
    }

    // ——— executeAdjustment: wrong status (pending) returns response 2 ———

    public function test_it_returns_failure_when_status_is_not_approved_on_execute(): void
    {
        $this->repoMock
            ->shouldReceive('getAdjustmentHeader')
            ->once()
            ->with(15)
            ->andReturn((object) ['status' => 1]); // pending, not approved

        $service = $this->makeService();
        $result = $service->executeAdjustment('Admin', 15);

        $this->assertEquals(2, $result['response']);
        $this->assertEquals('Only APPROVED adjustments can be executed', $result['message']);
    }

    // ——— executeAdjustment: successful execution ———

    public function test_it_executes_adjustment_successfully(): void
    {
        $this->repoMock
            ->shouldReceive('getAdjustmentHeader')
            ->once()
            ->with(15)
            ->andReturn((object) ['status' => 2]); // approved

        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $this->repoMock
            ->shouldReceive('executeAdjustment')
            ->once()
            ->with(15)
            ->andReturn(['response' => 1]);

        $conn->shouldReceive('insert')->once()->andReturn(true);

        $service = $this->makeService();
        $result = $service->executeAdjustment('Admin', 15);

        $this->assertEquals(1, $result['response']);
    }

    // ——— cancelAdjustment: header not found returns response 0 ———

    public function test_it_returns_failure_when_adjustment_header_not_found_on_cancel(): void
    {
        $this->repoMock
            ->shouldReceive('getAdjustmentHeader')
            ->once()
            ->with(99)
            ->andReturn(null);

        $service = $this->makeService();
        $result = $service->cancelAdjustment('Admin', 99, 'Wrong data');

        $this->assertEquals(0, $result['response']);
        $this->assertEquals('Adjustment not found', $result['message']);
    }

    // ——— cancelAdjustment: invalid status (executed → 3) returns response 2 ———

    public function test_it_returns_failure_when_status_is_not_cancellable(): void
    {
        $this->repoMock
            ->shouldReceive('getAdjustmentHeader')
            ->once()
            ->with(20)
            ->andReturn((object) ['status' => 3]); // executed — not in [1,2]

        $service = $this->makeService();
        $result = $service->cancelAdjustment('Admin', 20, 'Wrong data');

        $this->assertEquals(2, $result['response']);
        $this->assertEquals('Cannot cancel adjustment in current status', $result['message']);
    }

    // ——— cancelAdjustment: successful cancellation (status 1 — pending) ———

    public function test_it_cancels_adjustment_successfully_when_status_is_pending(): void
    {
        $this->repoMock
            ->shouldReceive('getAdjustmentHeader')
            ->once()
            ->with(20)
            ->andReturn((object) ['status' => 1]); // pending

        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $this->repoMock
            ->shouldReceive('cancelAdjustment')
            ->once()
            ->with(20, 'Wrong data', 'Admin')
            ->andReturn(['response' => 1]);

        $conn->shouldReceive('insert')->once()->andReturn(true);

        $service = $this->makeService();
        $result = $service->cancelAdjustment('Admin', 20, 'Wrong data');

        $this->assertEquals(1, $result['response']);
    }

    // ——— cancelAdjustment: successful cancellation (status 2 — approved) ———

    public function test_it_cancels_adjustment_successfully_when_status_is_approved(): void
    {
        $this->repoMock
            ->shouldReceive('getAdjustmentHeader')
            ->once()
            ->with(21)
            ->andReturn((object) ['status' => 2]); // approved — also cancellable

        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $this->repoMock
            ->shouldReceive('cancelAdjustment')
            ->once()
            ->with(21, 'Data error', 'Admin')
            ->andReturn(['response' => 1]);

        $conn->shouldReceive('insert')->once()->andReturn(true);

        $service = $this->makeService();
        $result = $service->cancelAdjustment('Admin', 21, 'Data error');

        $this->assertEquals(1, $result['response']);
    }

    // ——— storeAdjustment: repository returns success → audit DB insert called ———

    public function test_it_stores_adjustment_and_logs_audit_on_success(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $data = ['id_adjust_head' => 1, 'id_material' => 5, 'after_adjust' => 200.0];

        $this->repoMock
            ->shouldReceive('storeAdjustment')
            ->once()
            ->with('Admin', $data, 1002)
            ->andReturn(['response' => 1]);

        // AuditService::logAdjustment → AuditService::log → insert
        $conn->shouldReceive('insert')->once()->andReturn(true);

        $service = $this->makeService();
        $result = $service->storeAdjustment('Admin', $data, 1002);

        $this->assertEquals(1, $result['response']);
    }

    // ——— storeAdjustment: repository returns failure → audit NOT called ———

    public function test_it_does_not_log_audit_when_store_adjustment_fails(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $data = ['id_adjust_head' => 1, 'id_material' => 5, 'after_adjust' => 200.0];

        $this->repoMock
            ->shouldReceive('storeAdjustment')
            ->once()
            ->with('Admin', $data, 1002)
            ->andReturn(['response' => 0]);

        // AuditService should NOT call insert because response != 1
        $conn->shouldReceive('insert')->never();

        $service = $this->makeService();
        $result = $service->storeAdjustment('Admin', $data, 1002);

        $this->assertEquals(0, $result['response']);
    }

    // ——— destroyAdjustment: successful deletion → audit logged ———

    public function test_it_destroys_adjustment_and_logs_audit_on_success(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $this->repoMock
            ->shouldReceive('destroyAdjustment')
            ->once()
            ->with(7, 'Admin')
            ->andReturn(['response' => 1]);

        $conn->shouldReceive('insert')->once()->andReturn(true);

        $service = $this->makeService();
        $result = $service->destroyAdjustment(7, 'Admin');

        $this->assertEquals(1, $result['response']);
    }

    // ——— deleteSupplierTemp: delegates to repository ———

    public function test_it_deletes_supplier_temp(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $this->repoMock
            ->shouldReceive('deleteSupplierTemp')
            ->once()
            ->with(55)
            ->andReturn(['response' => 1]);

        // deleteSupplierTemp response != 1 check: response is 1 but no audit in deleteSupplierTemp
        // (the method has no logAdjustment call — just delegates)
        $conn->shouldReceive('insert')->never();

        $service = $this->makeService();
        $result = $service->deleteSupplierTemp(55);

        $this->assertEquals(1, $result['response']);
    }

    // ——— addEntrySupplier: success → audit logged ———

    public function test_it_adds_entry_supplier_and_logs_audit_on_success(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $data = ['id_supplier' => 10, 'qty' => 100.0];

        $this->repoMock
            ->shouldReceive('addEntrySupplier')
            ->once()
            ->with('Admin', $data, 1002)
            ->andReturn(['response' => 1]);

        $conn->shouldReceive('insert')->once()->andReturn(true);

        $service = $this->makeService();
        $result = $service->addEntrySupplier('Admin', $data, 1002);

        $this->assertEquals(1, $result['response']);
    }

    // ——— adjustMaterialDocument: success → audit logged ———

    public function test_it_adjusts_material_document_and_logs_audit_on_success(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $this->repoMock
            ->shouldReceive('adjustMaterialDocument')
            ->once()
            ->with(42, 'MD-001', 'Admin')
            ->andReturn(['response' => 1]);

        $conn->shouldReceive('insert')->once()->andReturn(true);

        $service = $this->makeService();
        $result = $service->adjustMaterialDocument(42, 'MD-001', 'Admin');

        $this->assertEquals(1, $result['response']);
    }

    // ——— adjustMaterialDocument: null doc (clearing) ———

    public function test_it_adjusts_material_document_with_null_doc(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $this->repoMock
            ->shouldReceive('adjustMaterialDocument')
            ->once()
            ->with(42, null, 'Admin')
            ->andReturn(['response' => 1]);

        $conn->shouldReceive('insert')->once()->andReturn(true);

        $service = $this->makeService();
        $result = $service->adjustMaterialDocument(42, null, 'Admin');

        $this->assertEquals(1, $result['response']);
    }
}
