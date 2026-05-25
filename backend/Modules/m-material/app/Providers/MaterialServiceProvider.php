<?php

namespace Modules\Material\Providers;

use Illuminate\Support\ServiceProvider;

class MaterialServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Modules\Material\Repositories\Contracts\MaterialRepositoryInterface::class,
            \Modules\Material\Repositories\MaterialRepository::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
