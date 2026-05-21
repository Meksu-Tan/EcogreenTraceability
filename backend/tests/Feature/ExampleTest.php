<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example — verifies API returns 401 for unauthenticated requests.
     */
    public function test_the_application_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/user');

        $response->assertStatus(401);
    }
}
