<?php
declare(strict_types=1);
namespace Modules\Admin\Repositories;

use App\Models\User;
use Modules\Admin\Repositories\Contracts\AdminRepositoryInterface;
use Spatie\Permission\Models\Role;

class AdminRepository implements AdminRepositoryInterface
{
    public function getAllUsers(): array
    {
        return User::with(['roles', 'plants'])->get()->toArray();
    }

    public function findUserById(int $id): ?object
    {
        return User::with(['roles', 'plants'])->find($id);
    }

    public function createUser(array $data): object
    {
        return \DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => $data['password'],
                'id_plant' => $data['plants'][0] ?? null,
            ]);

            if (!empty($data['role'])) {
                $user->assignRole($data['role']);
            }

            if (!empty($data['plants'])) {
                $user->plants()->sync($data['plants']);
            }

            return $user;
        });
    }

    public function updateUser(int $id, array $data): bool
    {
        return \DB::transaction(function () use ($id, $data) {
            $user = User::find($id);
            if (!$user) return false;

            $updateData = [
                'name'     => $data['name'],
                'email'    => $data['email'],
            ];

            if (!empty($data['password'])) {
                $updateData['password'] = $data['password'];
            }

            $user->update($updateData);

            if (!empty($data['role'])) {
                $user->syncRoles([$data['role']]);
            }

            if (isset($data['plants'])) {
                $user->plants()->sync($data['plants']);
                if (!empty($data['plants'])) {
                    $user->update(['id_plant' => $data['plants'][0]]);
                } else {
                    $user->update(['id_plant' => null]);
                }
            }

            return true;
        });
    }

    public function deleteUser(int $id): bool
    {
        return User::destroy($id) > 0;
    }

    public function getAllRoles(): array
    {
        return Role::all()->toArray();
    }
}
