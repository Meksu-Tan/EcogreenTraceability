<?php

namespace Tests\Feature\Modules;

use Tests\TestCase;

class AuthModuleTest extends TestCase
{
    public function test_login_requires_credentials(): void
    {
        $response = $this->postJson('/api/v1/login', []);
        $response->assertStatus(422);

        $responseNonV1 = $this->postJson('/api/login', []);
        $responseNonV1->assertStatus(422);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/logout');
        $response->assertStatus(401);

        $responseNonV1 = $this->postJson('/api/logout');
        $responseNonV1->assertStatus(401);
    }

    public function test_user_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/user');
        $response->assertStatus(401);

        $responseNonV1 = $this->getJson('/api/user');
        $responseNonV1->assertStatus(401);
    }
}
