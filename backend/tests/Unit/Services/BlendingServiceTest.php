<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\TsBlending\Services\BlendingService;
use Modules\TsBlending\Repositories\Contracts\BlendingRepositoryInterface;
use Illuminate\Support\Collection;
use Mockery;

class BlendingServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // getActiveMaterials
    // -------------------------------------------------------------------------

    public function test_it_delegates_get_active_materials_to_repository(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);
        $expected = collect([(object)['id_material' => 1, 'description' => 'CPO']]);

        $repoMock->shouldReceive('getActiveMaterials')
            ->once()
            ->andReturn($expected);

        $service = new BlendingService($repoMock);
        $result = $service->getActiveMaterials();

        $this->assertEquals($expected, $result);
    }

    // -------------------------------------------------------------------------
    // generateEntryNo — plantId 0 stays 0 (resolvePlantCode falsy short-circuit)
    // -------------------------------------------------------------------------

    public function test_it_generates_entry_no_via_repository(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);
        $expected = '82605240010101';

        $repoMock->shouldReceive('generateBlendingEntryNo')
            ->once()
            ->with(3, 0)
            ->andReturn($expected);

        $service = new BlendingService($repoMock);
        $result = $service->generateEntryNo(3, 0);

        $this->assertEquals($expected, $result);
    }

    public function test_it_returns_null_when_no_entry_no_available(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);

        $repoMock->shouldReceive('generateBlendingEntryNo')
            ->once()
            ->andReturn(null);

        $service = new BlendingService($repoMock);
        $result = $service->generateEntryNo(3, 0);

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // getTotalStockMaterial
    // -------------------------------------------------------------------------

    public function test_it_returns_total_stock_material(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);

        $repoMock->shouldReceive('getTotalStockMaterial')
            ->once()
            ->with(3, 0)
            ->andReturn(150.5);

        $service = new BlendingService($repoMock);
        $result = $service->getTotalStockMaterial(3, 0);

        $this->assertEquals(150.5, $result);
    }

    public function test_it_returns_zero_when_no_stock(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);

        $repoMock->shouldReceive('getTotalStockMaterial')
            ->once()
            ->andReturn(0.0);

        $service = new BlendingService($repoMock);
        $result = $service->getTotalStockMaterial(3, 0);

        $this->assertEquals(0.0, $result);
    }

    // -------------------------------------------------------------------------
    // getBlendingList
    // -------------------------------------------------------------------------

    public function test_it_returns_blending_list(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);
        $expected = ['data' => collect([]), 'total' => 0];

        $repoMock->shouldReceive('getBlendingList')
            ->once()
            ->with(0, 1, 5)
            ->andReturn($expected);

        $service = new BlendingService($repoMock);
        $result = $service->getBlendingList(0);

        $this->assertEquals($expected, $result);
    }

    // -------------------------------------------------------------------------
    // addMaterialToBlending — ADD mode, duplicate → response 2
    // -------------------------------------------------------------------------

    public function test_it_returns_response_2_when_duplicate_material_in_add_mode(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);

        $repoMock->shouldReceive('checkMaterialInTemporary')
            ->once()
            ->with(3, '82605240010101', 0)
            ->andReturn(true);

        $service = new BlendingService($repoMock);
        $result = $service->addMaterialToBlending('admin', [
            'entryNo'          => '82605240010101',
            'idMaterialSource' => 3,
            'qty'              => '100',
            'idSloc'           => 5,
            'mode'             => 'ADD',
        ], 0);

        $this->assertEquals(['response' => 2], $result);
    }

    public function test_it_adds_material_in_add_mode_when_not_duplicate(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);
        $expected = ['response' => 1];

        $repoMock->shouldReceive('checkMaterialInTemporary')
            ->once()
            ->andReturn(false);

        $repoMock->shouldReceive('addBlendingEntryMaterial')
            ->once()
            ->with('admin', '82605240010101', 3, 100.0, 5, 0)
            ->andReturn($expected);

        $service = new BlendingService($repoMock);
        $result = $service->addMaterialToBlending('admin', [
            'entryNo'          => '82605240010101',
            'idMaterialSource' => 3,
            'qty'              => '100',
            'idSloc'           => 5,
            'mode'             => 'ADD',
        ], 0);

        $this->assertEquals($expected, $result);
    }

    public function test_it_skips_duplicate_check_in_edit_mode(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);
        $expected = ['response' => 1];

        // checkMaterialInTemporary should NOT be called in EDIT mode
        $repoMock->shouldNotReceive('checkMaterialInTemporary');

        $repoMock->shouldReceive('addBlendingEntryMaterial')
            ->once()
            ->andReturn($expected);

        $service = new BlendingService($repoMock);
        $result = $service->addMaterialToBlending('admin', [
            'entryNo'          => '82605240010101',
            'idMaterialSource' => 3,
            'qty'              => '50.5',
            'idSloc'           => 5,
            'mode'             => 'EDIT',
        ], 0);

        $this->assertEquals($expected, $result);
    }

    public function test_it_strips_commas_from_qty_when_adding_material(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);

        $repoMock->shouldReceive('checkMaterialInTemporary')
            ->once()
            ->andReturn(false);

        $repoMock->shouldReceive('addBlendingEntryMaterial')
            ->once()
            ->with('admin', '82605240010101', 3, 1500.25, 5, 0)
            ->andReturn(['response' => 1]);

        $service = new BlendingService($repoMock);
        $result = $service->addMaterialToBlending('admin', [
            'entryNo'          => '82605240010101',
            'idMaterialSource' => 3,
            'qty'              => '1,500.25',
            'idSloc'           => 5,
            'mode'             => 'ADD',
        ], 0);

        $this->assertEquals(['response' => 1], $result);
    }

    // -------------------------------------------------------------------------
    // deleteBlendingMaterial
    // -------------------------------------------------------------------------

    public function test_it_deletes_blending_material(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);

        $repoMock->shouldReceive('deleteBlendingMaterial')
            ->once()
            ->with(42)
            ->andReturn(true);

        $service = new BlendingService($repoMock);
        $result = $service->deleteBlendingMaterial(42);

        $this->assertTrue($result);
    }

    public function test_it_returns_false_when_delete_fails(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);

        $repoMock->shouldReceive('deleteBlendingMaterial')
            ->once()
            ->andReturn(false);

        $service = new BlendingService($repoMock);
        $result = $service->deleteBlendingMaterial(999);

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // executeBlending — period locked → response 99
    // -------------------------------------------------------------------------

    public function test_it_returns_response_99_when_period_is_locked(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);

        $repoMock->shouldReceive('getLockStatus')
            ->once()
            ->with('2026-06-12')
            ->andReturn(true);

        $service = new BlendingService($repoMock);
        $result = $service->executeBlending('admin', [
            'entry_no'    => '82605240010101',
            'entry_date'  => '2026-06-12',
            'id_material' => 3,
            'material_doc' => '',
            'qty'         => '100',
            'tankNo'      => [],
        ], 0);

        $this->assertEquals(['response' => 99], $result);
    }

    // -------------------------------------------------------------------------
    // executeBlending — item count zero → response 4
    // -------------------------------------------------------------------------

    public function test_it_returns_response_4_when_no_materials_in_temporary(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);

        $repoMock->shouldReceive('getLockStatus')
            ->once()
            ->andReturn(false);

        $repoMock->shouldReceive('getTemporaryItemCount')
            ->once()
            ->with('82605240010101')
            ->andReturn(0);

        $service = new BlendingService($repoMock);
        $result = $service->executeBlending('admin', [
            'entry_no'    => '82605240010101',
            'entry_date'  => '2026-06-12',
            'id_material' => 3,
            'material_doc' => '',
            'qty'         => '100',
            'tankNo'      => [],
        ], 0);

        $this->assertEquals(['response' => 4], $result);
    }

    // -------------------------------------------------------------------------
    // executeBlending — temporary entries empty → response 4
    // -------------------------------------------------------------------------

    public function test_it_returns_response_4_when_temporary_entries_empty(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);

        $repoMock->shouldReceive('getLockStatus')
            ->once()
            ->andReturn(false);

        $repoMock->shouldReceive('getTemporaryItemCount')
            ->once()
            ->andReturn(1);

        $repoMock->shouldReceive('getTemporaryEntries')
            ->once()
            ->with('82605240010101')
            ->andReturn(collect([]));

        $service = new BlendingService($repoMock);
        $result = $service->executeBlending('admin', [
            'entry_no'    => '82605240010101',
            'entry_date'  => '2026-06-12',
            'id_material' => 3,
            'material_doc' => '',
            'qty'         => '100',
            'tankNo'      => [],
        ], 0);

        $this->assertEquals(['response' => 4], $result);
    }

    // -------------------------------------------------------------------------
    // deactivateBlending
    // -------------------------------------------------------------------------

    public function test_it_deactivates_blending(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);
        $expected = ['response' => 1];

        $repoMock->shouldReceive('deactivateBlending')
            ->once()
            ->with('82605240010101', 'admin')
            ->andReturn($expected);

        $service = new BlendingService($repoMock);
        $result = $service->deactivateBlending('82605240010101', 'admin');

        $this->assertEquals($expected, $result);
    }

    public function test_it_returns_failure_response_when_deactivate_fails(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);
        $expected = ['response' => 0];

        $repoMock->shouldReceive('deactivateBlending')
            ->once()
            ->andReturn($expected);

        $service = new BlendingService($repoMock);
        $result = $service->deactivateBlending('nonexistent', 'admin');

        $this->assertEquals($expected, $result);
    }

    // -------------------------------------------------------------------------
    // getTanks — null plantId passthrough
    // -------------------------------------------------------------------------

    public function test_it_gets_tanks_without_plant_filter(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);
        $expected = collect([(object)['id_sloc' => 1, 'description' => 'T-01']]);

        $repoMock->shouldReceive('getTanks')
            ->once()
            ->with(null)
            ->andReturn($expected);

        $service = new BlendingService($repoMock);
        $result = $service->getTanks(null);

        $this->assertEquals($expected, $result);
    }

    // -------------------------------------------------------------------------
    // createMaterialDocument
    // -------------------------------------------------------------------------

    public function test_it_creates_material_document(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);
        $expected = ['response' => 1];

        $repoMock->shouldReceive('createMaterialDocument')
            ->once()
            ->with('admin', 99, 'DOC-001', 'ADD')
            ->andReturn($expected);

        $service = new BlendingService($repoMock);
        $result = $service->createMaterialDocument('admin', 99, 'DOC-001', 'ADD');

        $this->assertEquals($expected, $result);
    }

    // -------------------------------------------------------------------------
    // updateEntrySubTank
    // -------------------------------------------------------------------------

    public function test_it_updates_entry_sub_tank(): void
    {
        $repoMock = Mockery::mock(BlendingRepositoryInterface::class);
        $expected = ['response' => 1];
        $tails = [101, 102];

        $repoMock->shouldReceive('updateEntrySubTank')
            ->once()
            ->with('admin', 7, $tails)
            ->andReturn($expected);

        $service = new BlendingService($repoMock);
        $result = $service->updateEntrySubTank('admin', 7, $tails);

        $this->assertEquals($expected, $result);
    }
}
