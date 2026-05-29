<?php declare(strict_types=1);

namespace Modules\Admin\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\Admin\Http\Requests\StoreUserRequest;
use Modules\Admin\Http\Requests\UpdateUserRequest;
use Modules\Admin\Services\Contracts\AdminServiceInterface;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        protected AdminServiceInterface $adminService
    ) {}

    /**
     * GET /api/v1/admin/users
     */
    public function index(): JsonResponse
    {
        $users = $this->adminService->listUsers();
        return ApiResponse::success($users, 'OK', 200);
    }

    /**
     * POST /api/v1/admin/users
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->adminService->createUser($request->validated());
        return ApiResponse::success($user, 'User berhasil ditambahkan.', 201);
    }

    /**
     * PUT /api/v1/admin/users/{id}
     */
    public function update(UpdateUserRequest $request, $id): JsonResponse
    {
        $success = $this->adminService->updateUser((int)$id, $request->validated());
        if (!$success) {
            return ApiResponse::error('User tidak ditemukan.', 404);
        }

        $user = $this->adminService->findUserById((int)$id);
        return ApiResponse::success($user, 'User berhasil diperbarui.', 200);
    }

    /**
     * DELETE /api/v1/admin/users/{id}
     */
    public function destroy($id): JsonResponse
    {
        $user = $this->adminService->findUserById((int)$id);
        if (!$user) {
            return ApiResponse::error('User tidak ditemukan.', 404);
        }

        if (auth()->id() == $user->id) {
            return ApiResponse::error('Tidak dapat menghapus akun Anda sendiri.', 403);
        }

        $this->adminService->deleteUser((int)$id);
        return ApiResponse::success(null, 'User berhasil dihapus.', 200);
    }
}
