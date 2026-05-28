<?php declare(strict_types=1);
namespace Modules\Storage\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Storage\Repositories\Contracts\StorageTankRepositoryInterface;
use Modules\Storage\Repositories\Contracts\StorageWarehouseRepositoryInterface;
use Modules\Storage\Repositories\StorageTankRepository;
use Modules\Storage\Repositories\StorageWarehouseRepository;

class StorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StorageTankRepositoryInterface::class, StorageTankRepository::class);
        $this->app->bind(StorageWarehouseRepositoryInterface::class, StorageWarehouseRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}