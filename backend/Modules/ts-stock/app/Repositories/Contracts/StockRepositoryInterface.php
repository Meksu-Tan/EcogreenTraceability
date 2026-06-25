<?php
declare(strict_types=1);
namespace Modules\TsStock\Repositories\Contracts;

interface StockRepositoryInterface
{
    public function getStockList(array $filters): array;
    public function getStockDetail(int $id): ?array;
    public function getActiveMaterialStock(?string $search = null, ?string $type = null): array;
    public function getStockMovement(array $filters): array;
    public function getActiveSlocs(): array;
}
