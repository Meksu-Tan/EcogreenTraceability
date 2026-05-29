<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixFeedLogAlcoholCrudeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $traceIds = [39897, 39899, 39901];

        DB::connection('eudr_ts')->transaction(function () use ($traceIds) {
            $updatedHeaders = DB::connection('eudr_ts')
                ->table('t_trace_header')
                ->whereIn('id_trace_head', $traceIds)
                ->update(['status' => 0, 'updated_by' => 'System Fix Script']);

            $updatedDetails = DB::connection('eudr_ts')
                ->table('t_trace_detail')
                ->whereIn('id_trace_head', $traceIds)
                ->update(['status' => 0, 'updated_by' => 'System Fix Script']);

            echo "Updated $updatedHeaders headers and $updatedDetails details.\n";
        });
    }
}
