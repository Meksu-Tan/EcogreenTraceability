<?php declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\User;
use Modules\TsPackage\Services\Contracts\PackageServiceInterface;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Mockery;
use Tests\TestCase;

class PackageEntryModuleTest extends TestCase
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

    public function test_package_entries_index_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/transactions/package-entries');
        $response->assertStatus(401);
    }

    public function test_package_entries_store_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/transactions/package-entries', []);
        $response->assertStatus(401);
    }

    public function test_new_trace_no_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/transactions/package-entries/new-trace-no?warehouse=1&id_plant=1');
        $response->assertStatus(401);
    }

    public function test_new_trace_no_returns_generated_trace_number(): void
    {
        $user = User::factory()->make();

        $mockService = Mockery::mock(PackageServiceInterface::class);
        $mockService->shouldReceive('generateTraceNo')
            ->once()
            ->with(1, 1, 1, null)
            ->andReturn('4' . date('ymd') . '0210101');
        $this->app->instance(PackageServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/package-entries/new-trace-no?warehouse=1&id_plant=1&id_material=1');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.traceNo', '4' . date('ymd') . '0210101');
    }

    public function test_new_trace_no_validates_required_parameters(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/package-entries/new-trace-no');

        $response->assertStatus(422);
    }

    public function test_get_wip_materials_success(): void
    {
        $user = User::factory()->make();

        $mockService = Mockery::mock(PackageServiceInterface::class);
        $mockService->shouldReceive('getWipMaterialByFgProduct')
            ->once()
            ->with([
                'idMaterialPck' => '1',
                'tank' => null,
                'id_plant' => null,
            ])
            ->andReturn(collect([
                ['wip_material' => 'CPO (CPO) || Balance : 100.000 MT', 'balance' => '100.000', 'id_rundown' => '001']
            ]));
        $this->app->instance(PackageServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/package-entries/wip-materials?idMaterialPck=1');

        $response->assertStatus(200)
            ->assertJsonPath('status', 1);
    }
}



