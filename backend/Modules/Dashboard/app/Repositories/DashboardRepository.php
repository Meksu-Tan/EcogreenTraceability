<?php declare(strict_types=1);
namespace Modules\Dashboard\Repositories;

use App\Models\User;
use Modules\Dashboard\Repositories\Contracts\DashboardRepositoryInterface;
use Modules\Material\Models\Material;
use Modules\Storage\Models\StorageTank;
use Modules\Supplier\Models\Supplier;
use Modules\TsRaw\Models\BalanceHeader;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function getMaterialCount(): int
    {
        return Material::count();
    }

    public function getStorageCount(): int
    {
        return StorageTank::count();
    }

    public function getSupplierCount(): int
    {
        return Supplier::count();
    }

    public function getUserCount(): int
    {
        return User::count();
    }

    public function getTransactionCounts(): array
    {
        return [
            'rm_entries' => BalanceHeader::rmEntry()->count(),
            'transfers'  => BalanceHeader::transfer()->count(),
        ];
    }
}
