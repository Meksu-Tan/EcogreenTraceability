<?php

declare(strict_types=1);

namespace Modules\Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Admin\Repositories\AdminRepositoryInterface;
use Modules\Admin\Repositories\EloquentAdminRepository;
use Modules\Admin\Services\AdminService;
use Modules\Admin\Services\Contracts\AdminServiceInterface;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AdminRepositoryInterface::class, EloquentAdminRepository::class);
        $this->app->singleton(AdminServiceInterface::class, AdminService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
