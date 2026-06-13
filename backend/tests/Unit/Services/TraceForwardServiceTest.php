<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\TraceForward\Services\TraceForwardService;
use Modules\TraceForward\Repositories\Contracts\TraceForwardRepositoryInterface;
use Mockery;

class TraceForwardServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_delegates_get_forward_list_to_repository(): void
    {
        $repoMock = Mockery::mock(TraceForwardRepositoryInterface::class);
        $filters = ['page' => 1, 'per_page' => 25, 'id_plant' => null, 'user_id' => 1];
        $expected = ['data' => [], 'total' => 0, 'page' => 1, 'per_page' => 25, 'last_page' => 1];

        $repoMock->shouldReceive('getForwardList')
            ->once()
            ->with($filters)
            ->andReturn($expected);

        $service = new TraceForwardService($repoMock);
        $result = $service->getForwardList($filters);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_forward_trace_detail_to_repository(): void
    {
        $repoMock = Mockery::mock(TraceForwardRepositoryInterface::class);
        $expected = ['initial' => [], 'chain' => []];

        $repoMock->shouldReceive('getForwardTraceDetail')
            ->once()
            ->with(1, '100001-001', 5, null, 1)
            ->andReturn($expected);

        $service = new TraceForwardService($repoMock);
        $result = $service->getForwardTraceDetail(1, '100001-001', 5, null, 1);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_forward_trace_to_repository(): void
    {
        $repoMock = Mockery::mock(TraceForwardRepositoryInterface::class);
        $expected = [['trace_no' => '100001-001']];

        $repoMock->shouldReceive('forwardTrace')
            ->once()
            ->with('100001-001', 5)
            ->andReturn($expected);

        $service = new TraceForwardService($repoMock);
        $result = $service->forwardTrace('100001-001', 5);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_search_traces_to_repository(): void
    {
        $repoMock = Mockery::mock(TraceForwardRepositoryInterface::class);
        $expected = [['id_trace_head' => 1]];

        $repoMock->shouldReceive('searchTraces')
            ->once()
            ->with(5, 'B001', 1002, 1)
            ->andReturn($expected);

        $service = new TraceForwardService($repoMock);
        $result = $service->searchTraces(5, 'B001', 1002, 1);

        $this->assertEquals($expected, $result);
    }
}
