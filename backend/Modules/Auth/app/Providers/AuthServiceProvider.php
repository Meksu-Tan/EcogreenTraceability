<?php declare(strict_types=1);
namespace Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Modules\Auth\Repositories\AuthRepository;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->singleton(\Modules\Auth\Services\Contracts\AuthServiceInterface::class, \Modules\Auth\Services\AuthService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
