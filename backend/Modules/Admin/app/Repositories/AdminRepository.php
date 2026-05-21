<?php

namespace Modules\Admin\Repositories;

use App\Models\User;
use Modules\Admin\Repositories\Contracts\AdminRepositoryInterface;
use Spatie\Permission\Models\Role;

class AdminRepository implements AdminRepositoryInterface
{
    public function getAllUsers(): array
    {
        return User::with('roles')->get()->toArray();
    }

    public function findUserById(int $id): ?object
    {
        return User::with('roles')->find($id);
    }

    public function createUser(array $data): object
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
            'id_plant' => $data['id_plant'] ?? null,
        ]);

        if (!empty($data['role'])) {
            $user->assignRole($data['role']);
        }

        return $user;
    }

    public function updateUser(int $id, array $data): bool
    {
        $user = User::find($id);
        if (!$user) return false;

        $updateData = [
            'name'     => $data['name'],
            'email'    => $data['email'],
            'id_plant' => $data['id_plant'] ?? $user->id_plant,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = bcrypt($data['password']);
        }

        $user->update($updateData);

        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return true;
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
