<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use Tests\TestCase;

class TankModuleTest extends TestCase
{
    public function test_tank_index_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/tanks');
        $response->assertStatus(401);
    }

    public function test_tank_store_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/tanks', []);
        $response->assertStatus(401);
    }
}
