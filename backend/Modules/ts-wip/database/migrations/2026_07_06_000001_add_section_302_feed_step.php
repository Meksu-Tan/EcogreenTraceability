<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        $section = DB::connection('eudr_ts')
            ->table('m_wip_section')
            ->where('code', '302')
            ->first();

        if (! $section) {
            return;
        }

        $sid = $section->id;

        // Desired order: START(1), UME(feed new→2), PROCESS(label→3), WME(rundown→4), ME28(rundown→5), END(label→6)

        // 1. Shift all >= 2 up by 1 → START(1), WME(3), PROCESS(4), ME28(5), END(6)
        DB::connection('eudr_ts')
            ->table('m_wip_process_step')
            ->where('section_id', $sid)
            ->where('sort_order', '>=', 2)
            ->increment('sort_order', 1);

        // 2. Insert UME feed at 2
        DB::connection('eudr_ts')
            ->table('m_wip_process_step')
            ->insert([
                'section_id' => $sid,
                'step_type' => 'feed',
                'label' => 'UME 302 FT102',
                'feed_id' => '005',
                'pipe_number' => '302 FT102',
                'dcs_tag' => '302_FT102',
                'sort_order' => 2,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        // 3. Swap WME (3) and PROCESS (4)
        DB::connection('eudr_ts')
            ->table('m_wip_process_step')
            ->where('section_id', $sid)
            ->where('sort_order', 3)
            ->where('step_type', 'rundown')
            ->update(['sort_order' => 99]);

        DB::connection('eudr_ts')
            ->table('m_wip_process_step')
            ->where('section_id', $sid)
            ->where('sort_order', 4)
            ->where('step_type', 'label')
            ->where('label', 'PROCESS OF SECTION 302')
            ->update(['sort_order' => 3]);

        DB::connection('eudr_ts')
            ->table('m_wip_process_step')
            ->where('section_id', $sid)
            ->where('sort_order', 99)
            ->update(['sort_order' => 4]);
    }

    public function down(): void
    {
        $section = DB::connection('eudr_ts')
            ->table('m_wip_section')
            ->where('code', '302')
            ->first();

        if (! $section) {
            return;
        }

        $sid = $section->id;

        // Reverse: swap WME(4) and PROCESS(3) back
        DB::connection('eudr_ts')
            ->table('m_wip_process_step')
            ->where('section_id', $sid)
            ->where('sort_order', 4)
            ->where('step_type', 'rundown')
            ->update(['sort_order' => 99]);

        DB::connection('eudr_ts')
            ->table('m_wip_process_step')
            ->where('section_id', $sid)
            ->where('sort_order', 3)
            ->where('step_type', 'label')
            ->update(['sort_order' => 4]);

        DB::connection('eudr_ts')
            ->table('m_wip_process_step')
            ->where('section_id', $sid)
            ->where('sort_order', 99)
            ->update(['sort_order' => 3]);

        // Delete UME feed
        DB::connection('eudr_ts')
            ->table('m_wip_process_step')
            ->where('section_id', $sid)
            ->where('label', 'UME 302 FT102')
            ->delete();

        // Shift back: decrement >= 2
        DB::connection('eudr_ts')
            ->table('m_wip_process_step')
            ->where('section_id', $sid)
            ->where('sort_order', '>=', 2)
            ->decrement('sort_order', 1);
    }
};
