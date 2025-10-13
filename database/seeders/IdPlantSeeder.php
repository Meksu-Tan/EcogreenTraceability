<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IdPlantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = [
            't_adjustment_header',
            't_adjustment_detail',
            't_balance_header',
            't_balance_detail',
            't_shipment_header',
            't_shipment_detail',
            't_trace_header',
            't_trace_detail',
            't_warehouse_header',
            't_warehouse_detail',
            't_balance_temporary',
        ];

        foreach ($tables as $table) {
            DB::table($table)
                ->whereNull('id_plant')
                ->update(['id_plant' => "1002"]);
        }
    }
}
