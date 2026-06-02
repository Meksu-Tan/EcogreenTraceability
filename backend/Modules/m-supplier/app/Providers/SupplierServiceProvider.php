<?php declare(strict_types=1);
namespace Modules\Supplier\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use Modules\Supplier\Repositories\SupplierRepository;
use Modules\Supplier\Services\Contracts\SupplierServiceInterface;
use Modules\Supplier\Services\SupplierService;

class SupplierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SupplierRepositoryInterface::class, SupplierRepository::class);
        $this->app->singleton(SupplierServiceInterface::class, SupplierService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
