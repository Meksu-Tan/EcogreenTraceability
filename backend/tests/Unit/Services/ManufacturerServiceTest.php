<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\Manufacturer\Services\ManufacturerService;
use Modules\Manufacturer\Repositories\Contracts\ManufacturerRepositoryInterface;
use Mockery;

class ManufacturerServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_returns_all_manufacturers(): void
    {
        $repoMock = Mockery::mock(ManufacturerRepositoryInterface::class);
        $expected = [
            ['id' => 1, 'manufacturer_code' => 'MFG001', 'manufacturer_name' => 'Acme Corp'],
            ['id' => 2, 'manufacturer_code' => 'MFG002', 'manufacturer_name' => 'Beta Ltd'],
        ];

        $repoMock->shouldReceive('getAll')
            ->once()
            ->andReturn($expected);

        $service = new ManufacturerService($repoMock);
        $result = $service->listManufacturers();

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_active_manufacturers(): void
    {
        $repoMock = Mockery::mock(ManufacturerRepositoryInterface::class);
        $expected = [
            ['id' => 1, 'manufacturer_code' => 'MFG001', 'is_active' => 1],
        ];

        $repoMock->shouldReceive('getActive')
            ->once()
            ->andReturn($expected);

        $service = new ManufacturerService($repoMock);
        $result = $service->getActiveManufacturers();

        $this->assertSame($expected, $result);
    }

    public function test_it_stores_manufacturer_successfully(): void
    {
        $repoMock = Mockery::mock(ManufacturerRepositoryInterface::class);
        $data = ['manufacturer_code' => 'MFG003', 'manufacturer_name' => 'Gamma Inc'];

        $repoMock->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn(true);

        $service = new ManufacturerService($repoMock);
        $result = $service->storeManufacturer($data);

        $this->assertSame(['status' => 1, 'message' => 'Manufacturer created successfully'], $result);
    }

    public function test_it_returns_failure_when_manufacturer_code_already_exists_on_store(): void
    {
        $repoMock = Mockery::mock(ManufacturerRepositoryInterface::class);
        $data = ['manufacturer_code' => 'MFG001', 'manufacturer_name' => 'Duplicate'];

        $repoMock->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn(false);

        $service = new ManufacturerService($repoMock);
        $result = $service->storeManufacturer($data);

        $this->assertSame(['status' => 0, 'message' => 'Manufacturer code already exists'], $result);
    }

    public function test_it_updates_manufacturer_successfully(): void
    {
        $repoMock = Mockery::mock(ManufacturerRepositoryInterface::class);
        $data = ['manufacturer_name' => 'Updated Name'];

        $repoMock->shouldReceive('update')
            ->once()
            ->with(1, $data)
            ->andReturn(true);

        $service = new ManufacturerService($repoMock);
        $result = $service->updateManufacturer(1, $data);

        $this->assertSame(['status' => 1, 'message' => 'Manufacturer updated successfully'], $result);
    }

    public function test_it_returns_failure_when_update_manufacturer_fails(): void
    {
        $repoMock = Mockery::mock(ManufacturerRepositoryInterface::class);
        $data = ['manufacturer_name' => 'Updated Name'];

        $repoMock->shouldReceive('update')
            ->once()
            ->with(99, $data)
            ->andReturn(false);

        $service = new ManufacturerService($repoMock);
        $result = $service->updateManufacturer(99, $data);

        $this->assertSame(['status' => 0, 'message' => 'Failed to update manufacturer'], $result);
    }

    public function test_it_deactivates_manufacturer_successfully(): void
    {
        $repoMock = Mockery::mock(ManufacturerRepositoryInterface::class);

        $repoMock->shouldReceive('deactivate')
            ->once()
            ->with(1, 'admin')
            ->andReturn(true);

        $service = new ManufacturerService($repoMock);
        $result = $service->deactivateManufacturer(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Manufacturer deactivated'], $result);
    }

    public function test_it_returns_failure_when_deactivate_manufacturer_fails(): void
    {
        $repoMock = Mockery::mock(ManufacturerRepositoryInterface::class);

        $repoMock->shouldReceive('deactivate')
            ->once()
            ->with(99, 'admin')
            ->andReturn(false);

        $service = new ManufacturerService($repoMock);
        $result = $service->deactivateManufacturer(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to deactivate'], $result);
    }

    public function test_it_activates_manufacturer_successfully(): void
    {
        $repoMock = Mockery::mock(ManufacturerRepositoryInterface::class);

        $repoMock->shouldReceive('activate')
            ->once()
            ->with(1, 'admin')
            ->andReturn(true);

        $service = new ManufacturerService($repoMock);
        $result = $service->activateManufacturer(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Manufacturer activated'], $result);
    }

    public function test_it_returns_failure_when_activate_manufacturer_fails(): void
    {
        $repoMock = Mockery::mock(ManufacturerRepositoryInterface::class);

        $repoMock->shouldReceive('activate')
            ->once()
            ->with(99, 'admin')
            ->andReturn(false);

        $service = new ManufacturerService($repoMock);
        $result = $service->activateManufacturer(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to activate'], $result);
    }
}
