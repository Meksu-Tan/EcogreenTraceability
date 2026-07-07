<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Mockery;
use Modules\Material\Repositories\Contracts\MaterialRepositoryInterface;
use Modules\Material\Services\MaterialService;
use Tests\TestCase;

class MaterialServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ── Material CRUD ─────────────────────────────────────────────────────────

    public function test_it_returns_all_materials(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);
        $expected = [
            ['id' => 1, 'material_code' => 'MAT001', 'material_name' => 'Palm Oil'],
            ['id' => 2, 'material_code' => 'MAT002', 'material_name' => 'Oleic Acid'],
        ];

        $repoMock->shouldReceive('getAll')
            ->once()
            ->with(null)
            ->andReturn($expected);

        $service = new MaterialService($repoMock);
        $result = $service->listMaterials();

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_materials_filtered_by_type(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);
        $expected = [
            ['id' => 1, 'material_code' => 'MAT001', 'type' => 'RAW'],
        ];

        $repoMock->shouldReceive('getAll')
            ->once()
            ->with('RAW')
            ->andReturn($expected);

        $service = new MaterialService($repoMock);
        $result = $service->listMaterials('RAW');

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_empty_array_when_no_materials(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);

        $repoMock->shouldReceive('getAll')
            ->once()
            ->with(null)
            ->andReturn([]);

        $service = new MaterialService($repoMock);
        $result = $service->listMaterials();

        $this->assertSame([], $result);
    }

    public function test_it_stores_material_successfully(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);
        $data = ['material_code' => 'MAT003', 'material_name' => 'Stearic Acid'];

        $repoMock->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn(true);

        $service = new MaterialService($repoMock);
        $result = $service->storeMaterial($data);

        $this->assertSame(['status' => 1, 'message' => 'Material created successfully'], $result);
    }

    public function test_it_returns_failure_when_material_code_already_exists_on_store(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);
        $data = ['material_code' => 'MAT001', 'material_name' => 'Duplicate'];

        $repoMock->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn(false);

        $service = new MaterialService($repoMock);
        $result = $service->storeMaterial($data);

        $this->assertSame(['status' => 0, 'message' => 'Material code already exists'], $result);
    }

    public function test_it_updates_material_successfully(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);
        $data = ['material_name' => 'Updated Material'];

        $repoMock->shouldReceive('update')
            ->once()
            ->with(1, $data)
            ->andReturn(true);

        $service = new MaterialService($repoMock);
        $result = $service->updateMaterial(1, $data);

        $this->assertSame(['status' => 1, 'message' => 'Material updated successfully'], $result);
    }

    public function test_it_returns_failure_when_update_material_fails(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);
        $data = ['material_name' => 'Updated Material'];

        $repoMock->shouldReceive('update')
            ->once()
            ->with(99, $data)
            ->andReturn(false);

        $service = new MaterialService($repoMock);
        $result = $service->updateMaterial(99, $data);

        $this->assertSame(['status' => 0, 'message' => 'Failed to update material'], $result);
    }

    public function test_it_deactivates_material_successfully(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);

        $repoMock->shouldReceive('deactivate')
            ->once()
            ->with(1, 'admin')
            ->andReturn(true);

        $service = new MaterialService($repoMock);
        $result = $service->deactivateMaterial(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Material deactivated'], $result);
    }

    public function test_it_returns_failure_when_deactivate_material_fails(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);

        $repoMock->shouldReceive('deactivate')
            ->once()
            ->with(99, 'admin')
            ->andReturn(false);

        $service = new MaterialService($repoMock);
        $result = $service->deactivateMaterial(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to deactivate material'], $result);
    }

    public function test_it_activates_material_successfully(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);

        $repoMock->shouldReceive('activate')
            ->once()
            ->with(1, 'admin')
            ->andReturn(true);

        $service = new MaterialService($repoMock);
        $result = $service->activateMaterial(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Material activated'], $result);
    }

    public function test_it_returns_failure_when_activate_material_fails(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);

        $repoMock->shouldReceive('activate')
            ->once()
            ->with(99, 'admin')
            ->andReturn(false);

        $service = new MaterialService($repoMock);
        $result = $service->activateMaterial(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to activate material'], $result);
    }

    // ── Packaging CRUD ────────────────────────────────────────────────────────

    public function test_it_returns_all_packagings(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);
        $expected = [
            ['id' => 1, 'packaging_code' => 'PKG001', 'packaging_name' => 'Drum 200L'],
        ];

        $repoMock->shouldReceive('getAllPackagings')
            ->once()
            ->andReturn($expected);

        $service = new MaterialService($repoMock);
        $result = $service->listPackagings();

        $this->assertSame($expected, $result);
    }

    public function test_it_stores_packaging_successfully(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);
        $data = ['packaging_code' => 'PKG002', 'packaging_name' => 'IBC 1000L'];

        $repoMock->shouldReceive('createPackaging')
            ->once()
            ->with($data)
            ->andReturn(true);

        $service = new MaterialService($repoMock);
        $result = $service->storePackaging($data);

        $this->assertSame(['status' => 1, 'message' => 'Material packaging created successfully'], $result);
    }

    public function test_it_returns_failure_when_packaging_code_already_exists_on_store(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);
        $data = ['packaging_code' => 'PKG001', 'packaging_name' => 'Duplicate'];

        $repoMock->shouldReceive('createPackaging')
            ->once()
            ->with($data)
            ->andReturn(false);

        $service = new MaterialService($repoMock);
        $result = $service->storePackaging($data);

        $this->assertSame(['status' => 0, 'message' => 'Packaging code already exists'], $result);
    }

    public function test_it_updates_packaging_successfully(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);
        $data = ['packaging_name' => 'Updated Packaging'];

        $repoMock->shouldReceive('updatePackaging')
            ->once()
            ->with(1, $data)
            ->andReturn(true);

        $service = new MaterialService($repoMock);
        $result = $service->updatePackaging(1, $data);

        $this->assertSame(['status' => 1, 'message' => 'Packaging updated successfully'], $result);
    }

    public function test_it_returns_failure_when_update_packaging_fails(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);
        $data = ['packaging_name' => 'Updated Packaging'];

        $repoMock->shouldReceive('updatePackaging')
            ->once()
            ->with(99, $data)
            ->andReturn(false);

        $service = new MaterialService($repoMock);
        $result = $service->updatePackaging(99, $data);

        $this->assertSame(['status' => 0, 'message' => 'Failed to update packaging'], $result);
    }

    public function test_it_deactivates_packaging_successfully(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);

        $repoMock->shouldReceive('deactivatePackaging')
            ->once()
            ->with(1, 'admin')
            ->andReturn(true);

        $service = new MaterialService($repoMock);
        $result = $service->deactivatePackaging(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Packaging deactivated'], $result);
    }

    public function test_it_returns_failure_when_deactivate_packaging_fails(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);

        $repoMock->shouldReceive('deactivatePackaging')
            ->once()
            ->with(99, 'admin')
            ->andReturn(false);

        $service = new MaterialService($repoMock);
        $result = $service->deactivatePackaging(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to deactivate packaging'], $result);
    }

    public function test_it_activates_packaging_successfully(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);

        $repoMock->shouldReceive('activatePackaging')
            ->once()
            ->with(1, 'admin')
            ->andReturn(true);

        $service = new MaterialService($repoMock);
        $result = $service->activatePackaging(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Packaging activated'], $result);
    }

    public function test_it_returns_failure_when_activate_packaging_fails(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);

        $repoMock->shouldReceive('activatePackaging')
            ->once()
            ->with(99, 'admin')
            ->andReturn(false);

        $service = new MaterialService($repoMock);
        $result = $service->activatePackaging(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to activate packaging'], $result);
    }

    public function test_it_returns_active_source_products(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);
        $expected = [
            ['id' => 1, 'material_code' => 'MAT001', 'is_active' => 1],
        ];

        $repoMock->shouldReceive('getActiveSourceProducts')
            ->once()
            ->andReturn($expected);

        $service = new MaterialService($repoMock);
        $result = $service->getActiveSourceProducts();

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_empty_array_when_no_active_source_products(): void
    {
        $repoMock = Mockery::mock(MaterialRepositoryInterface::class);

        $repoMock->shouldReceive('getActiveSourceProducts')
            ->once()
            ->andReturn([]);

        $service = new MaterialService($repoMock);
        $result = $service->getActiveSourceProducts();

        $this->assertSame([], $result);
    }
}
