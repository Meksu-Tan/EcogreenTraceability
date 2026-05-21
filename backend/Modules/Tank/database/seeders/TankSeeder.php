<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tank\Models\Tank;

class TankSeeder extends Seeder
{
    public function run(): void
    {
        $tanks = [
            ['plant_code' => 'P01', 'plant_name' => 'Plant 01', 'tank_number' => 'TN-001', 'description' => 'Storage Tank 1', 'status' => 1],
            ['plant_code' => 'P01', 'plant_name' => 'Plant 01', 'tank_number' => 'TN-002', 'description' => 'Storage Tank 2', 'status' => 1],
            ['plant_code' => 'P02', 'plant_name' => 'Plant 02', 'tank_number' => 'TN-003', 'description' => 'Feed Tank 1', 'status' => 1],
        ];

        foreach ($tanks as $data) {
            Tank::firstOrCreate(
                ['tank_number' => $data['tank_number']],
                $data + ['created_by' => 1, 'updated_by' => 1]
            );
        }
    }
}
