<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Material\Models\Material;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        $materials = [
            ['code' => 'MAT-001', 'description' => 'Crude Palm Oil', 'type' => 'feed', 'status' => 'active'],
            ['code' => 'MAT-002', 'description' => 'Refined Palm Oil', 'type' => 'feed', 'status' => 'active'],
            ['code' => 'MAT-003', 'description' => 'Stearin', 'type' => 'rundown', 'status' => 'active'],
            ['code' => 'MAT-004', 'description' => 'Olein', 'type' => 'rundown', 'status' => 'active'],
        ];

        foreach ($materials as $data) {
            Material::firstOrCreate(
                ['code' => $data['code']],
                $data + ['created_by' => 1, 'updated_by' => 1]
            );
        }
    }
}
