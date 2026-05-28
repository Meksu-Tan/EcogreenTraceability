<?php declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Storage\Models\Warehouse;
use Modules\Storage\Models\StorageTank;

class StorageSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            ['code' => 'WH-001', 'description' => 'Warehouse Utama', 'status' => 'active'],
            ['code' => 'WH-002', 'description' => 'Warehouse Cadangan', 'status' => 'active'],
        ];

        foreach ($warehouses as $data) {
            Warehouse::firstOrCreate(
                ['code' => $data['code']],
                $data + ['created_by' => 1, 'updated_by' => 1]
            );
        }
    }
}
