<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * business-process.md §4.3/§6 lists ME60 (rundown_id 013, FeedId 13)
 * as a Section 104 Fractionation product (Mode 1), sharing tank FT0157
 * with ME80. Original tree seed skipped it entirely.
 */
return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        $section = DB::connection('eudr_ts')
            ->table('m_wip_section')
            ->where('code', '104')
            ->first();

        if (! $section) {
            return;
        }

        $sid = $section->id;

        // Shift sort_orders >= 4 up by 1 to make room
        DB::connection('eudr_ts')
            ->table('m_wip_process_step')
            ->where('section_id', $sid)
            ->where('sort_order', '>=', 4)
            ->increment('sort_order', 1);

        // Insert ME60 rundown at position 4 (after PROCESS label, before BDME)
        DB::connection('eudr_ts')
            ->table('m_wip_process_step')
            ->insert([
                'section_id' => $sid,
                'step_type' => 'rundown',
                'label' => 'ME60 RUNDOWNS (104 FT0157)',
                'rundown_id' => '013',
                'pipe_number' => '104 FT0157',
                'dcs_tag' => '104_F0157',
                'sort_order' => 4,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::connection('eudr_ts')
            ->table('m_wip_process_step')
            ->where('label', 'ME60 RUNDOWNS (104 FT0157)')
            ->where('rundown_id', '013')
            ->delete();
    }
};
