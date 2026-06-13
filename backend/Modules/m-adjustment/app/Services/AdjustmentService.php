<?php declare(strict_types=1);

namespace Modules\Adjustment\Services;

use Modules\Adjustment\Services\Contracts\AdjustmentServiceInterface;
use Modules\Adjustment\Services\Contracts\AdjustmentPeriodServiceInterface;
use Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;
use Modules\Adjustment\Services\Contracts\AdjustmentMutationServiceInterface;
use Modules\Shared\Services\PeriodLockService;
use Modules\Shared\Services\AuditService;
use Illuminate\Support\Facades\DB;

class AdjustmentService implements AdjustmentServiceInterface
{
    public function __construct(
        protected AdjustmentRepositoryInterface $repository,
        protected PeriodLockService $periodLockService,
        protected AuditService $auditService,
        protected AdjustmentPeriodServiceInterface $periodService,
        protected AdjustmentMutationServiceInterface $mutationService
    ) {}

    // ——— Lookups (pure delegate) ———
    public function getActiveMaterials(): array
    {
        return $this->repository->getActiveMaterials();
    }

    public function getActiveMaterialWhx(): array
    {
        return $this->repository->getActiveMaterialWhx();
    }

    public function getActiveTanks(mixed $plantId): array
    {
        return $this->repository->getActiveTanks($plantId);
    }

    public function getActiveSpecificTanks(int $sloc): array
    {
        return $this->repository->getActiveSpecificTanks($sloc);
    }

    public function getActiveWhx(): array
    {
        return $this->repository->getActiveWhx();
    }

    public function getLockStatus(string $entryDate): array
    {
        return $this->repository->getLockStatus($entryDate);
    }

    public function getSupplierByFilter(int $idMaterial, int $idTank): array
    {
        return $this->repository->getSupplierByFilter($idMaterial, $idTank);
    }

    public function getBatchBySupplier(int $idMaterial, int $idTank, int $idSupplier): array
    {
        return $this->repository->getBatchBySupplier($idMaterial, $idTank, $idSupplier);
    }

    // ——— Mutations with audit ———
    public function storeAdjustment(string $user, array $data, mixed $plantId): array
    {
        return $this->mutationService->storeAdjustment($user, $data, $plantId);
    }

    public function destroyAdjustment(int $id, string $user): array
    {
        return $this->mutationService->destroyAdjustment($id, $user);
    }

    public function addEntrySupplier(string $user, array $data, mixed $plantId): array
    {
        return $this->mutationService->addEntrySupplier($user, $data, $plantId);
    }

    public function deleteSupplierTemp(int $id): array
    {
        return $this->mutationService->deleteSupplierTemp($id);
    }

    public function adjustmentInit(string $user, array $data, mixed $plantId): array
    {
        return $this->mutationService->adjustmentInit($user, $data, $plantId);
    }

    public function adjustmentSupplier(string $user, array $data, mixed $plantId): array
    {
        return $this->mutationService->adjustmentSupplier($user, $data, $plantId);
    }

    public function adjustMaterialDocument(int $idAdjustHead, ?string $materialDoc, string $user): array
    {
        return $this->mutationService->adjustMaterialDocument($idAdjustHead, $materialDoc, $user);
    }

    // ——— Existing methods follow ———
    public function getAdjustmentList(mixed $plantId, ?int $userId = null, string $adjType = 'wip', array $filters = []): array
    {
        return $this->repository->getAdjustmentList($plantId, $userId, $adjType, $filters);
    }

    public function getSupplierList(array $data, ?int $userId = null): array
    {
        return $this->repository->getSupplierList($data, $userId);
    }

    public function getTotalQtySupplier(array $data, ?int $userId = null): ?float
    {
        return $this->repository->getTotalQtySupplier($data, $userId);
    }

    public function getActiveSuppliers(string $search, ?int $userId = null): array
    {
        return $this->repository->getActiveSuppliers($search, $userId);
    }

    public function generateEntryNo(?string $entryDate, mixed $plantId): ?string
    {
        return $this->repository->generateEntryNo($entryDate, $plantId);
    }

    public function createAdjustmentHeader(string $user, array $data, mixed $plantId): array
    {
        return $this->mutationService->createAdjustmentHeader($user, $data, $plantId);
    }

    public function createAdjustmentDetail(string $user, int $headerId, array $data): array
    {
        return $this->mutationService->createAdjustmentDetail($user, $headerId, $data);
    }

    public function approveAdjustment(string $user, int $headerId, int $status): array
    {
        return $this->mutationService->approveAdjustment($user, $headerId, $status);
    }

    public function executeAdjustment(string $user, int $headerId): array
    {
        return $this->mutationService->executeAdjustment($user, $headerId);
    }

    public function cancelAdjustment(string $user, int $headerId, string $reason): array
    {
        return $this->mutationService->cancelAdjustment($user, $headerId, $reason);
    }

    public function getAdjustmentDetail(int $headerId): ?array
    {
        return $this->repository->getAdjustmentDetail($headerId);
    }

    // ═══════════════════════════════════════════════════════════
    //  Period + WHX — delegasi ke AdjustmentPeriodService
    // ═══════════════════════════════════════════════════════════

    public function getPeriodHeaders(): array
    {
        return $this->periodService->getPeriodHeaders();
    }

    public function getPeriodViewData(int $idHead): array
    {
        return $this->periodService->getPeriodViewData($idHead);
    }

    public function periodHeadersUpload(string $user, array $data, $file): array
    {
        return $this->periodService->periodHeadersUpload($user, $data, $file);
    }

    public function periodViewOnHand(string $user, int $idHead): array
    {
        return $this->periodService->periodViewOnHand($user, $idHead);
    }

    public function periodViewAdjustment(string $user, int $idHead): array
    {
        return $this->periodService->periodViewAdjustment($user, $idHead);
    }

    public function periodHeaderLock(string $user, int $idHead): array
    {
        return $this->periodService->periodHeaderLock($user, $idHead);
    }

    public function destroyAdjustmentPeriod(int $id, string $user): array
    {
        return $this->periodService->destroyAdjustmentPeriod($id, $user);
    }

    public function getLastAdjustmentRecord(mixed $plantId): array
    {
        return $this->periodService->getLastAdjustmentRecord($plantId);
    }

    public function storeAdjustmentWhx(string $user, array $data, mixed $plantId): array
    {
        return $this->periodService->storeAdjustmentWhx($user, $data, $plantId);
    }

    public function adjustmentInitWhx(string $user, array $data, mixed $plantId): array
    {
        return $this->periodService->adjustmentInitWhx($user, $data, $plantId);
    }

    public function getAdjustStatus(?string $adjustNo, ?int $idAdjustHead): array
    {
        return $this->periodService->getAdjustStatus($adjustNo, $idAdjustHead);
    }
}
