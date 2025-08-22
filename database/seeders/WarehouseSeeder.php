<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        \DB::table('m_warehouse')->insert([
            'id_batch' => 'P1',
            'code' => '630',
            'description' => 'Warehouse 630',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => 'P2',
            'code' => '630',
            'description' => 'Warehouse 630',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => '13',
            'code' => '630',
            'description' => 'Warehouse 630',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => '14',
            'code' => '630',
            'description' => 'Warehouse 630',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => '1R',
            'code' => '630',
            'description' => 'Warehouse 630',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => '23',
            'code' => '630',
            'description' => 'Warehouse 630',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => '24',
            'code' => '630',
            'description' => 'Warehouse 630',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => '32',
            'code' => '635',
            'description' => 'Warehouse 635',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => '41',
            'code' => '635',
            'description' => 'Warehouse 635',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => '46',
            'code' => '635',
            'description' => 'Warehouse 635',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => '58',
            'code' => '635',
            'description' => 'Warehouse 635',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => '62',
            'code' => '635',
            'description' => 'Warehouse 635',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => '71',
            'code' => '635',
            'description' => 'Warehouse 635',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => '88',
            'code' => '635',
            'description' => 'Warehouse 635',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => '96',
            'code' => '635',
            'description' => 'Warehouse 635',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => '99',
            'code' => '635',
            'description' => 'Warehouse 635',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => 'P3',
            'code' => '755',
            'description' => 'Warehouse 755',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => 'P4',
            'code' => '755',
            'description' => 'Warehouse 755',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => 'P5',
            'code' => '755',
            'description' => 'Warehouse 755',
            'created_by' => 'santo'
        ]);
        \DB::table('m_warehouse')->insert([
            'id_batch' => 'P6',
            'code' => '755',
            'description' => 'Warehouse 755',
            'created_by' => 'santo'
        ]);

    }
}
