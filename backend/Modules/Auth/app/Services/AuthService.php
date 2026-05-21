<?php

namespace Modules\Auth\Services;

use Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Modules\Auth\Services\Contracts\AuthServiceInterface;
use Illuminate\Support\Facades\Hash;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        protected AuthRepositoryInterface $authRepository
    ) {}

    public function login(array $credentials): array
    {
        $user = $this->authRepository->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return [
                'status'  => 0,
                'message' => 'Email atau password salah.',
            ];
        }

        $token = $this->authRepository->createToken($user);
        $userData = $this->authRepository->getUserWithPermissions($user);

        return [
            'status' => 1,
            'token'  => $token,
            'data'   => [
                'id'          => $userData->id,
                'name'        => $userData->name,
                'email'       => $userData->email,
                'id_plant'    => $userData->id_plant,
                'roles'       => $userData->getRoleNames(),
                'permissions' => $userData->getAllPermissions()->pluck('name'),
            ],
        ];
    }

    public function logout(object $user): void
    {
        $this->authRepository->revokeCurrentToken($user);
    }

    public function getUserData(object $user): array
    {
        $userData = $this->authRepository->getUserWithPermissions($user);

        return [
            'status' => 1,
            'data'   => [
                'id'          => $userData->id,
                'name'        => $userData->name,
                'email'       => $userData->email,
                'id_plant'    => $userData->id_plant,
                'roles'       => $userData->getRoleNames(),
                'permissions' => $userData->getAllPermissions()->pluck('name'),
            ],
        ];
    }

    public function listRoles(): array
    {
        return $this->authRepository->getAllRoles();
    }

    public function listPermissions(): array
    {
        return $this->authRepository->getAllPermissions();
    }
}
