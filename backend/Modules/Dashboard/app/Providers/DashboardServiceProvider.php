<?php declare(strict_types=1);
namespace Modules\Dashboard\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Dashboard\Repositories\Contracts\DashboardRepositoryInterface;
use Modules\Dashboard\Repositories\DashboardRepository;
use Modules\Dashboard\Services\Contracts\DashboardServiceInterface;
use Modules\Dashboard\Services\DashboardService;

class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->singleton(DashboardServiceInterface::class, DashboardService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
