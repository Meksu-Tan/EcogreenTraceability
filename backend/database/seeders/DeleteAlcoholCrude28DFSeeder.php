<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeleteAlcoholCrude28DFSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $materialName = 'ALCOHOL CRUDE 28-DF';
        $targetDate = '2025-05-28';

        // First, find the material ID
        $material = DB::connection('eudr_ts')->select(
            'SELECT id_material, description FROM m_material WHERE description LIKE ? AND status = 1',
            ['%'.$materialName.'%']
        );

        if (empty($material)) {
            $this->command->warn("Material '{$materialName}' not found in database.");

            return;
        }

        $materialId = $material[0]->id_material;
        $this->command->info("Found material: {$material[0]->description} (ID: {$materialId})");

        // Find trace headers for this material on the target date
        $traceHeaders = DB::connection('eudr_ts')->select(
            'SELECT id_trace_head, to_trace_no, entry_date, id_material 
             FROM t_trace_header 
             WHERE id_material = ? 
             AND entry_date = ? 
             AND status = 1',
            [$materialId, $targetDate]
        );

        if (empty($traceHeaders)) {
            $this->command->warn("No active trace headers found for material '{$materialName}' on date '{$targetDate}'.");

            return;
        }

        $traceIds = array_column($traceHeaders, 'id_trace_head');
        $this->command->info('Found '.count($traceIds).' trace headers to deactivate:');

        foreach ($traceHeaders as $header) {
            $this->command->line("  - ID: {$header->id_trace_head}, Trace No: {$header->to_trace_no}, Date: {$header->entry_date}");
        }

        if (! $this->command->confirm('Do you wish to proceed with deactivation?')) {
            $this->command->warn('Operation cancelled by user.');

            return;
        }

        DB::connection('eudr_ts')->transaction(function () use ($traceIds) {
            $updatedHeaders = DB::connection('eudr_ts')
                ->table('t_trace_header')
                ->whereIn('id_trace_head', $traceIds)
                ->update(['status' => 0, 'updated_by' => 'DeleteAlcoholCrude28DFSeeder']);

            $updatedDetails = DB::connection('eudr_ts')
                ->table('t_trace_detail')
                ->whereIn('id_trace_head', $traceIds)
                ->update(['status' => 0, 'updated_by' => 'DeleteAlcoholCrude28DFSeeder']);

            $this->command->info("Successfully deactivated {$updatedHeaders} trace headers and {$updatedDetails} trace details.");
        });
    }
}
