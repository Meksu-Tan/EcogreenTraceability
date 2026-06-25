<?php
declare(strict_types=1);
namespace Modules\Material\Repositories\Contracts;

interface MaterialRepositoryInterface
{
    public function getAll(?string $type = null): array;
    public function findById(int $id): ?object;
    public function create(array $data): bool;
    public function update(int $id, array $data): bool;
    public function deactivate(int $id, string $user): bool;
    public function activate(int $id, string $user): bool;

    // Packaging
    public function getAllPackagings(): array;
    public function findPackagingById(int $id): ?object;
    public function createPackaging(array $data): bool;
    public function updatePackaging(int $id, array $data): bool;
    public function deactivatePackaging(int $id, string $user): bool;
    public function activatePackaging(int $id, string $user): bool;
    public function getActiveSourceProducts(): array;
}
