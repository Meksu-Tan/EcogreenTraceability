<?php
declare(strict_types=1);
namespace Modules\Inquiry\Providers;

use Illuminate\Support\ServiceProvider;

class InquiryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Inquiry services have been moved to dedicated modules:
        // ts-stock, ts-tsreport, ts-rmreport
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
