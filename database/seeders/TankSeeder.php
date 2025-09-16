<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        \DB::table('m_tank')->insert([
            'id_tank' => 3,
            'code' => 'T01',
            'code_2' => 'RM',
            'code_3' => 'FEED',
            'id_plant' => '1002',
            'description' => 'FEED TANK',
            'status' => '1',
            'created_by' => 'santo'
        ]);
        \DB::table('m_tank')->insert([
            'id_tank' => 4,
            'code' => 'T00',
            'code_2' => 'RM',
            'code_2' => 'STORAGE',
            'id_plant' => '1002',
            'description' => 'STORAGE TANK',
            'status' => '1',
            'created_by' => 'santo'
        ]);
        \DB::table('m_tank')->insert([
            'id_tank' => 5,
            'code' => 'T02',
            'code_2' => 'WIP',
            'code_2' => 'WIP',
            'id_plant' => '1002',
            'description' => 'WIP TANK',
            'status' => '1',
            'created_by' => 'santo'
        ]);
        \DB::table('m_tank')->insert([
            'id_tank' => 6,
            'code' => 'T03',
            'code_2' => 'PRD',
            'code_2' => 'PRD',
            'id_plant' => '1002',
            'description' => 'PRODUCT TANK',
            'status' => '1',
            'created_by' => 'santo'
        ]);
    }
}
