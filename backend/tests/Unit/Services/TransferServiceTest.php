<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\TsTransfer\Services\TransferService;
use Modules\TsTransfer\Services\TransferApprovalService;
use Modules\TsTransfer\Repositories\Contracts\TransferRepositoryInterface;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;

class TransferServiceTest extends TestCase
{
    protected MockInterface $repoMock;
    protected MockInterface $approvalMock;
    protected TransferService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoMock = Mockery::mock(TransferRepositoryInterface::class);
        $this->approvalMock = Mockery::mock(TransferApprovalService::class);
        $this->service = new TransferService($this->repoMock, $this->approvalMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========== getActiveMaterials ==========

    public function test_it_delegates_get_active_materials_to_repository(): void
    {
        $expected = [
            (object) ['id_material' => 1, 'material' => 'PALM OIL (PO - RAW)'],
            (object) ['id_material' => 2, 'material' => 'STEARIN (ST - RAW)'],
        ];

        $this->repoMock->shouldReceive('getActiveMaterials')
            ->once()
            ->withNoArgs()
            ->andReturn(collect($expected));

        $result = $this->service->getActiveMaterials();

        $this->assertEquals($expected, $result);
    }

    public function test_it_returns_empty_array_when_no_active_materials(): void
    {
        $this->repoMock->shouldReceive('getActiveMaterials')
            ->once()
            ->andReturn(collect([]));

        $result = $this->service->getActiveMaterials();

        $this->assertEmpty($result);
    }

    // ========== generateEntryNo ==========

    public function test_it_delegates_generate_entry_no_to_repository(): void
    {
        $expectedEntryNo = '726060310001';

        $this->repoMock->shouldReceive('generateTransferEntryNo')
            ->once()
            ->with(1, 1001)
            ->andReturn($expectedEntryNo);

        $result = $this->service->generateEntryNo(1, 1001);

        $this->assertEquals($expectedEntryNo, $result);
    }

    public function test_it_returns_null_when_entry_no_cannot_be_generated(): void
    {
        $this->repoMock->shouldReceive('generateTransferEntryNo')
            ->once()
            ->with(99, 9999)
            ->andReturn(null);

        $result = $this->service->generateEntryNo(99, 9999);

        $this->assertNull($result);
    }

    // ========== getTotalStockMaterial ==========

    public function test_it_delegates_get_total_stock_material_to_repository(): void
    {
        $this->repoMock->shouldReceive('getTotalStockMaterial')
            ->once()
            ->with(1, 5, 1001)
            ->andReturn(150.5);

        $result = $this->service->getTotalStockMaterial(1, 5, 1001);

        $this->assertEquals(150.5, $result);
    }

    public function test_it_returns_zero_when_no_stock_available(): void
    {
        $this->repoMock->shouldReceive('getTotalStockMaterial')
            ->once()
            ->with(1, 5, 1001)
            ->andReturn(0.0);

        $result = $this->service->getTotalStockMaterial(1, 5, 1001);

        $this->assertEquals(0.0, $result);
    }

    // ========== getTransferList ==========

    public function test_it_returns_all_plants_transfer_list_when_plant_id_is_zero(): void
    {
        $expectedList = [
            ['id_balance_head' => 1, 'trace_no' => '726060310001'],
            ['id_balance_head' => 2, 'trace_no' => '726060310002'],
        ];

        // When plantId = 0, plantCode stays 0 and no DB call for plant lookup
        $this->repoMock->shouldReceive('getTransferList')
            ->once()
            ->with(0, 1, 5)
            ->andReturn($expectedList);

        $result = $this->service->getTransferList(0);

        $this->assertEquals($expectedList, $result);
    }

    public function test_it_uses_default_pagination_when_getting_transfer_list(): void
    {
        $this->repoMock->shouldReceive('getTransferList')
            ->once()
            ->with(0, 1, 5)
            ->andReturn([]);

        $result = $this->service->getTransferList(0);

        $this->assertEquals([], $result);
    }

    public function test_it_passes_custom_pagination_to_repository(): void
    {
        $expectedList = [['id_balance_head' => 10]];

        $this->repoMock->shouldReceive('getTransferList')
            ->once()
            ->with(0, 2, 10)
            ->andReturn($expectedList);

        $result = $this->service->getTransferList(0, 2, 10);

        $this->assertEquals($expectedList, $result);
    }

    // ========== getActiveTanksRundown ==========

    public function test_it_delegates_get_active_tanks_rundown_to_repository(): void
    {
        $expected = [
            (object) ['id_tank' => 1, 'tank' => 'Storage EOB1'],
        ];

        $this->repoMock->shouldReceive('getActiveTanksRundown')
            ->once()
            ->with(1, 1001)
            ->andReturn(collect($expected));

        $result = $this->service->getActiveTanksRundown(1, 1001);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_active_tanks_rundown_with_null_material(): void
    {
        $this->repoMock->shouldReceive('getActiveTanksRundown')
            ->once()
            ->with(null, 1001)
            ->andReturn(collect([]));

        $result = $this->service->getActiveTanksRundown(null, 1001);

        $this->assertEmpty($result);
    }

    // ========== getActiveSpecificTanksRundown ==========

    public function test_it_delegates_get_active_specific_tanks_rundown_to_repository(): void
    {
        $expected = [
            (object) ['id_sloc_tail' => 10, 'id_tank_tail' => 'S10'],
        ];

        $this->repoMock->shouldReceive('getActiveSpecificTanksRundown')
            ->once()
            ->with(5)
            ->andReturn(collect($expected));

        $result = $this->service->getActiveSpecificTanksRundown(5);

        $this->assertEquals($expected, $result);
    }

    // ========== getUpdateSupplierMaterial ==========

    public function test_it_delegates_get_update_supplier_material_to_repository(): void
    {
        $expected = (object) ['supplierCode' => '26060101PO', 'idSupplier' => 3];

        $this->repoMock->shouldReceive('getUpdateSupplierMaterial')
            ->once()
            ->with(1, 5, 1001)
            ->andReturn($expected);

        $result = $this->service->getUpdateSupplierMaterial(1, 5, 1001);

        $this->assertEquals($expected, $result);
    }

    public function test_it_returns_null_when_supplier_material_not_found(): void
    {
        $this->repoMock->shouldReceive('getUpdateSupplierMaterial')
            ->once()
            ->with(99, 99, 99)
            ->andReturn(null);

        $result = $this->service->getUpdateSupplierMaterial(99, 99, 99);

        $this->assertNull($result);
    }

    // ========== createMaterialDocument ==========

    public function test_it_delegates_create_material_document_to_repository(): void
    {
        $expected = ['response' => 1];

        $this->repoMock->shouldReceive('createMaterialDocument')
            ->once()
            ->with('admin', 42, 'DOC-001', 'ADD')
            ->andReturn($expected);

        $result = $this->service->createMaterialDocument('admin', 42, 'DOC-001', 'ADD');

        $this->assertEquals($expected, $result);
    }

    // ========== updateEntrySubTank ==========

    public function test_it_delegates_update_entry_sub_tank_to_repository(): void
    {
        $expected = ['response' => 1];

        $this->repoMock->shouldReceive('updateEntrySubTank')
            ->once()
            ->with('admin', 10, [1, 2, 3])
            ->andReturn($expected);

        $result = $this->service->updateEntrySubTank('admin', 10, [1, 2, 3]);

        $this->assertEquals($expected, $result);
    }

    // ========== deactivateTransfer ==========

    public function test_it_deactivates_transfer_when_approval_allows_deletion(): void
    {
        $id = '5|12';
        $expected = ['response' => 1];

        $this->approvalMock->shouldReceive('canDelete')
            ->once()
            ->with('5')
            ->andReturn(true);

        $this->repoMock->shouldReceive('deactivateTransfer')
            ->once()
            ->with($id, 'admin')
            ->andReturn($expected);

        $result = $this->service->deactivateTransfer($id, 'admin');

        $this->assertEquals($expected, $result);
    }

    public function test_it_blocks_deactivation_when_approval_status_disallows_deletion(): void
    {
        $id = '5|12';

        $this->approvalMock->shouldReceive('canDelete')
            ->once()
            ->with('5')
            ->andReturn(false);

        $this->repoMock->shouldNotReceive('deactivateTransfer');

        $result = $this->service->deactivateTransfer($id, 'admin');

        $this->assertEquals(5, $result['response']);
        $this->assertStringContainsString('cannot be deleted', $result['message']);
    }

    public function test_it_parses_id_with_spaces_when_checking_approval_for_deletion(): void
    {
        $id = ' 7 |15';

        $this->approvalMock->shouldReceive('canDelete')
            ->once()
            ->with('7')
            ->andReturn(true);

        $this->repoMock->shouldReceive('deactivateTransfer')
            ->once()
            ->andReturn(['response' => 1]);

        $result = $this->service->deactivateTransfer($id, 'admin');

        $this->assertEquals(1, $result['response']);
    }

    // ========== Approval workflow delegation ==========

    public function test_it_delegates_submit_for_approval_to_approval_service(): void
    {
        $expected = ['response' => 1, 'message' => 'Transfer submitted for approval'];

        $this->approvalMock->shouldReceive('submit')
            ->once()
            ->with('42', 'admin')
            ->andReturn($expected);

        $result = $this->service->submitForApproval('42', 'admin');

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_approve_transfer_to_approval_service(): void
    {
        $expected = ['response' => 1, 'message' => 'Transfer approved'];

        $this->approvalMock->shouldReceive('approve')
            ->once()
            ->with('42', 'admin', 'Looks good')
            ->andReturn($expected);

        $result = $this->service->approveTransfer('42', 'admin', 'Looks good');

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_approve_transfer_with_null_notes_to_approval_service(): void
    {
        $expected = ['response' => 1, 'message' => 'Transfer approved'];

        $this->approvalMock->shouldReceive('approve')
            ->once()
            ->with('42', 'admin', null)
            ->andReturn($expected);

        $result = $this->service->approveTransfer('42', 'admin');

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_reject_transfer_to_approval_service(): void
    {
        $expected = ['response' => 1, 'message' => 'Transfer rejected'];

        $this->approvalMock->shouldReceive('reject')
            ->once()
            ->with('42', 'admin', 'Incorrect amount')
            ->andReturn($expected);

        $result = $this->service->rejectTransfer('42', 'admin', 'Incorrect amount');

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_cancel_transfer_to_approval_service(): void
    {
        $expected = ['response' => 1, 'message' => 'Transfer cancelled'];

        $this->approvalMock->shouldReceive('cancel')
            ->once()
            ->with('42', 'admin')
            ->andReturn($expected);

        $result = $this->service->cancelTransfer('42', 'admin');

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_pending_approvals_to_approval_service(): void
    {
        $expected = [
            ['id_approval' => 1, 'entry_no' => '726060310001', 'status' => 'PENDING'],
        ];

        $this->approvalMock->shouldReceive('getPendingApprovals')
            ->once()
            ->with(1001)
            ->andReturn($expected);

        $result = $this->service->getPendingApprovals(1001);

        $this->assertEquals($expected, $result);
    }

    public function test_it_gets_all_plant_pending_approvals_when_plant_id_is_zero(): void
    {
        $expected = [];

        $this->approvalMock->shouldReceive('getPendingApprovals')
            ->once()
            ->with(0)
            ->andReturn($expected);

        $result = $this->service->getPendingApprovals(0);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_approval_history_to_approval_service(): void
    {
        $expected = [
            ['id_approval' => 1, 'status' => 'APPROVED', 'approved_by' => 'admin'],
        ];

        $this->approvalMock->shouldReceive('getApprovalHistory')
            ->once()
            ->with('42')
            ->andReturn($expected);

        $result = $this->service->getApprovalHistory('42');

        $this->assertEquals($expected, $result);
    }

    // ========== executeTransfer — period lock ==========

    public function test_it_returns_response_99_when_period_is_locked_on_execute_transfer(): void
    {
        $data = [
            'entry_no'      => '726060310001',
            'entry_date'    => '2026-06-03',
            'id_material'   => 1,
            'material_doc'  => '',
            'trf_qty'       => '100.00',
            'source_sloc'   => 1,
            'trf_sloc'      => 2,
            'source_sloc_no' => [],
            'trf_sloc_no'   => [],
        ];

        // getSlocPlant → resolves srcPlant and destPlant
        $this->repoMock->shouldReceive('getSlocPlant')
            ->twice()
            ->andReturn(1001);

        $this->repoMock->shouldReceive('getLockStatus')
            ->once()
            ->with('2026-06-03')
            ->andReturn(true);

        $result = $this->service->executeTransfer('admin', $data, 1001);

        $this->assertEquals(99, $result['response']);
    }

    public function test_it_returns_response_4_when_stock_is_insufficient_on_execute_transfer(): void
    {
        $data = [
            'entry_no'      => '726060310001',
            'entry_date'    => '2026-06-03',
            'id_material'   => 1,
            'material_doc'  => '',
            'trf_qty'       => '200.000',
            'source_sloc'   => 1,
            'trf_sloc'      => 2,
            'source_sloc_no' => [],
            'trf_sloc_no'   => [],
        ];

        $this->repoMock->shouldReceive('getSlocPlant')
            ->twice()
            ->andReturn(1001);

        $this->repoMock->shouldReceive('findOrphanHeads')
            ->once()
            ->andReturn([]);

        $this->repoMock->shouldReceive('getLockStatus')
            ->once()
            ->andReturn(false);

        // Current stock is 50, but requested is 200 → insufficient
        $this->repoMock->shouldReceive('getTotalStockMaterial')
            ->once()
            ->with(1, 1, 1001)
            ->andReturn(50.0);

        $result = $this->service->executeTransfer('admin', $data, 1001);

        $this->assertEquals(4, $result['response']);
    }
}
