<?php

declare(strict_types=1);

namespace Modules\Shared\Services;

use Illuminate\Support\Facades\DB;

/**
 * Shared balance operations for transfer flows.
 *
 * Consolidates duplicate methods between RmEntryTransferTrait and TransferRepository.
 * Both modules delegate here for low-level balance/header/detail operations.
 */
class TransferBalanceService
{
    protected string $connection = 'eudr_ts';

    public function findBalanceByTraceNo(string $traceNo): ?object
    {
        $result = DB::connection($this->connection)->select(
            'SELECT * FROM t_balance_header WHERE trace_no = ? AND status = 1 LIMIT 1',
            [$traceNo]
        );

        return $result[0] ?? null;
    }

    public function findBalanceById(int $id): ?object
    {
        $result = DB::connection($this->connection)->select(
            'SELECT * FROM t_balance_header WHERE id_balance_head = ? AND status = 1 LIMIT 1',
            [$id]
        );

        return $result[0] ?? null;
    }

    public function findTraceByBalanceHeadId(int $balanceHeadId): ?object
    {
        return DB::connection($this->connection)->table('t_trace_header')
            ->where('id_balance_head', $balanceHeadId)
            ->where('status', 1)
            ->first();
    }

    public function findTraceById(int $id): ?object
    {
        return DB::connection($this->connection)->table('t_trace_header')
            ->where('id_trace_head', $id)
            ->where('status', 1)
            ->first();
    }

    /**
     * Create a balance header for transfer.
     * Keeps id_sloc as-is (preserving JSON array) — no scalar truncation.
     */
    public function createBalanceHeader(array $data): int
    {
        $data['created_at'] ??= now();

        return DB::connection($this->connection)->table('t_balance_header')->insertGetId($data, 'id_balance_head');
    }

    /**
     * Create a trace header for transfer.
     */
    public function createTraceHeader(array $data): int
    {
        $data['created_at'] ??= now();

        return DB::connection($this->connection)->table('t_trace_header')->insertGetId($data, 'id_trace_head');
    }

    /**
     * Decrease source balance qty, increase out_qty.
     */
    public function deductSourceBalance(int $balanceId, float $qty): bool
    {
        return (bool) DB::connection($this->connection)->table('t_balance_header')
            ->where('id_balance_head', $balanceId)
            ->update([
                'qty' => DB::raw('qty - '.$qty),
                'out_qty' => DB::raw('out_qty + '.$qty),
            ]);
    }

    /**
     * Increase source trace out_qty.
     */
    public function deductSourceTrace(int $balanceHeadId, float $qty): bool
    {
        return (bool) DB::connection($this->connection)->table('t_trace_header')
            ->where('id_balance_head', $balanceHeadId)
            ->where('status', 1)
            ->update([
                'out_qty' => DB::raw('out_qty + '.$qty),
            ]);
    }

    /**
     * Revert source balance (add back qty, decrease out_qty).
     */
    public function revertSourceBalance(string $traceNo, float $qty): bool
    {
        return (bool) DB::connection($this->connection)->table('t_balance_header')
            ->where('trace_no', $traceNo)
            ->where('status', 1)
            ->update([
                'qty' => DB::raw('qty + '.$qty),
                'out_qty' => DB::raw('out_qty - '.$qty),
            ]);
    }

    /**
     * Revert source trace out_qty.
     */
    public function revertSourceTrace(int $balanceHeadId, float $qty): bool
    {
        return (bool) DB::connection($this->connection)->table('t_trace_header')
            ->where('id_balance_head', $balanceHeadId)
            ->where('status', 1)
            ->update([
                'out_qty' => DB::raw('out_qty - '.$qty),
            ]);
    }

    public function deactivateBalance(int $balanceId, string $user): bool
    {
        return (bool) DB::connection($this->connection)->table('t_balance_header')
            ->where('id_balance_head', $balanceId)
            ->update(['status' => 0, 'updated_by' => $user]);
    }

    public function deactivateTrace(int $traceId, string $user): bool
    {
        return (bool) DB::connection($this->connection)->table('t_trace_header')
            ->where('id_trace_head', $traceId)
            ->update(['status' => 0, 'updated_by' => $user]);
    }

    /**
     * Get source entries for a plant (trace prefix '1', storage tanks).
     */
    public function getSourceEntries(int $plantId): array
    {
        return DB::connection($this->connection)->select(
            "SELECT bh.id_balance_head, bh.trace_no, m.description AS material,
                    sl.description AS tank, sl.tf_number
               FROM t_balance_header bh
               JOIN m_material m ON bh.id_material = m.id_material
               JOIN m_sloc sl ON bh.id_sloc = sl.id_sloc
              WHERE bh.status = 1
                AND SUBSTRING(bh.trace_no, 1, 1) = '1'
                AND bh.qty > 0
                AND (bh.id_plant = ? OR ? = 0)
                AND sl.description LIKE '%STORAGE%'
              ORDER BY bh.id_balance_head DESC",
            [$plantId, $plantId]
        );
    }

    /**
     * Get destination tanks for a plant (FEED keyword).
     */
    public function getDestTanks(int $plantId): array
    {
        return DB::connection($this->connection)->select(
            "SELECT DISTINCT description AS tank, id_sloc, tf_number
               FROM m_sloc
              WHERE status = 1
                AND description LIKE '%FEED%'
                AND (CAST(? AS TEXT) = '0' OR CAST(id_plant AS TEXT) = CAST(? AS TEXT))
              ORDER BY description",
            [$plantId, $plantId]
        );
    }
}
