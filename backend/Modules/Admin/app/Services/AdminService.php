<?php declare(strict_types=1);
namespace Modules\Admin\Services;

use Modules\Admin\Repositories\Contracts\AdminRepositoryInterface;

use Modules\Admin\Services\Contracts\AdminServiceInterface;

class AdminService implements AdminServiceInterface
{
    public function __construct(
        protected AdminRepositoryInterface $adminRepository
    ) {}

    public function listUsers(): array
    {
        return $this->adminRepository->getAllUsers();
    }

    public function createUser(array $data): object
    {
        return $this->adminRepository->createUser($data);
    }

    public function updateUser(int $id, array $data): bool
    {
        return $this->adminRepository->updateUser($id, $data);
    }

    public function deleteUser(int $id): bool
    {
        return $this->adminRepository->deleteUser($id);
    }

    public function findUserById(int $id): ?object
    {
        return $this->adminRepository->findUserById($id);
    }

    public function listRoles(): array
    {
        return $this->adminRepository->getAllRoles();
    }
}
