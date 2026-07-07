<?php

declare(strict_types=1);

namespace Modules\TsStock\Services;

use Modules\TsStock\Repositories\Contracts\StockRepositoryInterface;
use Modules\TsStock\Services\Contracts\StockServiceInterface;

class StockService implements StockServiceInterface
{
    public function __construct(
        protected StockRepositoryInterface $stockRepository
    ) {}

    public function getStockList(array $filters = []): array
    {
        return [
            'status' => 1,
            'data' => $this->stockRepository->getStockList($filters),
            'message' => 'Stock list retrieved successfully',
        ];
    }

    public function getStockDetail(int $id): array
    {
        $data = $this->stockRepository->getStockDetail($id);

        if ($data === null) {
            return [
                'status' => 0,
                'data' => null,
                'message' => 'Stock detail not found',
            ];
        }

        return [
            'status' => 1,
            'data' => $data,
            'message' => 'Stock detail retrieved successfully',
        ];
    }

    public function getActiveMaterialStock(?string $search = null, ?string $type = null): array
    {
        return [
            'status' => 1,
            'data' => $this->stockRepository->getActiveMaterialStock($search, $type),
            'message' => 'Active materials retrieved',
        ];
    }

    public function getStockMovement(array $filters = []): array
    {
        return [
            'status' => 1,
            'data' => $this->stockRepository->getStockMovement($filters),
            'message' => 'Stock movements retrieved',
        ];
    }

    public function getActiveSlocs(): array
    {
        return [
            'status' => 1,
            'data' => $this->stockRepository->getActiveSlocs(),
            'message' => 'Active slocs retrieved',
        ];
    }
}
