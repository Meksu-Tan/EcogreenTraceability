<?php

namespace Modules\TsRaw\Providers;

use Illuminate\Support\ServiceProvider;

class TsRawServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Modules\TsRaw\Repositories\Contracts\RmEntryRepositoryInterface::class,
            \Modules\TsRaw\Repositories\RmEntryRepository::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
