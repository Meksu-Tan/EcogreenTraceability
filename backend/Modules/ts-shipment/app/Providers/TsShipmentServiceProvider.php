<?php

declare(strict_types=1);

namespace Modules\TsShipment\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\TsShipment\Policies\ShipmentPolicy;
use Modules\TsShipment\Repositories\Contracts\ShipmentRepositoryInterface;
use Modules\TsShipment\Repositories\EloquentShipmentRepository;
use Modules\TsShipment\Services\Contracts\ShipmentServiceInterface;
use Modules\TsShipment\Services\ShipmentService;

class TsShipmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ShipmentRepositoryInterface::class,
            EloquentShipmentRepository::class
        );
        $this->app->bind(
            ShipmentServiceInterface::class,
            ShipmentService::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');

        Gate::define('shipment.view', [new ShipmentPolicy, 'viewAny']);
        Gate::define('shipment.create', [new ShipmentPolicy, 'create']);
        Gate::define('shipment.delete', [new ShipmentPolicy, 'delete']);
    }
}
