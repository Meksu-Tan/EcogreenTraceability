<?php
declare(strict_types=1);
namespace Modules\Material\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Material\Repositories\Contracts\MaterialRepositoryInterface;
use Modules\Material\Repositories\MaterialRepository;
use Modules\Material\Services\Contracts\MaterialServiceInterface;
use Modules\Material\Services\MaterialService;

class MaterialServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MaterialRepositoryInterface::class, MaterialRepository::class);
        $this->app->singleton(MaterialServiceInterface::class, MaterialService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
