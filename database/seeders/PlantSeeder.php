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
            'code' => 'EOB-1',
            'code_2' => '1002',
            'description' => 'EOB-1/1002',
            'status' => '1',
            'created_by' => 'qijie'
        ]);
        \DB::table('m_plant')->insert([
            'id_plant' => 2,
            'code' => 'EOB-2',
            'code_2' => '1003',
            'description' => 'EOB-2/1003',
            'status' => '1',
            'created_by' => 'qijie'
        ]);
        \DB::table('m_plant')->insert([
            'id_plant' => 3,
            'code' => 'EOB-3',
            'code_2' => '1007',
            'description' => 'EOB-3/1007',
            'status' => '1',
            'created_by' => 'qijie'
        ]);
        \DB::table('m_plant')->insert([
            'id_plant' => 4,
            'code' => 'EOMB',
            'code_2' => '1001',
            'description' => 'EOMB/1001',
            'status' => '1',
            'created_by' => 'qijie'
        ]);
    }
}
