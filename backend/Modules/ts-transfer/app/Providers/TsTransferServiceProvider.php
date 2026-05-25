<?php

namespace Modules\TsTransfer\Providers;

use Illuminate\Support\ServiceProvider;

class TsTransferServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Modules\TsTransfer\Repositories\Contracts\TransferRepositoryInterface::class,
            \Modules\TsTransfer\Repositories\TransferRepository::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
