<?php declare(strict_types=1);
namespace Modules\Dashboard\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Dashboard\Repositories\Contracts\DashboardRepositoryInterface;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function getMaterialCount(): int
    {
        return DB::connection('mysql')->table('m_material')->where('status', 1)->count();
    }

    public function getStorageCount(): int
    {
        return DB::connection('mysql')->table('m_sloc')->where('status', 1)->count();
    }

    public function getSupplierCount(): int
    {
        return DB::connection('mysql')->table('m_supplier')->where('status', 1)->count();
    }

    public function getUserCount(): int
    {
        return User::count();
    }

    public function getTransactionCounts(): array
    {
        return [
            'rm_entries' => DB::connection('eudr_ts')->table('t_balance_header')->where(function ($q): void {
                $q->where('trace_no', 'LIKE', '1%')->orWhere('trace_no', 'LIKE', '9%');
            })->count(),
            'transfers'  => DB::connection('eudr_ts')->table('t_balance_header')->where('trace_no', 'LIKE', '7%')->count(),
        ];
    }
}
