<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use Tests\TestCase;

class StockModuleTest extends TestCase
{
    public function test_stock_index_requires_auth(): void
    {
        $this->getJson('/api/v1/transactions/stock')->assertStatus(401);
    }
}
