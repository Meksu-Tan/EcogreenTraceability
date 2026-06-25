<?php
declare(strict_types=1);
namespace Modules\Shared\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Traits\TransactionLoggerTrait;
use Modules\Shared\Traits\DbCompatTrait;
use Modules\Shared\Constants\TransactionResponseCode;
use Modules\Shared\Services\PeriodLockService;
use Modules\Shared\Services\AuditService;
use Exception;

class TransactionCancellationService
{
    use TransactionLoggerTrait;
    use DbCompatTrait;

    protected string $connection = 'eudr_ts';

    /**
     * Cancel/Rollback a WIP Feed transaction.
     */
    public function cancelWipFeed(string $traceNo, string $user): array
    {
        $entryDate = DB::connection($this->connection)->selectOne(
            'SELECT entry_date FROM t_trace_header WHERE to_trace_no = ? AND status = 1 LIMIT 1',
            [$traceNo]
        );
        if (!$entryDate) {
            return ['response' => TransactionResponseCode::GENERIC_FAILURE];
        }

        if (PeriodLockService::isLocked($entryDate->entry_date)) {
            return ['response' => TransactionResponseCode::PERIOD_LOCKED];
        }

        return DB::connection($this->connection)->transaction(function () use ($traceNo, $user) {
            $traceHeads = DB::connection($this->connection)->select(
                'SELECT id_trace_head, id_balance_head, out_qty
                   FROM t_trace_header
                  WHERE to_trace_no = ? AND status = 1
                  ORDER BY id_trace_head DESC',
                [$traceNo]
            );

            foreach ($traceHeads as $head) {
                $idTraceHead = $head->id_trace_head;
                $idBalanceHead = $head->id_balance_head;
                $traceHeadOutQty = (float)$head->out_qty;

                // Deactivate trace header
                DB::connection($this->connection)->update(
                    'UPDATE t_trace_header SET status = 0, updated_by = ? WHERE id_trace_head = ? AND status = 1',
                    [$user, $idTraceHead]
                );
                $this->logTransaction('T_TRACE_HEAD', 'DELETE',
                    'IDTRACEHEAD: ' . $idTraceHead . ' IDHEAD: ' . $idBalanceHead . ' | Status: 1 >>> 0', $user);

                // Revert stock on balance header
                $balHead = DB::connection($this->connection)->selectOne(
                    'SELECT qty, in_qty, out_qty FROM t_balance_header WHERE id_balance_head = ? AND status = 1',
                    [$idBalanceHead]
                );
                if ($balHead) {
                    $oldQty = (float)$balHead->qty;
                    $oldInQty = (float)$balHead->in_qty;
                    $oldOutQty = (float)$balHead->out_qty;

                    DB::connection($this->connection)->update(
                        'UPDATE t_balance_header SET qty = ?, in_qty = ?, out_qty = ?, updated_by = ?
                          WHERE id_balance_head = ? AND status = 1',
                        [$oldQty + $traceHeadOutQty, $oldInQty, $oldOutQty - $traceHeadOutQty, $user, $idBalanceHead]
                    );

                    $this->logTransaction('T_BALANCE_HEAD', 'UPDATE',
                        'IDHEAD: ' . $idBalanceHead . ' | QTY: ' . $oldQty . ' >>> ' . ($oldQty + $traceHeadOutQty) .
                        ' / OUT_QTY: ' . $oldOutQty . ' >>> ' . ($oldOutQty - $traceHeadOutQty) . ' | Status: 1', $user);
                }

                // Revert trace details and balance details
                $traceTails = DB::connection($this->connection)->select(
                    'SELECT id_trace_tail, id_balance_tail, out_qty
                       FROM t_trace_detail
                      WHERE id_trace_head = ? AND status = 1
                      ORDER BY id_trace_tail DESC',
                    [$idTraceHead]
                );

                foreach ($traceTails as $tail) {
                    DB::connection($this->connection)->update(
                        'UPDATE t_trace_detail SET status = 0, updated_by = ? WHERE id_trace_tail = ? AND status = 1',
                        [$user, $tail->id_trace_tail]
                    );

                    $balTail = DB::connection($this->connection)->selectOne(
                        'SELECT qty, in_qty, out_qty FROM t_balance_detail WHERE id_balance_tail = ? AND status = 1',
                        [$tail->id_balance_tail]
                    );

                    if ($balTail) {
                        $tailOutQty = (float)$tail->out_qty;
                        DB::connection($this->connection)->update(
                            'UPDATE t_balance_detail SET qty = ?, in_qty = ?, out_qty = ?, updated_by = ?
                              WHERE id_balance_tail = ? AND status = 1',
                            [$balTail->qty + $tailOutQty, $balTail->in_qty, $balTail->out_qty - $tailOutQty, $user, $tail->id_balance_tail]
                        );
                    }
                }

                // Deactivate production log
                DB::connection($this->connection)->update(
                    'UPDATE t_prod_log SET status = 0, updated_by = ? WHERE id_trace_head = ? AND status = 1',
                    [$user, $idTraceHead]
                );
                $this->logTransaction('T_PROD_LOG', 'DELETE',
                    'IDTRACEHEAD: ' . $idTraceHead . ' | Status: 1 >>> 0 | Cancel Feed', $user);
            }

            return ['response' => TransactionResponseCode::SUCCESS];
        });
    }

