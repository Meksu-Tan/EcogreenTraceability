<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\User;
use Mockery;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Modules\Tank\Repositories\Contracts\WarehouseRepositoryInterface;
use Tests\TestCase;

class WarehouseModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlantContextMiddleware::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_warehouse_index_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/warehouses');
        $response->assertStatus(401);
    }

    public function test_warehouse_store_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/warehouses', []);
        $response->assertStatus(401);
    }

    public function test_warehouse_index_success(): void
    {
        $user = User::factory()->make();
        $expected = [
            [
                'id_warehouse' => 1,
                'id_batch' => 'BATCH-A',
                'code' => 'WH01',
                'description' => 'Warehouse Alpha',
                'status' => 1,
            ],
        ];

        $mockRepo = Mockery::mock(WarehouseRepositoryInterface::class);
        $mockRepo->shouldReceive('getAll')
            ->once()
            ->andReturn($expected);
        $this->app->instance(WarehouseRepositoryInterface::class, $mockRepo);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/warehouses');

        $response->assertStatus(200)
            ->assertJsonPath('data', $expected);
    }

    public function test_warehouse_store_success(): void
    {
        $user = User::factory()->make(['name' => 'JohnDoe']);
        $payload = [
            'id_batch' => 'BATCH-B',
            'code' => 'WH02',
            'description' => 'Warehouse Beta',
        ];

        $mockRepo = Mockery::mock(WarehouseRepositoryInterface::class);
        $mockRepo->shouldReceive('create')
            ->once()
            ->with('JohnDoe', $payload)
            ->andReturn(2);
        $this->app->instance(WarehouseRepositoryInterface::class, $mockRepo);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/warehouses', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.id', 2);
    }

    public function test_warehouse_update_success(): void
    {
        $user = User::factory()->make(['name' => 'JohnDoe']);
        $payload = [
            'id_batch' => 'BATCH-C-UPDATED',
            'code' => 'WH03-UPD',
            'description' => 'Warehouse Gamma Updated',
        ];

        $mockRepo = Mockery::mock(WarehouseRepositoryInterface::class);
        $mockRepo->shouldReceive('update')
            ->once()
            ->with(3, 'JohnDoe', $payload)
            ->andReturn(true);
        $this->app->instance(WarehouseRepositoryInterface::class, $mockRepo);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/warehouses/3', $payload);

        $response->assertStatus(200);
    }

    public function test_warehouse_destroy_toggle_status(): void
    {
        $user = User::factory()->make(['name' => 'JohnDoe']);

        $mockRepo = Mockery::mock(WarehouseRepositoryInterface::class);

        // Deactivate
        $mockRepo->shouldReceive('deactivate')
            ->once()
            ->with(4, 'JohnDoe')
            ->andReturn(true);

        // Activate
        $mockRepo->shouldReceive('activate')
            ->once()
            ->with(4, 'JohnDoe')
            ->andReturn(true);

        $this->app->instance(WarehouseRepositoryInterface::class, $mockRepo);

        // Run deactivate
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/warehouses/4?action=deactivate');
        $response->assertStatus(200);

        // Run activate
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/warehouses/4?action=activate');
        $response->assertStatus(200);
    }
}
