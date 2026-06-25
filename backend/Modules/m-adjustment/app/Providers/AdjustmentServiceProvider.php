<?php
declare(strict_types=1);
namespace Modules\Adjustment\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;
use Modules\Adjustment\Repositories\AdjustmentRepository;
use Modules\Adjustment\Services\Contracts\AdjustmentServiceInterface;
use Modules\Adjustment\Services\Contracts\AdjustmentPeriodServiceInterface;
use Modules\Adjustment\Services\AdjustmentService;
use Modules\Adjustment\Services\Contracts\AdjustmentMutationServiceInterface;
use Modules\Adjustment\Services\AdjustmentMutationService;
use Modules\Adjustment\Services\AdjustmentPeriodService;

class AdjustmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AdjustmentRepositoryInterface::class,
            AdjustmentRepository::class
        );
        $this->app->singleton(
            AdjustmentPeriodServiceInterface::class,
            AdjustmentPeriodService::class
        );
        $this->app->singleton(
            AdjustmentServiceInterface::class,
            AdjustmentService::class
        );
        $this->app->singleton(
            AdjustmentMutationServiceInterface::class,
            AdjustmentMutationService::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
