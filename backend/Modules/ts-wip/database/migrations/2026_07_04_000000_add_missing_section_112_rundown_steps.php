<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * business-process.md §4.3/§7 lists fa12(039), fa14lrr(079), fa18lrr(049) as
 * Section 112/114 rundown outputs, but the original tree seed
 * (2026_06_26_000000_create_m_wip_process_tree_tables.php) never created
 * process_step rows for them — those products had no trace/balance path.
 *
 * ponytail: no confirmed mode112 condition or DCS tag exists for these three
 * yet (business doc gives rundown_id only, not physical tag/mode mapping).
 * Seeded unconditional (always visible, no dcs_tag) — safer than guessing
 * wrong mode/tag. Follow up with a data migration once process team confirms
 * pipe_number/dcs_tag and which mode112 option each belongs to.
 */
return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        $sectionId = DB::connection('eudr_ts')->table('m_wip_section')
            ->where('code', '112')->value('id');

        if (! $sectionId) {
            return;
        }

        $nextSort = (int) DB::connection('eudr_ts')->table('m_wip_process_step')
            ->where('section_id', $sectionId)->max('sort_order');

        $rows = [
            ['label' => 'FA12 RUNDOWNS', 'rundown_id' => '039'],
            ['label' => 'FA14lrr RUNDOWNS', 'rundown_id' => '079'],
            ['label' => 'FA18lrr RUNDOWNS', 'rundown_id' => '049'],
        ];

        foreach ($rows as $i => $row) {
            DB::connection('eudr_ts')->table('m_wip_process_step')->insert([
                'section_id' => $sectionId,
                'parent_step_id' => null,
                'step_type' => 'rundown',
                'label' => $row['label'],
                'feed_id' => null,
                'rundown_id' => $row['rundown_id'],
                'pipe_number' => null,
                'dcs_tag' => null,
                'mode_group' => null,
                'mode_value' => null,
                'conditions' => null,
                'mode_options' => null,
                'sort_order' => $nextSort + $i + 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::connection('eudr_ts')->table('m_wip_process_step')
            ->whereIn('rundown_id', ['039', '079', '049'])
            ->where('step_type', 'rundown')
            ->whereNull('dcs_tag')
            ->delete();
    }
};
