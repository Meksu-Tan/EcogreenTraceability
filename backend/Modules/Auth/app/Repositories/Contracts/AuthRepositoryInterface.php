<?php declare(strict_types=1);
namespace Modules\Auth\Repositories\Contracts;

interface AuthRepositoryInterface
{
    public function findByEmail(string $email): ?object;
    public function createToken(object $user, string $name = 'auth-token'): string;
    public function revokeCurrentToken(object $user): void;
    public function getUserWithPermissions(object $user): object;
    public function getAllRoles(): array;
    public function getAllPermissions(): array;
}
