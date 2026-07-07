<?php

declare(strict_types=1);

namespace Modules\TsWip\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\TsWip\Policies\WipEntryPolicy;
use Modules\TsWip\Repositories\Contracts\WipEntryRepositoryInterface;
use Modules\TsWip\Repositories\Contracts\WipProcessRepositoryInterface;
use Modules\TsWip\Repositories\Contracts\WipTreeRepositoryInterface;
use Modules\TsWip\Repositories\WipEntryRepository;
use Modules\TsWip\Repositories\WipProcessRepository;
use Modules\TsWip\Repositories\WipTreeRepository;
use Modules\TsWip\Services\Contracts\WipEntryServiceInterface;
use Modules\TsWip\Services\Contracts\WipProcessServiceInterface;
use Modules\TsWip\Services\Contracts\WipTreeServiceInterface;
use Modules\TsWip\Services\WipEntryService;
use Modules\TsWip\Services\WipProcessService;
use Modules\TsWip\Services\WipTreeService;

class TsWipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            WipEntryRepositoryInterface::class,
            WipEntryRepository::class
        );
        $this->app->bind(
            WipTreeRepositoryInterface::class,
            WipTreeRepository::class
        );
        $this->app->bind(
            WipProcessRepositoryInterface::class,
            WipProcessRepository::class
        );
        $this->app->singleton(
            WipEntryServiceInterface::class,
            WipEntryService::class
        );
        $this->app->singleton(
            WipTreeServiceInterface::class,
            WipTreeService::class
        );
        $this->app->singleton(
            WipProcessServiceInterface::class,
            WipProcessService::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');

        Gate::define('wipentry.view', [new WipEntryPolicy, 'viewAny']);
        Gate::define('wipentry.create', [new WipEntryPolicy, 'create']);
        Gate::define('wipentry.delete', [new WipEntryPolicy, 'delete']);
    }
}
