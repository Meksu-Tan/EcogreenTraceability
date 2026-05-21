<?php

namespace Modules\Manufacturer\Providers;

use Illuminate\Support\ServiceProvider;

class ManufacturerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Modules\Manufacturer\Repositories\Contracts\ManufacturerRepositoryInterface::class,
            \Modules\Manufacturer\Repositories\ManufacturerRepository::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
