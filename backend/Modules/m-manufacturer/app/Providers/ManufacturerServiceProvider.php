<?php
declare(strict_types=1);
namespace Modules\Manufacturer\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Manufacturer\Repositories\Contracts\ManufacturerRepositoryInterface;
use Modules\Manufacturer\Repositories\ManufacturerRepository;
use Modules\Manufacturer\Services\Contracts\ManufacturerServiceInterface;
use Modules\Manufacturer\Services\ManufacturerService;

class ManufacturerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ManufacturerRepositoryInterface::class, ManufacturerRepository::class);
        $this->app->singleton(ManufacturerServiceInterface::class, ManufacturerService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
