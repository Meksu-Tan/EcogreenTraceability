<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $updates = [
            'BDME RUNDOWNS' => ['104_F0215', '104 F0215'],
            'ECONOATE 665 RUNDOWNS' => ['104_F0170', '104 F0170'],
            'ME80 RUNDOWNS' => ['104_F0157', '104 F0157'],
            'GLYCERINE RUNDOWNS' => ['111_FT0314', '111 FT0314'],
            'FA18lrr RUNDOWNS' => ['106_F0112', '106 F0112'],
            'FA12 RUNDOWNS' => ['112_F0235', '112 F0235'],
            'FA14lrr RUNDOWNS' => ['112_F0224', '112 F0224'],
            'WME RUNDOWNS' => ['302_FT101', '302 FT101'],
        ];

        foreach ($updates as $label => [$dcs, $pipe]) {
            DB::connection('eudr_ts')
                ->table('m_wip_process_step')
                ->where('label', $label)
                ->whereNull('dcs_tag')
                ->update([
                    'dcs_tag' => $dcs,
                    'pipe_number' => $pipe,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $labels = [
            'BDME RUNDOWNS',
            'ECONOATE 665 RUNDOWNS',
            'ME80 RUNDOWNS',
            'GLYCERINE RUNDOWNS',
            'FA18lrr RUNDOWNS',
            'FA12 RUNDOWNS',
            'FA14lrr RUNDOWNS',
            'WME RUNDOWNS',
        ];

        DB::connection('eudr_ts')
            ->table('m_wip_process_step')
            ->whereIn('label', $labels)
            ->update([
                'dcs_tag' => null,
                'pipe_number' => null,
                'updated_at' => now(),
            ]);
    }
};
