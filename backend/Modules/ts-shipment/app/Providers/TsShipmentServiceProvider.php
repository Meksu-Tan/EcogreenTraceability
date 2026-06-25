<?php
declare(strict_types=1);
namespace Modules\TsShipment\Providers;

use Illuminate\Support\ServiceProvider;

class TsShipmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Modules\TsShipment\Repositories\Contracts\ShipmentRepositoryInterface::class,
            \Modules\TsShipment\Repositories\EloquentShipmentRepository::class
        );
        $this->app->bind(
            \Modules\TsShipment\Services\Contracts\ShipmentServiceInterface::class,
            \Modules\TsShipment\Services\ShipmentService::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
