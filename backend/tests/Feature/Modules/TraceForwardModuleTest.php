<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\User;
use Mockery;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Modules\TraceForward\Services\Contracts\TraceForwardServiceInterface;
use Tests\TestCase;

class TraceForwardModuleTest extends TestCase
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

    public function test_it_returns_401_when_unauthenticated_on_index(): void
    {
        $this->getJson('/api/v1/trace/forward')->assertStatus(401);
    }

    public function test_it_returns_401_when_unauthenticated_on_search(): void
    {
        $this->getJson('/api/v1/trace/forward/search')->assertStatus(401);
    }

    public function test_it_returns_401_when_unauthenticated_on_detail(): void
    {
        $this->getJson('/api/v1/trace/forward/detail')->assertStatus(401);
    }

    public function test_it_validates_id_material_required_for_search(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trace/forward/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_material']);
    }

    public function test_it_validates_trace_no_and_id_material_for_detail(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trace/forward/detail');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['trace_no', 'id_material']);
    }

    public function test_it_validates_per_page_max_value_on_list(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trace/forward?per_page=500');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_it_returns_paginated_list_on_index(): void
    {
        $user = User::factory()->make();

        $mockData = [
            'data' => [
                (object) [
                    'id_balance_head' => 1,
                    'trace_no' => '100001-001',
                    'entry_date' => '2026-01-01',
                    'material' => 'CPO :: Crude Palm Oil',
                    'tank' => 'T001',
                    'tank_type' => 'STORAGE',
                    'init_qty' => '10.000',
                    'qty' => '8.000',
                    'supplier' => 'S001 :: Supplier A / B001 / Qty: 10.000 MT',
                    'batch_sap' => 'B001',
                    'tf_number' => 'TF-001',
                    'traced' => 'TRACED',
                    'material_document' => 'MD-001',
                    'po_so' => 'PO-001',
                    'created_at' => '2026-01-01 08:00:00',
                    'created_by' => 'admin',
                    'id_material' => 5,
                ],
            ],
            'total' => 1,
            'page' => 1,
            'per_page' => 25,
            'last_page' => 1,
        ];

        $serviceMock = Mockery::mock(TraceForwardServiceInterface::class);
        $serviceMock->shouldReceive('getForwardList')
            ->once()
            ->andReturn($mockData);
        $this->app->instance(TraceForwardServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trace/forward?page=1&per_page=25');

        $response->assertStatus(200)
            ->assertJsonPath('data.data.0.trace_no', '100001-001')
            ->assertJsonPath('data.data.0.batch_sap', 'B001')
            ->assertJsonPath('data.data.0.tf_number', 'TF-001')
            ->assertJsonPath('data.data.0.traced', 'TRACED')
            ->assertJsonPath('data.data.0.po_so', 'PO-001')
            ->assertJsonPath('data.total', 1);
    }

    public function test_it_returns_initial_and_chain_for_trace_detail(): void
    {
        $user = User::factory()->make();

        $mockData = [
            'initial' => [
                (object) [
                    'prev_trace' => null,
                    'curr_trace' => '100001-001',
                    'batch_date' => '2026-01-01',
                    'material' => 'CPO',
                    'in_qty' => '10.000',
                    'out_qty' => '8.000',
                    'sloc' => 'T001',
                    'supplier' => 'S001 / B001 / 10.000 MT',
                    'material_document' => 'MD-001',
                    'level' => 1,
                    'path' => '1',
                    'created_at' => '2026-01-01 08:00:00',
                    'created_by' => 'admin',
                ],
            ],
            'chain' => [
                (object) [
                    'prev_trace' => '100001-001',
                    'curr_trace' => '200002-001',
                    'batch_date' => '2026-01-02',
                    'material' => 'Refined',
                    'in_qty' => '8.000',
                    'out_qty' => '5.000',
                    'sloc' => 'T002',
                    'supplier' => 'internal / - / -',
                    'material_document' => 'MD-002',
                    'level' => 2,
                    'path' => '1.01',
                    'created_at' => '2026-01-02 09:00:00',
                    'created_by' => 'admin',
                ],
            ],
        ];

        $serviceMock = Mockery::mock(TraceForwardServiceInterface::class);
        $serviceMock->shouldReceive('getForwardTraceDetail')
            ->once()
            ->andReturn($mockData);
        $this->app->instance(TraceForwardServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trace/forward/detail?trace_no=12606300001&id_material=5');

        $response->assertStatus(200)
            ->assertJsonPath('data.initial.0.curr_trace', '100001-001')
            ->assertJsonPath('data.chain.0.curr_trace', '200002-001');
    }

    public function test_it_returns_500_when_service_throws_on_index(): void
    {
        $user = User::factory()->make();

        $serviceMock = Mockery::mock(TraceForwardServiceInterface::class);
        $serviceMock->shouldReceive('getForwardList')
            ->once()
            ->andThrow(new \RuntimeException('DB unreachable'));
        $this->app->instance(TraceForwardServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trace/forward');

        $response->assertStatus(500)
            ->assertJsonPath('status', 0)
            ->assertJsonPath('message', 'Failed to retrieve forward list');
    }

    public function test_it_returns_500_when_service_throws_on_trace_detail(): void
    {
        $user = User::factory()->make();

        $serviceMock = Mockery::mock(TraceForwardServiceInterface::class);
        $serviceMock->shouldReceive('getForwardTraceDetail')
            ->once()
            ->andThrow(new \RuntimeException('SQL syntax error'));
        $this->app->instance(TraceForwardServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trace/forward/detail?trace_no=12606300001&id_material=5');

        $response->assertStatus(500)
            ->assertJsonPath('status', 0)
            ->assertJsonPath('message', 'Failed to retrieve trace detail');
    }
}
