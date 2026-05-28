<?php declare(strict_types=1);
namespace Modules\Supplier\Providers;

use Illuminate\Support\ServiceProvider;

class SupplierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface::class,
            \Modules\Supplier\Repositories\SupplierRepository::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
