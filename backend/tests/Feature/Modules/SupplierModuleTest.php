<?php

namespace Tests\Feature\Modules;

use Tests\TestCase;

class SupplierModuleTest extends TestCase
{
    public function test_supplier_index_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/suppliers');
        $response->assertStatus(401);
    }

    public function test_supplier_store_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/suppliers', []);
        $response->assertStatus(401);
    }
}
