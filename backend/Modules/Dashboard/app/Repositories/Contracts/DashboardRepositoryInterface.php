<?php declare(strict_types=1);
namespace Modules\Dashboard\Repositories\Contracts;

interface DashboardRepositoryInterface
{
    public function getMaterialCount(): int;
    public function getStorageCount(): int;
    public function getSupplierCount(): int;
    public function getUserCount(): int;
    public function getTransactionCounts(): array;
}
