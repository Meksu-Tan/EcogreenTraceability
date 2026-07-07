<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use Tests\TestCase;

class TsReportModuleTest extends TestCase
{
    public function test_tsreport_index_requires_auth(): void
    {
        $this->getJson('/api/v1/transactions/ts-report')->assertStatus(401);
    }
}
