<?php

declare(strict_types=1);

namespace Modules\TsTsreport\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\TsTsreport\Repositories\Contracts\TsReportRepositoryInterface;
use Modules\TsTsreport\Repositories\TsReportRepository;
use Modules\TsTsreport\Services\Contracts\TsReportServiceInterface;
use Modules\TsTsreport\Services\TsReportService;

class TsTsreportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TsReportRepositoryInterface::class,
            TsReportRepository::class
        );
        $this->app->singleton(
            TsReportServiceInterface::class,
            TsReportService::class
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
