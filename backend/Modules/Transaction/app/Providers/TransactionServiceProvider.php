<?php

namespace Modules\Transaction\Providers;

use Illuminate\Support\ServiceProvider;

class TransactionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Modules\Transaction\Repositories\Contracts\RmEntryRepositoryInterface::class,
            \Modules\Transaction\Repositories\RmEntryRepository::class
        );
        $this->app->bind(
            \Modules\Transaction\Repositories\Contracts\TransferRepositoryInterface::class,
            \Modules\Transaction\Repositories\TransferRepository::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
