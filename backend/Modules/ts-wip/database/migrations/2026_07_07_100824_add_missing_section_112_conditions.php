<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * business-process.md §4.3/§7 lists fa12(039), fa14lrr(079), fa18lrr(049) as
 * Section 112/114 rundown outputs, but the original tree seed
 * (2026_06_26_000000_create_m_wip_process_tree_tables.php) never created
 * process_step rows for them.
 *
 * ponytail: mode assignments are best guess from business doc — process team
 * should confirm. 039→mode-112-2 (FA24 co-product FA12), 079→mode-112-4
 * (FA14lrr recycle), 049→mode-112-5 (FA18lrr 112/114 recycle).
 * If wrong, update conditions column via data migration.
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

        // Check if already seeded (idempotent)
        $existing = DB::connection('eudr_ts')->table('m_wip_process_step')
            ->where('section_id', $sectionId)
            ->whereIn('rundown_id', ['039', '079', '049'])
            ->exists();

        if ($existing) {
            return;
        }

        $nextSort = (int) DB::connection('eudr_ts')->table('m_wip_process_step')
            ->where('section_id', $sectionId)->max('sort_order');

        $rows = [
            [
                'label' => 'FA12 RUNDOWNS (112 F0235)',
                'rundown_id' => '039',
                'dcs_tag' => '112_F0235',
                'pipe_number' => '112 F0235',
                'conditions' => json_encode(['mode112' => 'mode-112-2']),
            ],
            [
                'label' => 'fa14lrr RUNDOWNS (112 F0224)',
                'rundown_id' => '079',
                'dcs_tag' => '112_F0224',
                'pipe_number' => '112 F0224',
                'conditions' => json_encode(['mode112' => 'mode-112-4']),
            ],
            [
                'label' => 'FA18lrr RUNDOWNS (112 F0235)',
                'rundown_id' => '049',
                'dcs_tag' => '112_F0235',
                'pipe_number' => '112 F0235',
                'conditions' => json_encode(['mode112' => 'mode-112-5']),
            ],
        ];

        foreach ($rows as $i => $row) {
            DB::connection('eudr_ts')->table('m_wip_process_step')->insert([
                'section_id' => $sectionId,
                'parent_step_id' => null,
                'step_type' => 'rundown',
                'label' => $row['label'],
                'feed_id' => null,
                'rundown_id' => $row['rundown_id'],
                'pipe_number' => $row['pipe_number'],
                'dcs_tag' => $row['dcs_tag'],
                'mode_group' => 'mode112',
                'mode_value' => null,
                'conditions' => $row['conditions'],
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
            ->where('section_id', DB::connection('eudr_ts')->table('m_wip_section')
                ->where('code', '112')->value('id'))
            ->delete();
    }
};
