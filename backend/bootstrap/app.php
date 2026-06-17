<?php declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // $middleware->statefulApi(); // Removed to use stateless token auth

        // Register Plant Context Middleware aliases
        $middleware->alias([
            'plant.context' => \Modules\Shared\Http\Middleware\PlantContextMiddleware::class,
            'plant.scope' => \Modules\Shared\Http\Middleware\PlantScopeMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (!($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return \App\Helpers\ApiResponse::error('Unauthenticated.', 401);
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return \App\Helpers\ApiResponse::error(
                    $e->getMessage(),
                    422,
                    $e->errors()
                );
            }

            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return \App\Helpers\ApiResponse::error('This action is unauthorized.', 403);
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return \App\Helpers\ApiResponse::error('The requested resource was not found.', 404);
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                return \App\Helpers\ApiResponse::error($e->getMessage() ?: 'HTTP error.', $e->getStatusCode());
            }

            return \App\Helpers\ApiResponse::error($e->getMessage(), 500);
        });
    })->create();
