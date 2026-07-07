<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\User;
use Mockery;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Modules\TsBlending\Services\Contracts\BlendingServiceInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BlendingModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlantContextMiddleware::class);

        Permission::firstOrCreate(['name' => 'task-update']);
        foreach (['admin', 'super-admin', 'manager', 'superintendent', 'senior-supervisor', 'supervisor', 'senior-staff', 'staff'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createUserWithRole(string $role = 'admin'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_it_returns_401_on_index_without_auth(): void
    {
        $this->getJson('/api/v1/transactions/blendings')->assertStatus(401);
    }

    public function test_it_returns_401_on_store_without_auth(): void
    {
        $this->postJson('/api/v1/transactions/blendings', [])->assertStatus(401);
    }

    public function test_it_returns_401_on_destroy_without_auth(): void
    {
        $this->deleteJson('/api/v1/transactions/blendings/82605240010101')->assertStatus(401);
    }

    public function test_it_returns_401_on_active_materials_without_auth(): void
    {
        $this->getJson('/api/v1/transactions/blendings/active-materials')->assertStatus(401);
    }

    public function test_it_returns_401_on_new_entry_no_without_auth(): void
    {
        $this->getJson('/api/v1/transactions/blendings/new-entry-no')->assertStatus(401);
    }

    public function test_it_returns_401_on_tanks_without_auth(): void
    {
        $this->getJson('/api/v1/transactions/blendings/tanks')->assertStatus(401);
    }

    public function test_it_returns_401_on_all_tanks_without_auth(): void
    {
        $this->getJson('/api/v1/transactions/blendings/all-tanks')->assertStatus(401);
    }

    public function test_it_returns_401_on_total_stock_material_without_auth(): void
    {
        $this->getJson('/api/v1/transactions/blendings/total-stock-material')->assertStatus(401);
    }

    public function test_it_returns_401_on_total_qty_material_without_auth(): void
    {
        $this->getJson('/api/v1/transactions/blendings/total-qty-material')->assertStatus(401);
    }

    public function test_it_returns_401_on_material_list_without_auth(): void
    {
        $this->getJson('/api/v1/transactions/blendings/material-list')->assertStatus(401);
    }

    public function test_it_returns_active_materials_when_authenticated(): void
    {
        $user = $this->createUserWithRole('admin');

        $materials = collect([
            (object) ['id_material' => 1, 'description' => 'CPO', 'type' => 'RM'],
            (object) ['id_material' => 3, 'description' => 'RBDPO', 'type' => 'FG'],
        ]);

        $serviceMock = Mockery::mock(BlendingServiceInterface::class);
        $serviceMock->shouldReceive('getActiveMaterials')
            ->once()
            ->andReturn($materials);
        $this->app->instance(BlendingServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/blendings/active-materials');

        $response->assertStatus(200)
            ->assertJsonPath('status', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_it_returns_blending_list_when_authenticated(): void
    {
        $user = $this->createUserWithRole('admin');

        $listData = [
            'data' => collect([
                (object) [
                    'id_balance_head' => 1,
                    'entry_no' => '82605240010101',
                    'entry_date' => '2026-06-12',
                    'description' => 'RBDPO',
                    'qty' => '100.000',
                    'status' => 1,
                ],
            ]),
            'total' => 1,
        ];

        $serviceMock = Mockery::mock(BlendingServiceInterface::class);
        $serviceMock->shouldReceive('getBlendingList')
            ->once()
            ->andReturn($listData);
        $this->app->instance(BlendingServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/blendings');

        $response->assertStatus(200)
            ->assertJsonPath('status', 1)
            ->assertJsonPath('total', 1);
    }

    public function test_it_returns_tanks_when_authenticated(): void
    {
        $user = $this->createUserWithRole('admin');

        $tanks = collect([
            (object) ['id_sloc' => 1, 'description' => 'T-01', 'sloc' => 'SL01'],
        ]);

        $serviceMock = Mockery::mock(BlendingServiceInterface::class);
        $serviceMock->shouldReceive('getTanks')
            ->once()
            ->andReturn($tanks);
        $this->app->instance(BlendingServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/blendings/tanks');

        $response->assertStatus(200)
            ->assertJsonPath('status', 1);
    }

    public function test_it_returns_422_for_invalid_flag(): void
    {
        $user = $this->createUserWithRole('admin');
        $user->givePermissionTo('task-update');

        $serviceMock = Mockery::mock(BlendingServiceInterface::class);
        $this->app->instance(BlendingServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/transactions/blendings', [
                'flag' => 'unknown_flag',
            ]);

        $response->assertStatus(422);
    }

    public function test_it_returns_422_when_material_already_exists_in_blending(): void
    {
        $user = $this->createUserWithRole('admin');
        $user->givePermissionTo('task-update');

        $serviceMock = Mockery::mock(BlendingServiceInterface::class);
        $serviceMock->shouldReceive('addMaterialToBlending')
            ->once()
            ->andReturn(['response' => 2]);
        $this->app->instance(BlendingServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/transactions/blendings', [
                'flag' => 'post_blendingEntryMaterial',
                'mode' => 'ADD',
                'entryNo' => '82605240010101',
                'idMaterialSource' => 3,
                'qty' => '100',
                'idSloc' => 5,
                'id_plant' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 0)
            ->assertJsonFragment(['message' => 'Material already exists in blending entry']);
    }

    public function test_it_returns_422_when_period_is_locked_on_execute(): void
    {
        $user = $this->createUserWithRole('admin');
        $user->givePermissionTo('task-update');

        $serviceMock = Mockery::mock(BlendingServiceInterface::class);
        $serviceMock->shouldReceive('executeBlending')
            ->once()
            ->andReturn(['response' => 99]);
        $this->app->instance(BlendingServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/transactions/blendings', [
                'flag' => 'post_blendingEntry',
                'entry_no' => '82605240010101',
                'entry_date' => '2026-06-12',
                'id_material' => 3,
                'material_doc' => '',
                'qty' => '100',
                'tankNo' => [],
                'id_plant' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 0)
            ->assertJsonFragment(['message' => 'Period Locked!']);
    }

    public function test_it_returns_422_when_no_blend_material_on_execute(): void
    {
        $user = $this->createUserWithRole('admin');
        $user->givePermissionTo('task-update');

        $serviceMock = Mockery::mock(BlendingServiceInterface::class);
        $serviceMock->shouldReceive('executeBlending')
            ->once()
            ->andReturn(['response' => 4]);
        $this->app->instance(BlendingServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/transactions/blendings', [
                'flag' => 'post_blendingEntry',
                'entry_no' => '82605240010101',
                'entry_date' => '2026-06-12',
                'id_material' => 3,
                'material_doc' => '',
                'qty' => '100',
                'tankNo' => [],
                'id_plant' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 0)
            ->assertJsonFragment(['message' => 'No Blend Material!']);
    }

    public function test_it_returns_200_on_successful_execute_blending(): void
    {
        $user = $this->createUserWithRole('admin');
        $user->givePermissionTo('task-update');

        $serviceMock = Mockery::mock(BlendingServiceInterface::class);
        $serviceMock->shouldReceive('executeBlending')
            ->once()
            ->andReturn(['response' => 1]);
        $this->app->instance(BlendingServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/transactions/blendings', [
                'flag' => 'post_blendingEntry',
                'entry_no' => '82605240010101',
                'entry_date' => '2026-06-12',
                'id_material' => 3,
                'material_doc' => 'MD-001',
                'qty' => '100',
                'tankNo' => [],
                'id_plant' => 0,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 1);
    }

    public function test_it_returns_200_on_successful_deactivate_blending(): void
    {
        $user = $this->createUserWithRole('admin');

        $serviceMock = Mockery::mock(BlendingServiceInterface::class);
        $serviceMock->shouldReceive('deactivateBlending')
            ->once()
            ->andReturn(['response' => 1]);
        $this->app->instance(BlendingServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/transactions/blendings/82605240010101');

        $response->assertStatus(200)
            ->assertJsonPath('status', 1);
    }

    public function test_it_returns_500_when_service_throws_on_index(): void
    {
        $user = $this->createUserWithRole('admin');

        $serviceMock = Mockery::mock(BlendingServiceInterface::class);
        $serviceMock->shouldReceive('getBlendingList')
            ->once()
            ->andThrow(new \RuntimeException('Database connection failed'));
        $this->app->instance(BlendingServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/blendings');

        $response->assertStatus(500)
            ->assertJsonPath('status', 0);
    }

    public function test_it_returns_500_when_service_throws_on_active_materials(): void
    {
        $user = $this->createUserWithRole('admin');

        $serviceMock = Mockery::mock(BlendingServiceInterface::class);
        $serviceMock->shouldReceive('getActiveMaterials')
            ->once()
            ->andThrow(new \RuntimeException('Query timeout'));
        $this->app->instance(BlendingServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/blendings/active-materials');

        $response->assertStatus(500)
            ->assertJsonPath('status', 0);
    }

    public function test_it_returns_403_without_role(): void
    {
        $user = User::factory()->create();

        $serviceMock = Mockery::mock(BlendingServiceInterface::class);
        $this->app->instance(BlendingServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/blendings');

        $response->assertStatus(403);
    }
}
