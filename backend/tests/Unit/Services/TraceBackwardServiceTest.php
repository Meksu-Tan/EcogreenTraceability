<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\TraceBackward\Services\TraceBackwardService;
use Modules\TraceBackward\Repositories\Contracts\TraceBackwardRepositoryInterface;
use Mockery;

class TraceBackwardServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_delegates_get_backward_list_to_repository(): void
    {
        $repoMock = Mockery::mock(TraceBackwardRepositoryInterface::class);
        $filters = ['page' => 1, 'per_page' => 25, 'id_plant' => null, 'user_id' => 1];
        $expected = ['data' => [], 'total' => 0, 'page' => 1, 'per_page' => 25, 'last_page' => 1];

        $repoMock->shouldReceive('getBackwardList')
            ->once()
            ->with($filters)
            ->andReturn($expected);

        $service = new TraceBackwardService($repoMock);
        $result = $service->getBackwardList($filters);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_backward_trace_detail_to_repository(): void
    {
        $repoMock = Mockery::mock(TraceBackwardRepositoryInterface::class);
        $expected = [['curr_trace' => '100001-001']];

        $repoMock->shouldReceive('getBackwardTraceDetail')
            ->once()
            ->with('300001-001', 3, null, 1)
            ->andReturn($expected);

        $service = new TraceBackwardService($repoMock);
        $result = $service->getBackwardTraceDetail('300001-001', 3, null, 1);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_backward_trace_to_repository(): void
    {
        $repoMock = Mockery::mock(TraceBackwardRepositoryInterface::class);
        $expected = [['trace_no' => '300001-001']];

        $repoMock->shouldReceive('backwardTrace')
            ->once()
            ->with('300001-001', 3, 1002, 1)
            ->andReturn($expected);

        $service = new TraceBackwardService($repoMock);
        $result = $service->backwardTrace('300001-001', 3, 1002, 1);

        $this->assertEquals($expected, $result);
    }
}
