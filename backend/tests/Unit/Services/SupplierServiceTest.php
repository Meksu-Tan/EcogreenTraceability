<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Mockery;
use Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use Modules\Supplier\Services\SupplierService;
use Tests\TestCase;

class SupplierServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_returns_all_suppliers(): void
    {
        $repoMock = Mockery::mock(SupplierRepositoryInterface::class);
        $expected = [
            ['id' => 1, 'supplier_code' => 'SUP001', 'supplier_name' => 'Supply Co'],
            ['id' => 2, 'supplier_code' => 'SUP002', 'supplier_name' => 'Wholesale Ltd'],
        ];

        $repoMock->shouldReceive('getAll')
            ->once()
            ->andReturn($expected);

        $service = new SupplierService($repoMock);
        $result = $service->listSuppliers();

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_active_suppliers(): void
    {
        $repoMock = Mockery::mock(SupplierRepositoryInterface::class);
        $expected = [
            ['id' => 1, 'supplier_code' => 'SUP001', 'is_active' => 1],
        ];

        $repoMock->shouldReceive('getActive')
            ->once()
            ->andReturn($expected);

        $service = new SupplierService($repoMock);
        $result = $service->getActiveSuppliers();

        $this->assertSame($expected, $result);
    }

    public function test_it_stores_supplier_successfully(): void
    {
        $repoMock = Mockery::mock(SupplierRepositoryInterface::class);
        $data = ['supplier_code' => 'SUP003', 'supplier_name' => 'New Supplier'];

        $repoMock->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn(true);

        $service = new SupplierService($repoMock);
        $result = $service->storeSupplier($data);

        $this->assertSame(['status' => 1, 'message' => 'Supplier created successfully'], $result);
    }

    public function test_it_returns_failure_when_supplier_code_already_exists_on_store(): void
    {
        $repoMock = Mockery::mock(SupplierRepositoryInterface::class);
        $data = ['supplier_code' => 'SUP001', 'supplier_name' => 'Duplicate Supplier'];

        $repoMock->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn(false);

        $service = new SupplierService($repoMock);
        $result = $service->storeSupplier($data);

        $this->assertSame(['status' => 0, 'message' => 'Supplier code already exists'], $result);
    }

    public function test_it_updates_supplier_successfully(): void
    {
        $repoMock = Mockery::mock(SupplierRepositoryInterface::class);
        $data = ['supplier_name' => 'Updated Supplier Name'];

        $repoMock->shouldReceive('update')
            ->once()
            ->with(1, $data)
            ->andReturn(true);

        $service = new SupplierService($repoMock);
        $result = $service->updateSupplier(1, $data);

        $this->assertSame(['status' => 1, 'message' => 'Supplier updated successfully'], $result);
    }

    public function test_it_returns_failure_when_update_supplier_fails(): void
    {
        $repoMock = Mockery::mock(SupplierRepositoryInterface::class);
        $data = ['supplier_name' => 'Updated Supplier Name'];

        $repoMock->shouldReceive('update')
            ->once()
            ->with(99, $data)
            ->andReturn(false);

        $service = new SupplierService($repoMock);
        $result = $service->updateSupplier(99, $data);

        $this->assertSame(['status' => 0, 'message' => 'Failed to update supplier'], $result);
    }

    public function test_it_deactivates_supplier_successfully(): void
    {
        $repoMock = Mockery::mock(SupplierRepositoryInterface::class);

        $repoMock->shouldReceive('deactivate')
            ->once()
            ->with(1, 'admin')
            ->andReturn(true);

        $service = new SupplierService($repoMock);
        $result = $service->deactivateSupplier(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Supplier deactivated'], $result);
    }

    public function test_it_returns_failure_when_deactivate_supplier_fails(): void
    {
        $repoMock = Mockery::mock(SupplierRepositoryInterface::class);

        $repoMock->shouldReceive('deactivate')
            ->once()
            ->with(99, 'admin')
            ->andReturn(false);

        $service = new SupplierService($repoMock);
        $result = $service->deactivateSupplier(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to deactivate'], $result);
    }

    public function test_it_activates_supplier_successfully(): void
    {
        $repoMock = Mockery::mock(SupplierRepositoryInterface::class);

        $repoMock->shouldReceive('activate')
            ->once()
            ->with(1, 'admin')
            ->andReturn(true);

        $service = new SupplierService($repoMock);
        $result = $service->activateSupplier(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Supplier activated'], $result);
    }

    public function test_it_returns_failure_when_activate_supplier_fails(): void
    {
        $repoMock = Mockery::mock(SupplierRepositoryInterface::class);

        $repoMock->shouldReceive('activate')
            ->once()
            ->with(99, 'admin')
            ->andReturn(false);

        $service = new SupplierService($repoMock);
        $result = $service->activateSupplier(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to activate'], $result);
    }
}
