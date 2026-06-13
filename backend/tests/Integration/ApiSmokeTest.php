<?php declare(strict_types=1);

namespace Tests\Integration;

use App\Models\User;
use Mockery;
use Modules\TsBlending\Services\Contracts\BlendingServiceInterface;
use Modules\TsRaw\Services\Contracts\RmEntryServiceInterface;
use Modules\TsTransfer\Services\Contracts\TransferServiceInterface;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Tests\TestCase;

class ApiSmokeTest extends TestCase
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

    public function test_rm_entries_index_returns_200(): void
    {
        $user = User::factory()->make();

        $mockData = [
            'data'  => collect([(object)['id' => 1]]),
            'total' => 1,
        ];

        $serviceMock = Mockery::mock(RmEntryServiceInterface::class);
        $serviceMock->shouldReceive('getRmList')
            ->once()
            ->andReturn($mockData);
        $this->app->instance(RmEntryServiceInterface::class, $serviceMock);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/rm-entries?id_plant=0&page=1&per_page=5')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
    }

    public function test_transfers_index_returns_200(): void
    {
        $user = User::factory()->make();

        $mockData = [
            'data'  => collect([(object)['id_balance_head' => 1]]),
            'total' => 1,
        ];

        $serviceMock = Mockery::mock(TransferServiceInterface::class);
        $serviceMock->shouldReceive('getTransferList')
            ->once()
            ->andReturn($mockData);
        $this->app->instance(TransferServiceInterface::class, $serviceMock);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/transfers?id_plant=1&page=1&per_page=5')
            ->assertStatus(200);
    }

    public function test_blendings_index_returns_200(): void
    {
        $user = User::factory()->make();

        $mockData = [
            'data'  => collect([(object)['id_balance_head' => 1]]),
            'total' => 1,
        ];

        $serviceMock = Mockery::mock(BlendingServiceInterface::class);
        $serviceMock->shouldReceive('getBlendingList')
            ->once()
            ->andReturn($mockData);
        $this->app->instance(BlendingServiceInterface::class, $serviceMock);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/blendings?id_plant=1&page=1&per_page=5')
            ->assertStatus(200);
    }
}
