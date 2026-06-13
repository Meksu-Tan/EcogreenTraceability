<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\Tank\Services\TankService;
use Modules\Tank\Repositories\Contracts\TankRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Mockery;

class TankServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_returns_all_tanks(): void
    {
        $repoMock = Mockery::mock(TankRepositoryInterface::class);
        $expected = [
            ['id' => 1, 'tank_number' => 'T001', 'plant_code' => '1007'],
            ['id' => 2, 'tank_number' => 'T002', 'plant_code' => '1007'],
        ];

        $repoMock->shouldReceive('getAll')
            ->once()
            ->andReturn($expected);

        $service = new TankService($repoMock);
        $result = $service->listTanks();

        $this->assertSame($expected, $result);
    }

    public function test_it_stores_tank_successfully(): void
    {
        $repoMock = Mockery::mock(TankRepositoryInterface::class);
        $data = ['tank_number' => 'T003', 'plant_code' => '1007', 'tank_height' => 10.5];

        $repoMock->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn(3);

        $service = new TankService($repoMock);
        $result = $service->storeTank($data);

        $this->assertSame(1, $result['status']);
        $this->assertSame('Tank created successfully', $result['message']);
        $this->assertSame(['id' => 3], $result['data']);
    }

    public function test_it_returns_failure_when_tank_already_exists_on_store(): void
    {
        $repoMock = Mockery::mock(TankRepositoryInterface::class);
        $data = ['tank_number' => 'T001', 'plant_code' => '1007'];

        $repoMock->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn(false);

        $service = new TankService($repoMock);
        $result = $service->storeTank($data);

        $this->assertSame(['status' => 0, 'message' => 'Tank already exists for this plant'], $result);
    }

    public function test_it_updates_tank_successfully(): void
    {
        $repoMock = Mockery::mock(TankRepositoryInterface::class);
        $data = ['tank_height' => 12.0];

        $repoMock->shouldReceive('update')
            ->once()
            ->with(1, $data)
            ->andReturn(true);

        $service = new TankService($repoMock);
        $result = $service->updateTank(1, $data);

        $this->assertSame(['status' => 1, 'message' => 'Tank updated successfully'], $result);
    }

    public function test_it_returns_failure_when_update_tank_fails(): void
    {
        $repoMock = Mockery::mock(TankRepositoryInterface::class);
        $data = ['tank_height' => 12.0];

        $repoMock->shouldReceive('update')
            ->once()
            ->with(99, $data)
            ->andReturn(false);

        $service = new TankService($repoMock);
        $result = $service->updateTank(99, $data);

        $this->assertSame(['status' => 0, 'message' => 'Failed to update tank'], $result);
    }

    public function test_it_deactivates_tank_successfully(): void
    {
        $repoMock = Mockery::mock(TankRepositoryInterface::class);

        $repoMock->shouldReceive('deactivate')
            ->once()
            ->with(1, 'admin')
            ->andReturn(true);

        $service = new TankService($repoMock);
        $result = $service->deactivateTank(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Tank deactivated'], $result);
    }

    public function test_it_returns_failure_when_deactivate_tank_fails(): void
    {
        $repoMock = Mockery::mock(TankRepositoryInterface::class);

        $repoMock->shouldReceive('deactivate')
            ->once()
            ->with(99, 'admin')
            ->andReturn(false);

        $service = new TankService($repoMock);
        $result = $service->deactivateTank(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to deactivate tank'], $result);
    }

    public function test_it_activates_tank_successfully(): void
    {
        $repoMock = Mockery::mock(TankRepositoryInterface::class);

        $repoMock->shouldReceive('activate')
            ->once()
            ->with(1, 'admin')
            ->andReturn(true);

        $service = new TankService($repoMock);
        $result = $service->activateTank(1, 'admin');

        $this->assertSame(['status' => 1, 'message' => 'Tank activated'], $result);
    }

    public function test_it_returns_failure_when_activate_tank_fails(): void
    {
        $repoMock = Mockery::mock(TankRepositoryInterface::class);

        $repoMock->shouldReceive('activate')
            ->once()
            ->with(99, 'admin')
            ->andReturn(false);

        $service = new TankService($repoMock);
        $result = $service->activateTank(99, 'admin');

        $this->assertSame(['status' => 0, 'message' => 'Failed to activate tank'], $result);
    }

    public function test_it_syncs_tanks_from_external_api_successfully(): void
    {
        $repoMock = Mockery::mock(TankRepositoryInterface::class);

        $apiResponse = [
            'success' => true,
            'data' => [
                [
                    'plantCode' => '1007',
                    'plantName' => 'Plant A',
                    'tanks' => [
                        ['tankNumber' => 'T001', 'tankHeight' => 10.5],
                        ['tankNumber' => 'T002', 'tankHeight' => 8.0],
                    ],
                ],
            ],
        ];

        Http::fake([
            '*' => Http::response($apiResponse, 200),
        ]);

        $repoMock->shouldReceive('syncUpdateOrCreate')
            ->twice()
            ->andReturn(true);

        $service = new TankService($repoMock);
        $result = $service->syncFromExternal('admin');

        $this->assertSame(1, $result['status']);
        $this->assertStringContainsString('2 tanks', $result['message']);
    }

    public function test_it_returns_up_to_date_message_when_no_tanks_were_updated_on_sync(): void
    {
        $repoMock = Mockery::mock(TankRepositoryInterface::class);

        $apiResponse = [
            'success' => true,
            'data' => [
                [
                    'plantCode' => '1007',
                    'plantName' => 'Plant A',
                    'tanks' => [
                        ['tankNumber' => 'T001', 'tankHeight' => 10.5],
                    ],
                ],
            ],
        ];

        Http::fake([
            '*' => Http::response($apiResponse, 200),
        ]);

        $repoMock->shouldReceive('syncUpdateOrCreate')
            ->once()
            ->andReturn(false);

        $service = new TankService($repoMock);
        $result = $service->syncFromExternal('admin');

        $this->assertSame(2, $result['status']);
        $this->assertSame('All tanks are up to date. No updates needed.', $result['message']);
    }

    public function test_it_returns_failure_when_external_api_responds_with_error_status(): void
    {
        $repoMock = Mockery::mock(TankRepositoryInterface::class);

        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $service = new TankService($repoMock);
        $result = $service->syncFromExternal('admin');

        $this->assertSame(0, $result['status']);
        $this->assertSame('Failed to fetch data from external API.', $result['message']);
    }

    public function test_it_returns_failure_when_external_api_returns_invalid_response_structure(): void
    {
        $repoMock = Mockery::mock(TankRepositoryInterface::class);

        Http::fake([
            '*' => Http::response(['success' => false], 200),
        ]);

        $service = new TankService($repoMock);
        $result = $service->syncFromExternal('admin');

        $this->assertSame(0, $result['status']);
        $this->assertSame('Invalid response from external API.', $result['message']);
    }

    public function test_it_returns_failure_when_external_api_connection_fails(): void
    {
        $repoMock = Mockery::mock(TankRepositoryInterface::class);

        Http::fake(function () {
            throw new \Exception('Connection refused');
        });

        $service = new TankService($repoMock);
        $result = $service->syncFromExternal('admin');

        $this->assertSame(0, $result['status']);
        $this->assertStringStartsWith('Connection failed:', $result['message']);
    }

    public function test_it_skips_plant_entries_with_empty_tanks_on_sync(): void
    {
        $repoMock = Mockery::mock(TankRepositoryInterface::class);

        $apiResponse = [
            'success' => true,
            'data' => [
                [
                    'plantCode' => '1007',
                    'plantName' => 'Plant A',
                    'tanks' => [],
                ],
            ],
        ];

        Http::fake([
            '*' => Http::response($apiResponse, 200),
        ]);

        $repoMock->shouldReceive('syncUpdateOrCreate')->never();

        $service = new TankService($repoMock);
        $result = $service->syncFromExternal('admin');

        $this->assertSame(2, $result['status']);
        $this->assertSame('All tanks are up to date. No updates needed.', $result['message']);
    }
}
