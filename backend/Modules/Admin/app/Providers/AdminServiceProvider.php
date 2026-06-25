<?php
declare(strict_types=1);
namespace Modules\Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Admin\Repositories\Contracts\AdminRepositoryInterface;
use Modules\Admin\Repositories\AdminRepository;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AdminRepositoryInterface::class, AdminRepository::class);
        $this->app->singleton(\Modules\Admin\Services\Contracts\AdminServiceInterface::class, \Modules\Admin\Services\AdminService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
