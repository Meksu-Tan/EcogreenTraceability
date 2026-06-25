<?php
declare(strict_types=1);
namespace Modules\TsWip\Providers;

use Illuminate\Support\ServiceProvider;

class TsWipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Modules\TsWip\Repositories\Contracts\WipEntryRepositoryInterface::class,
            \Modules\TsWip\Repositories\WipEntryRepository::class
        );
        $this->app->singleton(
            \Modules\TsWip\Services\Contracts\WipEntryServiceInterface::class,
            \Modules\TsWip\Services\WipEntryService::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
