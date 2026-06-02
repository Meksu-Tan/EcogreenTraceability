<?php declare(strict_types=1);
namespace Modules\Storage\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Storage\Repositories\Contracts\StorageTankRepositoryInterface;
use Modules\Storage\Repositories\Contracts\StorageWarehouseRepositoryInterface;
use Modules\Storage\Repositories\StorageTankRepository;
use Modules\Storage\Repositories\StorageWarehouseRepository;
use Modules\Storage\Services\Contracts\StorageServiceInterface;
use Modules\Storage\Services\StorageService;

class StorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StorageTankRepositoryInterface::class, StorageTankRepository::class);
        $this->app->bind(StorageWarehouseRepositoryInterface::class, StorageWarehouseRepository::class);
        $this->app->singleton(StorageServiceInterface::class, StorageService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}