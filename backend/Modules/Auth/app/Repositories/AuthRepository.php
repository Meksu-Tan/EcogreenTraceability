<?php declare(strict_types=1);
namespace Modules\Auth\Repositories;

use App\Models\User;
use App\Support\DbConnectionRetry;
use Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AuthRepository implements AuthRepositoryInterface
{
    public function findByEmail(string $email): ?object
    {
        return DbConnectionRetry::execute(
            fn() => User::where('email', $email)->first()
        );
    }

    public function createToken(object $user, string $name = 'auth-token'): string
    {
        return $user->createToken($name)->plainTextToken;
    }

    public function revokeCurrentToken(object $user): void
    {
        $user->currentAccessToken()->delete();
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
        return Role::all()->toArray();
    }

    public function getAllPermissions(): array
    {
        return Permission::all()->toArray();
    }
}
