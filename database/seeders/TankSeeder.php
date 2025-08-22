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
            'code' => 'T01',
            'code_2' => 'RM',
            'description' => '504T02',
            'status' => '0',
            'created_by' => 'santo'
        ]);
        \DB::table('m_tank')->insert([
            'code' => 'T01',
            'code_2' => 'RM',
            'description' => '504T03',
            'status' => '0',
            'created_by' => 'santo'
        ]);
        \DB::table('m_tank')->insert([
            'code' => 'T01',
            'code_2' => 'FEED',
            'description' => 'FEED TANK',
            'status' => '1',
            'created_by' => 'santo'
        ]);
        \DB::table('m_tank')->insert([
            'code' => 'T00',
            'code_2' => 'STORAGE',
            'description' => 'STORAGE TANK',
            'status' => '1',
            'created_by' => 'santo'
        ]);
        \DB::table('m_tank')->insert([
            'code' => 'T02',
            'code_2' => 'WIP',
            'description' => 'WIP TANK',
            'status' => '1',
            'created_by' => 'santo'
        ]);
        \DB::table('m_tank')->insert([
            'code' => 'T03',
            'code_2' => 'PRD',
            'description' => 'PRODUCT TANK',
            'status' => '1',
            'created_by' => 'santo'
        ]);
    }
}
