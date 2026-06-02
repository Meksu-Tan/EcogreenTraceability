<?php declare(strict_types=1);
namespace Modules\TsTransfer\Services\Contracts;

interface TransferServiceInterface
{
    public function getActiveMaterials();
    public function generateEntryNo(int $materialId, int $plantId): ?string;
    public function getTotalStockMaterial(int $materialId, int $tankId, int $plantId): float;
    public function getTransferList(int $plantId);
    public function getActiveTanksRundown(?int $materialId, int $plantId);
    public function getActiveSpecificTanksRundown(int $sloc);
    public function getUpdateSupplierMaterial(int $idMaterial, int $idTank, int $plantId): ?object;
    public function createMaterialDocument(string $user, int $idTraceHead, string $materialDoc, string $mode): array;
    public function updateEntrySubTank(string $user, int $idHead, array $tails): array;
    public function deactivateTransfer(string $id, string $user): array;
    public function executeTransfer(string $user, array $data, int $plantId): array;
    public function executeTransferWithAdjustment(string $user, array $data, int $plantId): array;
}