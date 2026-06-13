<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Mockery;
use Modules\Adjustment\Services\AdjustmentService;
use Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;
use Modules\Adjustment\Services\Contracts\AdjustmentMutationServiceInterface;
use Modules\Adjustment\Services\Contracts\AdjustmentPeriodServiceInterface;
use Modules\Shared\Services\PeriodLockService;
use Modules\Shared\Services\AuditService;

class AdjustmentServiceTest extends TestCase
{
    protected AdjustmentRepositoryInterface $repoMock;
    protected AdjustmentMutationServiceInterface $mutationMock;
    protected AdjustmentPeriodServiceInterface $periodMock;
    protected PeriodLockService $lockMock;
    protected AuditService $auditMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoMock     = Mockery::mock(AdjustmentRepositoryInterface::class);
        $this->mutationMock = Mockery::mock(AdjustmentMutationServiceInterface::class);
        $this->periodMock   = Mockery::mock(AdjustmentPeriodServiceInterface::class);
        $this->lockMock     = Mockery::mock(PeriodLockService::class);
        $this->auditMock    = Mockery::mock(AuditService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeService(): AdjustmentService
    {
        return new AdjustmentService(
            $this->repoMock,
            $this->lockMock,
            $this->auditMock,
            $this->periodMock,
            $this->mutationMock
        );
    }

    // ——— Lookup delegates to repository ———

    public function test_it_delegates_get_active_materials_to_repository(): void
    {
        $expected = [['id_material' => 1, 'description' => 'CPO']];

        $this->repoMock
            ->shouldReceive('getActiveMaterials')
            ->once()
            ->andReturn($expected);

        $result = $this->makeService()->getActiveMaterials();

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_active_material_whx_to_repository(): void
    {
        $expected = [['id_material' => 2, 'description' => 'RBDPO']];

        $this->repoMock
            ->shouldReceive('getActiveMaterialWhx')
            ->once()
            ->andReturn($expected);

        $result = $this->makeService()->getActiveMaterialWhx();

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_active_tanks_to_repository(): void
    {
        $expected = [['id_tank' => 1, 'description' => 'T-01']];

        $this->repoMock
            ->shouldReceive('getActiveTanks')
            ->once()
            ->with(1002)
            ->andReturn($expected);

        $result = $this->makeService()->getActiveTanks(1002);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_active_specific_tanks_to_repository(): void
    {
        $expected = [['id_tank' => 3, 'sloc' => 'SL03']];

        $this->repoMock
            ->shouldReceive('getActiveSpecificTanks')
            ->once()
            ->with(5)
            ->andReturn($expected);

        $result = $this->makeService()->getActiveSpecificTanks(5);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_lock_status_to_repository(): void
    {
        $expected = [['lock_status' => '0']];

        $this->repoMock
            ->shouldReceive('getLockStatus')
            ->once()
            ->with('2024-01-15')
            ->andReturn($expected);

        $result = $this->makeService()->getLockStatus('2024-01-15');

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_supplier_by_filter_to_repository(): void
    {
        $expected = [['id_supplier' => 10, 'name' => 'PT ABC']];

        $this->repoMock
            ->shouldReceive('getSupplierByFilter')
            ->once()
            ->with(1, 2)
            ->andReturn($expected);

        $result = $this->makeService()->getSupplierByFilter(1, 2);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_batch_by_supplier_to_repository(): void
    {
        $expected = [['batch_sap' => 'BATCH-001']];

        $this->repoMock
            ->shouldReceive('getBatchBySupplier')
            ->once()
            ->with(1, 2, 10)
            ->andReturn($expected);

        $result = $this->makeService()->getBatchBySupplier(1, 2, 10);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_generate_entry_no_to_repository(): void
    {
        $expected = 'ADJ-2024-001';

        $this->repoMock
            ->shouldReceive('generateEntryNo')
            ->once()
            ->with('2024-01-15', 1002)
            ->andReturn($expected);

        $result = $this->makeService()->generateEntryNo('2024-01-15', 1002);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_adjustment_list_to_repository(): void
    {
        $expected = [['id_adjust_head' => 1]];

        $this->repoMock
            ->shouldReceive('getAdjustmentList')
            ->once()
            ->with(1002, null, 'wip', [])
            ->andReturn($expected);

        $result = $this->makeService()->getAdjustmentList(1002);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_adjustment_detail_to_repository(): void
    {
        $expected = ['id_adjust_head' => 42, 'adjust_no' => 'ADJ-001'];

        $this->repoMock
            ->shouldReceive('getAdjustmentDetail')
            ->once()
            ->with(42)
            ->andReturn($expected);

        $result = $this->makeService()->getAdjustmentDetail(42);

        $this->assertEquals($expected, $result);
    }

    public function test_it_returns_null_from_get_adjustment_detail_when_not_found(): void
    {
        $this->repoMock
            ->shouldReceive('getAdjustmentDetail')
            ->once()
            ->with(999)
            ->andReturn(null);

        $result = $this->makeService()->getAdjustmentDetail(999);

        $this->assertNull($result);
    }

    public function test_it_delegates_get_supplier_list_to_repository(): void
    {
        $data     = ['id_material' => 1, 'id_tank' => 2];
        $expected = [['id_supplier' => 5, 'name' => 'PT Supplier']];

        $this->repoMock
            ->shouldReceive('getSupplierList')
            ->once()
            ->with($data, null)
            ->andReturn($expected);

        $result = $this->makeService()->getSupplierList($data);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_total_qty_supplier_to_repository(): void
    {
        $data     = ['id_material' => 1, 'id_tank' => 2, 'id_supplier' => 5];

        $this->repoMock
            ->shouldReceive('getTotalQtySupplier')
            ->once()
            ->with($data, null)
            ->andReturn(1500.0);

        $result = $this->makeService()->getTotalQtySupplier($data);

        $this->assertEquals(1500.0, $result);
    }

    public function test_it_delegates_get_active_suppliers_to_repository(): void
    {
        $expected = [['id_supplier' => 7, 'name' => 'PT Vendor']];

        $this->repoMock
            ->shouldReceive('getActiveSuppliers')
            ->once()
            ->with('vendor', null)
            ->andReturn($expected);

        $result = $this->makeService()->getActiveSuppliers('vendor');

        $this->assertEquals($expected, $result);
    }

    // ——— Mutation delegates to mutationService ———

    public function test_it_delegates_store_adjustment_to_mutation_service(): void
    {
        $data     = ['id_material' => 1, 'after_adjust' => 500.0];
        $expected = ['response' => 1];

        $this->mutationMock
            ->shouldReceive('storeAdjustment')
            ->once()
            ->with('Admin', $data, 1002)
            ->andReturn($expected);

        $result = $this->makeService()->storeAdjustment('Admin', $data, 1002);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_destroy_adjustment_to_mutation_service(): void
    {
        $expected = ['response' => 1];

        $this->mutationMock
            ->shouldReceive('destroyAdjustment')
            ->once()
            ->with(10, 'Admin')
            ->andReturn($expected);

        $result = $this->makeService()->destroyAdjustment(10, 'Admin');

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_add_entry_supplier_to_mutation_service(): void
    {
        $data     = ['id_supplier' => 5, 'qty' => 200.0];
        $expected = ['response' => 1];

        $this->mutationMock
            ->shouldReceive('addEntrySupplier')
            ->once()
            ->with('Admin', $data, 1002)
            ->andReturn($expected);

        $result = $this->makeService()->addEntrySupplier('Admin', $data, 1002);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_delete_supplier_temp_to_mutation_service(): void
    {
        $expected = ['response' => 1];

        $this->mutationMock
            ->shouldReceive('deleteSupplierTemp')
            ->once()
            ->with(55)
            ->andReturn($expected);

        $result = $this->makeService()->deleteSupplierTemp(55);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_create_adjustment_header_to_mutation_service(): void
    {
        $data     = ['entry_date' => '2024-01-15', 'id_material' => 1];
        $expected = ['response' => 1, 'id' => 42];

        $this->mutationMock
            ->shouldReceive('createAdjustmentHeader')
            ->once()
            ->with('Admin', $data, 1002)
            ->andReturn($expected);

        $result = $this->makeService()->createAdjustmentHeader('Admin', $data, 1002);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_create_adjustment_detail_to_mutation_service(): void
    {
        $data     = ['id_supplier' => 5, 'batch_sap' => 'BATCH-001'];
        $expected = ['response' => 1];

        $this->mutationMock
            ->shouldReceive('createAdjustmentDetail')
            ->once()
            ->with('Admin', 42, $data)
            ->andReturn($expected);

        $result = $this->makeService()->createAdjustmentDetail('Admin', 42, $data);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_approve_adjustment_to_mutation_service(): void
    {
        $expected = ['response' => 1];

        $this->mutationMock
            ->shouldReceive('approveAdjustment')
            ->once()
            ->with('Admin', 10, 2)
            ->andReturn($expected);

        $result = $this->makeService()->approveAdjustment('Admin', 10, 2);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_execute_adjustment_to_mutation_service(): void
    {
        $expected = ['response' => 1];

        $this->mutationMock
            ->shouldReceive('executeAdjustment')
            ->once()
            ->with('Admin', 15)
            ->andReturn($expected);

        $result = $this->makeService()->executeAdjustment('Admin', 15);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_cancel_adjustment_to_mutation_service(): void
    {
        $expected = ['response' => 1];

        $this->mutationMock
            ->shouldReceive('cancelAdjustment')
            ->once()
            ->with('Admin', 20, 'Incorrect data')
            ->andReturn($expected);

        $result = $this->makeService()->cancelAdjustment('Admin', 20, 'Incorrect data');

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_adjustment_init_to_mutation_service(): void
    {
        $data     = ['id_material' => 1, 'qty' => 300.0];
        $expected = ['response' => 1];

        $this->mutationMock
            ->shouldReceive('adjustmentInit')
            ->once()
            ->with('Admin', $data, 1002)
            ->andReturn($expected);

        $result = $this->makeService()->adjustmentInit('Admin', $data, 1002);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_adjustment_supplier_to_mutation_service(): void
    {
        $data     = ['id_supplier' => 5, 'qty' => 150.0];
        $expected = ['response' => 1];

        $this->mutationMock
            ->shouldReceive('adjustmentSupplier')
            ->once()
            ->with('Admin', $data, 1002)
            ->andReturn($expected);

        $result = $this->makeService()->adjustmentSupplier('Admin', $data, 1002);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_adjust_material_document_to_mutation_service(): void
    {
        $expected = ['response' => 1];

        $this->mutationMock
            ->shouldReceive('adjustMaterialDocument')
            ->once()
            ->with(42, 'MD-2024-001', 'Admin')
            ->andReturn($expected);

        $result = $this->makeService()->adjustMaterialDocument(42, 'MD-2024-001', 'Admin');

        $this->assertEquals($expected, $result);
    }

    // ——— Period delegates to periodService ———

    public function test_it_delegates_get_period_headers_to_period_service(): void
    {
        $expected = [['id' => 1, 'period' => '2024-01-01']];

        $this->periodMock
            ->shouldReceive('getPeriodHeaders')
            ->once()
            ->andReturn($expected);

        $result = $this->makeService()->getPeriodHeaders();

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_period_header_lock_to_period_service(): void
    {
        $expected = ['response' => 1, 'message' => 'Period locked'];

        $this->periodMock
            ->shouldReceive('periodHeaderLock')
            ->once()
            ->with('Admin', 1)
            ->andReturn($expected);

        $result = $this->makeService()->periodHeaderLock('Admin', 1);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_last_adjustment_record_to_period_service(): void
    {
        $expected = [['id_adjust_head' => 99]];

        $this->periodMock
            ->shouldReceive('getLastAdjustmentRecord')
            ->once()
            ->with(1002)
            ->andReturn($expected);

        $result = $this->makeService()->getLastAdjustmentRecord(1002);

        $this->assertEquals($expected, $result);
    }
}
