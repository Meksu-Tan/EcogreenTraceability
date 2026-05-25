<?php

namespace Modules\Storage\Services;

use Modules\Storage\Repositories\Contracts\StorageRepositoryInterface;

class StorageService
{
    public function __construct(
        protected StorageRepositoryInterface $storageRepo
    ) {}

    // Tank
    public function listTanks(): array { return $this->storageRepo->getAllTanks(); }

    public function storeTank(array $data): array
    {
        $result = $this->storageRepo->createTank($data);
        return $result
            ? ['status' => 1, 'message' => 'Storage tank created successfully']
            : ['status' => 0, 'message' => 'Storage tank already exists'];
    }

    public function updateTank(int $id, array $data): array
    {
        $result = $this->storageRepo->updateTank($id, $data);
        return $result
            ? ['status' => 1, 'message' => 'Storage tank updated successfully']
            : ['status' => 0, 'message' => 'Failed to update storage tank'];
    }

    public function deactivateTank(int $id, string $user): array
    {
        return $this->storageRepo->deactivateTank($id, $user)
            ? ['status' => 1, 'message' => 'Storage tank deactivated']
            : ['status' => 0, 'message' => 'Failed to deactivate'];
    }

    public function activateTank(int $id, string $user): array
    {
        return $this->storageRepo->activateTank($id, $user)
            ? ['status' => 1, 'message' => 'Storage tank activated']
            : ['status' => 0, 'message' => 'Failed to activate'];
    }

    // Detail
    public function listDetails(int $tankId): array { return $this->storageRepo->getDetailsByTank($tankId); }

    public function storeDetail(array $data): array
    {
        $result = $this->storageRepo->createDetail($data);
        return $result
            ? ['status' => 1, 'message' => 'Storage detail created successfully']
            : ['status' => 0, 'message' => 'TF Number already exists'];
    }

    public function updateDetail(int $id, array $data): array
    {
        $result = $this->storageRepo->updateDetail($id, $data);
        return $result
            ? ['status' => 1, 'message' => 'Storage detail updated successfully']
            : ['status' => 0, 'message' => 'TF Number already exists or record not found'];
    }

    public function deactivateDetail(int $id, string $user): array
    {
        return $this->storageRepo->deactivateDetail($id, $user)
            ? ['status' => 1, 'message' => 'Storage detail deactivated']
            : ['status' => 0, 'message' => 'Failed to deactivate'];
    }

    public function activateDetail(int $id, string $user): array
    {
        return $this->storageRepo->activateDetail($id, $user)
            ? ['status' => 1, 'message' => 'Storage detail activated']
            : ['status' => 0, 'message' => 'Failed to activate'];
    }

    // Warehouse
    public function listWarehouses(): array { return $this->storageRepo->getAllWarehouses(); }

    public function storeWarehouse(array $data): array
    {
        $result = $this->storageRepo->createWarehouse($data);
        return $result
            ? ['status' => 1, 'message' => 'Warehouse created successfully']
            : ['status' => 0, 'message' => 'Warehouse already exists'];
    }

    public function updateWarehouse(int $id, array $data): array
    {
        $result = $this->storageRepo->updateWarehouse($id, $data);
        return $result
            ? ['status' => 1, 'message' => 'Warehouse updated successfully']
            : ['status' => 0, 'message' => 'Failed to update warehouse'];
    }

    public function deactivateWarehouse(int $id, string $user): array
    {
        return $this->storageRepo->deactivateWarehouse($id, $user)
            ? ['status' => 1, 'message' => 'Warehouse deactivated']
            : ['status' => 0, 'message' => 'Failed to deactivate'];
    }

    public function activateWarehouse(int $id, string $user): array
    {
        return $this->storageRepo->activateWarehouse($id, $user)
            ? ['status' => 1, 'message' => 'Warehouse activated']
            : ['status' => 0, 'message' => 'Failed to activate'];
    }
}
