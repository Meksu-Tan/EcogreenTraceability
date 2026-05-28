<?php declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\Auth\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * POST /api/login
     * Cookie-based Sanctum SPA auth — no token returned
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password, 'isActive' => 1])) {
            return ApiResponse::error('Email atau password salah, atau akun Anda tidak aktif.', 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'id_plant'    => $user->id_plant,
            'roles'       => $user->getRoleNames()->push($user->role)->unique()->filter()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ], 'Login berhasil.', 200, ['token' => $token]);
    }

    /**
     * POST /api/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logout berhasil.');
    }

    /**
     * GET /api/user
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success([
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'id_plant'    => $user->id_plant,
            'roles'       => $user->getRoleNames()->push($user->role)->unique()->filter()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}
