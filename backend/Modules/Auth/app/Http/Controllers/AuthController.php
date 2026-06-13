<?php declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Services\Contracts\AuthServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthServiceInterface $authService
    ) {}

    /**
     * POST /api/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if (!$result['status']) {
            return ApiResponse::error($result['message'], 401);
        }

        return ApiResponse::success(
            $result['data'],
            'Login successful.',
            200,
            ['token' => $result['token']]
        );
    }

    /**
     * POST /api/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return ApiResponse::success(null, 'Logout successful.');
    }

    /**
     * GET /api/user
     */
    public function user(Request $request): JsonResponse
    {
        $result = $this->authService->getUserData($request->user());

        return ApiResponse::success($result['data']);
    }
}
