<?php declare(strict_types=1);
namespace Modules\TsTransfer\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\TsTransfer\Repositories\Contracts\TransferRepositoryInterface;
use Modules\TsTransfer\Repositories\TransferRepository;
use Modules\TsTransfer\Services\Contracts\TransferServiceInterface;
use Modules\TsTransfer\Services\TransferService;

class TsTransferServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TransferRepositoryInterface::class, TransferRepository::class);
        $this->app->singleton(TransferServiceInterface::class, TransferService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
