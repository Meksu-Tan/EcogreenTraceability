<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Modules\Shared\Services\AuditService;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // log — success
    // -------------------------------------------------------------------------

    public function test_it_logs_transaction_successfully(): void
    {
        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('insert')
            ->once()
            ->with(
                Mockery::type('string'),
                ['BLENDING', 'ADD', 'Test description', 'admin']
            )
            ->andReturn(true);

        $result = AuditService::log('BLENDING', 'ADD', 'Test description', 'admin');

        $this->assertTrue($result);
    }

    public function test_it_returns_false_when_db_insert_throws_exception(): void
    {
        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('insert')
            ->once()
            ->andThrow(new \Exception('DB connection failed'));

        Log::shouldReceive('error')->once();

        $result = AuditService::log('BLENDING', 'ADD', 'Test', 'admin');

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // logTransfer
    // -------------------------------------------------------------------------

    public function test_it_logs_transfer_create_operation(): void
    {
        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('insert')
            ->once()
            ->with(
                Mockery::type('string'),
                Mockery::on(function (array $params) {
                    return $params[0] === 'TRANSFER' && $params[1] === 'CREATE';
                })
            )
            ->andReturn(true);

        $data = [
            'entry_no' => 'TRF-2606001',
            'id_material' => 3,
            'trf_qty' => '500.000',
            'source_sloc' => 'SL01',
            'trf_sloc' => 'SL02',
        ];

        // Should not throw
        AuditService::logTransfer('CREATE', $data, 'admin', 1);

        $this->assertTrue(true); // Assertion that no exception was thrown
    }

    // -------------------------------------------------------------------------
    // logWip
    // -------------------------------------------------------------------------

    public function test_it_logs_wip_feed_operation(): void
    {
        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('insert')
            ->once()
            ->with(
                Mockery::type('string'),
                Mockery::on(function (array $params) {
                    return $params[0] === 'WIP' && $params[1] === 'FEED';
                })
            )
            ->andReturn(true);

        $data = ['entry_no' => 'WIP-2606001', 'section_id' => 1, 'qty' => '100.000'];

        AuditService::logWip('FEED', $data, 'supervisor', 1);

        $this->assertTrue(true);
    }

    // -------------------------------------------------------------------------
    // logBlending
    // -------------------------------------------------------------------------

    public function test_it_logs_blending_delete_operation(): void
    {
        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('insert')
            ->once()
            ->with(
                Mockery::type('string'),
                Mockery::on(function (array $params) {
                    return $params[0] === 'BLENDING' && $params[1] === 'DELETE';
                })
            )
            ->andReturn(true);

        $data = ['entry_no' => '82605240010101', 'id_material' => 3, 'qty' => '200.000'];

        AuditService::logBlending('DELETE', $data, 'manager', 1);

        $this->assertTrue(true);
    }

    // -------------------------------------------------------------------------
    // logAdjustment
    // -------------------------------------------------------------------------

    public function test_it_logs_adjustment_approve_operation(): void
    {
        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('insert')
            ->once()
            ->with(
                Mockery::type('string'),
                Mockery::on(function (array $params) {
                    return $params[0] === 'ADJUSTMENT' && $params[1] === 'APPROVE';
                })
            )
            ->andReturn(true);

        $data = [
            'adj_no' => 'ADJ-2606001',
            'id_material' => 5,
            'qty' => '50.000',
            'before_adjust' => '200.000',
            'after_adjust' => '250.000',
        ];

        AuditService::logAdjustment('APPROVE', $data, 'admin', 1);

        $this->assertTrue(true);
    }

    // -------------------------------------------------------------------------
    // logPeriodLock
    // -------------------------------------------------------------------------

    public function test_it_logs_period_lock_operation(): void
    {
        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('insert')
            ->once()
            ->with(
                Mockery::type('string'),
                Mockery::on(function (array $params) {
                    return $params[0] === 'PERIOD_LOCK' && $params[1] === 'LOCK';
                })
            )
            ->andReturn(true);

        AuditService::logPeriodLock('LOCK', '2026-06-01', 'admin', 1);

        $this->assertTrue(true);
    }

    // -------------------------------------------------------------------------
    // getLogs
    // -------------------------------------------------------------------------

    public function test_it_returns_logs_with_module_filter(): void
    {
        $rows = [
            (object) ['log_module' => 'BLENDING', 'log_type' => 'ADD', 'created_by' => 'admin'],
        ];

        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('select')
            ->once()
            ->andReturn($rows);

        $result = AuditService::getLogs(['module' => 'BLENDING'], 50);

        $this->assertSame($rows, $result);
    }

    public function test_it_returns_empty_array_when_get_logs_throws_exception(): void
    {
        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('select')
            ->once()
            ->andThrow(new \Exception('Query failed'));

        Log::shouldReceive('error')->once();

        $result = AuditService::getLogs(['module' => 'WIP'], 10);

        $this->assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // getSummary
    // -------------------------------------------------------------------------

    public function test_it_returns_summary_grouped_by_module_and_type(): void
    {
        $rows = [
            (object) ['log_module' => 'BLENDING', 'log_type' => 'ADD', 'total' => 5],
            (object) ['log_module' => 'TRANSFER', 'log_type' => 'CREATE', 'total' => 12],
        ];

        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('select')
            ->once()
            ->andReturn($rows);

        $result = AuditService::getSummary('2026-06-01', '2026-06-30');

        $this->assertCount(2, $result);
        $this->assertSame('BLENDING', $result[0]->log_module);
    }

    public function test_it_returns_empty_array_when_get_summary_throws_exception(): void
    {
        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('select')
            ->once()
            ->andThrow(new \Exception('Connection timeout'));

        Log::shouldReceive('error')->once();

        $result = AuditService::getSummary('2026-01-01', '2026-01-31');

        $this->assertSame([], $result);
    }
}
