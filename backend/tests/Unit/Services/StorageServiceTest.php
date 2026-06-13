<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\Storage\Services\StorageService;
use Modules\Storage\Repositories\Contracts\StorageTankRepositoryInterface;
use Modules\Storage\Repositories\Contracts\StorageWarehouseRepositoryInterface;
use Mockery;

class StorageServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeService(
        ?StorageTankRepositoryInterface $tankRepo = null,
        ?StorageWarehouseRepositoryInterface $warehouseRepo = null
    ): StorageService {
        return new StorageService(
            $tankRepo ?? Mockery::mock(StorageTankRepositoryInterface::class),
            $warehouseRepo ?? Mockery::mock(StorageWarehouseRepositoryInterface::class)
        );
    }

    // ── Tank CRUD ─────────────────────────────────────────────────────────────

    public function test_it_returns_all_tanks(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);
        $expected = [
            ['id' => 1, 'tank_code' => 'TK001', 'tank_name' => 'Tank A'],
        ];

        $tankMock->shouldReceive('getAllTanks')->once()->andReturn($expected);

        $service = $this->makeService($tankMock);
        $result = $service->listTanks();

        $this->assertSame($expected, $result);
    }

    public function test_it_stores_tank_successfully(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);
        $data = ['tank_code' => 'TK002', 'tank_name' => 'Tank B'];

        $tankMock->shouldReceive('createTank')->once()->with($data)->andReturn(true);

        $service = $this->makeService($tankMock);
        $result = $service->storeTank($data);

        $this->assertSame(['status' => 1, 'message' => 'Storage tank created successfully'], $result);
    }

    public function test_it_returns_failure_when_tank_already_exists_on_store(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);
        $data = ['tank_code' => 'TK001', 'tank_name' => 'Duplicate'];

        $tankMock->shouldReceive('createTank')->once()->with($data)->andReturn(false);

        $service = $this->makeService($tankMock);
        $result = $service->storeTank($data);

        $this->assertSame(['status' => 0, 'message' => 'Storage tank already exists'], $result);
    }

    public function test_it_updates_tank_successfully(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);
        $data = ['tank_name' => 'Updated Tank'];

        $tankMock->shouldReceive('updateTank')->once()->with(1, $data)->andReturn(true);

        $service = $this->makeService($tankMock);
        $result = $service->updateTank(1, $data);

        $this->assertSame(['status' => 1, 'message' => 'Storage tank updated successfully'], $result);
    }

    public function test_it_returns_failure_when_update_tank_fails(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);
        $data = ['tank_name' => 'Updated Tank'];

        $tankMock->shouldReceive('updateTank')->once()->with(99, $data)->andReturn(false);

        $service = $this->makeService($tankMock);
        $result = $service->updateTank(99, $data);

        $this->assertSame(['status' => 0, 'message' => 'Failed to update storage tank'], $result);
    }

    public function test_it_deactivates_tank_successfully(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);

        $tankMock->shouldReceive('deactivateTank')->once()->with(1, 'admin')->andReturn(true);

        $service = $this->makeService($tankMock);
        $result = $service->deactivateTank(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Storage tank deactivated'], $result);
    }

    public function test_it_returns_failure_when_deactivate_tank_fails(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);

        $tankMock->shouldReceive('deactivateTank')->once()->with(99, 'admin')->andReturn(false);

        $service = $this->makeService($tankMock);
        $result = $service->deactivateTank(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to deactivate'], $result);
    }

    public function test_it_activates_tank_successfully(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);

        $tankMock->shouldReceive('activateTank')->once()->with(1, 'admin')->andReturn(true);

        $service = $this->makeService($tankMock);
        $result = $service->activateTank(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Storage tank activated'], $result);
    }

    public function test_it_returns_failure_when_activate_tank_fails(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);

        $tankMock->shouldReceive('activateTank')->once()->with(99, 'admin')->andReturn(false);

        $service = $this->makeService($tankMock);
        $result = $service->activateTank(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to activate'], $result);
    }

    // ── Detail CRUD ───────────────────────────────────────────────────────────

    public function test_it_returns_details_for_a_tank(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);
        $expected = [
            ['id' => 1, 'tf_number' => 'TF001', 'tank_id' => 1],
        ];

        $tankMock->shouldReceive('getDetailsByTank')->once()->with(1)->andReturn($expected);

        $service = $this->makeService($tankMock);
        $result = $service->listDetails(1);

        $this->assertSame($expected, $result);
    }

    public function test_it_stores_detail_successfully(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);
        $data = ['tf_number' => 'TF002', 'tank_id' => 1];

        $tankMock->shouldReceive('createDetail')->once()->with($data)->andReturn(true);

        $service = $this->makeService($tankMock);
        $result = $service->storeDetail($data);

        $this->assertSame(['status' => 1, 'message' => 'Storage detail created successfully'], $result);
    }

    public function test_it_returns_failure_when_tf_number_already_exists_on_store_detail(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);
        $data = ['tf_number' => 'TF001', 'tank_id' => 1];

        $tankMock->shouldReceive('createDetail')->once()->with($data)->andReturn(false);

        $service = $this->makeService($tankMock);
        $result = $service->storeDetail($data);

        $this->assertSame(['status' => 0, 'message' => 'TF Number already exists'], $result);
    }

    public function test_it_updates_detail_successfully(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);
        $data = ['capacity' => 5000];

        $tankMock->shouldReceive('updateDetail')->once()->with(1, $data)->andReturn(true);

        $service = $this->makeService($tankMock);
        $result = $service->updateDetail(1, $data);

        $this->assertSame(['status' => 1, 'message' => 'Storage detail updated successfully'], $result);
    }

    public function test_it_returns_failure_when_update_detail_fails(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);
        $data = ['capacity' => 5000];

        $tankMock->shouldReceive('updateDetail')->once()->with(99, $data)->andReturn(false);

        $service = $this->makeService($tankMock);
        $result = $service->updateDetail(99, $data);

        $this->assertSame(['status' => 0, 'message' => 'TF Number already exists or record not found'], $result);
    }

    public function test_it_deactivates_detail_successfully(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);

        $tankMock->shouldReceive('deactivateDetail')->once()->with(1, 'admin')->andReturn(true);

        $service = $this->makeService($tankMock);
        $result = $service->deactivateDetail(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Storage detail deactivated'], $result);
    }

    public function test_it_returns_failure_when_deactivate_detail_fails(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);

        $tankMock->shouldReceive('deactivateDetail')->once()->with(99, 'admin')->andReturn(false);

        $service = $this->makeService($tankMock);
        $result = $service->deactivateDetail(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to deactivate'], $result);
    }

    public function test_it_activates_detail_successfully(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);

        $tankMock->shouldReceive('activateDetail')->once()->with(1, 'admin')->andReturn(true);

        $service = $this->makeService($tankMock);
        $result = $service->activateDetail(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Storage detail activated'], $result);
    }

    public function test_it_returns_failure_when_activate_detail_fails(): void
    {
        $tankMock = Mockery::mock(StorageTankRepositoryInterface::class);

        $tankMock->shouldReceive('activateDetail')->once()->with(99, 'admin')->andReturn(false);

        $service = $this->makeService($tankMock);
        $result = $service->activateDetail(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to activate'], $result);
    }

    // ── Warehouse CRUD ────────────────────────────────────────────────────────

    public function test_it_returns_all_warehouses(): void
    {
        $warehouseMock = Mockery::mock(StorageWarehouseRepositoryInterface::class);
        $expected = [
            ['id' => 1, 'warehouse_code' => 'WH001', 'warehouse_name' => 'Main Warehouse'],
        ];

        $warehouseMock->shouldReceive('getAllWarehouses')->once()->andReturn($expected);

        $service = $this->makeService(null, $warehouseMock);
        $result = $service->listWarehouses();

        $this->assertSame($expected, $result);
    }

    public function test_it_stores_warehouse_successfully(): void
    {
        $warehouseMock = Mockery::mock(StorageWarehouseRepositoryInterface::class);
        $data = ['warehouse_code' => 'WH002', 'warehouse_name' => 'Secondary Warehouse'];

        $warehouseMock->shouldReceive('createWarehouse')->once()->with($data)->andReturn(true);

        $service = $this->makeService(null, $warehouseMock);
        $result = $service->storeWarehouse($data);

        $this->assertSame(['status' => 1, 'message' => 'Warehouse created successfully'], $result);
    }

    public function test_it_returns_failure_when_warehouse_already_exists_on_store(): void
    {
        $warehouseMock = Mockery::mock(StorageWarehouseRepositoryInterface::class);
        $data = ['warehouse_code' => 'WH001', 'warehouse_name' => 'Duplicate'];

        $warehouseMock->shouldReceive('createWarehouse')->once()->with($data)->andReturn(false);

        $service = $this->makeService(null, $warehouseMock);
        $result = $service->storeWarehouse($data);

        $this->assertSame(['status' => 0, 'message' => 'Warehouse already exists'], $result);
    }

    public function test_it_updates_warehouse_successfully(): void
    {
        $warehouseMock = Mockery::mock(StorageWarehouseRepositoryInterface::class);
        $data = ['warehouse_name' => 'Updated Warehouse'];

        $warehouseMock->shouldReceive('updateWarehouse')->once()->with(1, $data)->andReturn(true);

        $service = $this->makeService(null, $warehouseMock);
        $result = $service->updateWarehouse(1, $data);

        $this->assertSame(['status' => 1, 'message' => 'Warehouse updated successfully'], $result);
    }

    public function test_it_returns_failure_when_update_warehouse_fails(): void
    {
        $warehouseMock = Mockery::mock(StorageWarehouseRepositoryInterface::class);
        $data = ['warehouse_name' => 'Updated Warehouse'];

        $warehouseMock->shouldReceive('updateWarehouse')->once()->with(99, $data)->andReturn(false);

        $service = $this->makeService(null, $warehouseMock);
        $result = $service->updateWarehouse(99, $data);

        $this->assertSame(['status' => 0, 'message' => 'Failed to update warehouse'], $result);
    }

    public function test_it_deactivates_warehouse_successfully(): void
    {
        $warehouseMock = Mockery::mock(StorageWarehouseRepositoryInterface::class);

        $warehouseMock->shouldReceive('deactivateWarehouse')->once()->with(1, 'admin')->andReturn(true);

        $service = $this->makeService(null, $warehouseMock);
        $result = $service->deactivateWarehouse(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Warehouse deactivated'], $result);
    }

    public function test_it_returns_failure_when_deactivate_warehouse_fails(): void
    {
        $warehouseMock = Mockery::mock(StorageWarehouseRepositoryInterface::class);

        $warehouseMock->shouldReceive('deactivateWarehouse')->once()->with(99, 'admin')->andReturn(false);

        $service = $this->makeService(null, $warehouseMock);
        $result = $service->deactivateWarehouse(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to deactivate'], $result);
    }

    public function test_it_activates_warehouse_successfully(): void
    {
        $warehouseMock = Mockery::mock(StorageWarehouseRepositoryInterface::class);

        $warehouseMock->shouldReceive('activateWarehouse')->once()->with(1, 'admin')->andReturn(true);

        $service = $this->makeService(null, $warehouseMock);
        $result = $service->activateWarehouse(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Warehouse activated'], $result);
    }

    public function test_it_returns_failure_when_activate_warehouse_fails(): void
    {
        $warehouseMock = Mockery::mock(StorageWarehouseRepositoryInterface::class);

        $warehouseMock->shouldReceive('activateWarehouse')->once()->with(99, 'admin')->andReturn(false);

        $service = $this->makeService(null, $warehouseMock);
        $result = $service->activateWarehouse(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to activate'], $result);
    }
}
