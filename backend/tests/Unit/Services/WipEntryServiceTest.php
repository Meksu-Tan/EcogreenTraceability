<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\TsWip\Services\WipEntryService;
use Modules\TsWip\Repositories\Contracts\WipEntryRepositoryInterface;
use Mockery;

class WipEntryServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_can_get_balance(): void
    {
        $repoMock = Mockery::mock(WipEntryRepositoryInterface::class);
        $expectedBalance = [['qty' => 50]];

        $repoMock->shouldReceive('getBalance')
            ->once()
            ->with('101', 1002, null)
            ->andReturn($expectedBalance);

        $service = new WipEntryService($repoMock);
        $result = $service->getBalance('101', 1002);

        $this->assertEquals($expectedBalance, $result);
    }

    public function test_it_can_generate_new_feed_number(): void
    {
        $repoMock = Mockery::mock(WipEntryRepositoryInterface::class);
        $expectedNumber = '32605240010101';

        $repoMock->shouldReceive('generateNewFeedNumber')
            ->once()
            ->with('101', 1002)
            ->andReturn($expectedNumber);

        $service = new WipEntryService($repoMock);
        $result = $service->generateNewFeedNumber('101', 1002);

        $this->assertEquals($expectedNumber, $result);
    }
}
