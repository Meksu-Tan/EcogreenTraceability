<?php
declare(strict_types=1);
namespace Modules\Plant\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Plant\Repositories\Contracts\PlantRepositoryInterface;
use Modules\Plant\Repositories\PlantRepository;
use Modules\Plant\Services\Contracts\PlantServiceInterface;
use Modules\Plant\Services\PlantService;

class PlantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PlantRepositoryInterface::class, PlantRepository::class);
        $this->app->singleton(PlantServiceInterface::class, PlantService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
