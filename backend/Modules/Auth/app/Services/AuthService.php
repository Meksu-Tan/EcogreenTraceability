<?php declare(strict_types=1);
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

        if (!$user || !Hash::check($credentials['password'], $user->password) || !$user->isActive) {
            return [
                'status'  => 0,
                'message' => 'Invalid email or password, or your account is inactive.',
            ];
        }

        $token = $this->authRepository->createToken($user, 'auth_token');
        $userData = $this->authRepository->getUserWithPermissions($user);

        return [
            'status' => 1,
            'token'  => $token,
            'data'   => $this->buildUserPayload($userData),
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
            'data'   => $this->buildUserPayload($userData),
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

    private function buildUserPayload(object $user): array
    {
        $roles = $user->getRoleNames()
            ->push($user->role)
            ->unique()
            ->filter()
            ->values();

        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'id_plant'    => $user->id_plant,
            'roles'       => $roles,
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'plants'      => $user->plants()->get(['m_plant.id_plant', 'm_plant.code_3', 'm_plant.description'])->toArray(),
        ];
    }
}
