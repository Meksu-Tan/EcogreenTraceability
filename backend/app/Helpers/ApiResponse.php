<?php declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'Success', int $code = 200, array $extra = []): JsonResponse
    {
        $payload = [
            'status' => 1,
            'message' => $message,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        // Merge any extra fields (e.g., token for auth)
        foreach ($extra as $key => $value) {
            $payload[$key] = $value;
        }

        return response()->json($payload, $code);
    }

    public static function error(string $message = 'Error', int $code = 422, mixed $errors = null): JsonResponse
    {
        $payload = [
            'status' => 0,
            'message' => $message,
        ];
        if ($errors !== null) {
            $payload['errors'] = $errors;
        }
        return response()->json($payload, $code);
    }
}
