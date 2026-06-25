<?php
declare(strict_types=1);
namespace Modules\TraceForward\Providers;

use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;
use Modules\TraceForward\Repositories\Concerns\ForwardDetailQuery;
use Modules\TraceForward\Repositories\Concerns\ForwardListQuery;
use Modules\TraceForward\Repositories\Concerns\ForwardSearchQuery;
use Modules\TraceForward\Repositories\Concerns\ForwardTraceQuery;
use Modules\TraceForward\Repositories\Contracts\TraceForwardRepositoryInterface;
use Modules\TraceForward\Repositories\TraceForwardRepository;
use Modules\TraceForward\Services\Contracts\TraceForwardServiceInterface;
use Modules\TraceForward\Services\TraceForwardService;

class TraceForwardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TraceForwardServiceInterface::class, TraceForwardService::class);

        $this->app->bind(TraceForwardRepositoryInterface::class, function ($app) {
            $conn = $app['db']->connection('eudr_ts');
            return new TraceForwardRepository(
                $conn,
                new ForwardListQuery($conn),
                new ForwardDetailQuery($conn),
                new ForwardTraceQuery($conn),
                new ForwardSearchQuery($conn),
            );
        });

        $this->app->when([
            ForwardListQuery::class,
            ForwardDetailQuery::class,
            ForwardTraceQuery::class,
            ForwardSearchQuery::class,
        ])
        ->needs(Connection::class)
        ->give(fn ($app) => $app['db']->connection('eudr_ts'));
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
