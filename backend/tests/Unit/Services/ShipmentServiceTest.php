<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\TsShipment\Services\ShipmentService;
use Modules\TsShipment\Repositories\Contracts\ShipmentRepositoryInterface;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;

class ShipmentServiceTest extends TestCase
{
    protected MockInterface $repoMock;
    protected ShipmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoMock = Mockery::mock(ShipmentRepositoryInterface::class);
        $this->service  = new ShipmentService($this->repoMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========== getDtShipEntry ==========

    public function test_it_delegates_get_dt_ship_entry_to_repository(): void
    {
        $expected = [
            'data' => collect([
                (object) ['id_ship' => 1, 'trace_no' => 'SHP001'],
                (object) ['id_ship' => 2, 'trace_no' => 'SHP002'],
            ]),
            'total' => 2,
        ];

        $this->repoMock->shouldReceive('getDtShipEntry')
            ->once()
            ->with(0, 1, 50)
            ->andReturn($expected);

        $result = $this->service->getDtShipEntry(0, 1, 50);

        $this->assertSame($expected, $result);
        $this->assertIsArray($result);
    }

    public function test_it_returns_empty_collection_when_no_dt_ship_entry(): void
    {
        $expected = [
            'data' => collect([]),
            'total' => 0,
        ];

        $this->repoMock->shouldReceive('getDtShipEntry')
            ->once()
            ->with(0, 1, 50)
            ->andReturn($expected);

        $result = $this->service->getDtShipEntry(0, 1, 50);

        $this->assertIsArray($result);
        $this->assertEmpty($result['data']);
    }

    // ========== getActiveFgProduct ==========

    public function test_it_delegates_get_active_fg_product_to_repository(): void
    {
        $expected = collect([
            (object) ['id_material' => 10, 'material' => 'FAME (FA - FG)'],
        ]);

        $this->repoMock->shouldReceive('getActiveFgProduct')
            ->once()
            ->withNoArgs()
            ->andReturn($expected);

        $result = $this->service->getActiveFgProduct();

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_empty_collection_when_no_active_fg_product(): void
    {
        $this->repoMock->shouldReceive('getActiveFgProduct')
            ->once()
            ->andReturn(collect([]));

        $result = $this->service->getActiveFgProduct();

        $this->assertEmpty($result);
    }

    // ========== getWipMaterialByFgProduct ==========

    public function test_it_delegates_get_wip_material_by_fg_product_to_repository(): void
    {
        $data     = ['id_fg_product' => 10];
        $expected = collect([
            (object) ['id_material' => 3, 'material' => 'CRUDE PALM OIL'],
        ]);

        $this->repoMock->shouldReceive('getWipMaterialByFgProduct')
            ->once()
            ->with($data)
            ->andReturn($expected);

        $result = $this->service->getWipMaterialByFgProduct($data);

        $this->assertSame($expected, $result);
    }

    // ========== getActiveBatchProduct ==========

    public function test_it_delegates_get_active_batch_product_to_repository(): void
    {
        $data     = ['id_plant' => 1, 'id_fg_product' => 10];
        $expected = collect([
            (object) ['batch_sap' => 'BATCH001', 'qty' => 5000.0],
        ]);

        $this->repoMock->shouldReceive('getActiveBatchProduct')
            ->once()
            ->with($data)
            ->andReturn($expected);

        $result = $this->service->getActiveBatchProduct($data);

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_empty_collection_when_no_active_batch_product(): void
    {
        $data = ['id_plant' => 99, 'id_fg_product' => 99];

        $this->repoMock->shouldReceive('getActiveBatchProduct')
            ->once()
            ->with($data)
            ->andReturn(collect([]));

        $result = $this->service->getActiveBatchProduct($data);

        $this->assertEmpty($result);
    }

    // ========== store ==========

    public function test_it_delegates_store_to_repository_and_returns_success_response(): void
    {
        $data     = ['trace_no' => 'SHP001', 'id_plant' => 1];
        $expected = ['response' => 1, 'message' => 'Shipment stored successfully'];

        $this->repoMock->shouldReceive('store')
            ->once()
            ->with('admin', $data)
            ->andReturn($expected);

        $result = $this->service->store('admin', $data);

        $this->assertEquals($expected, $result);
        $this->assertEquals(1, $result['response']);
    }

    public function test_it_returns_failure_response_when_store_fails(): void
    {
        $data     = ['trace_no' => 'SHP001', 'id_plant' => 1];
        $expected = ['response' => 0, 'message' => 'Failed to store shipment'];

        $this->repoMock->shouldReceive('store')
            ->once()
            ->with('admin', $data)
            ->andReturn($expected);

        $result = $this->service->store('admin', $data);

        $this->assertEquals(0, $result['response']);
    }

    // ========== cancel ==========

    public function test_it_delegates_cancel_to_repository_and_returns_success_response(): void
    {
        $data     = ['id_ship' => 5];
        $expected = ['response' => 1, 'message' => 'Shipment cancelled'];

        $this->repoMock->shouldReceive('cancel')
            ->once()
            ->with('admin', $data)
            ->andReturn($expected);

        $result = $this->service->cancel('admin', $data);

        $this->assertEquals($expected, $result);
    }

    public function test_it_returns_failure_response_when_cancel_fails(): void
    {
        $data     = ['id_ship' => 99];
        $expected = ['response' => 0, 'message' => 'Cancel failed'];

        $this->repoMock->shouldReceive('cancel')
            ->once()
            ->with('admin', $data)
            ->andReturn($expected);

        $result = $this->service->cancel('admin', $data);

        $this->assertEquals(0, $result['response']);
    }

    // ========== updateSo ==========

    public function test_it_delegates_update_so_to_repository(): void
    {
        $data     = ['id_ship' => 5, 'so_number' => 'SO-2026-001'];
        $expected = ['response' => 1, 'message' => 'SO updated'];

        $this->repoMock->shouldReceive('updateSo')
            ->once()
            ->with('admin', $data)
            ->andReturn($expected);

        $result = $this->service->updateSo('admin', $data);

        $this->assertEquals($expected, $result);
    }

    // ========== generateTraceNo ==========

    public function test_it_delegates_generate_trace_no_to_repository(): void
    {
        $this->repoMock->shouldReceive('generateTraceNo')
            ->once()
            ->with(1, 1, null)
            ->andReturn('5260603010101');

        $result = $this->service->generateTraceNo(1, 1);

        $this->assertEquals('5260603010101', $result);
    }

    // ========== getShipmentBatchPackaging ==========

    public function test_it_delegates_get_shipment_batch_packaging_to_repository(): void
    {
        $data     = ['id_ship' => 5];
        $expected = collect([
            (object) ['id_packaging' => 1, 'packaging_code' => 'PKG001'],
        ]);

        $this->repoMock->shouldReceive('getShipmentBatchPackaging')
            ->once()
            ->with($data)
            ->andReturn($expected);

        $result = $this->service->getShipmentBatchPackaging($data);

        $this->assertSame($expected, $result);
    }

    // ========== getLabel ==========

    public function test_it_delegates_get_label_to_repository(): void
    {
        $data     = ['id_ship' => 5];
        $expected = collect([
            (object) ['label_no' => 'LBL001', 'product_name' => 'FAME'],
        ]);

        $this->repoMock->shouldReceive('getLabel')
            ->once()
            ->with($data)
            ->andReturn($expected);

        $result = $this->service->getLabel($data);

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_empty_collection_when_no_labels(): void
    {
        $data = ['id_ship' => 99];

        $this->repoMock->shouldReceive('getLabel')
            ->once()
            ->with($data)
            ->andReturn(collect([]));

        $result = $this->service->getLabel($data);

        $this->assertEmpty($result);
    }

    // ========== getSpecialLabel ==========

    public function test_it_delegates_get_special_label_to_repository(): void
    {
        $data     = ['id_ship' => 5];
        $expected = collect([
            (object) ['label_no' => 'SPL001', 'product_name' => 'PALM STEARIN'],
        ]);

        $this->repoMock->shouldReceive('getSpecialLabel')
            ->once()
            ->with($data)
            ->andReturn($expected);

        $result = $this->service->getSpecialLabel($data);

        $this->assertSame($expected, $result);
    }

    // ========== getDatShipment ==========

    public function test_it_delegates_get_dat_shipment_to_repository(): void
    {
        $data     = ['id_ship' => 5];
        $expected = [
            'header'  => ['trace_no' => 'SHP001'],
            'details' => [],
        ];

        $this->repoMock->shouldReceive('getDatShipment')
            ->once()
            ->with($data)
            ->andReturn($expected);

        $result = $this->service->getDatShipment($data);

        $this->assertEquals($expected, $result);
    }

    // ========== getDatSoAllocation ==========

    public function test_it_delegates_get_dat_so_allocation_to_repository(): void
    {
        $data     = ['id_ship' => 5];
        $expected = [
            ['so_number' => 'SO-2026-001', 'allocated_qty' => 1000.0],
        ];

        $this->repoMock->shouldReceive('getDatSoAllocation')
            ->once()
            ->with($data)
            ->andReturn($expected);

        $result = $this->service->getDatSoAllocation($data);

        $this->assertEquals($expected, $result);
    }

    public function test_it_returns_empty_array_when_no_so_allocation(): void
    {
        $data = ['id_ship' => 99];

        $this->repoMock->shouldReceive('getDatSoAllocation')
            ->once()
            ->with($data)
            ->andReturn([]);

        $result = $this->service->getDatSoAllocation($data);

        $this->assertEmpty($result);
    }
}