    /**
     * Cancel/Rollback a WIP Rundown transaction.
     */
    public function cancelWipRundown(string $traceNo, string $user): array
    {
        $entryDate = DB::connection($this->connection)->selectOne(
            'SELECT entry_date FROM t_trace_header WHERE to_trace_no = ? AND status = 1 LIMIT 1',
            [$traceNo]
        );
        if (!$entryDate) {
            return ['response' => TransactionResponseCode::GENERIC_FAILURE];
        }

        if (PeriodLockService::isLocked($entryDate->entry_date)) {
            return ['response' => TransactionResponseCode::PERIOD_LOCKED];
        }

        return DB::connection($this->connection)->transaction(function () use ($traceNo, $user) {
            $traceHeads = DB::connection($this->connection)->select(
                'SELECT id_trace_head, id_balance_head
                   FROM t_trace_header
                  WHERE to_trace_no = ? AND status = 1
                  ORDER BY id_trace_head DESC',
                [$traceNo]
            );

            foreach ($traceHeads as $head) {
                $idTraceHead = $head->id_trace_head;
                $idBalanceHead = $head->id_balance_head;

                // Deactivate trace header
                DB::connection($this->connection)->update(
                    'UPDATE t_trace_header SET status = 0, updated_by = ? WHERE id_trace_head = ? AND status = 1',
                    [$user, $idTraceHead]
                );
                $this->logTransaction('T_TRACE_HEAD', 'DELETE',
                    'IDTRACEHEAD: ' . $idTraceHead . ' IDHEAD: ' . $idBalanceHead . ' | Status: 1 >>> 0', $user);

                // Deactivate balance header
                DB::connection($this->connection)->update(
                    'UPDATE t_balance_header SET status = 0, updated_by = ? WHERE id_balance_head = ? AND status = 1',
                    [$user, $idBalanceHead]
                );
                $this->logTransaction('T_BALANCE_HEAD', 'UPDATE',
                    'IDHEAD: ' . $idBalanceHead . ' | Status: 1 >>> 0', $user);

                // Deactivate details
                $traceTails = DB::connection($this->connection)->select(
                    'SELECT id_trace_tail, id_balance_tail FROM t_trace_detail
                      WHERE id_trace_head = ? AND status = 1',
                    [$idTraceHead]
                );

                foreach ($traceTails as $tail) {
                    DB::connection($this->connection)->update(
                        'UPDATE t_trace_detail SET status = 0, updated_by = ? WHERE id_trace_tail = ? AND status = 1',
                        [$user, $tail->id_trace_tail]
                    );
                    DB::connection($this->connection)->update(
                        'UPDATE t_balance_detail SET status = 0, updated_by = ? WHERE id_balance_tail = ? AND status = 1',
                        [$user, $tail->id_balance_tail]
                    );
                }
            }

            // Deactivate associated WIP adjustment header/detail if trace_no starts with '9'
            if (str_starts_with($traceNo, '9')) {
                $adjHead = DB::connection($this->connection)->selectOne(
                    'SELECT id_adjust_head FROM t_adjustment_header WHERE adjust_no = ? AND status = 1 LIMIT 1',
                    [$traceNo]
                );
                if ($adjHead) {
                    $idAdjustHead = $adjHead->id_adjust_head;
                    DB::connection($this->connection)->update(
                        'UPDATE t_adjustment_header SET status = 0, updated_by = ? WHERE id_adjust_head = ? AND status = 1',
                        [$user, $idAdjustHead]
                    );
                    DB::connection($this->connection)->update(
                        'UPDATE t_adjustment_detail SET status = 0, updated_by = ? WHERE id_adjust_head = ? AND status = 1',
                        [$user, $idAdjustHead]
                    );
                }
            }

            return ['response' => TransactionResponseCode::SUCCESS];
        });
    }

