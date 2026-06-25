<?php
declare(strict_types=1);
namespace Modules\TsTransfer\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\TsTransfer\Repositories\Contracts\TransferApprovalRepositoryInterface;
use Modules\TsTransfer\Repositories\Contracts\TransferRepositoryInterface;
use Modules\TsTransfer\Repositories\TransferApprovalRepository;
use Modules\TsTransfer\Repositories\TransferRepository;
use Modules\TsTransfer\Services\Contracts\TransferServiceInterface;
use Modules\TsTransfer\Services\TransferApprovalService;
use Modules\TsTransfer\Services\TransferService;

class TsTransferServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TransferRepositoryInterface::class, TransferRepository::class);
        $this->app->bind(TransferApprovalRepositoryInterface::class, TransferApprovalRepository::class);
        $this->app->singleton(TransferApprovalService::class, TransferApprovalService::class);
        $this->app->singleton(TransferServiceInterface::class, TransferService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
