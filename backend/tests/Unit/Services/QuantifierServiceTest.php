<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;
use Illuminate\Support\Facades\DB;
use Modules\Quantifier\Services\QuantifierService;
use Modules\Quantifier\Repositories\Contracts\QuantifierRepositoryInterface;

class QuantifierServiceTest extends TestCase
{
    protected QuantifierRepositoryInterface|MockInterface $repoMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoMock = Mockery::mock(QuantifierRepositoryInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeService(): QuantifierService
    {
        return new QuantifierService($this->repoMock);
    }

    private function mockEudrConnection(): MockInterface
    {
        $conn = Mockery::mock('EudrTsConnection');

        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->andReturn($conn);

        return $conn;
    }

    // ——— getQuantifierList ———

    public function test_it_returns_quantifier_list_with_status_and_message(): void
    {
        $data = [
            ['id' => 1, 'flowmeter' => 'FM-01', 'value' => 100.0],
            ['id' => 2, 'flowmeter' => 'FM-02', 'value' => 200.0],
        ];

        $this->repoMock
            ->shouldReceive('getQuantifierList')
            ->once()
            ->with([])
            ->andReturn($data);

        $result = $this->makeService()->getQuantifierList();

        $this->assertSame(1, $result['status']);
        $this->assertSame($data, $result['data']);
        $this->assertSame('Quantifier list retrieved', $result['message']);
    }

    public function test_it_returns_quantifier_list_with_filters(): void
    {
        $filters = ['flowmeter' => 'FM-01'];
        $data = [['id' => 1, 'flowmeter' => 'FM-01', 'value' => 100.0]];

        $this->repoMock
            ->shouldReceive('getQuantifierList')
            ->once()
            ->with($filters)
            ->andReturn($data);

        $result = $this->makeService()->getQuantifierList($filters);

        $this->assertSame(1, $result['status']);
        $this->assertSame($data, $result['data']);
    }

    public function test_it_returns_empty_data_array_when_no_quantifiers(): void
    {
        $this->repoMock
            ->shouldReceive('getQuantifierList')
            ->once()
            ->with([])
            ->andReturn([]);

        $result = $this->makeService()->getQuantifierList();

        $this->assertSame(1, $result['status']);
        $this->assertSame([], $result['data']);
    }

    // ——— getActiveFlowmeters ———

    public function test_it_returns_active_flowmeters_with_status_and_message(): void
    {
        $expected = [
            ['flowmeter' => 'FM-01', 'is_active' => 1],
            ['flowmeter' => 'FM-02', 'is_active' => 1],
        ];

        $this->repoMock
            ->shouldReceive('getActiveFlowmeters')
            ->once()
            ->andReturn($expected);

        $result = $this->makeService()->getActiveFlowmeters();

        $this->assertSame(1, $result['status']);
        $this->assertSame($expected, $result['data']);
        $this->assertSame('Active flowmeters retrieved', $result['message']);
    }

    public function test_it_returns_empty_data_when_no_active_flowmeters(): void
    {
        $this->repoMock
            ->shouldReceive('getActiveFlowmeters')
            ->once()
            ->andReturn([]);

        $result = $this->makeService()->getActiveFlowmeters();

        $this->assertSame(1, $result['status']);
        $this->assertSame([], $result['data']);
    }

    // ——— getQuantifierDetail ———

    public function test_it_returns_quantifier_detail_when_found(): void
    {
        $expected = ['id' => 5, 'flowmeter' => 'FM-05', 'value' => 350.0];

        $this->repoMock
            ->shouldReceive('getQuantifierDetail')
            ->once()
            ->with(5)
            ->andReturn($expected);

        $result = $this->makeService()->getQuantifierDetail(5);

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_null_from_get_quantifier_detail_when_not_found(): void
    {
        $this->repoMock
            ->shouldReceive('getQuantifierDetail')
            ->once()
            ->with(999)
            ->andReturn(null);

        $result = $this->makeService()->getQuantifierDetail(999);

        $this->assertNull($result);
    }

    // ——— storeQuantifier: ADD single flowmeter ———

    public function test_it_creates_single_quantifier_when_mode_is_add_and_flowmeter_provided(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $this->repoMock
            ->shouldReceive('createQuantifier')
            ->once()
            ->with('2026-01-15', 'FM-01', 100.0, 'Resetting', 'Admin')
            ->andReturn(10);

        $data = [
            'mode'       => 'ADD',
            'flowmeter'  => 'FM-01',
            'reset_date' => '2026-01-15',
            'value'      => 100.0,
            'remark'     => 'Resetting',
        ];

        $result = $this->makeService()->storeQuantifier('Admin', $data);

        $this->assertSame(1, $result['response']);
        $this->assertSame('Quantifier created', $result['message']);
        $this->assertSame(10, $result['id']);
    }

    // ——— storeQuantifier: ADD bulk (no flowmeter specified) ———

    public function test_it_creates_bulk_quantifiers_when_mode_is_add_and_no_flowmeter(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $flowmeters = [
            ['flowmeter' => 'FM-01'],
            ['flowmeter' => 'FM-02'],
        ];

        $this->repoMock
            ->shouldReceive('getActiveFlowmeters')
            ->once()
            ->andReturn($flowmeters);

        $this->repoMock
            ->shouldReceive('createQuantifier')
            ->twice()
            ->andReturn(11, 12);

        $data = [
            'mode'       => 'ADD',
            'reset_date' => '2026-01-15',
            'value'      => 0.0,
            'remark'     => '',
        ];

        $result = $this->makeService()->storeQuantifier('Admin', $data);

        $this->assertSame(1, $result['response']);
        $this->assertSame('Bulk quantifier created', $result['message']);
        $this->assertSame([11, 12], $result['ids']);
    }

    // ——— storeQuantifier: UPDATE ———

    public function test_it_updates_quantifier_when_mode_is_update(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $updateResult = ['response' => 1, 'message' => 'Quantifier updated'];

        $this->repoMock
            ->shouldReceive('updateQuantifier')
            ->once()
            ->with(5, '2026-01-15', 'FM-01', 150.0, 'Updated remark', 'Admin')
            ->andReturn($updateResult);

        $data = [
            'mode'       => 'UPDATE',
            'id'         => 5,
            'flowmeter'  => 'FM-01',
            'reset_date' => '2026-01-15',
            'value'      => 150.0,
            'remark'     => 'Updated remark',
        ];

        $result = $this->makeService()->storeQuantifier('Admin', $data);

        $this->assertSame($updateResult, $result);
    }

    // ——— storeQuantifier: invalid mode ———

    public function test_it_returns_invalid_mode_response_for_unknown_mode(): void
    {
        $conn = $this->mockEudrConnection();

        $conn->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $cb) => $cb());

        $data = ['mode' => 'DELETE'];

        $result = $this->makeService()->storeQuantifier('Admin', $data);

        $this->assertSame(0, $result['response']);
        $this->assertSame('Invalid mode', $result['message']);
    }