    /**
     * Deactivate RM Entry transaction.
     */
    public function deactivateRmEntry(int $id, string $user): array
    {
        return DB::connection($this->connection)->transaction(function () use ($id, $user) {
            $used = DB::connection($this->connection)->table('t_trace_header')
                ->where('id_balance_head', $id)
                ->where('out_qty', '!=', 0)
                ->where('status', 1)
                ->count();

            if ($used > 0) {
                throw new Exception('RM Entry has been used and cannot be deactivated');
            }

            DB::connection($this->connection)->table('t_balance_header')
                ->where('id_balance_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            DB::connection($this->connection)->table('t_balance_detail')
                ->where('id_balance_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            $traceHead = DB::connection($this->connection)->table('t_trace_header')
                ->where('id_balance_head', $id)
                ->where('status', 1)
                ->first();

            if ($traceHead) {
                DB::connection($this->connection)->table('t_trace_header')
                    ->where('id_trace_head', $traceHead->id_trace_head)
                    ->update(['status' => 0, 'updated_by' => $user]);

                DB::connection($this->connection)->table('t_trace_detail')
                    ->where('id_trace_head', $traceHead->id_trace_head)
                    ->update(['status' => 0, 'updated_by' => $user]);
            }

            $this->logTransaction('RM_ENTRY', 'DEACTIVATE', 'ID: ' . $id, $user);
            return ['success' => true];
        });
    }

    /**
     * Deactivate RM Entry Transfer transaction.
     */
    public function deactivateRmEntryTrf(int $id, string $user): array
    {
        return DB::connection($this->connection)->transaction(function () use ($id, $user) {
            $head = DB::connection($this->connection)->selectOne(
                'SELECT trace_no FROM t_balance_header WHERE id_balance_head = ? AND status = 1',
                [$id]
            );
            if (!$head) {
                throw new Exception('RM Entry not found');
            }

            $traceNo = $head->trace_no;

            $traceHead = DB::connection($this->connection)->selectOne(
                'SELECT id_trace_head, from_trace_no, out_qty FROM t_trace_header
                  WHERE from_trace_no = ? AND status = 1 LIMIT 1',
                [$traceNo]
            );

            if ($traceHead) {
                $sourceTraceNo = $traceHead->from_trace_no;
                $sourceTraceHead = DB::connection($this->connection)->selectOne(
                    'SELECT id_trace_head, id_balance_head FROM t_trace_header WHERE to_trace_no = ? AND status = 1 LIMIT 1',
                    [$sourceTraceNo]
                );

                if ($sourceTraceHead) {
                    $balanceHead = DB::connection($this->connection)->selectOne(
                        'SELECT id_balance_head, qty, out_qty FROM t_balance_header WHERE id_balance_head = ? AND status = 1',
                        [$sourceTraceHead->id_balance_head]
                    );

                    if ($balanceHead) {
                        DB::connection($this->connection)->update(
                            'UPDATE t_balance_header SET qty = qty + ?, out_qty = out_qty - ?, updated_by = ? WHERE id_balance_head = ? AND status = 1',
                            [$traceHead->out_qty, $traceHead->out_qty, $user, $sourceTraceHead->id_balance_head]
                        );
                    }
                }
            }

            DB::connection($this->connection)->table('t_balance_header')
                ->where('id_balance_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            DB::connection($this->connection)->table('t_balance_detail')
                ->where('id_balance_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            $traceHead2 = DB::connection($this->connection)->selectOne(
                'SELECT id_trace_head FROM t_trace_header WHERE id_balance_head = ? AND status = 1 LIMIT 1',
                [$id]
            );

            if ($traceHead2) {
                DB::connection($this->connection)->table('t_trace_header')
                    ->where('id_trace_head', $traceHead2->id_trace_head)
                    ->update(['status' => 0, 'updated_by' => $user]);

                DB::connection($this->connection)->table('t_trace_detail')
                    ->where('id_trace_head', $traceHead2->id_trace_head)
                    ->update(['status' => 0, 'updated_by' => $user]);
            }

            $this->logTransaction('RMTRF_ENTRY', 'DEACTIVATE', 'ID: ' . $id . ' | Trace: ' . $traceNo, $user);
            return ['success' => true];
        });
    }

    /**
     * Deactivate Feed Log Entry transaction.
     */
    public function deactivateFeedLogEntry(int $id, string $user): array
    {
        return DB::connection($this->connection)->transaction(function () use ($id, $user) {
            $traceHead = DB::connection($this->connection)->table('t_trace_header')
                ->where('id_trace_head', $id)
                ->where('status', 1)
                ->first();

            if (!$traceHead) {
                throw new Exception('Feed log entry not found');
            }

            $toTraceNo = (string) ($traceHead->to_trace_no ?? '');
            if (str_starts_with($toTraceNo, '7')) {
                throw new Exception('Use transfer deactivation for transfer entries');
            }

            $usedCount = DB::connection($this->connection)->table('t_trace_header')
                ->where('id_balance_head', $traceHead->id_balance_head)
                ->where('out_qty', '!=', 0)
                ->where('status', 1)
                ->count();

            if ($usedCount > 0) {
                throw new Exception('Feed log entry has been used and cannot be deactivated');
            }

            // Restore source balance header quantity
            if ($traceHead->from_trace_no) {
                $sourceTraceHead = DB::connection($this->connection)->table('t_trace_header')
                    ->where('to_trace_no', $traceHead->from_trace_no)
                    ->where('status', 1)
                    ->first();

                if ($sourceTraceHead) {
                    DB::connection($this->connection)->update(
                        'UPDATE t_balance_header SET qty = qty + ?, out_qty = out_qty - ?, updated_by = ? WHERE id_balance_head = ? AND status = 1',
                        [$traceHead->out_qty, $traceHead->out_qty, $user, $sourceTraceHead->id_balance_head]
                    );
                }
            }

            // Restore source balance detail quantities
            $traceDetails = DB::connection($this->connection)->table('t_trace_detail')
                ->where('id_trace_head', $id)
                ->where('status', 1)
                ->get();

            foreach ($traceDetails as $td) {
                if ($td->id_balance_tail) {
                    DB::connection($this->connection)->update(
                        'UPDATE t_balance_detail SET qty = qty + ?, out_qty = out_qty - ?, updated_by = ? WHERE id_balance_tail = ? AND status = 1',
                        [$td->out_qty, $td->out_qty, $user, $td->id_balance_tail]
                    );
                }
            }

            DB::connection($this->connection)->table('t_trace_header')
                ->where('id_trace_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            DB::connection($this->connection)->table('t_trace_detail')
                ->where('id_trace_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            $this->logTransaction('FEED_LOG', 'DEACTIVATE', 'ID: ' . $id . ' | Trace: ' . $toTraceNo, $user);
            return ['success' => true];
        });
    }

    /**
     * Deactivate Inter-Plant Transfer transaction.
     */
    public function deactivateTransfer(string $id, string $user): array
    {
        $idTmp = explode("|", $id);
        $idHead = trim($idTmp[0]);
        $idTraceHead = trim($idTmp[1]);

        try {
            DB::connection($this->connection)->beginTransaction();

            $entryDate = DB::connection($this->connection)->select(
                'SELECT entry_date FROM t_trace_header WHERE id_trace_head = ? AND status = 1',
                [$idTraceHead]
            );

            if (empty($entryDate)) {
                DB::connection($this->connection)->rollBack();
                return ['response' => 98];
            }

            $currEntryDate = $entryDate[0]->entry_date;
            if (PeriodLockService::isLocked($currEntryDate)) {
                DB::connection($this->connection)->rollBack();
                return ['response' => 99];
            }

            $counter = 0;
            $maxIterations = 100;

            do {
                $this->logTransaction('TRANSFER_ENTRY', 'DE-ACTIVATE',
                    'IdBalHead: ' . $idHead . ' | Status: 1 >> 0', $user);

                AuditService::log('TRANSFER', 'DELETE',
                    'Deactivating transfer | IdBalHead: ' . $idHead . ' | IdTraceHead: ' . $idTraceHead,
                    $user, ['id_balance_head' => $idHead, 'id_trace_head' => $idTraceHead]);

                DB::connection($this->connection)->update(
                    'UPDATE t_balance_detail SET status = 0, updated_by = ? WHERE id_balance_head = ?',
                    [$user, $idHead]
                );
                DB::connection($this->connection)->update(
                    'UPDATE t_balance_header SET status = 0, updated_by = ? WHERE id_balance_head = ?',
                    [$user, $idHead]
                );

                $dateFmtCreatedAt = $this->dbDateFormat('a.created_at', '%Y-%m-%d %H:%i');
                $datTraceHead = DB::connection($this->connection)->select(
                    "SELECT b.id_balance_head, b.out_qty, b.id_trace_head, a.id_material, a.in_qty,
                            {$dateFmtCreatedAt} AS created_at
                       FROM t_trace_header a
                       LEFT JOIN t_trace_header b ON a.from_trace_no = b.to_trace_no AND b.status = 1
                      WHERE a.id_balance_head = ? AND a.status = 1",
                    [$idHead]
                );

                if (empty($datTraceHead)) {
                    break;
                }

                $createdAt = $datTraceHead[0]->created_at;
                $idMaterial = $datTraceHead[0]->id_material;
                $inQty = $datTraceHead[0]->in_qty;

                foreach ($datTraceHead as $row) {
                    $idBalHead = $row->id_balance_head;
                    $idTracHead = $row->id_trace_head;
                    $outQtyHead = $row->out_qty;

                    $datBalHeadSource = DB::connection($this->connection)->select(
                        'SELECT a.qty, a.out_qty FROM t_balance_header a WHERE a.status = 1 AND a.id_balance_head = ?',
                        [$idBalHead]
                    );

                    if (!empty($datBalHeadSource)) {
                        $outQtyBalHeadSource = $datBalHeadSource[0]->out_qty - $outQtyHead;
                        $onhandQtyBalHeadSource = $datBalHeadSource[0]->qty + $outQtyHead;

                        DB::connection($this->connection)->update(
                            'UPDATE t_balance_header SET qty = ?, out_qty = ?, updated_by = ? WHERE id_balance_head = ?',
                            [$onhandQtyBalHeadSource, $outQtyBalHeadSource, $user, $idBalHead]
                        );

                        $datAdjustHead = DB::connection($this->connection)->select(
                            'SELECT id_adjust_head FROM t_adjustment_header WHERE id_balance_head = ? AND status = 1',
                            [$idBalHead]
                        );

                        foreach ($datAdjustHead as $adj) {
                            DB::connection($this->connection)->update(
                                'UPDATE t_adjustment_header SET status = 0, updated_by = ? WHERE id_adjust_head = ?',
                                [$user, $adj->id_adjust_head]
                            );
                            DB::connection($this->connection)->update(
                                'UPDATE t_adjustment_detail SET status = 0, updated_by = ? WHERE id_adjust_head = ?',
                                [$user, $adj->id_adjust_head]
                            );
                        }
                    }

                    $datTraceTail = DB::connection($this->connection)->select(
                        'SELECT a.id_balance_tail, a.out_qty, a.id_trace_tail
                           FROM t_trace_detail a WHERE a.id_trace_head = ? AND a.status = 1',
                        [$idTracHead]
                    );

                    foreach ($datTraceTail as $tail) {
                        $datBalTailSource = DB::connection($this->connection)->select(
                            'SELECT a.qty, a.out_qty FROM t_balance_detail a WHERE a.status = 1 AND a.id_balance_tail = ?',
                            [$tail->id_balance_tail]
                        );

                        if (!empty($datBalTailSource)) {
                            $outQtyBalTailSource = $datBalTailSource[0]->out_qty - $tail->out_qty;
                            $onhandQtyBalTailSource = $datBalTailSource[0]->qty + $tail->out_qty;

                            DB::connection($this->connection)->update(
                                'UPDATE t_balance_detail SET qty = ?, out_qty = ?, updated_by = ? WHERE id_balance_tail = ?',
                                [$onhandQtyBalTailSource, $outQtyBalTailSource, $user, $tail->id_balance_tail]
                            );
                        }

                        DB::connection($this->connection)->update(
                            'UPDATE t_trace_detail SET status = 0, updated_by = ? WHERE id_trace_tail = ?',
                            [$user, $tail->id_trace_tail]
                        );
                    }

                    DB::connection($this->connection)->update(
                        'UPDATE t_trace_header SET status = 0, updated_by = ? WHERE id_trace_head = ?',
                        [$user, $idTracHead]
                    );
                }

                DB::connection($this->connection)->update(
                    'UPDATE t_trace_header SET status = 0, updated_by = ? WHERE id_balance_head = ?',
                    [$user, $idHead]
                );
                DB::connection($this->connection)->update(
                    'UPDATE t_trace_detail SET status = 0, updated_by = ? WHERE id_trace_head = ?',
                    [$user, $idTraceHead]
                );

                /* DESTROYING AUTO ADJUSTMENT IN (prefix 9) */
                $datAdjustIn = DB::connection($this->connection)->select(
                    "SELECT id_balance_head, id_trace_head
                       FROM t_trace_header
                      WHERE status = 1
                        AND from_trace_no IS NULL
                        AND SUBSTRING(to_trace_no,1,1) = 9
                        AND {$dateFmtCreatedAt} = ?
                        AND id_material = ?
                        AND in_qty = ?",
                    [$createdAt, $idMaterial, $inQty]
                );

                if (count($datAdjustIn) > 0) {
                    $idBalHead = $datAdjustIn[0]->id_balance_head;
                    $idTraceHead = $datAdjustIn[0]->id_trace_head;

                    DB::connection($this->connection)->update(
                        'UPDATE t_trace_header SET status = 0, updated_by = ? WHERE status = 1 AND id_trace_head = ?',
                        [$user, $idTraceHead]
                    );
                    DB::connection($this->connection)->update(
                        'UPDATE t_trace_detail SET status = 0, updated_by = ? WHERE status = 1 AND id_trace_head = ?',
                        [$user, $idTraceHead]
                    );
                    DB::connection($this->connection)->update(
                        'UPDATE t_balance_header SET status = 0, updated_by = ? WHERE status = 1 AND id_balance_head = ?',
                        [$user, $idBalHead]
                    );
                    DB::connection($this->connection)->update(
                        'UPDATE t_balance_detail SET status = 0, updated_by = ? WHERE status = 1 AND id_balance_head = ?',
                        [$user, $idBalHead]
                    );
                }

                /* DESTROYING AUTO TRF TO ADJUSTMENT OUT (prefix 7) */
                $datAdjustOut = DB::connection($this->connection)->select(
                    "SELECT id_balance_head, id_trace_head
                       FROM t_trace_header
                      WHERE status = 1
                        AND SUBSTRING(to_trace_no,1,1) = 7
                        AND {$dateFmtCreatedAt} = ?
                        AND id_material = ?
                        AND in_qty = ?",
                    [$createdAt, $idMaterial, $inQty]
                );

                if (count($datAdjustOut) > 0) {
                    $idHead = $datAdjustOut[0]->id_balance_head;
                    $idTraceHead = $datAdjustOut[0]->id_trace_head;
                } else {
                    break;
                }

                if (++$counter >= $maxIterations) {
                    throw new \Exception("Infinite loop detected in transfer_destroy");
                }
            } while (true);

            DB::connection($this->connection)->commit();
            return ['response' => 1];
        } catch (\Exception $e) {
            DB::connection($this->connection)->rollBack();
            return ['response' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Deactivate Blending transaction.
     */
    public function deactivateBlending(string $id, string $user): array
    {
        $idTmp = explode("|", $id);
        $idHead = trim($idTmp[0]);
        $idTraceHead = trim($idTmp[1]);

        try {
            DB::connection($this->connection)->beginTransaction();

            $entryDate = DB::connection($this->connection)->select(
                'SELECT entry_date FROM t_trace_header WHERE id_trace_head = ? AND status = 1',
                [$idTraceHead]
            );

            if (empty($entryDate)) {
                DB::connection($this->connection)->rollBack();
                return ['response' => 98];
            }

            $curr_entryDate = $entryDate[0]->entry_date;

            if (PeriodLockService::isLocked($curr_entryDate)) {
                DB::connection($this->connection)->rollBack();
                return ['response' => 99];
            }

            $this->logTransaction('BLENDING_ENTRY', 'DE-ACTIVATE', 'IdBalHead: ' . $idHead . ' | Status: 1 >> 0', $user);

            DB::connection($this->connection)->update(
                'UPDATE t_balance_detail SET status = 0, updated_by = ? WHERE id_balance_head = ?',
                [$user, $idHead]
            );
            DB::connection($this->connection)->update(
                'UPDATE t_balance_header SET status = 0, updated_by = ? WHERE id_balance_head = ?',
                [$user, $idHead]
            );

            $datTraceHead = DB::connection($this->connection)->select(
                'SELECT b.id_balance_head, b.out_qty, b.id_trace_head
                   FROM t_trace_header a
                   LEFT JOIN t_trace_header b ON a.from_trace_no = b.to_trace_no AND b.status = 1
                  WHERE a.id_balance_head = ? AND a.status = 1',
                [$idHead]
            );

            foreach ($datTraceHead as $row) {
                $datBalHeadSource = DB::connection($this->connection)->select(
                    'SELECT a.qty, a.out_qty FROM t_balance_header a WHERE a.status = 1 AND a.id_balance_head = ?',
                    [$row->id_balance_head]
                );

                if (!empty($datBalHeadSource)) {
                    $outQtyBalHeadSource = $datBalHeadSource[0]->out_qty - $row->out_qty;
                    $onhandQtyBalHeadSource = $datBalHeadSource[0]->qty + $row->out_qty;

                    DB::connection($this->connection)->update(
                        'UPDATE t_balance_header SET qty = ?, out_qty = ?, updated_by = ? WHERE id_balance_head = ?',
                        [$onhandQtyBalHeadSource, $outQtyBalHeadSource, $user, $row->id_balance_head]
                    );

                    $datTraceTail = DB::connection($this->connection)->select(
                        'SELECT a.id_balance_tail, a.out_qty, a.id_trace_tail
                           FROM t_trace_detail a WHERE a.id_trace_head = ? AND a.status = 1',
                        [$row->id_trace_head]
                    );

                    foreach ($datTraceTail as $tail) {
                        $datBalTailSource = DB::connection($this->connection)->select(
                            'SELECT a.qty, a.out_qty FROM t_balance_detail a WHERE a.status = 1 AND a.id_balance_tail = ?',
                            [$tail->id_balance_tail]
                        );

                        if (!empty($datBalTailSource)) {
                            $outQtyBalTailSource = $datBalTailSource[0]->out_qty - $tail->out_qty;
                            $onhandQtyBalTailSource = $datBalTailSource[0]->qty + $tail->out_qty;

                            DB::connection($this->connection)->update(
                                'UPDATE t_balance_detail SET qty = ?, out_qty = ?, updated_by = ? WHERE id_balance_tail = ?',
                                [$onhandQtyBalTailSource, $outQtyBalTailSource, $user, $tail->id_balance_tail]
                            );
                        }

                        DB::connection($this->connection)->update(
                            'UPDATE t_trace_detail SET status = 0, updated_by = ? WHERE id_trace_tail = ?',
                            [$user, $tail->id_trace_tail]
                        );
                    }

                    DB::connection($this->connection)->update(
                        'UPDATE t_trace_header SET status = 0, updated_by = ? WHERE id_trace_head = ?',
                        [$user, $row->id_trace_head]
                    );
                }
            }

            DB::connection($this->connection)->update(
                'UPDATE t_trace_header SET status = 0, updated_by = ? WHERE id_balance_head = ?',
                [$user, $idHead]
            );
            DB::connection($this->connection)->update(
                'UPDATE t_trace_detail SET status = 0, updated_by = ? WHERE id_trace_head = ?',
                [$user, $idTraceHead]
            );

            DB::connection($this->connection)->commit();
            return ['response' => 1];
        } catch (\Exception $e) {
            DB::connection($this->connection)->rollBack();
            return ['response' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Cancel Shipment transaction.
     *
     * Restores qty to source (warehouse type 4, or balance/WIP type 1).
     * Deactivates trace_header, shipment_header, shipment_detail, trace_detail.
     *
     * @param string $traceNo Shipment trace number (prefix 5)
     * @param string $user    User performing the cancel
     */
    public function cancelShipment(string $traceNo, string $user): array
    {
        $entryRec = DB::connection($this->connection)->selectOne(
            'SELECT entry_date FROM t_trace_header WHERE to_trace_no = ? AND status = 1 LIMIT 1',
            [$traceNo]
        );

        if (!$entryRec) {
            return ['response' => 0, 'message' => 'Active trace not found.'];
        }

        if (PeriodLockService::isLocked($entryRec->entry_date)) {
            return ['response' => 99, 'message' => 'Period is locked.'];
        }

        $datTraceHead = DB::connection($this->connection)->select(
            'SELECT from_trace_no, id_balance_head, out_qty, id_trace_head
               FROM t_trace_header
              WHERE to_trace_no = ? AND status = 1',
            [$traceNo]
        );

        if (empty($datTraceHead)) {
            return ['response' => 4, 'message' => 'Trace records not found.'];
        }

        $fromTraceNo = $datTraceHead[0]->from_trace_no;
        $origin = (int) substr((string) $fromTraceNo, 0, 1);
        if ($origin < 1 || $origin > 9) $origin = 4;

        return DB::connection($this->connection)->transaction(
            function () use ($datTraceHead, $traceNo, $origin, $user) {
                foreach ($datTraceHead as $headRow) {
                    $idTraceHead  = $headRow->id_trace_head;
                    $idHead       = $headRow->id_balance_head;
                    $outQtyShip   = (float) $headRow->out_qty;
                    $fromTraceNo  = $headRow->from_trace_no;

                    $datTraceTail = DB::connection($this->connection)->select(
                        'SELECT id_trace_tail, id_balance_tail, out_qty
                           FROM t_trace_detail
                          WHERE id_trace_head = ? AND status = 1',
                        [$idTraceHead]
                    );

                    if ($origin === 4) {
                        // Source = packaging warehouse (t_warehouse_header / t_warehouse_detail)
                        $datWhxHead = DB::connection($this->connection)->selectOne(
                            'SELECT qty, out_qty FROM t_warehouse_header WHERE id_whx_head = ? AND status = 1',
                            [$idHead]
                        );

                        if ($datWhxHead) {
                            DB::connection($this->connection)->update(
                                'UPDATE t_warehouse_header
                                    SET qty = ?, out_qty = ?, updated_by = ?
                                  WHERE id_whx_head = ? AND status = 1',
                                [(float)$datWhxHead->qty + $outQtyShip,
                                 (float)$datWhxHead->out_qty - $outQtyShip,
                                 $user, $idHead]
                            );
                            $this->logTransaction('T_WAREHOUSE_HEAD', 'UPDATE',
                                'IDHEAD: ' . $idHead . ' | QTY: ' . $datWhxHead->qty . ' >>> '
                                . ((float)$datWhxHead->qty + $outQtyShip), $user);
                        }

                        DB::connection($this->connection)->update(
                            'UPDATE t_trace_header SET status = 0, updated_by = ? WHERE id_trace_head = ?',
                            [$user, $idTraceHead]
                        );

                        DB::connection($this->connection)->update(
                            'UPDATE t_shipment_header SET status = 0, updated_by = ?
                              WHERE from_trace_no = ? AND trace_no = ? AND qty = ?',
                            [$user, $fromTraceNo, $traceNo, $outQtyShip]
                        );

                        $idShipHeadRow = DB::connection($this->connection)->selectOne(
                            'SELECT id_ship_head FROM t_shipment_header
                              WHERE from_trace_no = ? AND trace_no = ? AND qty = ?',
                            [$fromTraceNo, $traceNo, $outQtyShip]
                        );

                        if ($idShipHeadRow) {
                            DB::connection($this->connection)->update(
                                'UPDATE t_shipment_detail SET status = 0, updated_by = ?
                                  WHERE id_ship_head = ?',
                                [$user, $idShipHeadRow->id_ship_head]
                            );
                        }

                        foreach ($datTraceTail as $tailRow) {
                            $idTail        = $tailRow->id_balance_tail;
                            $outQtyTail    = (float) $tailRow->out_qty;

                            $datWhxTail = DB::connection($this->connection)->selectOne(
                                'SELECT qty, out_qty FROM t_warehouse_detail WHERE id_whx_tail = ?',
                                [$idTail]
                            );

                            if ($datWhxTail) {
                                DB::connection($this->connection)->update(
                                    'UPDATE t_warehouse_detail SET qty = ?, out_qty = ?, updated_by = ?
                                      WHERE id_whx_tail = ?',
                                    [(float)$datWhxTail->qty + $outQtyTail,
                                     (float)$datWhxTail->out_qty - $outQtyTail,
                                     $user, $idTail]
                                );
                                $this->logTransaction('T_WAREHOUSE_DETAIL', 'UPDATE',
                                    'IDTAIL: ' . $idTail . ' | QTY: ' . $datWhxTail->qty
                                    . ' >>> ' . ((float)$datWhxTail->qty + $outQtyTail), $user);
                            }

                            DB::connection($this->connection)->update(
                                'UPDATE t_trace_detail SET status = 0, updated_by = ?
                                  WHERE id_trace_tail = ?',
                                [$user, $tailRow->id_trace_tail]
                            );
                        }

                    } else {
                        // Source = WIP/balance (t_balance_header / t_balance_detail)
                        $datWipHead = DB::connection($this->connection)->selectOne(
                            'SELECT qty, out_qty FROM t_balance_header
                              WHERE id_balance_head = ? AND status = 1',
                            [$idHead]
                        );

                        if ($datWipHead) {
                            DB::connection($this->connection)->update(
                                'UPDATE t_balance_header
                                    SET qty = ?, out_qty = ?, updated_by = ?
                                  WHERE id_balance_head = ? AND status = 1',
                                [(float)$datWipHead->qty + $outQtyShip,
                                 (float)$datWipHead->out_qty - $outQtyShip,
                                 $user, $idHead]
                            );
                            $this->logTransaction('T_BALANCE_HEAD', 'UPDATE',
                                'IDHEAD: ' . $idHead . ' | QTY: ' . $datWipHead->qty . ' >>> '
                                . ((float)$datWipHead->qty + $outQtyShip), $user);
                        }

                        DB::connection($this->connection)->update(
                            'UPDATE t_trace_header SET status = 0, updated_by = ? WHERE id_trace_head = ?',
                            [$user, $idTraceHead]
                        );

                        DB::connection($this->connection)->update(
                            'UPDATE t_shipment_header SET status = 0, updated_by = ?
                              WHERE from_trace_no = ? AND trace_no = ? AND qty = ?',
                            [$user, $fromTraceNo, $traceNo, $outQtyShip]
                        );

                        $idShipHeadRow = DB::connection($this->connection)->selectOne(
                            'SELECT id_ship_head FROM t_shipment_header
                              WHERE from_trace_no = ? AND trace_no = ? AND qty = ?',
                            [$fromTraceNo, $traceNo, $outQtyShip]
                        );

                        if ($idShipHeadRow) {
                            DB::connection($this->connection)->update(
                                'UPDATE t_shipment_detail SET status = 0, updated_by = ?
                                  WHERE id_ship_head = ?',
                                [$user, $idShipHeadRow->id_ship_head]
                            );
                        }

                        foreach ($datTraceTail as $tailRow) {
                            $idTail     = $tailRow->id_balance_tail;
                            $outQtyTail = (float) $tailRow->out_qty;

                            $datBalTail = DB::connection($this->connection)->selectOne(
                                'SELECT qty, out_qty FROM t_balance_detail WHERE id_balance_tail = ?',
                                [$idTail]
                            );

                            if ($datBalTail) {
                                DB::connection($this->connection)->update(
                                    'UPDATE t_balance_detail SET qty = ?, out_qty = ?, updated_by = ?
                                      WHERE id_balance_tail = ?',
                                    [(float)$datBalTail->qty + $outQtyTail,
                                     (float)$datBalTail->out_qty - $outQtyTail,
                                     $user, $idTail]
                                );
                                $this->logTransaction('T_BALANCE_DETAIL', 'UPDATE',
                                    'IDTAIL: ' . $idTail . ' | QTY: ' . $datBalTail->qty
                                    . ' >>> ' . ((float)$datBalTail->qty + $outQtyTail), $user);
                            }

                            DB::connection($this->connection)->update(
                                'UPDATE t_trace_detail SET status = 0, updated_by = ?
                                  WHERE id_trace_tail = ?',
                                [$user, $tailRow->id_trace_tail]
                            );
                        }
                    }
                }

                AuditService::log('SHIPMENT', 'CANCEL',
                    'Cancelling shipment | TraceNo: ' . $traceNo . ' | Origin: ' . $origin,
                    $user, ['trace_no' => $traceNo]);

                $this->logTransaction('SHIPMENT', 'CANCEL',
                    'TraceNo: ' . $traceNo . ' | Origin: ' . $origin, $user);

                return ['response' => 1, 'message' => 'Shipment cancelled successfully.'];
            }
        );
    }
}
