<?php declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\User;
use Modules\Quantifier\Services\Contracts\QuantifierServiceInterface;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Mockery;
use Tests\TestCase;

class QuantifierModuleTest extends TestCase
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
        $user = User::factory()->make();

        $mockService = Mockery::mock(QuantifierServiceInterface::class);
        $mockService->shouldReceive('getQuantifierList')->once()->andReturn(['data' => []]);
        $this->app->instance(QuantifierServiceInterface::class, $mockService);

        $this->actingAs($user, 'sanctum')
             ->getJson('/api/v1/master/quantifier')
             ->assertStatus(200);
    }
}
