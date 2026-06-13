<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\Shared\Services\PeriodLockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;

class PeriodLockServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // isLocked — returns true when lock_status = '1'
    // -------------------------------------------------------------------------

    public function test_it_returns_true_when_period_is_locked(): void
    {
        $lockRow = [(object)['lock_status' => '1']];

        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('select')
            ->once()
            ->andReturn($lockRow);

        $result = PeriodLockService::isLocked('2026-06-01');

        $this->assertTrue($result);
    }

    public function test_it_returns_false_when_period_is_not_locked(): void
    {
        // Primary table returns lock_status='0' (not locked), then falls through
        // to the fallback table which also returns empty (no lock).
        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->twice()
            ->andReturnSelf();

        DB::shouldReceive('select')
            ->twice()
            ->andReturnValues([
                [(object)['lock_status' => '0']],
                [],
            ]);

        $result = PeriodLockService::isLocked('2026-05-01');

        $this->assertFalse($result);
    }

    public function test_it_returns_false_when_no_period_lock_record_exists(): void
    {
        // Primary table returns empty; fallback also returns empty
        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->twice()
            ->andReturnSelf();

        DB::shouldReceive('select')
            ->twice()
            ->andReturn([]);

        $result = PeriodLockService::isLocked('2026-04-01');

        $this->assertFalse($result);
    }

    public function test_it_falls_back_to_pspa_head_when_m_period_lock_table_missing(): void
    {
        // First call throws (table not found), second returns locked row
        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->twice()
            ->andReturnSelf();

        DB::shouldReceive('select')
            ->once()
            ->andThrow(new \Illuminate\Database\QueryException('eudr_ts', 'SELECT', [], new \Exception('Table does not exist')));

        Log::shouldReceive('debug')->once();

        DB::shouldReceive('select')
            ->once()
            ->andReturn([(object)['lock_status' => '1']]);

        $result = PeriodLockService::isLocked('2026-03-01');

        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // lock
    // -------------------------------------------------------------------------

    public function test_it_inserts_new_period_lock_record(): void
    {
        // getExisting returns empty (no existing record) — 2 DB::connection calls total:
        // 1 for select (check existing), 1 for insert (new record)
        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->twice()
            ->andReturnSelf();

        DB::shouldReceive('select')
            ->once()
            ->andReturn([]);

        DB::shouldReceive('insert')
            ->once()
            ->andReturn(true);

        $result = PeriodLockService::lock('2026-07-01', 'admin', 'Month-end close');

        $this->assertSame(1, $result['response']);
        $this->assertSame('Period locked successfully', $result['message']);
    }

    public function test_it_returns_already_locked_when_period_is_already_locked(): void
    {
        $existing = [(object)['id' => 1, 'lock_status' => '1']];

        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('select')
            ->once()
            ->andReturn($existing);

        $result = PeriodLockService::lock('2026-06-01', 'admin');

        $this->assertSame(2, $result['response']);
        $this->assertSame('Period already locked', $result['message']);
    }

    public function test_it_returns_error_response_when_lock_throws_exception(): void
    {
        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('select')
            ->once()
            ->andThrow(new \Exception('Connection refused'));

        Log::shouldReceive('error')->once();

        $result = PeriodLockService::lock('2026-06-01', 'admin');

        $this->assertSame(0, $result['response']);
        $this->assertStringContainsString('Failed to lock period', $result['message']);
    }

    // -------------------------------------------------------------------------
    // unlock
    // -------------------------------------------------------------------------

    public function test_it_unlocks_a_locked_period_successfully(): void
    {
        $existing = [(object)['id' => 1, 'lock_status' => '1']];

        // 2 DB::connection calls: 1 for select (check existing), 1 for update
        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->twice()
            ->andReturnSelf();

        DB::shouldReceive('select')
            ->once()
            ->andReturn($existing);

        DB::shouldReceive('update')
            ->once()
            ->andReturn(1);

        $result = PeriodLockService::unlock('2026-06-01', 'admin');

        $this->assertSame(1, $result['response']);
        $this->assertSame('Period unlocked successfully', $result['message']);
    }

    public function test_it_returns_not_found_when_unlocking_nonexistent_period(): void
    {
        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('select')
            ->once()
            ->andReturn([]);

        $result = PeriodLockService::unlock('2026-01-01', 'admin');

        $this->assertSame(0, $result['response']);
        $this->assertSame('Period not found', $result['message']);
    }

    public function test_it_returns_already_unlocked_when_period_is_not_locked(): void
    {
        $existing = [(object)['id' => 2, 'lock_status' => '0']];

        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('select')
            ->once()
            ->andReturn($existing);

        $result = PeriodLockService::unlock('2026-05-01', 'admin');

        $this->assertSame(2, $result['response']);
        $this->assertSame('Period already unlocked', $result['message']);
    }

    // -------------------------------------------------------------------------
    // getLockedPeriods
    // -------------------------------------------------------------------------

    public function test_it_returns_locked_periods_in_date_range(): void
    {
        $rows = [
            (object)['period' => '2026-05-01', 'lock_status' => '1', 'locked_by' => 'admin'],
            (object)['period' => '2026-06-01', 'lock_status' => '1', 'locked_by' => 'admin'],
        ];

        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('select')
            ->once()
            ->andReturn($rows);

        $result = PeriodLockService::getLockedPeriods('2026-05-01', '2026-06-30');

        $this->assertCount(2, $result);
        $this->assertSame('2026-05-01', $result[0]->period);
    }

    public function test_it_returns_empty_array_when_get_locked_periods_throws_exception(): void
    {
        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->once()
            ->andReturnSelf();

        DB::shouldReceive('select')
            ->once()
            ->andThrow(new \Exception('Table not found'));

        Log::shouldReceive('warning')->once();

        $result = PeriodLockService::getLockedPeriods('2026-01-01', '2026-12-31');

        $this->assertSame([], $result);
    }
}
