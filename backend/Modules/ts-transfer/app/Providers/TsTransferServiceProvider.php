<?php

declare(strict_types=1);

namespace Modules\TsTransfer\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\TsTransfer\Policies\TransferPolicy;
use Modules\TsTransfer\Repositories\EloquentTransferApprovalRepository;
use Modules\TsTransfer\Repositories\EloquentTransferRepository;
use Modules\TsTransfer\Repositories\TransferApprovalRepositoryInterface;
use Modules\TsTransfer\Repositories\TransferRepositoryInterface;
use Modules\TsTransfer\Services\Contracts\TransferServiceInterface;
use Modules\TsTransfer\Services\TransferApprovalService;
use Modules\TsTransfer\Services\TransferService;

class TsTransferServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TransferRepositoryInterface::class, EloquentTransferRepository::class);
        $this->app->bind(TransferApprovalRepositoryInterface::class, EloquentTransferApprovalRepository::class);
        $this->app->singleton(TransferApprovalService::class, TransferApprovalService::class);
        $this->app->singleton(TransferServiceInterface::class, TransferService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');

        Gate::define('transfer.view', [new TransferPolicy, 'viewAny']);
        Gate::define('transfer.create', [new TransferPolicy, 'create']);
        Gate::define('transfer.approve', [new TransferPolicy, 'approve']);
        Gate::define('transfer.reject', [new TransferPolicy, 'reject']);
        Gate::define('transfer.cancel', [new TransferPolicy, 'cancel']);
    }
}
