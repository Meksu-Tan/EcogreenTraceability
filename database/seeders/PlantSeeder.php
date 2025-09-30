<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        \DB::table('m_plant')->insert([
            'id_plant' => 1,
            'code' => 'P01',
            'code_2' => 'EOB-1',
            'code_3' => '1002',
            'id_tank' => 'T00',
            'description' => 'EOB-1/1002',
            'status' => '1',
            'created_by' => 'qijie'
        ]);
        \DB::table('m_plant')->insert([
            'id_plant' => 2,
            'code' => 'P02',
            'code_2' => 'EOB-2',
            'code_3' => '1003',
            'id_tank' => 'T00',
            'description' => 'EOB-2/1003',
            'status' => '1',
            'created_by' => 'qijie'
        ]);
        \DB::table('m_plant')->insert([
            'id_plant' => 3,
            'code' => 'P03',
            'code_2' => 'EOB-3',
            'code_3' => '1007',
            'id_tank' => 'T00',
            'description' => 'EOB-3/1007',
            'status' => '1',
            'created_by' => 'qijie'
        ]);
        \DB::table('m_plant')->insert([
            'id_plant' => 4,
            'code' => 'P04',
            'code_2' => 'EOMB',
            'code_3' => '1001',
            'id_tank' => 'T00',
            'description' => 'EOMB/1001',
            'status' => '1',
            'created_by' => 'qijie'
        ]);
    }
}
