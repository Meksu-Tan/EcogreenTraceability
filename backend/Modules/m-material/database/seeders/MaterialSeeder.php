<?php declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Material\Models\Material;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        $materials = [
            ['code' => 'MAT-001', 'code_noneudr' => 'MAT-001', 'description' => 'Crude Palm Oil', 'type' => 'RM', 'qtf_feed' => 1, 'qtf_rundown' => 0, 'status' => 1],
            ['code' => 'MAT-002', 'code_noneudr' => 'MAT-002', 'description' => 'Refined Palm Oil', 'type' => 'RM', 'qtf_feed' => 1, 'qtf_rundown' => 0, 'status' => 1],
            ['code' => 'MAT-003', 'code_noneudr' => 'MAT-003', 'description' => 'Stearin', 'type' => 'RM', 'qtf_feed' => 0, 'qtf_rundown' => 1, 'status' => 1],
            ['code' => 'MAT-004', 'code_noneudr' => 'MAT-004', 'description' => 'Olein', 'type' => 'RM', 'qtf_feed' => 0, 'qtf_rundown' => 1, 'status' => 1],
        ];

        foreach ($materials as $data) {
            Material::firstOrCreate(
                ['code' => $data['code']],
                $data + ['created_by' => 1, 'updated_by' => 1]
            );
        }
    }
}
