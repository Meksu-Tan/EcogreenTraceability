<?php
declare(strict_types=1);
namespace Modules\Quantifier\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Quantifier\Repositories\Contracts\QuantifierRepositoryInterface;
use Modules\Quantifier\Repositories\QuantifierRepository;
use Modules\Quantifier\Services\Contracts\QuantifierServiceInterface;
use Modules\Quantifier\Services\QuantifierService;

class QuantifierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            QuantifierRepositoryInterface::class,
            QuantifierRepository::class
        );
        $this->app->singleton(
            QuantifierServiceInterface::class,
            QuantifierService::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
