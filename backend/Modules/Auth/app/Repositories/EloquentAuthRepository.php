<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use App\Models\User;
use App\Support\DbConnectionRetry;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EloquentAuthRepository implements AuthRepositoryInterface
{
    public function findByEmail(string $email): ?object
    {
        return DbConnectionRetry::execute(
            fn () => User::where('email', $email)->first()
        );
    }

    public function createToken(object $user, string $name = 'auth-token'): string
    {
        return DbConnectionRetry::execute(
            fn () => $user->createToken($name)->plainTextToken
        );
    }

    public function revokeCurrentToken(object $user): void
    {
        DbConnectionRetry::execute(
            fn () => $user->currentAccessToken()?->delete()
        );
    }

    public function getUserWithPermissions(object $user): object
    {
        return DbConnectionRetry::execute(function () use ($user) {
            $user->load('roles', 'permissions');

            return $user;
        });
    }

    public function getAllRoles(): array
    {
        return DbConnectionRetry::execute(
            fn () => Role::all()->toArray()
        );
    }

    public function getAllPermissions(): array
    {
        return DbConnectionRetry::execute(
            fn () => Permission::all()->toArray()
        );
    }
}