    // ——— deactivateQuantifier ———

    public function test_it_deactivates_quantifier_and_returns_repository_result(): void
    {
        $expected = ['response' => 1, 'message' => 'Quantifier deactivated'];

        $this->repoMock
            ->shouldReceive('deactivateQuantifier')
            ->once()
            ->with(7, 'Admin')
            ->andReturn($expected);

        $result = $this->makeService()->deactivateQuantifier('Admin', 7);

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_failure_when_deactivate_quantifier_fails(): void
    {
        $expected = ['response' => 0, 'message' => 'Failed to deactivate'];

        $this->repoMock
            ->shouldReceive('deactivateQuantifier')
            ->once()
            ->with(999, 'Admin')
            ->andReturn($expected);

        $result = $this->makeService()->deactivateQuantifier('Admin', 999);

        $this->assertSame($expected, $result);
    }

    // ——— activateQuantifier ———

    public function test_it_activates_quantifier_and_returns_repository_result(): void
    {
        $expected = ['response' => 1, 'message' => 'Quantifier activated'];

        $this->repoMock
            ->shouldReceive('activateQuantifier')
            ->once()
            ->with(7, 'Admin')
            ->andReturn($expected);

        $result = $this->makeService()->activateQuantifier('Admin', 7);

        $this->assertSame($expected, $result);
    }

    public function test_it_returns_failure_when_activate_quantifier_fails(): void
    {
        $expected = ['response' => 0, 'message' => 'Failed to activate'];

        $this->repoMock
            ->shouldReceive('activateQuantifier')
            ->once()
            ->with(999, 'Admin')
            ->andReturn($expected);

        $result = $this->makeService()->activateQuantifier('Admin', 999);

        $this->assertSame($expected, $result);
    }
}
