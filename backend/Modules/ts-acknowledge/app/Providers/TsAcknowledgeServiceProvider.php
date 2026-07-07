<?php

declare(strict_types=1);

namespace Modules\TsAcknowledge\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\TsAcknowledge\Repositories\AcknowledgeRepositoryInterface;
use Modules\TsAcknowledge\Repositories\EloquentAcknowledgeRepository;

class TsAcknowledgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AcknowledgeRepositoryInterface::class,
            EloquentAcknowledgeRepository::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
