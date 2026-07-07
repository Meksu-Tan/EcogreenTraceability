<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\User;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Tests\TestCase;

class TransactionModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlantContextMiddleware::class);
    }

    public function test_rm_entries_index_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/transactions/rm-entries');
        $response->assertStatus(401);
    }

    public function test_transfers_storage_log_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/transactions/rm-entries/storage-log');
        $response->assertStatus(401);
    }

    public function test_transfers_feed_log_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/transactions/rm-entries/feed-log');
        $response->assertStatus(401);
    }

    public function test_transfer_post_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/transactions/rm-entries/transfer', []);
        $response->assertStatus(401);
    }

    public function test_add_supplier_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/transactions/rm-entries/suppliers', []);
        $response->assertStatus(401);
    }

    public function test_add_supplier_validation(): void
    {
        $user = User::factory()->make();
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/transactions/rm-entries/suppliers', []);

        $response->assertStatus(422);
    }
}
