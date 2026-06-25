<?php declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\User;
use Modules\TsTransfer\Services\Contracts\TransferServiceInterface;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Mockery;
use Tests\TestCase;

class TransferModuleTest extends TestCase
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

    // -------------------------------------------------------------------------
    // Unauthenticated — 401
    // -------------------------------------------------------------------------

    public function test_it_returns_401_on_index_without_auth(): void
    {
        $this->getJson('/api/v1/transactions/transfers')->assertStatus(401);
    }

    public function test_it_returns_401_on_store_without_auth(): void
    {
        $this->postJson('/api/v1/transactions/transfers', [])->assertStatus(401);
    }

    public function test_it_returns_401_on_destroy_without_auth(): void
    {
        $this->deleteJson('/api/v1/transactions/transfers/1')->assertStatus(401);
    }

    public function test_it_returns_401_on_active_materials_without_auth(): void
    {
        $this->getJson('/api/v1/transactions/transfers/active-materials')->assertStatus(401);
    }

    public function test_it_returns_401_on_new_entry_no_without_auth(): void
    {
        $this->getJson('/api/v1/transactions/transfers/new-entry-no')->assertStatus(401);
    }

    public function test_it_returns_401_on_tanks_rundown_without_auth(): void
    {
        $this->getJson('/api/v1/transactions/transfers/tanks-rundown')->assertStatus(401);
    }

    public function test_it_returns_401_on_total_stock_without_auth(): void
    {
        $this->getJson('/api/v1/transactions/transfers/total-stock')->assertStatus(401);
    }

    public function test_it_returns_401_on_approval_pending_without_auth(): void
    {
        $this->getJson('/api/v1/transactions/transfers/approval/pending')->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // C1 — Business logic validation tests (CLAUDE_FIX Task 6)
    // -------------------------------------------------------------------------

    /**
     * test_store_fails_when_quantity_is_negative
     * Negative trf_qty should be rejected by FormRequest validation.
     */
    public function test_store_fails_when_quantity_is_negative(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/transactions/transfers', [
                'flag'         => 'post_transferEntry',
                'entry_no'     => 'TEST001',
                'entry_date'   => '2026-06-13',
                'id_material'  => 1,
                'trf_qty'      => -10,
                'source_sloc'  => 1,
                'trf_sloc'     => 2,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['trf_qty']);
    }

    /**
     * test_store_fails_when_from_and_to_tank_are_same
     * source_sloc == trf_sloc should be rejected.
     */
    public function test_store_fails_when_from_and_to_tank_are_same(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/transactions/transfers', [
                'flag'         => 'post_transferEntry',
                'entry_no'     => 'TEST002',
                'entry_date'   => '2026-06-13',
                'id_material'  => 1,
                'trf_qty'      => 100,
                'source_sloc'  => 1,
                'trf_sloc'     => 1,
            ]);

        $response->assertStatus(422);
    }

    /**
     * test_store_fails_without_required_flag
     * Missing/invalid flag should be rejected.
     */
    public function test_store_fails_without_required_flag(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/transactions/transfers', [
                'quantity' => 100,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['entry_no']);
    }

    /**
     * test_index_returns_paginated_data
     */
    public function test_index_returns_paginated_data(): void
    {
        $user = User::factory()->make();

        $listData = [
            'data'  => collect([
                (object)[
                    'id_balance_head' => 1,
                    'entry_no'        => '82606130010101',
                    'entry_date'      => '2026-06-13',
                    'description'     => 'CPO',
                    'qty'             => '100.000',
                    'status'          => 1,
                ],
            ]),
            'total' => 1,
        ];

        $serviceMock = Mockery::mock(TransferServiceInterface::class);
        $serviceMock->shouldReceive('getTransferList')
            ->with(1, 1, 5)
            ->once()
            ->andReturn($listData);
        $this->app->instance(TransferServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/transfers?id_plant=1&page=1&per_page=5');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
    }

    /**
     * test_active_materials_returns_200_when_authenticated
     */
    public function test_active_materials_returns_200_when_authenticated(): void
    {
        $user = User::factory()->make();

        $materials = [
            (object)['id_material' => 1, 'description' => 'CPO'],
        ];

        $serviceMock = Mockery::mock(TransferServiceInterface::class);
        $serviceMock->shouldReceive('getActiveMaterials')
            ->once()
            ->andReturn($materials);
        $this->app->instance(TransferServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/transactions/transfers/active-materials');

        $response->assertStatus(200)
            ->assertJsonPath('status', 1);
    }
}
