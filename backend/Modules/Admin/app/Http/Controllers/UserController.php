<?php declare(strict_types=1);

namespace Modules\Admin\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\Admin\Http\Requests\StoreUserRequest;
use Modules\Admin\Http\Requests\UpdateUserRequest;
use Modules\Admin\Services\Contracts\AdminServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
        return ApiResponse::success($user, 'User successfully added.', 201);
    }

    /**
     * PUT /api/v1/admin/users/{id}
     */
    public function update(UpdateUserRequest $request, $id): JsonResponse
    {
        $success = $this->adminService->updateUser((int)$id, $request->validated());
        if (!$success) {
            return ApiResponse::error('User not found.', 404);
        }

        $user = $this->adminService->findUserById((int)$id);
        return ApiResponse::success($user, 'User successfully updated.', 200);
    }

    /**
     * DELETE /api/v1/admin/users/{id}
     */
    /**
     * TOCTOU NOTE: findUserById() then deleteUser() is a read-then-delete
     * pattern with a minimal race window in a single-request context. The
     * deleteUser() method performs its own findOrFail internally, which
     * mitigates the issue at the DB level. A future refactor could merge
     * the two calls into a single atomic operation (e.g. deleteOrFail).
     */
    public function destroy($id): JsonResponse
    {
        $user = $this->adminService->findUserById((int)$id);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        if (auth()->id() == $user->id) {
            return ApiResponse::error('Cannot delete your own account.', 403);
        }

        DB::transaction(function () use ($id) {
            $this->adminService->deleteUser((int)$id);
        });

        return ApiResponse::success(null, 'User successfully deleted.', 200);
    }
}
