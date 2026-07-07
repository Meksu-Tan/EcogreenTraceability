<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Mockery;
use Modules\Plant\Repositories\Contracts\PlantRepositoryInterface;
use Modules\Plant\Services\PlantService;
use Tests\TestCase;

class PlantServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_returns_all_plants(): void
    {
        $repoMock = Mockery::mock(PlantRepositoryInterface::class);
        $expected = [
            ['id_plant' => 1, 'plant_code' => 'PLT01', 'plant_name' => 'Plant A'],
            ['id_plant' => 2, 'plant_code' => 'PLT02', 'plant_name' => 'Plant B'],
        ];

        $repoMock->shouldReceive('getAll')
            ->once()
            ->andReturn($expected);

        $service = new PlantService($repoMock);
        $result = $service->listPlants();

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_empty_array_when_no_plants(): void
    {
        $repoMock = Mockery::mock(PlantRepositoryInterface::class);

        $repoMock->shouldReceive('getAll')
            ->once()
            ->andReturn([]);

        $service = new PlantService($repoMock);
        $result = $service->listPlants();

        $this->assertSame([], $result);
    }

    public function test_it_stores_plant_successfully(): void
    {
        $repoMock = Mockery::mock(PlantRepositoryInterface::class);
        $data = ['plant_code' => 'PLT03', 'plant_name' => 'Plant C'];

        $repoMock->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn(5);

        $service = new PlantService($repoMock);
        $result = $service->storePlant($data);

        $this->assertSame(
            ['status' => 1, 'message' => 'Plant created successfully', 'data' => ['id_plant' => 5]],
            $result
        );
    }

    public function test_it_returns_failure_when_plant_code_already_exists_on_store(): void
    {
        $repoMock = Mockery::mock(PlantRepositoryInterface::class);
        $data = ['plant_code' => 'PLT01', 'plant_name' => 'Duplicate'];

        $repoMock->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn(false);

        $service = new PlantService($repoMock);
        $result = $service->storePlant($data);

        $this->assertSame(['status' => 0, 'message' => 'Plant code already exists'], $result);
    }

    public function test_it_updates_plant_successfully(): void
    {
        $repoMock = Mockery::mock(PlantRepositoryInterface::class);
        $data = ['plant_name' => 'Updated Plant'];

        $repoMock->shouldReceive('update')
            ->once()
            ->with(1, $data)
            ->andReturn(true);

        $service = new PlantService($repoMock);
        $result = $service->updatePlant(1, $data);

        $this->assertSame(['status' => 1, 'message' => 'Plant updated successfully'], $result);
    }

    public function test_it_returns_failure_when_update_plant_fails(): void
    {
        $repoMock = Mockery::mock(PlantRepositoryInterface::class);
        $data = ['plant_name' => 'Updated Plant'];

        $repoMock->shouldReceive('update')
            ->once()
            ->with(99, $data)
            ->andReturn(false);

        $service = new PlantService($repoMock);
        $result = $service->updatePlant(99, $data);

        $this->assertSame(['status' => 0, 'message' => 'Failed to update plant'], $result);
    }

    public function test_it_deactivates_plant_successfully(): void
    {
        $repoMock = Mockery::mock(PlantRepositoryInterface::class);

        $repoMock->shouldReceive('deactivate')
            ->once()
            ->with(1, 'admin')
            ->andReturn(true);

        $service = new PlantService($repoMock);
        $result = $service->deactivatePlant(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Plant deactivated'], $result);
    }

    public function test_it_returns_failure_when_deactivate_plant_fails(): void
    {
        $repoMock = Mockery::mock(PlantRepositoryInterface::class);

        $repoMock->shouldReceive('deactivate')
            ->once()
            ->with(99, 'admin')
            ->andReturn(false);

        $service = new PlantService($repoMock);
        $result = $service->deactivatePlant(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to deactivate plant'], $result);
    }

    public function test_it_activates_plant_successfully(): void
    {
        $repoMock = Mockery::mock(PlantRepositoryInterface::class);

        $repoMock->shouldReceive('activate')
            ->once()
            ->with(1, 'admin')
            ->andReturn(true);

        $service = new PlantService($repoMock);
        $result = $service->activatePlant(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Plant activated'], $result);
    }

    public function test_it_returns_failure_when_activate_plant_fails(): void
    {
        $repoMock = Mockery::mock(PlantRepositoryInterface::class);

        $repoMock->shouldReceive('activate')
            ->once()
            ->with(99, 'admin')
            ->andReturn(false);

        $service = new PlantService($repoMock);
        $result = $service->activatePlant(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to activate plant'], $result);
    }
}
