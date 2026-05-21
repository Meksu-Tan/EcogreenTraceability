<?php

namespace Modules\Dashboard\Services;

use Modules\Dashboard\Repositories\Contracts\DashboardRepositoryInterface;

class DashboardService
{
    public function __construct(
        protected DashboardRepositoryInterface $dashboardRepository
    ) {}

    public function getStats(): array
    {
        return [
            'material_count'    => $this->dashboardRepository->getMaterialCount(),
            'storage_count'     => $this->dashboardRepository->getStorageCount(),
            'supplier_count'    => $this->dashboardRepository->getSupplierCount(),
            'user_count'        => $this->dashboardRepository->getUserCount(),
            'transaction_counts' => $this->dashboardRepository->getTransactionCounts(),
        ];
    }
}
