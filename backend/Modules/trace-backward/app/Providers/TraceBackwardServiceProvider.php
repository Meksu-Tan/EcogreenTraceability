<?php
declare(strict_types=1);
namespace Modules\TraceBackward\Providers;

use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;
use Modules\TraceBackward\Repositories\Concerns\BackwardDetailQuery;
use Modules\TraceBackward\Repositories\Concerns\BackwardListQuery;
use Modules\TraceBackward\Repositories\Concerns\BackwardSearchQuery;
use Modules\TraceBackward\Repositories\Concerns\BackwardTraceQuery;
use Modules\TraceBackward\Repositories\Concerns\ShipmentLookupQuery;
use Modules\TraceBackward\Repositories\Contracts\TraceBackwardRepositoryInterface;
use Modules\TraceBackward\Repositories\TraceBackwardRepository;
use Modules\TraceBackward\Services\Contracts\ShipmentTraceVerificationServiceInterface;
use Modules\TraceBackward\Services\Contracts\TraceBackwardServiceInterface;
use Modules\TraceBackward\Services\ShipmentTraceVerificationService;
use Modules\TraceBackward\Services\TraceBackwardService;

class TraceBackwardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TraceBackwardServiceInterface::class, TraceBackwardService::class);
        $this->app->bind(ShipmentTraceVerificationServiceInterface::class, ShipmentTraceVerificationService::class);

        $this->app->bind(TraceBackwardRepositoryInterface::class, function ($app) {
            $conn = $app['db']->connection('eudr_ts');
            return new TraceBackwardRepository(
                $conn,
                new BackwardListQuery($conn),
                new BackwardDetailQuery($conn),
                new BackwardTraceQuery($conn),
                new BackwardSearchQuery($conn),
                new ShipmentLookupQuery($conn),
            );
        });

        $this->app->when([
            BackwardListQuery::class,
            BackwardDetailQuery::class,
            BackwardTraceQuery::class,
            BackwardSearchQuery::class,
            ShipmentLookupQuery::class,
        ])
        ->needs(Connection::class)
        ->give(fn ($app) => $app['db']->connection('eudr_ts'));
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
