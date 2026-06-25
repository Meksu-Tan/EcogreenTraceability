<?php
declare(strict_types=1);
namespace Modules\Adjustment\Services;

use Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;
use Modules\Shared\Services\PeriodLockService;
use Modules\Shared\Services\AuditService;
use Modules\Adjustment\Services\Contracts\AdjustmentMutationServiceInterface;
use Modules\Shared\Helpers\ResponseCode;
use Illuminate\Support\Facades\DB;

class AdjustmentMutationService implements AdjustmentMutationServiceInterface
{
    public function __construct(
        protected AdjustmentRepositoryInterface $repository,
        protected AuditService $auditService
    ) {}

    public function storeAdjustment(string $user, array $data, mixed $plantId): array
    {
        return DB::connection('eudr_ts')->transaction(function () use ($user, $data, $plantId) {
            $result = $this->repository->storeAdjustment($user, $data, $plantId);
            if (($result['response'] ?? 0) == 1) {
                $this->auditService->logAdjustment('CREATE', $data, $user, 1);
            }
            return $result;
        });
    }

    public function destroyAdjustment(int $id, string $user): array
    {
        return DB::connection('eudr_ts')->transaction(function () use ($id, $user) {
            $result = $this->repository->destroyAdjustment($id, $user);
            if (($result['response'] ?? 0) == 1) {
                $this->auditService->logAdjustment('DELETE', ['id_adjust_head' => $id], $user, 1);
            }
            return $result;
        });
    }

    public function addEntrySupplier(string $user, array $data, mixed $plantId): array
    {
        return DB::connection('eudr_ts')->transaction(function () use ($user, $data, $plantId) {
            $result = $this->repository->addEntrySupplier($user, $data, $plantId);
            if (($result['response'] ?? 0) == 1) {
                $this->auditService->logAdjustment('ADD_SUPPLIER', $data, $user, 1);
            }
            return $result;
        });
    }

    public function deleteSupplierTemp(int $id): array
    {
        return DB::connection('eudr_ts')->transaction(function () use ($id) {
            $result = $this->repository->deleteSupplierTemp($id);
            return $result;
        });
    }

    public function adjustmentInit(string $user, array $data, mixed $plantId): array
    {
        return DB::connection('eudr_ts')->transaction(function () use ($user, $data, $plantId) {
            $result = $this->repository->adjustmentInit($user, $data, $plantId);
            if (($result['response'] ?? 0) == 1) {
                $this->auditService->logAdjustment('INIT', $data, $user, 1);
            }
            return $result;
        });
    }

    public function adjustmentSupplier(string $user, array $data, mixed $plantId): array
    {
        return DB::connection('eudr_ts')->transaction(function () use ($user, $data, $plantId) {
            $result = $this->repository->adjustmentSupplier($user, $data, $plantId);
            if (($result['response'] ?? 0) == 1) {
                $this->auditService->logAdjustment('SUPPLIER_ADJUST', $data, $user, 1);
            }
            return $result;
        });
    }

    public function adjustMaterialDocument(int $idAdjustHead, ?string $materialDoc, string $user): array
    {
        return DB::connection('eudr_ts')->transaction(function () use ($idAdjustHead, $materialDoc, $user) {
            $result = $this->repository->adjustMaterialDocument($idAdjustHead, $materialDoc, $user);
            if (($result['response'] ?? 0) == 1) {
                $this->auditService->logAdjustment('DOCUMENT', [
                    'id_adjust_head' => $idAdjustHead,
                    'material_doc' => $materialDoc,
                ], $user, 1);
            }
            return $result;
        });
    }

    public function createAdjustmentHeader(string $user, array $data, mixed $plantId): array
    {
        if (PeriodLockService::isLocked($data['entry_date'] ?? ResponseCode::FALLBACK_DATE)) {
            return ['response' => ResponseCode::PERIOD_LOCKED, 'message' => 'Period is locked'];
        }

        return DB::connection('eudr_ts')->transaction(function () use ($user, $data, $plantId) {
            $result = $this->repository->createAdjustmentHeader($user, $data, $plantId);
            if ($result['response'] == 1) {
                $this->auditService->logAdjustment('CREATE', [
                    'id_adjust_head' => $result['id'],
                    'entry_no' => $data['adjust_no'] ?? null,
                    'id_material' => $data['id_material'] ?? null,
                    'qty' => $data['after_adjust'] ?? null,
                ], $user, 1);
            }
            return $result;
        });
    }

    public function createAdjustmentDetail(string $user, int $headerId, array $data): array
    {
        return DB::connection('eudr_ts')->transaction(function () use ($user, $headerId, $data) {
            $result = $this->repository->createAdjustmentDetail($user, $headerId, $data);
            if ($result['response'] == 1) {
                $this->auditService->logAdjustment('CREATE_DETAIL', [
                    'id_adjust_head' => $headerId,
                    'id_supplier' => $data['id_supplier'] ?? null,
                    'batch_sap' => $data['batch_sap'] ?? null,
                ], $user, 1);
            }
            return $result;
        });
    }

    public function approveAdjustment(string $user, int $headerId, int $status): array
    {
        $header = $this->repository->getAdjustmentHeader($headerId);
        if (!$header) {
            return ['response' => 0, 'message' => 'Adjustment not found'];
        }
        if ($header->status != 1) {
            return ['response' => 2, 'message' => 'Adjustment already processed'];
        }

        return DB::connection('eudr_ts')->transaction(function () use ($user, $headerId, $status) {
            $result = $this->repository->approveAdjustment($headerId, $status, $user);
            if ($result['response'] == 1) {
                $action = $status == 2 ? 'APPROVE' : 'REJECT';
                $this->auditService->logAdjustment($action, ['id_adjust_head' => $headerId, 'status' => $status], $user, 1);
            }
            return $result;
        });
    }

    public function executeAdjustment(string $user, int $headerId): array
    {
        $header = $this->repository->getAdjustmentHeader($headerId);
        if (!$header) {
            return ['response' => 0, 'message' => 'Adjustment not found'];
        }
        if ($header->status != 2) {
            return ['response' => 2, 'message' => 'Only APPROVED adjustments can be executed'];
        }

        return DB::connection('eudr_ts')->transaction(function () use ($user, $headerId) {
            $result = $this->repository->executeAdjustment($headerId);
            if ($result['response'] == 1) {
                $this->auditService->logAdjustment('EXECUTE', ['id_adjust_head' => $headerId], $user, 1);
            }
            return $result;
        });
    }

    public function cancelAdjustment(string $user, int $headerId, string $reason): array
    {
        $header = $this->repository->getAdjustmentHeader($headerId);
        if (!$header) {
            return ['response' => 0, 'message' => 'Adjustment not found'];
        }
        if (!in_array($header->status, [1, 2])) {
            return ['response' => 2, 'message' => 'Cannot cancel adjustment in current status'];
        }

        return DB::connection('eudr_ts')->transaction(function () use ($user, $headerId, $reason) {
            $result = $this->repository->cancelAdjustment($headerId, $reason, $user);
            if ($result['response'] == 1) {
                $this->auditService->logAdjustment('CANCEL', ['id_adjust_head' => $headerId, 'reason' => $reason], $user, 1);
            }
            return $result;
        });
    }
}
