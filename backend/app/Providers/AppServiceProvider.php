<?php

namespace App\Providers;

use App\Contracts\Material\MaterialRepositoryInterface;
use App\Contracts\Plant\PlantRepositoryInterface;
use App\Contracts\Storage\StorageRepositoryInterface;
use App\Contracts\Supplier\SupplierRepositoryInterface;
use App\Repositories\Material\MaterialRepository;
use App\Repositories\Plant\PlantRepository;
use App\Repositories\Storage\StorageRepository;
use App\Repositories\Supplier\SupplierRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Interfaces to Implementations (Dependency Injection)
        $this->app->bind(MaterialRepositoryInterface::class, MaterialRepository::class);
        $this->app->bind(PlantRepositoryInterface::class, PlantRepository::class);
        $this->app->bind(StorageRepositoryInterface::class, StorageRepository::class);
        $this->app->bind(SupplierRepositoryInterface::class, SupplierRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
