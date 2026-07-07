<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use Tests\TestCase;

class DashboardModuleTest extends TestCase
{
    public function test_dashboard_stats_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/dashboard/stats');
        $response->assertStatus(401);
    }
}
