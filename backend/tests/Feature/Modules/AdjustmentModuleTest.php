<?php declare(strict_types=1);

namespace Tests\Feature\Modules;

use Tests\TestCase;

class AdjustmentModuleTest extends TestCase
{
    public function test_adjustment_index_requires_auth(): void
    {
        $this->getJson('/api/v1/master/adjustment')->assertStatus(401);
    }

    public function test_adjustment_whx_store_requires_auth(): void
    {
        $this->postJson('/api/v1/master/adjustment/store-adjustment-whx', [])->assertStatus(401);
    }

    public function test_adjustment_init_whx_requires_auth(): void
    {
        $this->postJson('/api/v1/master/adjustment/adjustment-init-whx', [])->assertStatus(401);
    }

    public function test_adjust_status_requires_auth(): void
    {
        $this->getJson('/api/v1/master/adjustment/adjust-status')->assertStatus(401);
    }
}
