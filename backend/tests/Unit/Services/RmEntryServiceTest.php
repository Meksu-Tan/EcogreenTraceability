<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\TsRaw\Services\RmEntryService;
use Modules\TsRaw\Repositories\Contracts\RmEntryRepositoryInterface;
use Mockery;

class RmEntryServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_can_get_rm_list(): void
    {
        $repoMock = Mockery::mock(RmEntryRepositoryInterface::class);
        $expectedList = [['id_balance_head' => 1]];

        $repoMock->shouldReceive('getRmList')
            ->once()
            ->with(1002)
            ->andReturn($expectedList);

        $service = new RmEntryService($repoMock);
        $result = $service->getRmList(1002);

        $this->assertEquals($expectedList, $result);
    }

    public function test_it_can_deactivate_rm_entry(): void
    {
        $repoMock = Mockery::mock(RmEntryRepositoryInterface::class);
        $expectedResult = ['response' => '1'];

        $repoMock->shouldReceive('deactivateRmEntry')
            ->once()
            ->with(1, 'Admin')
            ->andReturn($expectedResult);

        $service = new RmEntryService($repoMock);
        $result = $service->deactivateRmEntry(1, 'Admin');

        $this->assertEquals($expectedResult, $result);
    }
}
