<?php

declare(strict_types=1);

namespace Modules\TsPackage\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\TsPackage\Policies\PackagePolicy;
use Modules\TsPackage\Repositories\Contracts\PackageRepositoryInterface;
use Modules\TsPackage\Repositories\EloquentPackageRepository;
use Modules\TsPackage\Services\Contracts\PackageServiceInterface;
use Modules\TsPackage\Services\PackageService;

class TsPackageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PackageRepositoryInterface::class,
            EloquentPackageRepository::class
        );
        $this->app->bind(
            PackageServiceInterface::class,
            PackageService::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');

        Gate::define('package.view', [new PackagePolicy, 'viewAny']);
        Gate::define('package.create', [new PackagePolicy, 'create']);
        Gate::define('package.delete', [new PackagePolicy, 'delete']);
    }
}
