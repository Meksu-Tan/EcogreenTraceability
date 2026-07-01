<?php declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\User;
use Modules\TraceBackward\Services\Contracts\ShipmentTraceVerificationServiceInterface;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Mockery;
use Tests\TestCase;

class ShipmentTraceVerificationTest extends TestCase
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

    public function test_it_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/trace/backward/verify?so_no=43895840835')->assertStatus(401);
    }

    public function test_it_validates_so_no_or_trace_no_required(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trace/backward/verify');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['so_no']);
    }

    public function test_it_returns_verification_by_so_no(): void
    {
        $user = User::factory()->make();

        $mockData = [
            'found' => true,
            'so_no' => '43895840835',
            'trace_no' => '5260604001010103',
            'batch_no' => 'FB',
            'backward_trace' => [],
            'batch_packaging_detail' => collect([]),
            'shipment_overview' => ['response' => 0, 'data' => [], 'message' => 'OK'],
        ];

        $serviceMock = Mockery::mock(ShipmentTraceVerificationServiceInterface::class);
        $serviceMock->shouldReceive('verifyBySoNo')
            ->once()
            ->with('43895840835')
            ->andReturn($mockData);
        $this->app->instance(ShipmentTraceVerificationServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trace/backward/verify?so_no=43895840835');

        $response->assertStatus(200)
            ->assertJsonPath('data.found', true)
            ->assertJsonPath('data.batch_no', 'FB');
    }

    public function test_it_returns_verification_by_trace_no(): void
    {
        $user = User::factory()->make();

        $serviceMock = Mockery::mock(ShipmentTraceVerificationServiceInterface::class);
        $serviceMock->shouldReceive('verifyByTraceNo')
            ->once()
            ->with('52606040010103')
            ->andReturn(['found' => false]);
        $this->app->instance(ShipmentTraceVerificationServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trace/backward/verify?trace_no=52606040010103');

        $response->assertStatus(200)
            ->assertJsonPath('data.found', false);
    }

    public function test_it_returns_500_when_service_throws(): void
    {
        $user = User::factory()->make();

        $serviceMock = Mockery::mock(ShipmentTraceVerificationServiceInterface::class);
        $serviceMock->shouldReceive('verifyBySoNo')
            ->once()
            ->andThrow(new \RuntimeException('SAP unreachable'));
        $this->app->instance(ShipmentTraceVerificationServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trace/backward/verify?so_no=43895840835');

        $response->assertStatus(500)
            ->assertJsonPath('status', 0)
            ->assertJsonPath('message', 'Failed to verify backward trace');
    }
}
