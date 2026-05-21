<?php

namespace Tests\Feature\Modules;

use Tests\TestCase;

class PlantModuleTest extends TestCase
{
    public function test_plant_index_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/plants');
        $response->assertStatus(401);
    }

    public function test_plant_store_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/plants', []);
        $response->assertStatus(401);
    }
}
