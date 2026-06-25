<?php
declare(strict_types=1);
namespace Modules\TsStock\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\TsStock\Repositories\Contracts\StockRepositoryInterface;
use Modules\TsStock\Repositories\StockRepository;
use Modules\TsStock\Services\Contracts\StockServiceInterface;
use Modules\TsStock\Services\StockService;

class TsStockServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            StockRepositoryInterface::class,
            StockRepository::class
        );
        $this->app->singleton(
            StockServiceInterface::class,
            StockService::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
