<?php

namespace App\Contracts\Storage;

interface StorageRepositoryInterface
{
    // Storage Tank
    public function getAllTanks(): array;
    public function findTankById(int $id): ?object;
    public function createTank(array $data): bool;
    public function updateTank(int $id, array $data): bool;
    public function deactivateTank(int $id, string $user): bool;
    public function activateTank(int $id, string $user): bool;

    // Storage Detail
    public function getDetailsByTank(int $tankId): array;
    public function findDetailById(int $id): ?object;
    public function createDetail(array $data): bool;
    public function updateDetail(int $id, array $data): bool;
    public function deactivateDetail(int $id, string $user): bool;
    public function activateDetail(int $id, string $user): bool;

    // Warehouse
    public function getAllWarehouses(): array;
    public function findWarehouseById(int $id): ?object;
    public function createWarehouse(array $data): bool;
    public function updateWarehouse(int $id, array $data): bool;
    public function deactivateWarehouse(int $id, string $user): bool;
    public function activateWarehouse(int $id, string $user): bool;
}
