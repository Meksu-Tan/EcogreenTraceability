<?php declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\User;
use Modules\TsShipment\Services\Contracts\ShipmentServiceInterface;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Mockery;
use Tests\TestCase;

class ShipmentEntryModuleTest extends TestCase
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

    public function test_shipment_entries_index_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/transactions/shipment-entries');
        $response->assertStatus(401);
    }

    public function test_shipment_entries_store_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/transactions/shipment-entries', []);
        $response->assertStatus(401);
    }

    public function test_new_trace_no_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/transactions/shipment-entries/new-trace-no?id_plant=1');
        $response->assertStatus(401);
    }

    public function test_new_trace_no_returns_generated_trace_number(): void
    {
        $user = User::factory()->make();

        $mockService = Mockery::mock(ShipmentServiceInterface::class);
        $mockService->shouldReceive('generateTraceNo')
            ->once()
            ->with(1)
            ->andReturn('5' . date('ymd') . '0010101');
        $this->app->instance(ShipmentServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/shipment-entries/new-trace-no?id_plant=1');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.traceNo', '5' . date('ymd') . '0010101');
    }

    public function test_new_trace_no_validates_required_parameters(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/shipment-entries/new-trace-no');

        $response->assertStatus(422);
    }
}
