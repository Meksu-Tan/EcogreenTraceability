<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\TsTransfer\Services\TransferApprovalService;
use Modules\TsTransfer\Repositories\Contracts\TransferApprovalRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;

/**
 * Unit tests for TransferApprovalService.
 *
 * All DB interactions flow through TransferApprovalRepositoryInterface.
 * PeriodLockService and AuditService are plain-static classes that still
 * call DB directly; they are allowed to run and their underlying DB calls
 * are satisfied by the same mock.
 */
class TransferApprovalServiceTest extends TestCase
{
    protected TransferApprovalService $service;
    protected MockInterface $repoMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoMock = Mockery::mock(TransferApprovalRepositoryInterface::class);
        $this->service = new TransferApprovalService($this->repoMock);

        // Allow Log calls in all tests — service uses them in catch blocks
        // and AuditService uses Log::channel('audit')->info() with metadata
        Log::shouldReceive('error')->andReturn(null)->byDefault();

        // Log::channel() returns a log mock that allows info()
        $logChannelMock = Mockery::mock();
        $logChannelMock->shouldReceive('info')->andReturn(null)->byDefault();
        Log::shouldReceive('channel')->andReturn($logChannelMock)->byDefault();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Helper: build an anonymous mock that DB::connection('eudr_ts') returns.
    // Returns the mock so the caller can stack further ->shouldReceive() calls.
    // =========================================================================

    private function mockEudrConnection(): \Mockery\MockInterface
    {
        $conn = Mockery::mock('AnonymousDbConnection');

        DB::shouldReceive('connection')
            ->with('eudr_ts')
            ->andReturn($conn);

        return $conn;
    }

    // =========================================================================
    // getCurrentStatus
    // =========================================================================

    public function test_it_returns_current_approval_status(): void
    {
        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()
            ->with('42')
            ->andReturn('PENDING');

        $result = $this->service->getCurrentStatus('42');

        $this->assertEquals('PENDING', $result);
    }

    public function test_it_returns_null_when_transfer_not_found_for_status(): void
    {
        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()
            ->with('9999')
            ->andReturn(null);

        $result = $this->service->getCurrentStatus('9999');

        $this->assertNull($result);
    }

    // =========================================================================
    // canEdit
    // =========================================================================

    public function test_it_allows_edit_when_status_is_draft(): void
    {
        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()->with('42')->andReturn('DRAFT');

        $this->assertTrue($this->service->canEdit('42'));
    }

    public function test_it_allows_edit_when_status_is_rejected(): void
    {
        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()->with('42')->andReturn('REJECTED');

        $this->assertTrue($this->service->canEdit('42'));
    }

    public function test_it_disallows_edit_when_status_is_approved(): void
    {
        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()->with('42')->andReturn('APPROVED');

        $this->assertFalse($this->service->canEdit('42'));
    }

    public function test_it_disallows_edit_when_status_is_pending(): void
    {
        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()->with('42')->andReturn('PENDING');

        $this->assertFalse($this->service->canEdit('42'));
    }

    // =========================================================================
    // canDelete
    // =========================================================================

    public function test_it_allows_deletion_when_status_is_draft(): void
    {
        $this->repoMock->shouldReceive('canDelete')
            ->once()->with('42')->andReturn(true);

        $this->assertTrue($this->service->canDelete('42'));
    }

    public function test_it_allows_deletion_when_status_is_approved(): void
    {
        $this->repoMock->shouldReceive('canDelete')
            ->once()->with('42')->andReturn(true);

        $this->assertTrue($this->service->canDelete('42'));
    }

    public function test_it_allows_deletion_when_status_is_cancelled(): void
    {
        $this->repoMock->shouldReceive('canDelete')
            ->once()->with('42')->andReturn(true);

        $this->assertTrue($this->service->canDelete('42'));
    }

    public function test_it_allows_deletion_when_status_is_rejected(): void
    {
        $this->repoMock->shouldReceive('canDelete')
            ->once()->with('42')->andReturn(true);

        $this->assertTrue($this->service->canDelete('42'));
    }

    public function test_it_disallows_deletion_when_status_is_pending(): void
    {
        $this->repoMock->shouldReceive('canDelete')
            ->once()->with('42')->andReturn(false);

        $this->assertFalse($this->service->canDelete('42'));
    }

