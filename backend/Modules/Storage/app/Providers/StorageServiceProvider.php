<?php

namespace Modules\Storage\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Storage\Repositories\Contracts\StorageRepositoryInterface;
use Modules\Storage\Repositories\StorageRepository;

class StorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StorageRepositoryInterface::class, StorageRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
