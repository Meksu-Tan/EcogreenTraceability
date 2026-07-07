<?php

declare(strict_types=1);

namespace Modules\Tank\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Tank\Repositories\Contracts\TankRepositoryInterface;
use Modules\Tank\Repositories\Contracts\WarehouseRepositoryInterface;
use Modules\Tank\Repositories\EloquentWarehouseRepository;
use Modules\Tank\Repositories\TankRepository;
use Modules\Tank\Services\Contracts\TankServiceInterface;
use Modules\Tank\Services\TankService;

class TankServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TankRepositoryInterface::class, TankRepository::class);
        $this->app->bind(WarehouseRepositoryInterface::class, EloquentWarehouseRepository::class);
        $this->app->singleton(TankServiceInterface::class, TankService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
