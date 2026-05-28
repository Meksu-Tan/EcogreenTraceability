<?php declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Plant\Models\Plant;

class PlantSeeder extends Seeder
{
    public function run(): void
    {
        $plants = [
            ['code_2' => 'P1', 'code_3' => 'PLT', 'description' => 'Plant Utama', 'status' => 'active'],
            ['code_2' => 'P2', 'code_3' => 'PL2', 'description' => 'Plant Kedua', 'status' => 'active'],
        ];

        foreach ($plants as $data) {
            Plant::firstOrCreate(
                ['code_2' => $data['code_2']],
                $data + ['created_by' => 1, 'updated_by' => 1]
            );
        }
    }
}
