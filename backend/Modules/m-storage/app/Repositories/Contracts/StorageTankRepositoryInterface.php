<?php declare(strict_types=1);
namespace Modules\Storage\Repositories\Contracts;

interface StorageTankRepositoryInterface
{
    public function getAllTanks(): array;
    public function findTankById(int $id): ?object;
    public function createTank(array $data): bool;
    public function updateTank(int $id, array $data): bool;
    public function deactivateTank(int $id, string $user): bool;
    public function activateTank(int $id, string $user): bool;

    public function getDetailsByTank(int $tankId): array;
    public function findDetailById(int $id): ?object;
    public function createDetail(array $data): bool;
    public function updateDetail(int $id, array $data): bool;
    public function deactivateDetail(int $id, string $user): bool;
    public function activateDetail(int $id, string $user): bool;
}