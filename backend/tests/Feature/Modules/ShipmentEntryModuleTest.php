<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\User;
use Mockery;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Modules\TsShipment\Services\Contracts\ShipmentServiceInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShipmentEntryModuleTest extends TestCase
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

    public function test_shipment_entries_index_requires_auth(): void
    {
        $this->getJson('/api/v1/transactions/shipment-entries')->assertStatus(401);
    }

    public function test_shipment_entries_store_requires_auth(): void
    {
        $this->postJson('/api/v1/transactions/shipment-entries', [])->assertStatus(401);
    }

    public function test_new_trace_no_requires_auth(): void
    {
        $this->getJson('/api/v1/transactions/shipment-entries/new-trace-no?id_plant=1')->assertStatus(401);
    }

    public function test_new_trace_no_returns_generated_trace_number(): void
    {
        $user = $this->createUserWithRole('admin');

        $mockService = Mockery::mock(ShipmentServiceInterface::class);
        $mockService->shouldReceive('generateTraceNo')
            ->once()
            ->with(1, 1, null)
            ->andReturn('5'.date('ymd').'0010101');
        $this->app->instance(ShipmentServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/shipment-entries/new-trace-no?id_plant=1&id_material=1');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.traceNo', '5'.date('ymd').'0010101');
    }

    public function test_new_trace_no_validates_required_parameters(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/shipment-entries/new-trace-no');

        $response->assertStatus(422);
    }

    public function test_shipment_entries_cancel_requires_auth(): void
    {
        $this->deleteJson('/api/v1/transactions/shipment-entries/1', ['traceNo' => '52607010010101'])
            ->assertStatus(401);
    }

    public function test_shipment_entries_cancel_requires_trace_no(): void
    {
        $user = $this->createUserWithRole('admin');

        $mockService = Mockery::mock(ShipmentServiceInterface::class);
        $this->app->instance(ShipmentServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/transactions/shipment-entries/1');

        $response->assertStatus(422);
    }

    public function test_shipment_entries_cancel_returns_success(): void
    {
        $user = $this->createUserWithRole('admin');

        $mockService = Mockery::mock(ShipmentServiceInterface::class);
        $mockService->shouldReceive('cancel')
            ->once()
            ->andReturn(['response' => 1]);
        $this->app->instance(ShipmentServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/transactions/shipment-entries/1', [
                'traceNo' => '52607010010101',
                'idTraceHead' => 1,
                'idShipHead' => 1,
            ]);

        $response->assertStatus(200);
    }

    public function test_shipment_entries_cancel_returns_error_when_service_fails(): void
    {
        $user = $this->createUserWithRole('admin');

        $mockService = Mockery::mock(ShipmentServiceInterface::class);
        $mockService->shouldReceive('cancel')
            ->once()
            ->andReturn(['response' => 0, 'message' => 'Not found']);
        $this->app->instance(ShipmentServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/transactions/shipment-entries/1', ['traceNo' => '52607010010101']);

        $response->assertStatus(400);
    }

    public function test_shipment_entries_index_requires_role(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(ShipmentServiceInterface::class);
        $this->app->instance(ShipmentServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/shipment-entries');

        $response->assertStatus(403);
    }
}
