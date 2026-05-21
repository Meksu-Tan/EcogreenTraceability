<?php

namespace Tests\Feature\Modules;

use Tests\TestCase;

class MaterialModuleTest extends TestCase
{
    public function test_material_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/materials');
        $response->assertStatus(401);
    }

    public function test_material_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/materials', []);
        $response->assertStatus(401);
    }
}
