<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
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
            return response()->json([
                'status'  => 0,
                'message' => 'Email atau password salah, atau akun Anda tidak aktif.',
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => 1,
            'message' => 'Login berhasil.',
            'token'   => $token,
            'data'    => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'id_plant'    => $user->id_plant,
                'roles'       => $user->getRoleNames()->push($user->role)->unique()->filter()->values(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }

    /**
     * POST /api/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 1,
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * GET /api/user
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => 1,
            'data'   => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'id_plant'    => $user->id_plant,
                'roles'       => $user->getRoleNames()->push($user->role)->unique()->filter()->values(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }
}
