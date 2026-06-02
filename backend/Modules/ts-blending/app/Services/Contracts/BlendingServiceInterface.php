<?php declare(strict_types=1);
namespace Modules\TsBlending\Services\Contracts;

interface BlendingServiceInterface
{
    public function getActiveMaterials();
    public function generateEntryNo(int $materialId, int $plantId): ?string;
    public function getTotalStockMaterial(int $materialId, int $plantId): float;
    public function getTotalQtyMaterial(string $mode, string $entryNo, ?int $idHead, int $plantId): float;
    public function getMaterialList(string $mode, string $entryNo, ?int $idHead, int $plantId);
    public function getBlendingList(int $plantId);
    public function getActiveTanksRundown(int $materialId, int $plantId);
    public function getActiveSpecificTanksRundown(int $sloc);
    public function getTanks(?int $plantId = null);
    public function getTankDetails(string $tankDescription, ?int $plantId = null);
    public function getAllTanks(int $plantId);
    public function addMaterialToBlending(string $user, array $data, int $plantId): array;
    public function deleteBlendingMaterial(int $id): bool;
    public function createMaterialDocument(string $user, int $idTraceHead, string $materialDoc, string $mode): array;
    public function updateEntrySubTank(string $user, int $idHead, array $tails): array;
    public function executeBlending(string $user, array $data, int $plantId): array;
    public function deactivateBlending(string $id, string $user): array;
}