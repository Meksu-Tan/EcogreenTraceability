<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Contracts\Material\MaterialRepositoryInterface;
use App\Contracts\Storage\StorageRepositoryInterface;
use App\Contracts\Supplier\SupplierRepositoryInterface;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        protected MaterialRepositoryInterface $materialRepo,
        protected StorageRepositoryInterface $storageRepo,
        protected SupplierRepositoryInterface $supplierRepo
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
        $userCount = User::count();

        return response()->json([
            'status' => 1,
            'data' => [
                'material_count' => $materialCount,
                'storage_count' => $storageCount,
                'supplier_count' => $supplierCount,
                'user_count' => $userCount,
            ],
        ]);
    }
}
