<?php
declare(strict_types=1);
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Plant\Models\Plant;

class PlantSeeder extends Seeder
{
    public function run(): void
    {
        $plants = [
            ['code' => 'P01', 'code_2' => 'EOMB', 'code_3' => '1001', 'id_sloc' => 'T00', 'description' => 'EOMB/1001', 'status' => 1, 'created_by' => 'system'],
            ['code' => 'P02', 'code_2' => 'EOB1', 'code_3' => '1002', 'id_sloc' => 'T00', 'description' => 'EOB1/1002', 'status' => 1, 'created_by' => 'system'],
            ['code' => 'P03', 'code_2' => 'EOB2', 'code_3' => '1003', 'id_sloc' => 'T00', 'description' => 'EOB2/1003', 'status' => 1, 'created_by' => 'system'],
            ['code' => 'P04', 'code_2' => 'EOB-5', 'code_3' => '1005', 'id_sloc' => 'T000', 'description' => 'EOB-5/1005', 'status' => 1, 'created_by' => 'system'],
            ['code' => 'P05', 'code_2' => 'EOB3', 'code_3' => '1007', 'id_sloc' => 'T00', 'description' => 'EOB3/1007', 'status' => 1, 'created_by' => 'system'],
        ];

        foreach ($plants as $data) {
            Plant::firstOrCreate(
                ['code_3' => $data['code_3']],
                $data
            );
        }
    }
}
