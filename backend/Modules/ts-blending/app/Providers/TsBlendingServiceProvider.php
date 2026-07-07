<?php

declare(strict_types=1);

namespace Modules\TsBlending\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\TsBlending\Policies\BlendingPolicy;
use Modules\TsBlending\Repositories\BlendingRepository;
use Modules\TsBlending\Repositories\Contracts\BlendingRepositoryInterface;
use Modules\TsBlending\Services\BlendingService;
use Modules\TsBlending\Services\Contracts\BlendingServiceInterface;

class TsBlendingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BlendingRepositoryInterface::class, BlendingRepository::class);
        $this->app->singleton(BlendingServiceInterface::class, BlendingService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');

        Gate::define('blending.view', [new BlendingPolicy, 'viewAny']);
        Gate::define('blending.create', [new BlendingPolicy, 'create']);
        Gate::define('blending.delete', [new BlendingPolicy, 'delete']);
    }
}
