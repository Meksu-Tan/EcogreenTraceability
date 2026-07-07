<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use Tests\TestCase;

class InquiryModuleTest extends TestCase
{
    public function test_stock_inquiry_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/transactions/stock');
        $response->assertStatus(401);
    }

    public function test_ts_report_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/transactions/ts-report');
        $response->assertStatus(401);
    }

    public function test_rm_report_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/transactions/rm-report');
        $response->assertStatus(401);
    }
}
