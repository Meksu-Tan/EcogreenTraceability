<?php

namespace Tests\Feature\Modules;

use Tests\TestCase;

class TransactionModuleTest extends TestCase
{
    public function test_rm_entries_index_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/transactions/rm-entries');
        $response->assertStatus(401);
    }

    public function test_transfers_storage_log_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/transactions/transfers/storage-log');
        $response->assertStatus(401);
    }
}
