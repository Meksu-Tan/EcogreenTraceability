<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MaterialFlowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        \DB::table('m_material_flow')->insert([
            'flow_type' => 'volumetric_total',
            'created_by' => 'santo'
        ]);
        \DB::table('m_material_flow')->insert([
            'flow_type' => 'quantifier',
            'created_by' => 'santo'
        ]);

    }
}
