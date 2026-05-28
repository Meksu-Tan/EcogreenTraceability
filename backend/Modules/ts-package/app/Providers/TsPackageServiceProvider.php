<?php declare(strict_types=1);
namespace Modules\TsPackage\Providers;

use Illuminate\Support\ServiceProvider;

class TsPackageServiceProvider extends ServiceProvider
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
