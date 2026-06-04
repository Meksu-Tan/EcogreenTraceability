<?php declare(strict_types=1);

namespace Tests\Feature\Modules;

use Tests\TestCase;

class QuantifierModuleTest extends TestCase
{
    public function test_quantifier_index_requires_auth(): void
    {
        $this->getJson('/api/v1/master/quantifier')->assertStatus(401);
    }

    public function test_quantifier_flowmeters_requires_auth(): void
    {
        $this->getJson('/api/v1/master/quantifier/flowmeters')->assertStatus(401);
    }

    public function test_quantifier_store_requires_auth(): void
    {
        $this->postJson('/api/v1/master/quantifier', [])->assertStatus(401);
    }
}
