<?php

namespace Modules\Admin\Repositories\Contracts;

interface AdminRepositoryInterface
{
    public function getAllUsers(): array;
    public function findUserById(int $id): ?object;
    public function createUser(array $data): object;
    public function updateUser(int $id, array $data): bool;
    public function deleteUser(int $id): bool;
    public function getAllRoles(): array;
}
