<?php declare(strict_types=1);
namespace Modules\TsTransfer\Services\Contracts;

interface TransferServiceInterface
{
    public function getActiveMaterials(): array;
    public function generateEntryNo(int $materialId, int $plantId): ?string;
    public function getTotalStockMaterial(int $materialId, int $tankId, int $plantId): float;
    public function getTransferList(int $plantId, int $page = 1, int $perPage = 5): array;
    public function getActiveTanksRundown(?int $materialId, int $plantId, bool $excludePlant = true): array;
    public function getActiveSpecificTanksRundown(int $sloc): array;
    public function getUpdateSupplierMaterial(int $idMaterial, int $idTank, int $plantId): ?object;
    public function createMaterialDocument(string $user, int $idTraceHead, string $materialDoc, string $mode): array;
    public function updateEntrySubTank(string $user, int $idHead, array $tails): array;
    public function deactivateTransfer(string $id, string $user): array;
    public function executeTransfer(string $user, array $data, int $plantId): array;
    public function executeTransferWithAdjustment(string $user, array $data, int $plantId): array;

    // ========== APPROVAL WORKFLOW METHODS ==========
    public function submitForApproval(string $idBalanceHead, string $user): array;
    public function approveTransfer(string $idBalanceHead, string $user, ?string $notes = null): array;
    public function rejectTransfer(string $idBalanceHead, string $user, string $reason): array;
    public function cancelTransfer(string $idBalanceHead, string $user): array;
    public function getPendingApprovals(int $plantId = 0): array;
    public function getApprovalHistory(string $idBalanceHead): array;
}