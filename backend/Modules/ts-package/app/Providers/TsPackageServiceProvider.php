<?php declare(strict_types=1);
namespace Modules\TsPackage\Providers;

use Illuminate\Support\ServiceProvider;

class TsPackageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Modules\TsPackage\Repositories\Contracts\PackageRepositoryInterface::class,
            \Modules\TsPackage\Repositories\EloquentPackageRepository::class
        );
        $this->app->bind(
            \Modules\TsPackage\Services\Contracts\PackageServiceInterface::class,
            \Modules\TsPackage\Services\PackageService::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
