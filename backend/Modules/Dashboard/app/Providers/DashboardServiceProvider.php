<?php

declare(strict_types=1);

namespace Modules\Dashboard\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Dashboard\Repositories\DashboardRepositoryInterface;
use Modules\Dashboard\Repositories\EloquentDashboardRepository;
use Modules\Dashboard\Services\Contracts\DashboardServiceInterface;
use Modules\Dashboard\Services\DashboardService;

class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DashboardRepositoryInterface::class, EloquentDashboardRepository::class);
        $this->app->singleton(DashboardServiceInterface::class, DashboardService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
