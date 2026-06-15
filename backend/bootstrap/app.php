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
            if ($request->is('api/*') || $request->expectsJson()) {
                return \App\Helpers\ApiResponse::error($e->getMessage(), 500);
            }
        });
    })->create();
