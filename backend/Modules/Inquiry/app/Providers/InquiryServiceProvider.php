<?php declare(strict_types=1);
namespace Modules\Inquiry\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inquiry\Repositories\Contracts\InquiryRepositoryInterface;
use Modules\Inquiry\Repositories\InquiryRepository;

class InquiryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InquiryRepositoryInterface::class, InquiryRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
