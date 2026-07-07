<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\User;
use Mockery;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Modules\TraceBackward\Services\Contracts\TraceBackwardServiceInterface;
use Tests\TestCase;

class TraceBackwardModuleTest extends TestCase
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
        $this->getJson('/api/v1/trace/backward')->assertStatus(401);
    }

    public function test_it_returns_401_when_unauthenticated_on_detail(): void
    {
        $this->getJson('/api/v1/trace/backward/detail')->assertStatus(401);
    }

    public function test_it_validates_trace_no_required_for_detail(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trace/backward/detail');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['trace_no']);
    }

    public function test_it_validates_per_page_max_value_on_list(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trace/backward?per_page=500');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_it_returns_paginated_list_with_source_on_index(): void
    {
        $user = User::factory()->make();

        $mockData = [
            'data' => [
                (object) [
                    'id_shipment_head' => 1,
                    'entry_date' => '2026-01-01',
                    'trace_no' => '300001-001',
                    'so_no' => 'SO-001',
                    'batch_no' => 'B-SHIP-001',
                    'sloc' => 'FG-01',
                    'material' => 'OLEIN :: RBD Olein',
                    'qty' => '5.000',
                    'supplier' => 'S001 :: Supplier A / B001 / Qty: 5.000 MT',
                    'source_trace' => '200001-001',
                    'po_so' => 'PO-001',
                    'source' => 'B001 :: 100001-001 / PO-001',
                    'created_at' => '2026-01-01 08:00:00',
                    'created_by' => 'admin',
                    'id_material' => 3,
                ],
            ],
            'total' => 1,
            'page' => 1,
            'per_page' => 25,
            'last_page' => 1,
        ];

        $serviceMock = Mockery::mock(TraceBackwardServiceInterface::class);
        $serviceMock->shouldReceive('getBackwardList')
            ->once()
            ->andReturn($mockData);
        $this->app->instance(TraceBackwardServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trace/backward?page=1&per_page=25');

        $response->assertStatus(200)
            ->assertJsonPath('data.data.0.trace_no', '300001-001')
            ->assertJsonPath('data.data.0.source', 'B001 :: 100001-001 / PO-001')
            ->assertJsonPath('data.data.0.po_so', 'PO-001')
            ->assertJsonPath('data.total', 1);
    }

    public function test_it_returns_chain_on_trace_detail(): void
    {
        $user = User::factory()->make();

        $mockData = [
            (object) [
                'parent_trace_no' => '100001-001',
                'id_trace_head' => 1,
                'curr_trace' => '100001-001',
                'prev_trace' => null,
                'batch_date' => '2026-01-01',
                'material' => 'CPO',
                'in_qty' => '10.000',
                'out_qty' => '8.000',
                'supplier' => 'S001 / B001 / 10.000 MT',
                'level' => 1,
                'path' => '1',
                'material_document' => 'MD-001',
                'sloc' => 'T001',
            ],
        ];

        $serviceMock = Mockery::mock(TraceBackwardServiceInterface::class);
        $serviceMock->shouldReceive('getBackwardTraceDetail')
            ->once()
            ->andReturn($mockData);
        $this->app->instance(TraceBackwardServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trace/backward/detail?trace_no=30000100001&id_material=3');

        $response->assertStatus(200)
            ->assertJsonPath('data.initial.0.curr_trace', '100001-001')
            ->assertJsonPath('data.initial.0.material', 'CPO');
    }

    public function test_it_returns_500_when_service_throws_on_index(): void
    {
        $user = User::factory()->make();

        $serviceMock = Mockery::mock(TraceBackwardServiceInterface::class);
        $serviceMock->shouldReceive('getBackwardList')
            ->once()
            ->andThrow(new \RuntimeException('DB unreachable'));
        $this->app->instance(TraceBackwardServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trace/backward');

        $response->assertStatus(500)
            ->assertJsonPath('status', 0)
            ->assertJsonPath('message', 'Failed to retrieve backward list');
    }

    public function test_it_returns_500_when_service_throws_on_trace_detail(): void
    {
        $user = User::factory()->make();

        $serviceMock = Mockery::mock(TraceBackwardServiceInterface::class);
        $serviceMock->shouldReceive('getBackwardTraceDetail')
            ->once()
            ->andThrow(new \RuntimeException('SQL syntax error'));
        $this->app->instance(TraceBackwardServiceInterface::class, $serviceMock);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/trace/backward/detail?trace_no=10000100001');

        $response->assertStatus(500)
            ->assertJsonPath('status', 0)
            ->assertJsonPath('message', 'Failed to retrieve backward trace detail');
    }
}
