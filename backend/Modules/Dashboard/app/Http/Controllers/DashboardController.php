<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Modules\Material\Repositories\Contracts\MaterialRepositoryInterface;
use Modules\Storage\Repositories\Contracts\StorageRepositoryInterface;
use Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use Modules\Tank\Repositories\Contracts\TankRepositoryInterface;
use Modules\Manufacturer\Repositories\Contracts\ManufacturerRepositoryInterface;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        protected MaterialRepositoryInterface $materialRepo,
        protected StorageRepositoryInterface $storageRepo,
        protected SupplierRepositoryInterface $supplierRepo,
        protected TankRepositoryInterface $tankRepo,
        protected ManufacturerRepositoryInterface $manufacturerRepo
    ) {}

    /**
     * GET /api/v1/dashboard/stats
     * Get dashboard statistics
     */
    public function stats(): JsonResponse
    {
        $materialCount = count($this->materialRepo->getAll());
        $storageCount = count($this->storageRepo->getAllTanks());
        $supplierCount = count($this->supplierRepo->getAll());
        $tankCount = count($this->tankRepo->getAll());
        $manufacturerCount = count($this->manufacturerRepo->getAll());
        $userCount = User::count();

        return response()->json([
            'status' => 1,
            'data' => [
                'material_count' => $materialCount,
                'storage_count' => $storageCount,
                'supplier_count' => $supplierCount,
                'tank_count' => $tankCount,
                'manufacturer_count' => $manufacturerCount,
                'user_count' => $userCount,
            ],
        ]);
    }
}
