<?php

namespace Modules\TsBlending\Providers;

use Illuminate\Support\ServiceProvider;

class TsBlendingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No repository bindings needed for placeholder
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
