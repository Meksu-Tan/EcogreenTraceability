<?php declare(strict_types=1);
namespace Modules\TsRaw\Services\Contracts;

interface RmEntryServiceInterface
{
    public function getRmList($plantId);
    public function getRmEntryById($id);
    public function generateRmNumber($plantId);
    public function generateTransferNumber($plantId);
    public function getTanks($plantId);
    public function getTankDetails($tankId, $plantId);
    public function getMaterials();
    public function searchSuppliers($query);
    public function addSupplierTemp($data, $user);
    public function getSupplierList($entryNo);
    public function deleteSupplierTemp($id, $user);
    public function getTotalQtyTemp($entryNo);
    public function generateBatchCode($supplierId);
    public function saveRmEntry($data, $user);
    public function saveRmTrfEntry($data, $user);
    public function checkStockSynchronization($entryNo, $materialId = null);
    public function debugFifoStock($params);
    public function verifySeparateEntries($materialId, $tankId, $plantId, $hoursBack = 24);
    public function deactivateRmEntry($id, $user);
    public function updateRmEntry($id, $data, $user);
    public function getStorageLog($plantId);
    public function getFeedLog($plantId);
    public function debugFeedLog($plantId);
    public function getTransferList($plantId);
    public function transfer($data, $user);
    public function deactivateTransfer($id, $user);
    public function getDestTanksList($plantId): array;
    public function searchSuppliersList(string $search): array;
}