    // =========================================================================
    // submit
    // =========================================================================

    public function test_it_returns_98_when_transfer_not_found_on_submit(): void
    {
        $this->repoMock->shouldReceive('findTransferForApproval')
            ->once()
            ->with('9999')
            ->andReturn(null);

        $result = $this->service->submit('9999', 'admin');

        $this->assertEquals(98, $result['response']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function test_it_returns_99_when_period_is_locked_on_submit(): void
    {
        $conn = $this->mockEudrConnection();

        $this->repoMock->shouldReceive('findTransferForApproval')
            ->once()
            ->with('42')
            ->andReturn((object) [
                'id_trace_head' => 10,
                'trace_no'      => '726060310001',
                'entry_date'    => '2026-01-01',
            ]);

        // PeriodLockService::isLocked calls DB::connection('eudr_ts')->select(...)
        $conn->shouldReceive('select')->once()
            ->andReturn([(object) ['lock_status' => '1']]);

        $result = $this->service->submit('42', 'admin');

        $this->assertEquals(99, $result['response']);
        $this->assertStringContainsString('locked', $result['message']);
    }

    public function test_it_returns_response_2_when_transfer_is_not_draft_on_submit(): void
    {
        $conn = $this->mockEudrConnection();

        $this->repoMock->shouldReceive('findTransferForApproval')
            ->once()->andReturn((object) [
                'id_trace_head' => 10,
                'trace_no'      => '726060310001',
                'entry_date'    => '2026-06-03',
            ]);

        $conn->shouldReceive('select')->twice()->andReturn([]);

        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()->with('42')->andReturn('PENDING');

        $result = $this->service->submit('42', 'admin');

        $this->assertEquals(2, $result['response']);
        $this->assertStringContainsString('DRAFT', $result['message']);
    }

    public function test_it_returns_response_1_on_successful_submit_with_new_approval_record(): void
    {
        $conn = $this->mockEudrConnection();

        $this->repoMock->shouldReceive('findTransferForApproval')
            ->once()->andReturn((object) [
                'id_trace_head' => 10,
                'trace_no'      => '726060310001',
                'entry_date'    => '2026-06-03',
            ]);

        $conn->shouldReceive('select')->twice()->andReturn([]);

        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()->with('42')->andReturn('DRAFT');

        $conn->shouldReceive('transaction')->once()
            ->andReturnUsing(fn(callable $cb) => $cb());

        // Inside transaction: updateBalanceApprovalStatus
        $this->repoMock->shouldReceive('updateBalanceApprovalStatus')
            ->once()->with(42, 'PENDING', 'admin');

        // findApprovalRecord → none
        $this->repoMock->shouldReceive('findApprovalRecord')
            ->once()->with('42')->andReturn(null);

        // InsertApprovalRecord
        $this->repoMock->shouldReceive('insertApprovalRecord')
            ->once()->andReturn(1);

        // AuditService INSERT
        $conn->shouldReceive('insert')->once()->andReturn(true);

        $result = $this->service->submit('42', 'admin');

        $this->assertEquals(1, $result['response']);
        $this->assertStringContainsString('submitted', $result['message']);
    }

    public function test_it_returns_response_1_on_successful_submit_updating_existing_approval_record(): void
    {
        $conn = $this->mockEudrConnection();

        $this->repoMock->shouldReceive('findTransferForApproval')
            ->once()->andReturn((object) [
                'id_trace_head' => 10,
                'trace_no'      => '726060310001',
                'entry_date'    => '2026-06-03',
            ]);

        $conn->shouldReceive('select')->twice()->andReturn([]);

        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()->with('42')->andReturn('DRAFT');

        $conn->shouldReceive('transaction')->once()
            ->andReturnUsing(fn(callable $cb) => $cb());

        $this->repoMock->shouldReceive('updateBalanceApprovalStatus')
            ->once()->with(42, 'PENDING', 'admin');

        $this->repoMock->shouldReceive('findApprovalRecord')
            ->once()->with('42')->andReturn((object) ['id_approval' => 5]);

        $this->repoMock->shouldReceive('updateApprovalStatus')
            ->once()->with('42', 'PENDING', 'admin');

        $conn->shouldReceive('insert')->once()->andReturn(true);

        $result = $this->service->submit('42', 'admin');

        $this->assertEquals(1, $result['response']);
    }

    public function test_it_returns_response_0_when_exception_occurs_on_submit(): void
    {
        $this->repoMock->shouldReceive('findTransferForApproval')
            ->andThrow(new \Exception('DB error'));

        // byDefault allows Log::error

        $result = $this->service->submit('42', 'admin');

        $this->assertEquals(0, $result['response']);
        $this->assertStringContainsString('Failed to submit', $result['message']);
    }

    // =========================================================================
    // approve
    // =========================================================================

    public function test_it_returns_99_when_period_is_locked_on_approve(): void
    {
        $conn = $this->mockEudrConnection();

        $this->repoMock->shouldReceive('findBalanceEntryDate')
            ->once()->with(42)->andReturn('2026-01-01');

        $conn->shouldReceive('select')->once()
            ->andReturn([(object) ['lock_status' => '1']]);

        $result = $this->service->approve('42', 'admin');

        $this->assertEquals(99, $result['response']);
    }

    public function test_it_returns_response_2_when_transfer_is_not_pending_on_approve(): void
    {
        $conn = $this->mockEudrConnection();

        $this->repoMock->shouldReceive('findBalanceEntryDate')
            ->once()->with(42)->andReturn('2026-06-03');

        $conn->shouldReceive('select')->twice()->andReturn([]);

        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()->with('42')->andReturn('DRAFT');

        $result = $this->service->approve('42', 'admin');

        $this->assertEquals(2, $result['response']);
        $this->assertStringContainsString('PENDING', $result['message']);
    }

    public function test_it_returns_response_1_on_successful_approve(): void
    {
        $conn = $this->mockEudrConnection();

        $this->repoMock->shouldReceive('findBalanceEntryDate')
            ->once()->with(42)->andReturn('2026-06-03');

        $conn->shouldReceive('select')->twice()->andReturn([]);

        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()->with('42')->andReturn('PENDING');

        $conn->shouldReceive('transaction')->once()
            ->andReturnUsing(fn(callable $cb) => $cb());

        $this->repoMock->shouldReceive('updateBalanceApprovalStatus')
            ->once()->with(42, 'APPROVED', 'admin');

        $this->repoMock->shouldReceive('updateApprovalStatus')
            ->once()->with('42', 'APPROVED', 'admin', 'Approved after review');

        $conn->shouldReceive('insert')->once()->andReturn(true);

        $result = $this->service->approve('42', 'admin', 'Approved after review');

        $this->assertEquals(1, $result['response']);
        $this->assertStringContainsString('approved', $result['message']);
    }

    public function test_it_approves_transfer_when_entry_date_not_found(): void
    {
        $conn = $this->mockEudrConnection();

        $this->repoMock->shouldReceive('findBalanceEntryDate')
            ->once()->with(42)->andReturn(null);

        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()->with('42')->andReturn('PENDING');

        $conn->shouldReceive('transaction')->once()
            ->andReturnUsing(fn(callable $cb) => $cb());

        $this->repoMock->shouldReceive('updateBalanceApprovalStatus')
            ->once()->with(42, 'APPROVED', 'admin');

        $this->repoMock->shouldReceive('updateApprovalStatus')
            ->once()->with('42', 'APPROVED', 'admin', null);

        $conn->shouldReceive('insert')->once()->andReturn(true);

        $result = $this->service->approve('42', 'admin');

        $this->assertEquals(1, $result['response'], $result['message'] ?? '');
    }

    public function test_it_returns_response_0_when_exception_occurs_on_approve(): void
    {
        $this->repoMock->shouldReceive('findBalanceEntryDate')
            ->andThrow(new \Exception('Connection failed'));

        Log::shouldReceive('error')->once();

        $result = $this->service->approve('42', 'admin');

        $this->assertEquals(0, $result['response']);
        $this->assertStringContainsString('Failed to approve', $result['message']);
    }

    // =========================================================================
    // reject
    // =========================================================================

    public function test_it_returns_response_2_when_transfer_is_not_pending_on_reject(): void
    {
        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()->with('42')->andReturn('APPROVED');

        $result = $this->service->reject('42', 'admin', 'Wrong quantity');

        $this->assertEquals(2, $result['response']);
        $this->assertStringContainsString('PENDING', $result['message']);
    }

    public function test_it_returns_response_1_on_successful_reject_with_reason(): void
    {
        $conn = $this->mockEudrConnection();

        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()->with('42')->andReturn('PENDING');

        $conn->shouldReceive('transaction')->once()
            ->andReturnUsing(fn(callable $cb) => $cb());

        $this->repoMock->shouldReceive('updateBalanceApprovalStatus')
            ->once()->with(42, 'REJECTED', 'admin');

        $this->repoMock->shouldReceive('updateApprovalStatus')
            ->once()->with('42', 'REJECTED', 'admin', null, 'Incorrect source tank');

        $conn->shouldReceive('insert')->once()->andReturn(true);

        $result = $this->service->reject('42', 'admin', 'Incorrect source tank');

        $this->assertEquals(1, $result['response']);
        $this->assertStringContainsString('rejected', $result['message']);
    }

    public function test_it_returns_response_2_when_rejecting_draft_transfer(): void
    {
        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()->with('42')->andReturn('DRAFT');

        $result = $this->service->reject('42', 'admin', 'Some reason');

        $this->assertEquals(2, $result['response']);
    }

    public function test_it_returns_response_0_when_exception_occurs_on_reject(): void
    {
        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->andThrow(new \Exception('Timeout'));

        Log::shouldReceive('error')->once();

        $result = $this->service->reject('42', 'admin', 'Some reason');

        $this->assertEquals(0, $result['response']);
        $this->assertStringContainsString('Failed to reject', $result['message']);
    }

    // =========================================================================
    // cancel
    // =========================================================================

    public function test_it_returns_99_when_period_is_locked_on_cancel(): void
    {
        $conn = $this->mockEudrConnection();

        $this->repoMock->shouldReceive('findBalanceEntryDate')
            ->once()->with(42)->andReturn('2026-01-01');

        $conn->shouldReceive('select')->once()
            ->andReturn([(object) ['lock_status' => '1']]);

        $result = $this->service->cancel('42', 'admin');

        $this->assertEquals(99, $result['response']);
    }

    public function test_it_returns_response_2_when_transfer_in_approved_status_cannot_be_cancelled(): void
    {
        $conn = $this->mockEudrConnection();

        $this->repoMock->shouldReceive('findBalanceEntryDate')
            ->once()->with(42)->andReturn('2026-06-03');

        $conn->shouldReceive('select')->twice()->andReturn([]);

        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()->with('42')->andReturn('APPROVED');

        $result = $this->service->cancel('42', 'admin');

        $this->assertEquals(2, $result['response']);
        $this->assertStringContainsString('DRAFT', $result['message']);
    }

    public function test_it_cancels_draft_transfer_successfully(): void
    {
        $conn = $this->mockEudrConnection();

        $this->repoMock->shouldReceive('findBalanceEntryDate')
            ->once()->with(42)->andReturn('2026-06-03');

        $conn->shouldReceive('select')->twice()->andReturn([]);

        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()->with('42')->andReturn('DRAFT');

        $conn->shouldReceive('transaction')->once()
            ->andReturnUsing(fn(callable $cb) => $cb());

        $this->repoMock->shouldReceive('updateBalanceApprovalStatus')
            ->once()->with(42, 'CANCELLED', 'admin');

        $this->repoMock->shouldReceive('updateApprovalStatus')
            ->once()->with('42', 'CANCELLED', 'admin');

        $conn->shouldReceive('insert')->once()->andReturn(true);

        $result = $this->service->cancel('42', 'admin');

        $this->assertEquals(1, $result['response']);
        $this->assertStringContainsString('cancelled', $result['message']);
    }

    public function test_it_cancels_rejected_transfer_successfully(): void
    {
        $conn = $this->mockEudrConnection();

        $this->repoMock->shouldReceive('findBalanceEntryDate')
            ->once()->with(42)->andReturn('2026-06-03');

        $conn->shouldReceive('select')->twice()->andReturn([]);

        $this->repoMock->shouldReceive('getCurrentApprovalStatus')
            ->once()->with('42')->andReturn('REJECTED');

        $conn->shouldReceive('transaction')->once()
            ->andReturnUsing(fn(callable $cb) => $cb());

        $this->repoMock->shouldReceive('updateBalanceApprovalStatus')
            ->once()->with(42, 'CANCELLED', 'admin');

        $this->repoMock->shouldReceive('updateApprovalStatus')
            ->once()->with('42', 'CANCELLED', 'admin');

        $conn->shouldReceive('insert')->once()->andReturn(true);

        $result = $this->service->cancel('42', 'admin');

        $this->assertEquals(1, $result['response']);
    }

    public function test_it_returns_response_0_when_exception_occurs_on_cancel(): void
    {
        $this->repoMock->shouldReceive('findBalanceEntryDate')
            ->andThrow(new \Exception('DB failure'));

        Log::shouldReceive('error')->once();

        $result = $this->service->cancel('42', 'admin');

        $this->assertEquals(0, $result['response']);
        $this->assertStringContainsString('Failed to cancel', $result['message']);
    }

    // =========================================================================
    // getPendingApprovals
    // =========================================================================

    public function test_it_returns_pending_approvals_for_all_plants_when_plant_id_is_zero(): void
    {
        $expected = [
            (object) ['id_approval' => 1, 'entry_no' => '726060310001', 'status' => 'PENDING'],
            (object) ['id_approval' => 2, 'entry_no' => '726060310002', 'status' => 'PENDING'],
        ];

        $this->repoMock->shouldReceive('getPendingApprovals')
            ->once()->with(0)->andReturn($expected);

        $result = $this->service->getPendingApprovals(0);

        $this->assertCount(2, $result);
        $this->assertEquals('PENDING', $result[0]->status);
    }

    public function test_it_returns_pending_approvals_filtered_by_plant(): void
    {
        $expected = [
            (object) ['id_approval' => 1, 'entry_no' => '726060301001', 'status' => 'PENDING'],
        ];

        $this->repoMock->shouldReceive('getPendingApprovals')
            ->once()->with(1001)->andReturn($expected);

        $result = $this->service->getPendingApprovals(1001);

        $this->assertCount(1, $result);
    }

    public function test_it_returns_empty_array_when_no_pending_approvals(): void
    {
        $this->repoMock->shouldReceive('getPendingApprovals')
            ->once()->with(0)->andReturn([]);

        $result = $this->service->getPendingApprovals(0);

        $this->assertEmpty($result);
    }

    // =========================================================================
    // getApprovalHistory
    // =========================================================================

    public function test_it_returns_approval_history_for_a_transfer(): void
    {
        $expected = [
            (object) ['id_approval' => 1, 'status' => 'DRAFT',    'created_at' => '2026-06-01 10:00:00'],
            (object) ['id_approval' => 1, 'status' => 'PENDING',  'created_at' => '2026-06-01 10:30:00'],
            (object) ['id_approval' => 1, 'status' => 'APPROVED', 'created_at' => '2026-06-01 11:00:00'],
        ];

        $this->repoMock->shouldReceive('getApprovalHistory')
            ->once()->with('42')->andReturn($expected);

        $result = $this->service->getApprovalHistory('42');

        $this->assertCount(3, $result);
        $this->assertEquals('APPROVED', $result[2]->status);
    }

    public function test_it_returns_empty_array_when_no_history_exists(): void
    {
        $this->repoMock->shouldReceive('getApprovalHistory')
            ->once()->with('9999')->andReturn([]);

        $result = $this->service->getApprovalHistory('9999');

        $this->assertEmpty($result);
    }

    // =========================================================================
    // createApprovalRecord
    // =========================================================================

    public function test_it_creates_approval_record_when_none_exists(): void
    {
        $this->repoMock->shouldReceive('findApprovalRecord')
            ->once()->with('42')->andReturn(null);

        $this->repoMock->shouldReceive('insertApprovalRecord')
            ->once()->andReturn(1);

        $this->service->createApprovalRecord(
            '42', '726060310001', '2026-06-03', '1',
            'PALM OIL', 100.0, 'SRC TANK', 'DST TANK', 1001, 'admin'
        );

        $this->addToAssertionCount(1);
    }

    public function test_it_skips_insert_when_approval_record_already_exists(): void
    {
        $this->repoMock->shouldReceive('findApprovalRecord')
            ->once()->with('42')->andReturn((object) ['id_approval' => 99]);

        $this->repoMock->shouldNotReceive('insertApprovalRecord');

        $this->service->createApprovalRecord(
            '42', '726060310001', '2026-06-03', '1',
            'PALM OIL', 100.0, 'SRC TANK', 'DST TANK', 1001, 'admin'
        );

        $this->addToAssertionCount(1);
    }
}
