<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Supplier\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['code' => 'SUP-001', 'batch_code' => 'BATCH-C-001', 'description' => 'PT Supplier Satu', 'type' => 'local', 'status' => 1],
            ['code' => 'SUP-002', 'batch_code' => 'BATCH-C-002', 'description' => 'PT Supplier Dua', 'type' => 'local', 'status' => 1],
            ['code' => 'SUP-003', 'batch_code' => 'BATCH-D-001', 'description' => 'Overseas Supply Co', 'type' => 'import', 'status' => 1],
        ];

        foreach ($suppliers as $data) {
            Supplier::firstOrCreate(
                ['code' => $data['code']],
                $data + ['created_by' => 1, 'updated_by' => 1]
            );
        }
    }
}
