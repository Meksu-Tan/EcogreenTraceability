<?php

declare(strict_types=1);

namespace Modules\Adjustment\Services;

use Illuminate\Support\Facades\DB;
use Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;
use Modules\Adjustment\Services\Contracts\AdjustmentPeriodServiceInterface;
use Modules\Shared\Services\Contracts\AuditServiceInterface;

class AdjustmentPeriodService implements AdjustmentPeriodServiceInterface
{
    public function __construct(
        protected AdjustmentRepositoryInterface $repository,
        protected AuditServiceInterface $auditService
    ) {}

    // â€”â€”â€” Period Adjustment â€”â€”â€”

    public function getPeriodHeaders(array $filters = []): array
    {
        return $this->repository->getPeriodHeaders($filters);
    }

    public function getPeriodViewData(int $idHead): array
    {
        return $this->repository->getPeriodViewData($idHead);
    }

    public function periodHeadersUpload(string $user, array $data, mixed $file): array
    {
        return DB::connection('eudr_ts')->transaction(
            fn () => $this->repository->periodHeadersUpload($user, $data, $file)
        );
    }

    public function periodViewOnHand(string $user, int $idHead): array
    {
        return $this->repository->periodViewOnHand($user, $idHead);
    }

    public function periodViewAdjustment(string $user, int $idHead): array
    {
        return DB::connection('eudr_ts')->transaction(
            fn () => $this->repository->periodViewAdjustment($user, $idHead)
        );
    }

    public function periodHeaderLock(string $user, int $idHead): array
    {
        return DB::connection('eudr_ts')->transaction(
            fn () => $this->repository->periodHeaderLock($user, $idHead)
        );
    }

    public function periodHeaderUnlock(string $user, int $idHead): array
    {
        return DB::connection('eudr_ts')->transaction(
            fn () => $this->repository->periodHeaderUnlock($user, $idHead)
        );
    }

    public function destroyAdjustmentPeriod(int $id, string $user): array
    {
        return DB::connection('eudr_ts')->transaction(
            fn () => $this->repository->destroyAdjustmentPeriod($id, $user)
        );
    }

    public function getLastAdjustmentRecord(mixed $plantId): array
    {
        return $this->repository->getLastAdjustmentRecord($plantId);
    }

    // â€”â€”â€” WHX (Warehouse) â€”â€”â€”

    public function storeAdjustmentWhx(string $user, array $data, mixed $plantId): array
    {
        return DB::connection('eudr_ts')->transaction(function () use ($user, $data, $plantId) {
            $result = $this->repository->storeAdjustmentWhx($user, $data, $plantId);
            if (($result['response'] ?? 0) == 1) {
                $this->auditService->logAdjustment('CREATE_WHX', $data, $user, 1);
            }

            return $result;
        });
    }

    public function adjustmentInitWhx(string $user, array $data, mixed $plantId): array
    {
        return DB::connection('eudr_ts')->transaction(function () use ($user, $data, $plantId) {
            $result = $this->repository->adjustmentInitWhx($user, $data, $plantId);
            if (($result['response'] ?? 0) == 1) {
                $this->auditService->logAdjustment('INIT_WHX', $data, $user, 1);
            }

            return $result;
        });
    }

    public function getAdjustStatus(?string $adjustNo, ?int $idAdjustHead): array
    {
        return $this->repository->getAdjustStatus($adjustNo, $idAdjustHead);
    }
}
