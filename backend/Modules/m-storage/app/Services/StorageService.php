<?php declare(strict_types=1);
namespace Modules\Storage\Services;

use Modules\Storage\Repositories\Contracts\StorageTankRepositoryInterface;
use Modules\Storage\Repositories\Contracts\StorageWarehouseRepositoryInterface;
use Modules\Storage\Services\Contracts\StorageServiceInterface;

class StorageService implements StorageServiceInterface
{
    public function __construct(
        protected StorageTankRepositoryInterface $tankRepo,
        protected StorageWarehouseRepositoryInterface $warehouseRepo
    ) {}

    public function listTanks(): array { return $this->tankRepo->getAllTanks(); }

    public function storeTank(array $data): array
    {
        $result = $this->tankRepo->createTank($data);
        return $result
            ? ['status' => 1, 'message' => 'Storage tank created successfully']
            : ['status' => 0, 'message' => 'Storage tank already exists'];
    }

    public function updateTank(int $id, array $data): array
    {
        $result = $this->tankRepo->updateTank($id, $data);
        return $result
            ? ['status' => 1, 'message' => 'Storage tank updated successfully']
            : ['status' => 0, 'message' => 'Failed to update storage tank'];
    }

    public function deactivateTank(int $id, string $user): array
    {
        return $this->tankRepo->deactivateTank($id, $user)
            ? ['status' => 1, 'message' => 'Storage tank deactivated']
            : ['status' => 0, 'message' => 'Failed to deactivate'];
    }

    public function activateTank(int $id, string $user): array
    {
        return $this->tankRepo->activateTank($id, $user)
            ? ['status' => 1, 'message' => 'Storage tank activated']
            : ['status' => 0, 'message' => 'Failed to activate'];
    }

    public function listDetails(int $tankId): array { return $this->tankRepo->getDetailsByTank($tankId); }

    public function storeDetail(array $data): array
    {
        $result = $this->tankRepo->createDetail($data);
        return $result
            ? ['status' => 1, 'message' => 'Storage detail created successfully']
            : ['status' => 0, 'message' => 'TF Number already exists'];
    }

    public function updateDetail(int $id, array $data): array
    {
        $result = $this->tankRepo->updateDetail($id, $data);
        return $result
            ? ['status' => 1, 'message' => 'Storage detail updated successfully']
            : ['status' => 0, 'message' => 'TF Number already exists or record not found'];
    }

    public function deactivateDetail(int $id, string $user): array
    {
        return $this->tankRepo->deactivateDetail($id, $user)
            ? ['status' => 1, 'message' => 'Storage detail deactivated']
            : ['status' => 0, 'message' => 'Failed to deactivate'];
    }

    public function activateDetail(int $id, string $user): array
    {
        return $this->tankRepo->activateDetail($id, $user)
            ? ['status' => 1, 'message' => 'Storage detail activated']
            : ['status' => 0, 'message' => 'Failed to activate'];
    }

    public function listWarehouses(): array { return $this->warehouseRepo->getAllWarehouses(); }

    public function storeWarehouse(array $data): array
    {
        $result = $this->warehouseRepo->createWarehouse($data);
        return $result
            ? ['status' => 1, 'message' => 'Warehouse created successfully']
            : ['status' => 0, 'message' => 'Warehouse already exists'];
    }

    public function updateWarehouse(int $id, array $data): array
    {
        $result = $this->warehouseRepo->updateWarehouse($id, $data);
        return $result
            ? ['status' => 1, 'message' => 'Warehouse updated successfully']
            : ['status' => 0, 'message' => 'Failed to update warehouse'];
    }

    public function deactivateWarehouse(int $id, string $user): array
    {
        return $this->warehouseRepo->deactivateWarehouse($id, $user)
            ? ['status' => 1, 'message' => 'Warehouse deactivated']
            : ['status' => 0, 'message' => 'Failed to deactivate'];
    }

    public function activateWarehouse(int $id, string $user): array
    {
        return $this->warehouseRepo->activateWarehouse($id, $user)
            ? ['status' => 1, 'message' => 'Warehouse activated']
            : ['status' => 0, 'message' => 'Failed to activate'];
    }
}