<?php declare(strict_types=1);

namespace Modules\Admin\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\Admin\Http\Requests\StoreUserRequest;
use Modules\Admin\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * GET /api/v1/admin/users
     */
    public function index(): JsonResponse
    {
        // Fetch users along with their roles
        $users = User::with('roles')->orderBy('name')->get();
        return ApiResponse::success($users, 'OK', 200);
    }

    /**
     * POST /api/v1/admin/users
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        // Eager load roles for return payload
        $user->load('roles');

        return ApiResponse::success($user, 'User berhasil ditambahkan.', 201);
    }

    /**
     * PUT /api/v1/admin/users/{id}
     */
    public function update(UpdateUserRequest $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $validated = $request->validated();

        $user->name  = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Sync roles (replaces any old roles with the new one)
        $user->syncRoles([$validated['role']]);

        $user->load('roles');

        return ApiResponse::success($user, 'User berhasil diperbarui.', 200);
    }

    /**
     * DELETE /api/v1/admin/users/{id}
     */
    public function destroy($id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Optional safety check: Prevent deleting yourself
        if (auth()->id() == $user->id) {
            return ApiResponse::error('Tidak dapat menghapus akun Anda sendiri.', 403);
        }

        $user->delete();

        return ApiResponse::success(null, 'User berhasil dihapus.', 200);
    }
}
