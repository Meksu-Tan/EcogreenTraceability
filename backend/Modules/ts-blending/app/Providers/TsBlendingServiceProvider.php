<?php declare(strict_types=1);
namespace Modules\TsBlending\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\TsBlending\Repositories\Contracts\BlendingRepositoryInterface;
use Modules\TsBlending\Repositories\BlendingRepository;
use Modules\TsBlending\Services\BlendingService;

class TsBlendingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BlendingRepositoryInterface::class, BlendingRepository::class);
        $this->app->bind(BlendingService::class, function ($app) {
            return new BlendingService(
                $app->make(BlendingRepositoryInterface::class)
            );
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
