<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Contracts;

interface AuthServiceInterface
{
    public function login(array $credentials): array;

    public function logout(object $user): void;

    public function getUserData(object $user): array;

    public function listRoles(): array;

    public function listPermissions(): array;
}
