<?php declare(strict_types=1);

namespace Tests\Feature\Modules;

use Tests\TestCase;

class ManufacturerModuleTest extends TestCase
{
    public function test_manufacturer_index_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/manufacturers');
        $response->assertStatus(401);
    }

    public function test_manufacturer_store_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/manufacturers', []);
        $response->assertStatus(401);
    }
}
