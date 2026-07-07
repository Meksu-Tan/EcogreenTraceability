<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Tests\TestCase;

class WipProcessMasterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlantContextMiddleware::class);
        Gate::before(fn () => true);
    }

    public function test_it_lists_wip_process_sections_with_steps(): void
    {
        $sectionId = $this->createSection('901', 'Section 901');
        $this->createStep($sectionId, [
            'step_type' => 'feed',
            'label' => 'TEST FEEDS',
            'feed_id' => '901',
            'dcs_tag' => '901_FT0001',
        ]);

        $response = $this->actingAs($this->user(), 'sanctum')
            ->getJson('/api/v1/wip-process/sections');

        $response->assertOk()
            ->assertJsonFragment(['code' => '901'])
            ->assertJsonFragment(['label' => 'TEST FEEDS']);
    }

    public function test_it_creates_wip_process_section(): void
    {
        $response = $this->actingAs($this->user(), 'sanctum')
            ->postJson('/api/v1/wip-process/sections', [
                'code' => '902',
                'name' => 'Section 902',
                'plant_id' => null,
                'sort_order' => 10,
                'status' => 1,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.code', '902');

        $this->assertDatabaseHas('m_wip_section', [
            'code' => '902',
            'name' => 'Section 902',
        ], 'eudr_ts');
    }

    public function test_it_filters_sections_by_selected_plant_with_global_sections(): void
    {
        $this->createSection('910', 'Global Section');
        $this->createSection('911', 'Plant 1001 Section', '1001');
        $this->createSection('912', 'Plant 1002 Section', '1002');

        $response = $this->actingAs($this->user(), 'sanctum')
            ->getJson('/api/v1/wip-process/sections?id_plant=1001');

        $response->assertOk()
            ->assertJsonFragment(['code' => '910'])
            ->assertJsonFragment(['code' => '911'])
            ->assertJsonMissing(['code' => '912']);
    }

    public function test_it_creates_wip_process_step_and_updates_tree_endpoint(): void
    {
        $sectionId = $this->createSection('903', 'Section 903');

        $response = $this->actingAs($this->user(), 'sanctum')
            ->postJson('/api/v1/wip-process/steps', [
                'section_id' => $sectionId,
                'step_type' => 'rundown',
                'label' => 'TEST RUNDOWNS',
                'rundown_id' => '913',
                'dcs_tag' => '903_FT0002',
                'sort_order' => 1,
                'status' => 1,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.label', 'TEST RUNDOWNS');

        $treeResponse = $this->actingAs($this->user(), 'sanctum')
            ->getJson('/api/v1/transactions/wip-entries/tree?id_plant=0');

        $treeResponse->assertOk()
            ->assertJsonFragment(['code' => '903'])
            ->assertJsonFragment(['rundownId' => '913']);
    }

    private function user(): User
    {
        $user = new User;
        $user->id = 1;
        $user->name = 'Tester';

        return $user;
    }

    private function createSection(string $code, string $name, ?string $plantId = null): int
    {
        return (int) DB::connection('eudr_ts')->table('m_wip_section')->insertGetId([
            'code' => $code,
            'name' => $name,
            'plant_id' => $plantId,
            'sort_order' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createStep(int $sectionId, array $attributes): int
    {
        return (int) DB::connection('eudr_ts')->table('m_wip_process_step')->insertGetId(array_merge([
            'section_id' => $sectionId,
            'step_type' => 'label',
            'label' => 'START',
            'sort_order' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}
