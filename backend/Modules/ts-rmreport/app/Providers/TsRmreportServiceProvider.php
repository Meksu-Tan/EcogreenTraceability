<?php declare(strict_types=1);

namespace Modules\TsRmreport\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\TsRmreport\Repositories\Contracts\RmReportRepositoryInterface;
use Modules\TsRmreport\Repositories\RmReportRepository;
use Modules\TsRmreport\Services\Contracts\RmReportServiceInterface;
use Modules\TsRmreport\Services\RmReportService;

class TsRmreportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            RmReportRepositoryInterface::class,
            RmReportRepository::class
        );
        $this->app->singleton(
            RmReportServiceInterface::class,
            RmReportService::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
