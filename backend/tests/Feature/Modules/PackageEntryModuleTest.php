<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\User;
use Mockery;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Modules\TsPackage\Services\Contracts\PackageServiceInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PackageEntryModuleTest extends TestCase
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

    public function test_package_entries_index_requires_auth(): void
    {
        $this->getJson('/api/v1/transactions/package-entries')->assertStatus(401);
    }

    public function test_package_entries_store_requires_auth(): void
    {
        $this->postJson('/api/v1/transactions/package-entries', [])->assertStatus(401);
    }

    public function test_new_trace_no_requires_auth(): void
    {
        $this->getJson('/api/v1/transactions/package-entries/new-trace-no?warehouse=1&id_plant=1')->assertStatus(401);
    }

    public function test_new_trace_no_returns_generated_trace_number(): void
    {
        $user = $this->createUserWithRole('admin');

        $mockService = Mockery::mock(PackageServiceInterface::class);
        $mockService->shouldReceive('generateTraceNo')
            ->once()
            ->with(1, 1, 1, null)
            ->andReturn('4'.date('ymd').'0210101');
        $this->app->instance(PackageServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/package-entries/new-trace-no?warehouse=1&id_plant=1&id_material=1');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.traceNo', '4'.date('ymd').'0210101');
    }

    public function test_new_trace_no_validates_required_parameters(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/package-entries/new-trace-no');

        $response->assertStatus(422);
    }

    public function test_package_entries_index_returns_success(): void
    {
        $user = $this->createUserWithRole('admin');

        $mockService = Mockery::mock(PackageServiceInterface::class);
        $mockService->shouldReceive('getDtPckEntry')
            ->once()
            ->andReturn(['data' => collect([]), 'total' => 0]);
        $this->app->instance(PackageServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/package-entries');

        $response->assertStatus(200);
    }

    public function test_package_entries_store_returns_success(): void
    {
        $user = $this->createUserWithRole('admin');
        $user->givePermissionTo('task-update');

        $mockService = Mockery::mock(PackageServiceInterface::class);
        $mockService->shouldReceive('store')
            ->once()
            ->andReturn(['response' => 1]);
        $this->app->instance(PackageServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/transactions/package-entries', [
                'entryDate' => '2026-07-01',
                'fgProduct' => 1,
                'batchNo' => 'BATCH-001',
                'qty' => 100,
                'tank' => 1,
                'tankNo' => [1],
                'warehouse' => 1,
            ]);

        $response->assertStatus(200);
    }

    public function test_package_entries_cancel_requires_auth(): void
    {
        $this->deleteJson('/api/v1/transactions/package-entries/1', ['traceNo' => '42607010210101'])
            ->assertStatus(401);
    }

    public function test_package_entries_cancel_requires_trace_no(): void
    {
        $user = $this->createUserWithRole('admin');

        $mockService = Mockery::mock(PackageServiceInterface::class);
        $this->app->instance(PackageServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/transactions/package-entries/1');

        $response->assertStatus(422);
    }

    public function test_package_entries_cancel_returns_success(): void
    {
        $user = $this->createUserWithRole('admin');

        $mockService = Mockery::mock(PackageServiceInterface::class);
        $mockService->shouldReceive('cancel')
            ->once()
            ->andReturn(['response' => 1]);
        $this->app->instance(PackageServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/transactions/package-entries/1', ['traceNo' => '42607010210101']);

        $response->assertStatus(200);
    }

    public function test_package_entries_cancel_returns_error_when_service_fails(): void
    {
        $user = $this->createUserWithRole('admin');

        $mockService = Mockery::mock(PackageServiceInterface::class);
        $mockService->shouldReceive('cancel')
            ->once()
            ->andReturn(['response' => 0, 'message' => 'Not found']);
        $this->app->instance(PackageServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/transactions/package-entries/1', ['traceNo' => '42607010210101']);

        $response->assertStatus(400);
    }

    public function test_package_entries_index_requires_role(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(PackageServiceInterface::class);
        $this->app->instance(PackageServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/package-entries');

        $response->assertStatus(403);
    }
}
