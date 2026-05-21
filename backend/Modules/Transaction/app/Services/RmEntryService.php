<?php

namespace Modules\Transaction\Services;

use Modules\Transaction\Repositories\Contracts\RmEntryRepositoryInterface;
use Modules\Plant\Models\Plant;
use Exception;

class RmEntryService
{
    public function __construct(
        protected RmEntryRepositoryInterface $rmEntryRepo
    ) {}

    public function getRmList($plantId)
    {
        return $this->rmEntryRepo->getRmList($plantId);
    }

    public function generateRmNumber($plantId)
    {
        return $this->rmEntryRepo->getNewNumber($plantId);
    }

    public function generateTransferNumber($plantId)
    {
        return $this->rmEntryRepo->getTransferNumber($plantId);
    }

    public function getTanks($plantId)
    {
        return $this->rmEntryRepo->getTanks($plantId);
    }

    public function getTankDetails($tankId, $plantId)
    {
        return $this->rmEntryRepo->getTankDetails($tankId, $plantId);
    }

    public function getMaterials()
    {
        return $this->rmEntryRepo->getMaterials();
    }

    public function searchSuppliers($query)
    {
        return $this->rmEntryRepo->searchSuppliers($query);
    }

    public function addSupplierTemp($data, $user)
    {
        return $this->rmEntryRepo->addSupplierTemp($data, $user);
    }

    public function getSupplierList($entryNo)
    {
        return $this->rmEntryRepo->getSupplierList($entryNo);
    }

    public function deleteSupplierTemp($id, $user)
    {
        return $this->rmEntryRepo->deleteSupplierTemp($id, $user);
    }

    public function getTotalQtyTemp($entryNo)
    {
        return $this->rmEntryRepo->getTotalQtyTemp($entryNo);
    }

    public function generateBatchCode($supplierId)
    {
        return $this->rmEntryRepo->generateBatchCode($supplierId);
    }

    public function saveRmEntry($data, $user)
    {
        return $this->rmEntryRepo->saveRmEntry($data, $user);
    }

    public function saveRmTrfEntry($data, $user)
    {
        return $this->rmEntryRepo->saveRmTrfEntry($data, $user);
    }

    public function checkStockSynchronization($entryNo, $materialId = null)
    {
        return $this->rmEntryRepo->checkStockSynchronization($entryNo, $materialId);
    }

    public function debugFifoStock($params)
    {
        return $this->rmEntryRepo->debugFifoStock($params);
    }

    public function verifySeparateEntries($materialId, $tankId, $plantId, $hoursBack = 24)
    {
        return $this->rmEntryRepo->verifySeparateEntries($materialId, $tankId, $plantId, $hoursBack);
    }

    public function deactivateRmEntry($id, $user)
    {
        return $this->rmEntryRepo->deactivateRmEntry($id, $user);
    }
}
