<?php

declare(strict_types=1);

use App\Helpers\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Modules\Shared\Http\Middleware\PlantScopeMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // $middleware->statefulApi(); // Removed to use stateless token auth

        // Register Plant Context Middleware aliases
        $middleware->alias([
            'plant.context' => PlantContextMiddleware::class,
            'plant.scope' => PlantScopeMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponse::error('Unauthenticated.', 401);
            }

            if ($e instanceof ValidationException) {
                return ApiResponse::error(
                    $e->getMessage(),
                    422,
                    $e->errors()
                );
            }

            if ($e instanceof AuthorizationException) {
                return ApiResponse::error('This action is unauthorized.', 403);
            }

            if ($e instanceof NotFoundHttpException) {
                return ApiResponse::error('The requested resource was not found.', 404);
            }

            if ($e instanceof ModelNotFoundException) {
                return ApiResponse::error('The requested resource was not found.', 404);
            }

            if ($e instanceof ThrottleRequestsException) {
                return ApiResponse::error('Too many requests. Please slow down.', 429);
            }

            if ($e instanceof HttpException) {
                return ApiResponse::error($e->getMessage() ?: 'HTTP error.', $e->getStatusCode());
            }

            Log::error('Unhandled exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response = ApiResponse::error($e->getMessage(), 500);

            $origin = $request->headers->get('Origin');
            if ($origin) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            }

            return $response;
        });
    })->create();
