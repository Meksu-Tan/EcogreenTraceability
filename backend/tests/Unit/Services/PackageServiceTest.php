<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;
use Illuminate\Support\Collection;
use Modules\TsPackage\Services\PackageService;
use Modules\TsPackage\Repositories\Contracts\PackageRepositoryInterface;

class PackageServiceTest extends TestCase
{
    protected PackageRepositoryInterface|MockInterface $repoMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoMock = Mockery::mock(PackageRepositoryInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeService(): PackageService
    {
        return new PackageService($this->repoMock);
    }

    // ——— getDtPckEntry ———

    public function test_it_delegates_get_dt_pck_entry_to_repository(): void
    {
        $expected = collect([
            (object)['id_pck' => 1, 'entry_no' => 'PCK-001'],
            (object)['id_pck' => 2, 'entry_no' => 'PCK-002'],
        ]);

        $this->repoMock
            ->shouldReceive('getDtPckEntry')
            ->once()
            ->andReturn($expected);

        $result = $this->makeService()->getDtPckEntry();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals($expected, $result);
    }

    public function test_it_returns_empty_collection_from_get_dt_pck_entry_when_no_records(): void
    {
        $this->repoMock
            ->shouldReceive('getDtPckEntry')
            ->once()
            ->andReturn(collect());

        $result = $this->makeService()->getDtPckEntry();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    // ——— getActiveFgProduct ———

    public function test_it_delegates_get_active_fg_product_to_repository(): void
    {
        $expected = collect([
            (object)['id_material' => 10, 'description' => 'PKO'],
        ]);

        $this->repoMock
            ->shouldReceive('getActiveFgProduct')
            ->once()
            ->andReturn($expected);

        $result = $this->makeService()->getActiveFgProduct();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals($expected, $result);
    }

    // ——— getWipMaterialByFgProduct ———

    public function test_it_delegates_get_wip_material_by_fg_product_to_repository(): void
    {
        $data     = ['id_fg_material' => 10];
        $expected = collect([(object)['id_material' => 5, 'description' => 'RBDPO']]);

        $this->repoMock
            ->shouldReceive('getWipMaterialByFgProduct')
            ->once()
            ->with($data)
            ->andReturn($expected);

        $result = $this->makeService()->getWipMaterialByFgProduct($data);

        $this->assertEquals($expected, $result);
    }

    public function test_it_returns_empty_collection_from_get_wip_material_when_no_match(): void
    {
        $data = ['id_fg_material' => 999];

        $this->repoMock
            ->shouldReceive('getWipMaterialByFgProduct')
            ->once()
            ->with($data)
            ->andReturn(collect());

        $result = $this->makeService()->getWipMaterialByFgProduct($data);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    // ——— getCmbActiveTankPck ———

    public function test_it_delegates_get_cmb_active_tank_pck_to_repository(): void
    {
        $data     = ['id_material' => 5];
        $expected = collect([(object)['id_tank' => 3, 'tank_code' => 'T-03']]);

        $this->repoMock
            ->shouldReceive('getCmbActiveTankPck')
            ->once()
            ->with($data)
            ->andReturn($expected);

        $result = $this->makeService()->getCmbActiveTankPck($data);

        $this->assertEquals($expected, $result);
    }

    // ——— getCmbActiveWarehousePck ———

    public function test_it_delegates_get_cmb_active_warehouse_pck_to_repository(): void
    {
        $data     = ['id_material' => 5];
        $expected = collect([(object)['id_warehouse' => 7, 'warehouse_code' => 'WH-07']]);

        $this->repoMock
            ->shouldReceive('getCmbActiveWarehousePck')
            ->once()
            ->with($data)
            ->andReturn($expected);

        $result = $this->makeService()->getCmbActiveWarehousePck($data);

        $this->assertEquals($expected, $result);
    }

    // ——— getCmbActiveSpecificTank ———

    public function test_it_delegates_get_cmb_active_specific_tank_to_repository(): void
    {
        $data     = ['id_material' => 5, 'sloc' => 'SL01'];
        $expected = collect([(object)['id_tank' => 8, 'sloc' => 'SL01']]);

        $this->repoMock
            ->shouldReceive('getCmbActiveSpecificTank')
            ->once()
            ->with($data)
            ->andReturn($expected);

        $result = $this->makeService()->getCmbActiveSpecificTank($data);

        $this->assertEquals($expected, $result);
    }

    // ——— store ———

    public function test_it_delegates_store_to_repository_and_returns_result(): void
    {
        $data     = ['id_material' => 5, 'pck_qty' => 200.0];
        $expected = ['response' => 1, 'message' => 'Package stored'];

        $this->repoMock
            ->shouldReceive('store')
            ->once()
            ->with('Admin', $data)
            ->andReturn($expected);

        $result = $this->makeService()->store('Admin', $data);

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_failure_from_store_when_repository_fails(): void
    {
        $data     = ['id_material' => 999];
        $expected = ['response' => 0, 'message' => 'Store failed'];

        $this->repoMock
            ->shouldReceive('store')
            ->once()
            ->with('Admin', $data)
            ->andReturn($expected);

        $result = $this->makeService()->store('Admin', $data);

        $this->assertSame($expected, $result);
    }

    // ——— cancel ———

    public function test_it_delegates_cancel_to_repository(): void
    {
        $data     = ['id_pck' => 10, 'cancel_reason' => 'Wrong entry'];
        $expected = ['response' => 1, 'message' => 'Package cancelled'];

        $this->repoMock
            ->shouldReceive('cancel')
            ->once()
            ->with('Admin', $data)
            ->andReturn($expected);

        $result = $this->makeService()->cancel('Admin', $data);

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_failure_from_cancel_when_not_found(): void
    {
        $data     = ['id_pck' => 999];
        $expected = ['response' => 0, 'message' => 'Record not found'];

        $this->repoMock
            ->shouldReceive('cancel')
            ->once()
            ->with('Admin', $data)
            ->andReturn($expected);

        $result = $this->makeService()->cancel('Admin', $data);

        $this->assertSame($expected, $result);
    }

    // ——— updatePo ———

    public function test_it_delegates_update_po_to_repository(): void
    {
        $data     = ['id_pck' => 5, 'po_number' => 'PO-2026-001'];
        $expected = ['response' => 1, 'message' => 'PO updated'];

        $this->repoMock
            ->shouldReceive('updatePo')
            ->once()
            ->with('Admin', $data)
            ->andReturn($expected);

        $result = $this->makeService()->updatePo('Admin', $data);

        $this->assertSame($expected, $result);
    }

    // ——— updateBatch ———

    public function test_it_delegates_update_batch_to_repository(): void
    {
        $data     = ['id_pck' => 5, 'batch_sap' => 'BATCH-2026-001'];
        $expected = ['response' => 1, 'message' => 'Batch updated'];

        $this->repoMock
            ->shouldReceive('updateBatch')
            ->once()
            ->with('Admin', $data)
            ->andReturn($expected);

        $result = $this->makeService()->updateBatch('Admin', $data);

        $this->assertSame($expected, $result);
    }

    // ——— updateSubTank ———

    public function test_it_delegates_update_sub_tank_to_repository(): void
    {
        $data     = ['id_pck' => 5, 'id_sub_tank' => 12];
        $expected = ['response' => 1, 'message' => 'Sub tank updated'];

        $this->repoMock
            ->shouldReceive('updateSubTank')
            ->once()
            ->with('Admin', $data)
            ->andReturn($expected);

        $result = $this->makeService()->updateSubTank('Admin', $data);

        $this->assertSame($expected, $result);
    }

    // ——— generateTraceNo ———

    public function test_it_delegates_generate_trace_no_to_repository(): void
    {
        $expected = 'TRC-2026-00001';

        $this->repoMock
            ->shouldReceive('generateTraceNo')
            ->once()
            ->with(7, 3)
            ->andReturn($expected);

        $result = $this->makeService()->generateTraceNo(7, 3);

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_empty_string_from_generate_trace_no_when_none_available(): void
    {
        $this->repoMock
            ->shouldReceive('generateTraceNo')
            ->once()
            ->with(7, 3)
            ->andReturn('');

        $result = $this->makeService()->generateTraceNo(7, 3);

        $this->assertSame('', $result);
    }

    // ——— getAllWarehouses ———

    public function test_it_delegates_get_all_warehouses_to_repository(): void
    {
        $expected = collect([
            (object)['id_warehouse' => 1, 'warehouse_code' => 'WH-01'],
            (object)['id_warehouse' => 2, 'warehouse_code' => 'WH-02'],
        ]);

        $this->repoMock
            ->shouldReceive('getAllWarehouses')
            ->once()
            ->andReturn($expected);

        $result = $this->makeService()->getAllWarehouses();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals($expected, $result);
    }

    public function test_it_returns_empty_collection_from_get_all_warehouses_when_none_exist(): void
    {
        $this->repoMock
            ->shouldReceive('getAllWarehouses')
            ->once()
            ->andReturn(collect());

        $result = $this->makeService()->getAllWarehouses();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }
}
