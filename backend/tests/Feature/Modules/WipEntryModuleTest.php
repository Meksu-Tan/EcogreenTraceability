<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Modules\TsWip\Services\Contracts\WipEntryServiceInterface;
use Tests\TestCase;

class WipEntryModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlantContextMiddleware::class);
        Gate::before(fn () => true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_feed_logs_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/transactions/wip-entries/feed');
        $response->assertStatus(401);
    }

    public function test_get_rundown_logs_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/transactions/wip-entries/rundown');
        $response->assertStatus(401);
    }

    public function test_get_feed_logs_authenticated(): void
    {
        $user = User::factory()->make();

        $mockService = Mockery::mock(WipEntryServiceInterface::class);
        $mockService->shouldReceive('getFeed')
            ->once()
            ->with('001', 'LOG', 0, 1, 5)
            ->andReturn(['data' => [], 'total' => 0, 'page' => 1, 'per_page' => 5]);

        $this->app->instance(WipEntryServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/wip-entries/feed?feedId=001&mode=LOG&page=1&per_page=5');

        $response->assertStatus(200);
    }

    public function test_get_rundown_logs_authenticated(): void
    {
        $user = User::factory()->make();

        $mockService = Mockery::mock(WipEntryServiceInterface::class);
        $mockService->shouldReceive('getRundown')
            ->once()
            ->with('001', 'LOG', 0, 1, 5)
            ->andReturn(['data' => [], 'total' => 0, 'page' => 1, 'per_page' => 5]);

        $this->app->instance(WipEntryServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/wip-entries/rundown?rundownId=001&mode=LOG&page=1&per_page=5');

        $response->assertStatus(200);
    }
}
