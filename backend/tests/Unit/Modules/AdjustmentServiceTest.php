<?php declare(strict_types=1);

namespace Tests\Unit\Modules;

use Tests\TestCase;
use Modules\Adjustment\Services\AdjustmentService;
use Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;
use Modules\Shared\Services\PeriodLockService;
use Modules\Shared\Services\AuditService;
use Modules\Adjustment\Services\Contracts\AdjustmentPeriodServiceInterface;
use Modules\Adjustment\Services\Contracts\AdjustmentMutationServiceInterface;
use Mockery;

class AdjustmentServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_delegates_get_active_materials_to_repository(): void
    {
        $repoMock = Mockery::mock(AdjustmentRepositoryInterface::class);
        $periodLockMock = Mockery::mock(PeriodLockService::class);
        $auditMock = Mockery::mock(AuditService::class);
        $periodServiceMock = Mockery::mock(AdjustmentPeriodServiceInterface::class);
        $mutationServiceMock = Mockery::mock(AdjustmentMutationServiceInterface::class);

        $expected = [
            ['id' => 1, 'code' => 'MAT001', 'name' => 'Material A'],
            ['id' => 2, 'code' => 'MAT002', 'name' => 'Material B'],
        ];

        $repoMock->shouldReceive('getActiveMaterials')
            ->once()
            ->withNoArgs()
            ->andReturn($expected);

        $service = new AdjustmentService(
            $repoMock,
            $periodLockMock,
            $auditMock,
            $periodServiceMock,
            $mutationServiceMock
        );

        $result = $service->getActiveMaterials();

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_adjustment_list_to_repository(): void
    {
        $repoMock = Mockery::mock(AdjustmentRepositoryInterface::class);
        $periodLockMock = Mockery::mock(PeriodLockService::class);
        $auditMock = Mockery::mock(AuditService::class);
        $periodServiceMock = Mockery::mock(AdjustmentPeriodServiceInterface::class);
        $mutationServiceMock = Mockery::mock(AdjustmentMutationServiceInterface::class);

        $plantId = 'DUM';
        $userId = 5;
        $adjType = 'wip';
        $filters = ['date' => '2026-01-01'];
        $expected = [['id' => 1, 'entry_no' => 'ADJ001']];

        $repoMock->shouldReceive('getAdjustmentList')
            ->once()
            ->with($plantId, $userId, $adjType, $filters)
            ->andReturn($expected);

        $service = new AdjustmentService(
            $repoMock,
            $periodLockMock,
            $auditMock,
            $periodServiceMock,
            $mutationServiceMock
        );

        $result = $service->getAdjustmentList($plantId, $userId, $adjType, $filters);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_adjustment_detail_to_repository(): void
    {
        $repoMock = Mockery::mock(AdjustmentRepositoryInterface::class);
        $periodLockMock = Mockery::mock(PeriodLockService::class);
        $auditMock = Mockery::mock(AuditService::class);
        $periodServiceMock = Mockery::mock(AdjustmentPeriodServiceInterface::class);
        $mutationServiceMock = Mockery::mock(AdjustmentMutationServiceInterface::class);

        $headerId = 10;
        $expected = ['id' => 10, 'entry_no' => 'ADJ010', 'details' => []];

        $repoMock->shouldReceive('getAdjustmentDetail')
            ->once()
            ->with($headerId)
            ->andReturn($expected);

        $service = new AdjustmentService(
            $repoMock,
            $periodLockMock,
            $auditMock,
            $periodServiceMock,
            $mutationServiceMock
        );

        $result = $service->getAdjustmentDetail($headerId);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_active_suppliers_to_repository(): void
    {
        $repoMock = Mockery::mock(AdjustmentRepositoryInterface::class);
        $periodLockMock = Mockery::mock(PeriodLockService::class);
        $auditMock = Mockery::mock(AuditService::class);
        $periodServiceMock = Mockery::mock(AdjustmentPeriodServiceInterface::class);
        $mutationServiceMock = Mockery::mock(AdjustmentMutationServiceInterface::class);

        $search = 'acme';
        $userId = 3;
        $expected = [['id' => 1, 'name' => 'Acme Corp']];

        $repoMock->shouldReceive('getActiveSuppliers')
            ->once()
            ->with($search, $userId)
            ->andReturn($expected);

        $service = new AdjustmentService(
            $repoMock,
            $periodLockMock,
            $auditMock,
            $periodServiceMock,
            $mutationServiceMock
        );

        $result = $service->getActiveSuppliers($search, $userId);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_get_active_tanks_to_repository(): void
    {
        $repoMock = Mockery::mock(AdjustmentRepositoryInterface::class);
        $periodLockMock = Mockery::mock(PeriodLockService::class);
        $auditMock = Mockery::mock(AuditService::class);
        $periodServiceMock = Mockery::mock(AdjustmentPeriodServiceInterface::class);
        $mutationServiceMock = Mockery::mock(AdjustmentMutationServiceInterface::class);

        $plantId = 'DUM';
        $expected = [['id' => 1, 'code' => 'T001']];

        $repoMock->shouldReceive('getActiveTanks')
            ->once()
            ->with($plantId)
            ->andReturn($expected);

        $service = new AdjustmentService(
            $repoMock,
            $periodLockMock,
            $auditMock,
            $periodServiceMock,
            $mutationServiceMock
        );

        $result = $service->getActiveTanks($plantId);

        $this->assertEquals($expected, $result);
    }

    public function test_it_delegates_delete_supplier_temp_to_mutation_service(): void
    {
        $repoMock = Mockery::mock(AdjustmentRepositoryInterface::class);
        $periodLockMock = Mockery::mock(PeriodLockService::class);
        $auditMock = Mockery::mock(AuditService::class);
        $periodServiceMock = Mockery::mock(AdjustmentPeriodServiceInterface::class);
        $mutationServiceMock = Mockery::mock(AdjustmentMutationServiceInterface::class);

        $id = 42;
        $expected = ['success' => true, 'message' => 'Deleted'];

        $mutationServiceMock->shouldReceive('deleteSupplierTemp')
            ->once()
            ->with($id)
            ->andReturn($expected);

        $service = new AdjustmentService(
            $repoMock,
            $periodLockMock,
            $auditMock,
            $periodServiceMock,
            $mutationServiceMock
        );

        $result = $service->deleteSupplierTemp($id);

        $this->assertEquals($expected, $result);
    }
}