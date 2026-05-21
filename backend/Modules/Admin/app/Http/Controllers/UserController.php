<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * GET /api/v1/admin/users
     */
    public function index(): JsonResponse
    {
        // Fetch users along with their roles
        $users = User::with('roles')->orderBy('name')->get();
        return response()->json([
            'status' => 1,
            'data'   => $users,
        ]);
    }

    /**
     * POST /api/v1/admin/users
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        // Eager load roles for return payload
        $user->load('roles');

        return response()->json([
            'status'  => 1,
            'message' => 'User berhasil ditambahkan',
            'data'    => $user,
        ]);
    }

    /**
     * PUT /api/v1/admin/users/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role'     => ['required', 'string', 'exists:roles,name'],
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Sync roles (replaces any old roles with the new one)
        $user->syncRoles([$validated['role']]);

        $user->load('roles');

        return response()->json([
            'status'  => 1,
            'message' => 'User berhasil diperbarui',
            'data'    => $user,
        ]);
    }

    /**
     * DELETE /api/v1/admin/users/{id}
     */
    public function destroy($id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Optional safety check: Prevent deleting yourself
        if (auth()->id() == $user->id) {
            return response()->json([
                'status'  => 0,
                'message' => 'Tidak dapat menghapus akun Anda sendiri',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'status'  => 1,
            'message' => 'User berhasil dihapus',
        ]);
    }
}
