<?php declare(strict_types=1);
namespace Modules\Admin\Services\Contracts;

interface AdminServiceInterface
{
    public function listUsers(): array;
    public function createUser(array $data): object;
    public function updateUser(int $id, array $data): bool;
    public function deleteUser(int $id): bool;
    public function findUserById(int $id): ?object;
    public function listRoles(): array;
}
