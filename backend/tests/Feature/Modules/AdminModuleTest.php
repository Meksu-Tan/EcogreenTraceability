<?php

namespace Tests\Feature\Modules;

use Tests\TestCase;

class AdminModuleTest extends TestCase
{
    public function test_admin_users_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/admin/users');
        $response->assertStatus(401);
    }
}
