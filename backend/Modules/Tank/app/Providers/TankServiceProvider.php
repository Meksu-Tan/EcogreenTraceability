<?php

namespace Modules\Tank\Providers;

use Illuminate\Support\ServiceProvider;

class TankServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Modules\Tank\Repositories\Contracts\TankRepositoryInterface::class,
            \Modules\Tank\Repositories\TankRepository::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
