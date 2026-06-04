<?php declare(strict_types=1);

namespace Tests\Feature\Modules;

use Tests\TestCase;

class RmReportModuleTest extends TestCase
{
    public function test_rmreport_index_requires_auth(): void
    {
        $this->getJson('/api/v1/transactions/rm-report')->assertStatus(401);
    }
}
