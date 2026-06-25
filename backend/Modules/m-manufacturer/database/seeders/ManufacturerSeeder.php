<?php
declare(strict_types=1);
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Manufacturer\Models\Manufacturer;

class ManufacturerSeeder extends Seeder
{
    public function run(): void
    {
        $manufacturers = [
            ['code' => 'MFR-001', 'batch_code' => 'BATCH-A-001', 'description' => 'PT Manufaktur Utama', 'type' => 'local', 'status' => 'active'],
            ['code' => 'MFR-002', 'batch_code' => 'BATCH-A-002', 'description' => 'PT Industri Kimia', 'type' => 'local', 'status' => 'active'],
            ['code' => 'MFR-003', 'batch_code' => 'BATCH-B-001', 'description' => 'Global Chemical Corp', 'type' => 'import', 'status' => 'active'],
        ];

        foreach ($manufacturers as $data) {
            Manufacturer::firstOrCreate(
                ['code' => $data['code']],
                $data + ['created_by' => 1, 'updated_by' => 1]
            );
        }
    }
}
