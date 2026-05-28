<?php declare(strict_types=1);
namespace Modules\Plant\Providers;

use Illuminate\Support\ServiceProvider;

class PlantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Modules\Plant\Repositories\Contracts\PlantRepositoryInterface::class,
            \Modules\Plant\Repositories\PlantRepository::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
