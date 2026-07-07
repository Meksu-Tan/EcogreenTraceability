<?php

declare(strict_types=1);

namespace Modules\Shared\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Services\AuditService;
use Modules\Shared\Services\Contracts\AuditServiceInterface;
use Modules\Shared\Services\Contracts\PeriodLockServiceInterface;
use Modules\Shared\Services\Contracts\PlantContextServiceInterface;
use Modules\Shared\Services\FeedRundownOrchestrator;
use Modules\Shared\Services\PeriodLockService;
use Modules\Shared\Services\PlantContextService;
use Modules\Shared\Services\TraceNumberGeneratorService;
use Modules\Shared\Services\TransactionCancellationService;
use Modules\Shared\Services\TransactionCoreService;

class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditServiceInterface::class, AuditService::class);
        $this->app->singleton(PeriodLockServiceInterface::class, PeriodLockService::class);
        $this->app->singleton(PlantContextServiceInterface::class, PlantContextService::class);
        $this->app->singleton(FeedRundownOrchestrator::class);
        $this->app->singleton(TraceNumberGeneratorService::class);
        $this->app->singleton(TransactionCancellationService::class);
        $this->app->singleton(TransactionCoreService::class);
    }

    public function boot(): void
    {
        //
    }
}
