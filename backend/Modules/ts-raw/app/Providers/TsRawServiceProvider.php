<?php

declare(strict_types=1);

namespace Modules\TsRaw\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\TsRaw\Policies\RmEntryPolicy;
use Modules\TsRaw\Repositories\EloquentRmEntryRepository;
use Modules\TsRaw\Repositories\RmEntryRepositoryInterface;
use Modules\TsRaw\Services\Contracts\RmEntryServiceInterface;
use Modules\TsRaw\Services\RmEntryService;

class TsRawServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            RmEntryRepositoryInterface::class,
            EloquentRmEntryRepository::class
        );
        $this->app->singleton(
            RmEntryServiceInterface::class,
            RmEntryService::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');

        Gate::define('rmentry.view', [new RmEntryPolicy, 'viewAny']);
        Gate::define('rmentry.create', [new RmEntryPolicy, 'create']);
        Gate::define('rmentry.delete', [new RmEntryPolicy, 'delete']);
    }
}
