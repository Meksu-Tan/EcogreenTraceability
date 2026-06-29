<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        Schema::connection('eudr_ts')->create('m_wip_section', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('plant_id', 20)->nullable();
            $table->integer('sort_order')->default(0);
            $table->smallInteger('status')->default(1);
            $table->timestamps();
            $table->index(['plant_id', 'status', 'sort_order']);
        });

        Schema::connection('eudr_ts')->create('m_wip_process_step', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('section_id')->constrained('m_wip_section')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_step_id')->nullable();
            $table->string('step_type', 20);
            $table->string('label', 200);
            $table->string('feed_id', 20)->nullable();
            $table->string('rundown_id', 20)->nullable();
            $table->string('pipe_number', 50)->nullable();
            $table->string('dcs_tag', 50)->nullable();
            $table->string('mode_group', 50)->nullable();
            $table->string('mode_value', 50)->nullable();
            $table->jsonb('conditions')->nullable();
            $table->jsonb('mode_options')->nullable();
            $table->integer('sort_order')->default(0);
            $table->smallInteger('status')->default(1);
            $table->timestamps();
            $table->foreign('parent_step_id')->references('id')->on('m_wip_process_step')->nullOnDelete();
            $table->index(['section_id', 'status', 'sort_order']);
            $table->index(['mode_group', 'mode_value']);
        });

        $this->seedTree();
    }

    public function down(): void
    {
        Schema::connection('eudr_ts')->dropIfExists('m_wip_process_step');
        Schema::connection('eudr_ts')->dropIfExists('m_wip_section');
    }

    private function seedTree(): void
    {
        $sections = [
            ['101', 'Section 101/102'], ['103', 'Section 103'], ['104', 'Section 104'],
            ['105', 'Section 105'], ['106', 'Section 106/114'], ['110', 'Section 110'],
            ['111', 'Section 111/116'], ['112', 'Section 112/114'], ['302', 'Section 302'],
        ];

        foreach ($sections as $index => [$code, $name]) {
            $sectionId = DB::connection('eudr_ts')->table('m_wip_section')->insertGetId([
                'code' => $code,
                'name' => $name,
                'sort_order' => $index + 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->seedSteps((int) $sectionId, $code);
        }
    }

    private function seedSteps(int $sectionId, string $code): void
    {
        $steps = match ($code) {
            '101' => [
                $this->label('START OF SECTION 101/102'), $this->feed('CPKO FEEDS (101 FT0113)', '001', '101_FT0113'),
                $this->label('PROCESS OF SECTION 101/102'), $this->rundown('DA-OIL RUNDOWNS (102 FT0109)', '011', '102_FT0109'),
                $this->rundown('PKFAD RUNDOWNS (102 FT0129)', '021', '102_FT0129'), $this->label('END OF SECTION 101/102'),
            ],
            '103' => [
                $this->label('START OF SECTION 103'), $this->feed('DA-OIL FEEDS (103 FT0101)', '002', '103_FT0101'),
                $this->label('PROCESS OF SECTION 103'), $this->rundown('CRUDE-ME RUNDOWNS (103 FT0329)', '012', '103_FT0329'),
                $this->rundown('TREATED-GLY RUNDOWNS (103 FT0266)', '022', '103_FT0266'), $this->label('END OF SECTION 103'),
            ],
            '104' => [
                $this->mode('Mode', 'mode104', '1', [['value' => '1', 'label' => 'Mode 1'], ['value' => '2', 'label' => 'Mode 2']], 'toggle'),
                $this->label('START OF SECTION 104'), $this->feed('CRUDE-ME FEEDS (104 F0110)', '003', '104_F0110'),
                $this->label('PROCESS OF SECTION 104'), $this->rundown('BDME RUNDOWNS', '023'), $this->rundown('UME RUNDOWNS (104 F0110)', '033', '104_F0110'),
                $this->rundown('ME28 RUNDOWNS (104 F0332)', '043', '104_F0332'), $this->rundown('ECONOATE 665 RUNDOWNS', '053'),
                $this->rundown('ME80 RUNDOWNS', '063'), $this->rundown('ME60 RUNDOWNS (104 FT0157)', '013', '104_FT0157', ['mode104' => '1']),
                $this->label('END OF SECTION 104'),
            ],
            '105' => [
                $this->mode('Mode', 'mode105', '1', [['value' => '1', 'label' => 'Mode 1'], ['value' => '2', 'label' => 'Mode 2']], 'toggle'),
                $this->label('START OF SECTION 105'),
                $this->mode('Chain', 'mode105_chain', 'me28', [['value' => 'me28', 'label' => 'Short Chain (ME28)'], ['value' => 'me80', 'label' => 'Long Chain (ME80)']], 'select', ['mode105' => '1']),
                $this->feed('ME28 FEEDS (105 FQ104)', '006-01', '105_FQ104', ['mode105' => '1', 'mode105_chain' => 'me28']),
                $this->rundown('CFA28 RUNDOWNS (105 FQ808)', '016', '105_FQ808', ['mode105' => '1', 'mode105_chain' => 'me28']),
                $this->feed('ME80 FEEDS (105 FQ104)', '006-02', '105_FQ104', ['mode105' => '1', 'mode105_chain' => 'me80']),
                $this->rundown('CFA80 RUNDOWNS (105 FQ808)', '026', '105_FQ808', ['mode105' => '1', 'mode105_chain' => 'me80']),
                $this->feed('ME80 FEEDS (105 FQ104)', '006-02', '105_FQ104', ['mode105' => '2']),
                $this->label('PROCESS OF SECTION 105'), $this->rundown('CFA80 RUNDOWNS (105 FQ808)', '026', '105_FQ808', ['mode105' => '2']),
                $this->label('END OF SECTION 105'),
            ],
            '106' => [
                $this->mode('Mode', 'mode106_major', '1', [['value' => '1', 'label' => 'Mode 1'], ['value' => '2', 'label' => 'Mode 2']], 'toggle'),
                $this->label('START OF SECTION 106/114'),
                $this->mode('Mode', 'mode106', 'mode-106-1', [['value' => 'mode-106-1', 'label' => '- Mode ECOROL 24 -'], ['value' => 'mode-106-2', 'label' => '- Mode ECOROL 12/14 -']], 'select', ['mode106_major' => '1']),
                $this->feed('CFA28 FEEDS (106 F0115)', '008-01', '106_F0115', ['mode106_major' => '1']),
                $this->feed('CFA80 FEEDS (106 F0115)', '008-02', '106_F0115', ['mode106_major' => '2']),
                $this->rundown('ECOROL-WAX RUNDOWNS (106 F0245)', '018', '106_F0245', ['mode106_major' => '1']),
                $this->rundown('CFA28 RUNDOWNS (106 F0245)', '098', '106_F0245', ['mode106_major' => '2']),
                $this->rundown('LEFA RUNDOWNS (106 F0167)', '028', '106_F0167'),
                $this->rundown('FA24 RUNDOWNS (106 F0134)', '038', '106_F0134', ['mode106_major' => '1', 'mode106' => 'mode-106-1']),
                $this->rundown('FA16/99 RUNDOWNS (106 F0231)', '048', '106_F0231', ['mode106_major' => '1', 'mode106' => 'mode-106-1']),
                $this->rundown('FA18lrr RUNDOWNS (106 F0112)', '058', '106_F0112', ['mode106_major' => '1', 'mode106' => 'mode-106-1']),
                $this->rundown('FA26 RUNDOWNS (106 F0134)', '068', '106_F0134', ['mode106_major' => '1', 'mode106' => 'mode-106-1']),
                $this->rundown('FA12/99 RUNDOWNS (106 F0134)', '078', '106_F0134', ['mode106_major' => '1', 'mode106' => 'mode-106-2']),
                $this->rundown('FA14/99 RUNDOWNS (106 F0231)', '088', '106_F0231', ['mode106_major' => '1', 'mode106' => 'mode-106-2']),
                $this->rundown('FA8 RUNDOWNS (106 F0134)', '108', '106_F0134', ['mode106_major' => '2']),
                $this->rundown('FA10 RUNDOWNS (106 F0231)', '118', '106_F0231', ['mode106_major' => '2']),
                $this->label('PROCESS OF SECTION 106/114'), $this->label('END OF SECTION 106/114'),
            ],
            '110' => [$this->label('START OF SECTION 110'), $this->feed('TREATED-GLY FEEDS (110 F0107)', '004', '110_F0107'), $this->label('PROCESS OF SECTION 110'), $this->rundown('CRUDE-GLY RUNDOWNS (110 F0108)', '014', '110_F0108'), $this->label('END OF SECTION 110')],
            '111' => [$this->label('START OF SECTION 111/116'), $this->feed('CRUDE-GLY FEEDS (111 F0118 + 116 FC01)', '007', '111_F0118_116_FC01'), $this->label('PROCESS OF SECTION 111/116'), $this->rundown('GLYCERINE RUNDOWNS', '017'), $this->label('END OF SECTION 111/116')],
            '112' => [
                $this->label('START OF SECTION 112/114'), $this->mode('Mode', 'mode112', 'mode-112-1', [['value' => 'mode-112-1', 'label' => '- Mode ECOROL WAX 106/114 -'], ['value' => 'mode-112-2', 'label' => '- Mode FA24 106/114 -'], ['value' => 'mode-112-3', 'label' => '- Mode FA18lrr 106/114 -'], ['value' => 'mode-112-4', 'label' => '- Mode FA14lrr 112/114 -'], ['value' => 'mode-112-5', 'label' => '- Mode FA18lrr 112/114 -']], 'select'),
                $this->feed('ECOROL WAX FEEDS (112 F0109)', '009-04', '112_F0109', ['mode112' => 'mode-112-1']),
                $this->feed('FA24 FEEDS (112 F0109)', '009-01', '112_F0109', ['mode112' => 'mode-112-2']),
                $this->feed('FA18lrr FEEDS (112 F0109)', '009-03', '112_F0109', ['mode112' => 'mode-112-3']),
                $this->feed('FA14lrr FEEDS (112 F0109)', '009-02', '112_F0109', ['mode112' => 'mode-112-4']),
                $this->feed('FA18lrr FEEDS (112 F0109)', '009-03', '112_F0109', ['mode112' => 'mode-112-5']),
                $this->label('PROCESS OF SECTION 112/114'), $this->rundown('ECOROL-WAX RUNDOWNS (106 F0245)', '018', '106_F0245', ['mode112' => 'mode-112-1']),
                $this->rundown('FA24 RUNDOWNS (106 F0134)', '038', '106_F0134', ['mode112' => 'mode-112-2']),
                $this->rundown('FA18lrr RUNDOWNS (106 F0112)', '058', '106_F0112', ['mode112' => 'mode-112-3']),
                $this->rundown('CFA28 RUNDOWNS (112 F0139)', '069', '112_F0139', ['mode112' => ['mode-112-4', 'mode-112-5']]),
                $this->rundown('FA14/99 RUNDOWNS (112 F0224)', '059', '112_F0224', ['mode112' => 'mode-112-4']),
                $this->rundown('FA18/99 RUNDOWNS (112 F0235)', '029', '112_F0235', ['mode112' => 'mode-112-5']),
                $this->rundown('ECOROL WAX RUNDOWNS (112 F0224)', '019', '112_F0224', ['mode112' => 'mode-112-5']),
                $this->label('END OF SECTION 112/114'),
            ],
            '302' => [$this->label('START OF SECTION 302'), $this->rundown('WME RUNDOWNS', '015'), $this->label('PROCESS OF SECTION 302'), $this->rundown('ME28-302 RUNDOWNS (302V04)', '025', '302V04'), $this->label('END OF SECTION 302')],
        };

        foreach ($steps as $index => $step) {
            DB::connection('eudr_ts')->table('m_wip_process_step')->insert(array_merge($step, [
                'section_id' => $sectionId,
                'sort_order' => $index + 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    private function label(string $label): array
    {
        return ['step_type' => 'label', 'label' => $label];
    }

    private function feed(string $label, string $feedId, ?string $tag = null, ?array $conditions = null): array
    {
        return $this->step('feed', $label, $feedId, null, $tag, $conditions);
    }

    private function rundown(string $label, string $rundownId, ?string $tag = null, ?array $conditions = null): array
    {
        return $this->step('rundown', $label, null, $rundownId, $tag, $conditions);
    }

    private function mode(string $label, string $group, string $default, array $options, string $type, ?array $conditions = null): array
    {
        return [
            'step_type' => 'mode_switch',
            'label' => $label,
            'mode_group' => $group,
            'mode_value' => $default,
            'conditions' => $conditions ? json_encode($conditions) : null,
            'mode_options' => json_encode(['type' => $type, 'default' => $default, 'options' => $options]),
        ];
    }

    private function step(string $type, string $label, ?string $feedId, ?string $rundownId, ?string $tag, ?array $conditions): array
    {
        return [
            'step_type' => $type,
            'label' => $label,
            'feed_id' => $feedId,
            'rundown_id' => $rundownId,
            'pipe_number' => $tag ? str_replace('_', ' ', $tag) : null,
            'dcs_tag' => $tag,
            'conditions' => $conditions ? json_encode($conditions) : null,
        ];
    }
};
