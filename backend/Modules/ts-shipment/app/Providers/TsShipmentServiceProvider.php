<?php declare(strict_types=1);
namespace Modules\TsShipment\Providers;

use Illuminate\Support\ServiceProvider;

class TsShipmentServiceProvider extends ServiceProvider
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
