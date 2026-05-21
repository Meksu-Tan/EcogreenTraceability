<?php

namespace Tests\Feature\Modules;

use Tests\TestCase;

class StorageModuleTest extends TestCase
{
    public function test_storage_tanks_index_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/storage-tanks');
        $response->assertStatus(401);
    }

    public function test_storage_tanks_store_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/storage-tanks', []);
        $response->assertStatus(401);
    }

    public function test_storage_details_index_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/storage-details');
        $response->assertStatus(401);
    }
}
