<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\User;
use Mockery;
use Modules\Quantifier\Services\Contracts\QuantifierServiceInterface;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuantifierModuleTest extends TestCase
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

    public function test_quantifier_index_requires_auth(): void
    {
        $this->getJson('/api/v1/master/quantifier')->assertStatus(401);
    }

    public function test_quantifier_flowmeters_requires_auth(): void
    {
        $this->getJson('/api/v1/master/quantifier/flowmeters')->assertStatus(401);
    }

    public function test_quantifier_store_requires_auth(): void
    {
        $this->postJson('/api/v1/master/quantifier', [])->assertStatus(401);
    }

    public function test_quantifier_index_returns_success(): void
    {
        $user = $this->createUserWithRole('admin');

        $mockService = Mockery::mock(QuantifierServiceInterface::class);
        $mockService->shouldReceive('getQuantifierList')->once()->andReturn(['data' => [], 'total' => 0]);
        $this->app->instance(QuantifierServiceInterface::class, $mockService);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/master/quantifier')
            ->assertStatus(200);
    }

    public function test_quantifier_store_returns_success(): void
    {
        $user = $this->createUserWithRole('admin');
        $user->givePermissionTo('task-update');

        $mockService = Mockery::mock(QuantifierServiceInterface::class);
        $mockService->shouldReceive('storeQuantifier')
            ->once()
            ->andReturn(['response' => 1, 'message' => 'Quantifier saved']);
        $this->app->instance(QuantifierServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/master/quantifier', [
                'mode' => 'ADD',
                'reset_date' => '2026-07-01',
                'flowmeter' => 'FM-01',
                'value' => 100,
                'remark' => 'Monthly reset',
            ]);

        $response->assertStatus(200);
    }

    public function test_quantifier_store_validates_required_fields(): void
    {
        $user = $this->createUserWithRole('admin');
        $user->givePermissionTo('task-update');

        $mockService = Mockery::mock(QuantifierServiceInterface::class);
        $this->app->instance(QuantifierServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/master/quantifier', []);

        $response->assertStatus(422);
    }

    public function test_quantifier_deactivate_returns_success(): void
    {
        $user = $this->createUserWithRole('admin');

        $mockService = Mockery::mock(QuantifierServiceInterface::class);
        $mockService->shouldReceive('deactivateQuantifier')
            ->once()
            ->with($user->name, 1)
            ->andReturn(['response' => 1]);
        $this->app->instance(QuantifierServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/master/quantifier/1/deactivate');

        $response->assertStatus(200);
    }

    public function test_quantifier_activate_returns_success(): void
    {
        $user = $this->createUserWithRole('admin');

        $mockService = Mockery::mock(QuantifierServiceInterface::class);
        $mockService->shouldReceive('activateQuantifier')
            ->once()
            ->with($user->name, 1)
            ->andReturn(['response' => 1]);
        $this->app->instance(QuantifierServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/master/quantifier/1/activate');

        $response->assertStatus(200);
    }

    public function test_quantifier_deactivate_returns_error_when_service_fails(): void
    {
        $user = $this->createUserWithRole('admin');

        $mockService = Mockery::mock(QuantifierServiceInterface::class);
        $mockService->shouldReceive('deactivateQuantifier')
            ->once()
            ->andReturn(['response' => 0, 'message' => 'Failed']);
        $this->app->instance(QuantifierServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/master/quantifier/1/deactivate');

        $response->assertStatus(422);
    }

    public function test_quantifier_index_requires_role(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(QuantifierServiceInterface::class);
        $this->app->instance(QuantifierServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/master/quantifier');

        $response->assertStatus(403);
    }
}
