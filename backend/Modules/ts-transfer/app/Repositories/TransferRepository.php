<?php declare(strict_types=1);
namespace Modules\TsTransfer\Repositories;

use Modules\TsTransfer\Repositories\Contracts\TransferRepositoryInterface;
use Modules\Shared\Services\PeriodLockService;
use Modules\Shared\Services\AuditService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Traits\TankNameFormatterTrait;
use Modules\Shared\Traits\TransactionLoggerTrait;
use Modules\Shared\Traits\DbCompatTrait;

class TransferRepository implements TransferRepositoryInterface
{
    use TankNameFormatterTrait, TransactionLoggerTrait, DbCompatTrait;

    protected string $connection = 'eudr_ts';

    public function getActiveMaterials(): Collection
    {
        $whereIdRundown = $this->isPgsql()
            ? 'CAST(a.id_rundown AS TEXT) <> \'-\''
            : 'a.id_rundown <> "-"';

        return collect(DB::connection($this->connection)->select(
            "SELECT a.id_material, CONCAT(UPPER(a.description), ' (', a.code, ' - ', a.type, ')') AS material
               FROM m_material a
              WHERE a.status = 1
                AND {$whereIdRundown}
              GROUP BY a.code
              ORDER BY a.description ASC"
        ));
    }

    public function generateTransferEntryNo(int $materialId, int $plantId): ?string
    {
        if ($this->isPgsql()) {
            $result = DB::connection($this->connection)->select(
                'SELECT a.trace_no AS entryNo
                   FROM (SELECT b.to_trace_no + 1 AS trace_no
                           FROM m_material a
                           LEFT JOIN t_trace_header b
                             ON CAST(a.id_rundown AS TEXT) = SUBSTRING(b.to_trace_no, 8, 3)
                            AND b.to_trace_no = b.to_trace_no
                            AND b.status = 1
                          WHERE a.id_material = ?
                            AND CAST(SUBSTRING(b.to_trace_no, 1, 1) AS INTEGER) = 7
                            AND SUBSTRING(b.to_trace_no, 2, 6) = TO_CHAR(CURRENT_DATE, \'YYMMDD\')
                            AND SUBSTRING(b.to_trace_no, 11, 2) = LPAD(RIGHT(CAST(? AS TEXT), 2), 2, \'0\')
                            AND a.status = 1
                          ORDER BY b.id_trace_head DESC
                          LIMIT 1) a
                  UNION ALL
                  SELECT CONCAT(\'7\', TO_CHAR(CURRENT_DATE, \'YYMMDD\'),
                          CASE WHEN CAST(a.id_rundown AS TEXT) <> \'-\' THEN CAST(a.id_rundown AS TEXT) ELSE \'000\' END,
                          LPAD(RIGHT(CAST(? AS TEXT), 2), 2, \'0\'), \'01\') AS trace_no
                    FROM m_material a
                   WHERE a.status = 1
                     AND a.id_material = ?
                   LIMIT 1',
                [$materialId, $plantId, $plantId, $materialId]
            );
        } else {
            $result = DB::connection($this->connection)->select(
                'SELECT a.trace_no AS entryNo
                   FROM (SELECT b.to_trace_no + 1 AS trace_no
                           FROM m_material a
                           LEFT JOIN t_trace_header b
                             ON a.id_rundown = SUBSTRING(b.to_trace_no, 8, 3)
                            AND b.to_trace_no = b.to_trace_no
                            AND b.status = 1
                          WHERE a.id_material = ?
                            AND CAST(SUBSTRING(b.to_trace_no, 1, 1) AS INTEGER) = 7
                            AND SUBSTRING(b.to_trace_no, 2, 6) = DATE_FORMAT(CURDATE(), \'%y%m%d\')
                            AND SUBSTRING(b.to_trace_no, 11, 2) = LPAD(RIGHT(?, 2), 2, \'0\')
                            AND a.status = 1
                          ORDER BY b.id_trace_head DESC
                          LIMIT 1) a
                  UNION ALL
                  SELECT CONCAT(\'7\', DATE_FORMAT(CURDATE(), \'%y%m%d\'),
                          IF(CAST(a.id_rundown AS CHAR) <> \'-\', a.id_rundown, \'000\'),
                          LPAD(RIGHT(?, 2), 2, \'0\'), \'01\') AS trace_no
                    FROM m_material a
                   WHERE a.status = 1
                     AND a.id_material = ?
                   LIMIT 1',
                [$materialId, $plantId, $plantId, $materialId]
            );
        }

        return $result[0]->entryNo ?? null;
    }

    public function getTotalStockMaterial(int $materialId, int $tankId, int $plantId): float
    {
        if ($this->isPgsql()) {
            $result = DB::connection($this->connection)->select(
                "SELECT COALESCE(ROUND(CAST(SUM(c.qty) AS numeric), 3), 0) AS total
                   FROM m_material a
                   LEFT JOIN (SELECT b.code, b.id_material
                                FROM m_material b
                               WHERE b.status = 1) b
                     ON a.code = b.code
                   LEFT JOIN (SELECT c.id_material, c.qty
                                FROM t_balance_header c
                               WHERE c.status = 1
                                 AND c.id_sloc = CAST(? AS INTEGER)
                              ) c
                     ON b.id_material = c.id_material
                  WHERE a.status = 1
                    AND a.id_material = ?",
                [$tankId, $materialId]
            );
        } else {
            $result = DB::connection($this->connection)->select(
                'SELECT COALESCE(ROUND(SUM(c.qty), 3), 0) AS total
                   FROM m_material a
                   LEFT JOIN (SELECT b.code, b.id_material
                                FROM m_material b
                               WHERE b.status = 1) b
                     ON a.code = b.code
                   LEFT JOIN (SELECT c.id_material, c.qty
                                FROM t_balance_header c
                               WHERE c.status = 1
                                 AND (c.id_sloc = ?
                                      OR c.id_sloc LIKE CONCAT(\'%"\', ?, \'"%\')
                                      OR c.id_sloc LIKE CONCAT(\'%[\', ?, \',%\')
                                      OR c.id_sloc LIKE CONCAT(\'%, \', ?, \']%\')
                                      OR c.id_sloc LIKE CONCAT(\'%[\', ?, \']%\'))
                              ) c
                     ON b.id_material = c.id_material
                  WHERE a.status = 1
                    AND a.id_material = ?',
                [$tankId, $tankId, $tankId, $tankId, $tankId, $materialId]
            );
        }

        return (float) ($result[0]->total ?? 0);
    }

    public function getTransferList(int $plantId, int $page = 1, int $perPage = 5): array
    {
        $offset = ($page - 1) * $perPage;
        $slocJoin = $this->isPgsql()
            ? 'LEFT JOIN m_sloc t_from ON th_from.id_sloc @> to_jsonb(CAST(t_from.id_sloc AS TEXT))'
            : 'LEFT JOIN m_sloc t_from ON t_from.id_sloc = th_from.id_sloc';

        $totalResult = DB::connection($this->connection)->select(
            "SELECT COUNT(DISTINCT a.trace_no) AS total
               FROM t_balance_header a
               LEFT JOIN t_trace_header b ON a.id_balance_head = b.id_balance_head AND b.status = 1 AND CAST(SUBSTRING(b.to_trace_no,1,1) AS INTEGER) = 7 AND CAST(SUBSTRING(b.from_trace_no,1,1) AS INTEGER) = 7
               LEFT JOIN t_trace_header th_from ON th_from.to_trace_no = b.from_trace_no AND th_from.status = 1
               {$slocJoin}
              WHERE a.status = 1
                AND CAST(SUBSTRING(a.trace_no,1,1) AS INTEGER) = 7
                AND (t_from.id_plant = ? OR a.id_plant = ? OR ? = 0)",
            [$plantId, $plantId, $plantId]
        );
        $total = $totalResult[0]->total ?? 0;

        $idsQuery = DB::connection($this->connection)->table('t_balance_header as a')
            ->select('a.id_balance_head', 'a.trace_no')
            ->distinct()
            ->leftJoin('t_trace_header as b', function($join) {
                $join->on('a.id_balance_head', '=', 'b.id_balance_head')
                     ->where('b.status', 1)
                     ->whereRaw('CAST(SUBSTRING(b.to_trace_no,1,1) AS INTEGER) = 7')
                     ->whereRaw('CAST(SUBSTRING(b.from_trace_no,1,1) AS INTEGER) = 7');
            })
            ->leftJoin('t_trace_header as th_from', function($join) {
                $join->on('th_from.to_trace_no', '=', 'b.from_trace_no')
                     ->where('th_from.status', 1);
            })
            ->leftJoin('m_sloc as t_from', function($join) {
                if ($this->isPgsql()) {
                    $join->on('th_from.id_sloc', '=', DB::raw('CAST(t_from.id_sloc AS TEXT)'));
                } else {
                    $join->on('t_from.id_sloc', '=', 'th_from.id_sloc');
                }
            })
            ->where('a.status', 1)
            ->whereRaw('CAST(SUBSTRING(a.trace_no,1,1) AS INTEGER) = 7');

        if ($plantId != 0) {
            $idsQuery->where(function($q) use ($plantId) {
                $q->where('t_from.id_plant', $plantId)
                  ->orWhere('a.id_plant', $plantId);
            });
        }

        $idsResult = $idsQuery->orderByDesc('a.trace_no')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->toArray();

        if (empty($idsResult)) {
            return ['data' => [], 'total' => $total];
        }

        $idList = array_map(function($row) { return $row->id_balance_head; }, $idsResult);

        $fmt3 = function($col) {
            if ($this->isPgsql()) {
                return "TO_CHAR(ROUND(CAST({$col} AS numeric), 3), 'FM999999999999990.000')";
            }
            return "FORMAT(ROUND({$col},3),3)";
        };
        $fmtOnly3 = function($col) {
            if ($this->isPgsql()) {
                return "TO_CHAR(ROUND(CAST({$col} AS numeric), 3), 'FM999999999999990.000')";
            }
            return "FORMAT({$col},3)";
        };
        $rnd3 = function($col) {
            if ($this->isPgsql()) {
                return "ROUND(CAST({$col} AS numeric),3)";
            }
            return "ROUND({$col},3)";
        };
        $castText = $this->isPgsql() ? 'CAST(a.trace_no AS TEXT)' : 'CAST(a.trace_no AS CHAR)';

        $selectSql = $this->isPgsql()
            ? "a.entry_date, b.material_document,
                th_from.id_balance_head AS fromIdHead, th_from.id_sloc AS from_id_tank,
                {$castText} AS trace_no,
                {$fmt3('a.qty')} AS qty, {$fmt3('a.init_qty')} AS init_qty,
                a.id_balance_head AS idHead,
                CONCAT(c.description, ' (', c.code, ')') AS material,
                CASE SUBSTRING(a.trace_no, 11, 2)
                    WHEN '01' THEN 'EOMB'
                    WHEN '02' THEN 'EOB1'
                    WHEN '03' THEN 'EOB2'
                    WHEN '05' THEN 'EOB5'
                    WHEN '07' THEN 'EOB3'
                    ELSE p.code_2
                END AS plant_name,
                COALESCE(p_from.code_2,
                    CASE SUBSTRING(b.from_trace_no, 11, 2)
                        WHEN '01' THEN 'EOMB'
                        WHEN '02' THEN 'EOB1'
                        WHEN '03' THEN 'EOB2'
                        WHEN '05' THEN 'EOB5'
                        WHEN '07' THEN 'EOB3'
                        ELSE ''
                    END
                ) AS from_plant_name,
                SUBSTRING(a.trace_no, 11, 2) AS plant_code_from_trace,
                b.id_trace_head AS idTraceHead, b.is_last_row, b.next_process,
                {$fmt3('a.in_qty')} AS in_qty, {$fmt3('a.out_qty')} AS out_qty,
                STRING_AGG(DISTINCT CONCAT(f.description, ' / ', e.batch_sap,
                    ' / Qty : ', {$rnd3('e.init_qty')}, ' MT / Qty : ', {$rnd3('e.qty')}, ' MT')
                    , ' | ') AS supplier,
                CASE WHEN ABS(COALESCE(bs.init_qty,0) - a.init_qty) > 0.005 THEN {$fmtOnly3('COALESCE(bs.init_qty,0)')} ELSE {$fmtOnly3('a.init_qty')} END AS balance_supplier,
                ' >>> ' AS raw_sloc,
                a.id_sloc AS raw_id_sloc_to, th_from.id_sloc AS raw_id_sloc_from,
                t_from.id_plant AS from_plant_id,
                a.id_plant AS to_plant_id"
            : "a.entry_date, b.material_document,
                th_from.id_balance_head AS fromIdHead, th_from.id_sloc AS from_id_tank,
                CAST(a.trace_no AS CHAR) AS trace_no,
                FORMAT(ROUND(a.qty,3),3) AS qty, FORMAT(ROUND(a.init_qty,3),3) AS init_qty,
                a.id_balance_head AS idHead,
                CONCAT(c.description, ' (', c.code, ')') AS material,
                CASE SUBSTRING(a.trace_no, 11, 2)
                    WHEN '01' THEN 'EOMB'
                    WHEN '02' THEN 'EOB1'
                    WHEN '03' THEN 'EOB2'
                    WHEN '05' THEN 'EOB5'
                    WHEN '07' THEN 'EOB3'
                    ELSE p.code_2
                END AS plant_name,
                COALESCE(p_from.code_2,
                    CASE SUBSTRING(b.from_trace_no, 11, 2)
                        WHEN '01' THEN 'EOMB'
                        WHEN '02' THEN 'EOB1'
                        WHEN '03' THEN 'EOB2'
                        WHEN '05' THEN 'EOB5'
                        WHEN '07' THEN 'EOB3'
                        ELSE ''
                    END
                ) AS from_plant_name,
                SUBSTRING(a.trace_no, 11, 2) AS plant_code_from_trace,
                b.id_trace_head AS idTraceHead, b.is_last_row, b.next_process,
                FORMAT(ROUND(a.in_qty,3),3) AS in_qty, FORMAT(ROUND(a.out_qty,3),3) AS out_qty,
                GROUP_CONCAT(DISTINCT CONCAT(f.description, ' / ', e.batch_sap,
                    ' / Qty : ', ROUND(e.init_qty,3), ' MT / Qty : ', ROUND(e.qty,3), ' MT')
                    SEPARATOR ' | ') AS supplier,
                IF(ABS(COALESCE(bs.init_qty,0) - a.init_qty) > 0.005, FORMAT(COALESCE(bs.init_qty,0),3), FORMAT(a.init_qty,3)) AS balance_supplier,
                ' >>> ' AS raw_sloc,
                a.id_sloc AS raw_id_sloc_to, th_from.id_sloc AS raw_id_sloc_from,
                t_from.id_plant AS from_plant_id,
                a.id_plant AS to_plant_id";

        $bsSubquery = $this->isPgsql()
            ? "(SELECT h.trace_no, ROUND(CAST(SUM(d.init_qty) AS numeric),3) AS init_qty, ROUND(CAST(SUM(d.qty) AS numeric),3) AS qty
                FROM t_balance_header h
                JOIN t_balance_detail d ON d.id_balance_head = h.id_balance_head
               WHERE d.status = 1 AND d.init_qty > 0.0001
               GROUP BY h.trace_no) bs"
            : "(SELECT h.trace_no, ROUND(SUM(d.init_qty),3) AS init_qty, ROUND(SUM(d.qty),3) AS qty
                FROM t_balance_header h
                JOIN t_balance_detail d ON d.id_balance_head = h.id_balance_head
               WHERE d.status = 1 AND d.init_qty > 0.0001
               GROUP BY h.trace_no) bs";

        $result = DB::connection($this->connection)->table('t_balance_header as a')
            ->selectRaw($selectSql)
            ->leftJoin(DB::raw("(SELECT b.id_balance_head, b.id_trace_head, b.from_trace_no,
                                 d.material_document,
                                 CASE WHEN b.to_trace_no = (SELECT c.to_trace_no FROM t_trace_header c WHERE CAST(SUBSTRING(c.to_trace_no,1,1) AS INTEGER) = 7 AND CAST(SUBSTRING(c.to_trace_no,9,1) AS INTEGER) <> 0 AND c.status = 1 ORDER BY c.to_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS is_last_row,
                                 CASE WHEN b.to_trace_no = (SELECT c.from_trace_no FROM t_trace_header c WHERE c.from_trace_no = b.to_trace_no AND c.status = 1 ORDER BY c.from_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS next_process
                            FROM t_trace_header b
                            LEFT JOIN t_material_document d ON d.id_trace_head = b.id_trace_head
                           WHERE b.status = 1 AND CAST(SUBSTRING(b.to_trace_no,1,1) AS INTEGER) = 7 AND CAST(SUBSTRING(b.from_trace_no,1,1) AS INTEGER) = 7
                           GROUP BY b.id_balance_head) b"), 'a.id_balance_head', '=', 'b.id_balance_head')
            ->leftJoin('m_material as c', 'c.id_material', '=', 'a.id_material')
            ->leftJoin('t_trace_header as th_from', function($join) {
                $join->on('th_from.to_trace_no', '=', 'b.from_trace_no')->where('th_from.status', 1);
            })
            ->leftJoin('m_plant as p', function($join) {
                $onClauseRight = $this->isPgsql() ? 'RIGHT(p.code_3, 2)' : 'RIGHT(p.code_3, 2) COLLATE utf8mb4_unicode_ci';
                $join->on(DB::raw('SUBSTRING(a.trace_no, 11, 2)'), '=', DB::raw($onClauseRight))->where('p.status', 1);
            })
            ->leftJoin('m_sloc as t_from', function($join) {
                if ($this->isPgsql()) {
                    $join->on('th_from.id_sloc', '=', DB::raw('CAST(t_from.id_sloc AS TEXT)'));
                } else {
                    $join->on('t_from.id_sloc', '=', 'th_from.id_sloc');
                }
            })
            ->leftJoin('m_plant as p_from', function($join) {
                $onClauseRight = $this->isPgsql() ? 'p_from.code_3' : 'p_from.code_3 COLLATE utf8mb4_unicode_ci';
                $join->on(DB::raw('t_from.id_plant'), '=', DB::raw($onClauseRight))->where('p_from.status', 1);
            })
            ->leftJoin('t_balance_detail as e', function($join) {
                $join->on('a.id_balance_head', '=', 'e.id_balance_head')->where('e.status', 1);
            })
            ->leftJoin('m_supplier as f', 'e.id_supplier', '=', 'f.id_supplier')
            ->leftJoin(DB::raw($bsSubquery), 'bs.trace_no', '=', 'a.trace_no')
            ->where('a.status', 1)
            ->whereRaw('CAST(SUBSTRING(a.trace_no,1,1) AS INTEGER) = 7')
            ->whereIn('a.id_balance_head', $idList)
            ->groupBy('a.trace_no')
            ->orderByDesc('a.trace_no')
            ->get();

        $slocs = \Illuminate\Support\Facades\DB::connection('eudr_ts')
            ->table('m_sloc')
            ->select('id_sloc', 'description', 'id_tank')
            ->get()
            ->keyBy('id_sloc');

        $result = $result->map(function ($item) use ($slocs) {
            $item->raw_from_desc = '';
            $item->from_tf_number = '';
            if (isset($item->raw_id_sloc_from) && $item->raw_id_sloc_from !== null && $item->raw_id_sloc_from !== '') {
                $decoded = json_decode((string)$item->raw_id_sloc_from, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $firstDesc = '';
                    $tanks = [];
                    foreach ($decoded as $id) {
                        if (isset($slocs[$id])) {
                            if (!$firstDesc) $firstDesc = $slocs[$id]->description;
                            if ($slocs[$id]->id_tank) $tanks[] = $slocs[$id]->id_tank;
                        }
                    }
                    if ($firstDesc) $item->raw_from_desc = $firstDesc;
                    if (!empty($tanks)) {
                        sort($tanks);
                        $item->from_tf_number = implode(', ', array_unique($tanks));
                    }
                } else {
                    if (isset($slocs[$item->raw_id_sloc_from])) {
                        $item->raw_from_desc = $slocs[$item->raw_id_sloc_from]->description;
                        $item->from_tf_number = $slocs[$item->raw_id_sloc_from]->id_tank;
                    }
                }
            }

            $item->raw_to_desc = '';
            $item->to_tf_number = '';
            if (isset($item->raw_id_sloc_to) && $item->raw_id_sloc_to !== null && $item->raw_id_sloc_to !== '') {
                $decoded = json_decode((string)$item->raw_id_sloc_to, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $firstDesc = '';
                    $tanks = [];
                    foreach ($decoded as $id) {
                        if (isset($slocs[$id])) {
                            if (!$firstDesc) $firstDesc = $slocs[$id]->description;
                            if ($slocs[$id]->id_tank) $tanks[] = $slocs[$id]->id_tank;
                        }
                    }
                    if ($firstDesc) $item->raw_to_desc = $firstDesc;
                    if (!empty($tanks)) {
                        sort($tanks);
                        $item->to_tf_number = implode(', ', array_unique($tanks));
                    }
                } else {
                    if (isset($slocs[$item->raw_id_sloc_to])) {
                        $item->raw_to_desc = $slocs[$item->raw_id_sloc_to]->description;
                        $item->to_tf_number = $slocs[$item->raw_id_sloc_to]->id_tank;
                    }
                }
            }


            $fromFormatted = $this->formatTankName($item->raw_from_desc);
            $toFormatted = $this->formatTankName($item->raw_to_desc);

            $fromPart = '';
            if (!empty($item->from_tf_number)) {
                $fromPart = $item->from_tf_number . ' & ';
            }
            $fromPart .= $fromFormatted ?? '';

            $toPart = '';
            if (!empty($item->to_tf_number)) {
                $toPart = $item->to_tf_number . ' & ';
            }
            $toPart .= $toFormatted ?? '';

            if (($item->to_plant_id ?? 0) == 1001) {
                $item->sloc = trim($fromPart) . " >>> EOMB";
            } elseif (($item->from_plant_id ?? 0) == 1001) {
                $item->sloc = "EOMB >>> " . trim($toPart);
            } else {
                $item->sloc = trim($fromPart) . " >>> " . trim($toPart);
            }

            return $item;
        });

        return ['data' => $result, 'total' => $total];
    }

    public function getActiveTanksRundown(?int $materialId, int $plantId, bool $excludePlant = true): Collection
    {
        return app(\Modules\Shared\Repositories\TankQueryRepository::class)
            ->getActiveTanksRundown($materialId, $plantId, $excludePlant);
    }

    public function getActiveSpecificTanksRundown(int $sloc): Collection
    {
        return app(\Modules\Shared\Repositories\TankQueryRepository::class)
            ->getActiveSpecificTanksRundown($sloc);
    }

    public function getLockStatus(string $entryDate): bool
    {
        // Use shared PeriodLockService for consistent date lock mechanism
        return PeriodLockService::isLocked($entryDate);
    }

    public function getUpdateSupplierMaterial(int $idMaterial, int $idTank, int $plantId): ?object
    {
        $castSeq = $this->isPgsql()
            ? 'CAST(SUBSTRING(a.batch_sap,7,2) AS INTEGER)'
            : 'CAST(SUBSTRING(a.batch_sap,7,2) AS UNSIGNED)';
        $dateFmtSql = $this->isPgsql()
            ? "TO_CHAR(NOW(), 'YYMMDD')"
            : "DATE_FORMAT(NOW(), '%y%m%d')";
        $traceNoCond = $this->isPgsql()
            ? 'CAST(SUBSTRING(b.trace_no,1,1) AS INTEGER) = 7'
            : 'SUBSTRING(b.trace_no,1,1) = 7';
        $lpadExpr = $this->isPgsql()
            ? "LPAD(CAST(COALESCE(MAX({$castSeq}) + 1, 1) AS TEXT), 2, '0')"
            : "LPAD(COALESCE(MAX({$castSeq}) + 1, 1), 2, '0')";
        $datSeq = DB::connection($this->connection)->select(
            "SELECT {$lpadExpr} AS seq_no
               FROM t_balance_detail a
               LEFT JOIN t_balance_header b ON a.id_balance_head = b.id_balance_head
              WHERE a.status = 1
                AND SUBSTRING(a.batch_sap,1,6) = {$dateFmtSql}
                AND {$traceNoCond}",
            []
        );

        $seqNo = $datSeq[0]->seq_no ?? '01';

        $plantCode = DB::connection($this->connection)->table('m_plant')
            ->where('id_plant', $plantId)
            ->value('code_3');

        $dateSql = $this->isPgsql()
            ? "TO_CHAR(NOW(), 'YYMMDD')"
            : "DATE_FORMAT(NOW(), '%y%m%d')";

        $result = DB::connection($this->connection)->select(
            'SELECT CONCAT(' . $dateSql . ', ?, b.code_4, UPPER(a.code_matl_supplier)) AS supplierCode,
                    COALESCE(c.id_supplier, 0) AS idSupplier
               FROM (SELECT a.code_matl_supplier FROM m_material a WHERE a.status = 1 AND a.id_material = ?) a
               JOIN (SELECT s.code_4 FROM m_sloc s JOIN m_plant p ON s.id_plant = p.code_3 WHERE s.status = 1 AND s.id_sloc = ?) b
               LEFT JOIN (SELECT c.id_supplier FROM m_supplier c WHERE c.status = 1 AND c.type = ?) c ON 1=1
              LIMIT 1',
            [$seqNo, $idMaterial, $idTank, $idTank]
        );

        return $result[0] ?? null;
    }

    public function postAdjEntrySupplier(string $user, string $adjNumber, int $idSupplier, int $idMaterial, float $qty, string $batchSap, int $plantId): array
    {
        DB::connection($this->connection)->insert(
            'INSERT INTO t_balance_temporary (entry_no, id_supplier, id_material, qty, batch_sap, id_plant, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$adjNumber, $idSupplier, $idMaterial, $qty, $batchSap, $plantId, $user]
        );

        return ['response' => 1];
    }

    public function createMaterialDocument(string $user, int $idTraceHead, string $materialDoc, string $mode): array
    {
        return app(\Modules\Shared\Services\TransactionCoreService::class)
            ->createMaterialDocument($user, $idTraceHead, $materialDoc, $mode);
    }

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
            if ($this->getLockStatus($currEntryDate)) {
                DB::connection($this->connection)->rollBack();
                return ['response' => 99];
            }

            $counter = 0;
            $maxIterations = 100;

            do {
                $this->logTransaction('TRANSFER_ENTRY', 'DE-ACTIVATE',
                    'IdBalHead: ' . $idHead . ' | Status: 1 >> 0', $user);

                // Also use AuditService for structured logging
                AuditService::log('TRANSFER', 'DELETE',
                    'Deactivating transfer | IdBalHead: ' . $idHead . ' | IdTraceHead: ' . $idTraceHead,
                    $user, ['id_balance_head' => $idHead, 'id_trace_head' => $idTraceHead]);

                DB::connection($this->connection)->update(
                    'UPDATE t_balance_detail SET status = \'0\', updated_by = ? WHERE id_balance_head = ?',
                    [$user, $idHead]
                );
                DB::connection($this->connection)->update(
                    'UPDATE t_balance_header SET status = \'0\', updated_by = ? WHERE id_balance_head = ?',
                    [$user, $idHead]
                );

                $dateFmtSql = $this->isPgsql()
                    ? "TO_CHAR(a.created_at, 'YYYY-MM-DD HH24:MI')"
                    : "DATE_FORMAT(a.created_at, '%Y-%m-%d %H:%i')";

                $datTraceHead = DB::connection($this->connection)->select(
                    "SELECT b.id_balance_head, b.out_qty, b.id_trace_head, a.id_material, a.in_qty,
                            {$dateFmtSql} AS created_at
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
                            'UPDATE t_trace_detail SET status = \'0\', updated_by = ? WHERE id_trace_tail = ?',
                            [$user, $tail->id_trace_tail]
                        );
                    }

                    DB::connection($this->connection)->update(
                        'UPDATE t_trace_header SET status = \'0\', updated_by = ? WHERE id_trace_head = ?',
                        [$user, $idTracHead]
                    );
                }

                DB::connection($this->connection)->update(
                    'UPDATE t_trace_header SET status = \'0\', updated_by = ? WHERE id_balance_head = ?',
                    [$user, $idHead]
                );
                DB::connection($this->connection)->update(
                    'UPDATE t_trace_detail SET status = \'0\', updated_by = ? WHERE id_trace_head = ?',
                    [$user, $idTraceHead]
                );

                /* DESTROYING AUTO ADJUSTMENT IN (prefix 9) */
                $dateFmtWhere = $this->isPgsql()
                    ? "TO_CHAR(created_at, 'YYYY-MM-DD HH24:MI')"
                    : "DATE_FORMAT(created_at, '%Y-%m-%d %H:%i')";

                $tracePrefix9Cond = $this->isPgsql()
                    ? 'CAST(SUBSTRING(to_trace_no,1,1) AS INTEGER) = 9'
                    : 'SUBSTRING(to_trace_no,1,1) = 9';
                $tracePrefix7Cond = $this->isPgsql()
                    ? 'CAST(SUBSTRING(to_trace_no,1,1) AS INTEGER) = 7'
                    : 'SUBSTRING(to_trace_no,1,1) = 7';

                $datAdjustIn = DB::connection($this->connection)->select(
                    "SELECT id_balance_head, id_trace_head
                       FROM t_trace_header
                      WHERE status = 1
                        AND from_trace_no IS NULL
                        AND {$tracePrefix9Cond}
                        AND {$dateFmtWhere} = ?
                        AND id_material = ?
                        AND in_qty = ?",
                    [$createdAt, $idMaterial, $inQty]
                );

                if (count($datAdjustIn) > 0) {
                    $idBalHead = $datAdjustIn[0]->id_balance_head;
                    $idTraceHead = $datAdjustIn[0]->id_trace_head;

                    DB::connection($this->connection)->update(
                        'UPDATE t_trace_header SET status = \'0\', updated_by = ? WHERE status = 1 AND id_trace_head = ?',
                        [$user, $idTraceHead]
                    );
                    DB::connection($this->connection)->update(
                        'UPDATE t_trace_detail SET status = \'0\', updated_by = ? WHERE status = 1 AND id_trace_head = ?',
                        [$user, $idTraceHead]
                    );
                    DB::connection($this->connection)->update(
                        'UPDATE t_balance_header SET status = \'0\', updated_by = ? WHERE status = 1 AND id_balance_head = ?',
                        [$user, $idBalHead]
                    );
                    DB::connection($this->connection)->update(
                        'UPDATE t_balance_detail SET status = \'0\', updated_by = ? WHERE status = 1 AND id_balance_head = ?',
                        [$user, $idBalHead]
                    );
                }

                /* DESTROYING AUTO TRF TO ADJUSTMENT OUT (prefix 7) */
                $datAdjustOut = DB::connection($this->connection)->select(
                    "SELECT id_balance_head, id_trace_head
                       FROM t_trace_header
                      WHERE status = 1
                        AND {$tracePrefix7Cond}
                        AND {$dateFmtWhere} = ?
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

    public function updateEntrySubTank(string $user, int $idHead, array $tails): array
    {
        return app(\Modules\Shared\Services\TransactionCoreService::class)
            ->updateEntrySubTank($user, $idHead, $tails);
    }

    public function checkTraceNoExists(string $traceNo): bool
    {
        $result = DB::connection($this->connection)->select(
            'SELECT COUNT(to_trace_no) AS cnt FROM t_trace_header WHERE status = 1 AND to_trace_no = ?',
            [$traceNo]
        );
        return ($result[0]->cnt ?? 0) > 0;
    }

    public function getSlocPlant(int $sloc): ?int
    {
        $result = DB::connection($this->connection)->table('m_sloc')
            ->where('id_sloc', $sloc)->value('id_plant');

        return $result !== null ? (int) $result : null;
    }

    public function findOrphanHeads(int $idMaterial, int $sloc, int $plantId): array
    {
        $jsonCond = $this->isPgsql()
            ? 'bh.id_sloc = CAST(? AS INTEGER)'
            : 'JSON_CONTAINS(bh.id_sloc, JSON_ARRAY(?))';

        return DB::connection($this->connection)->select(
            "SELECT bh.id_balance_head, bh.trace_no, bh.qty
               FROM t_balance_header bh
               LEFT JOIN t_balance_detail bd
                 ON bh.id_balance_head = bd.id_balance_head
                AND bd.status = 1
                AND bd.qty > 0.0001
              WHERE bh.status = 1
                AND bh.qty > 0.0001
                AND bh.id_material = ?
                AND {$jsonCond}
                AND bh.id_plant = ?
                AND bd.id_balance_tail IS NULL",
            [$idMaterial, $sloc, $plantId]
        );
    }

    public function findPlantById(int $plantId): ?object
    {
        $result = DB::connection($this->connection)->table('m_plant')
            ->where('id_plant', $plantId)
            ->where('status', 1)
            ->first();

        return $result ?: null;
    }

    public function findPlantCode(int $plantId): string
    {
        if ($plantId) {
            $plant = DB::connection($this->connection)->table('m_plant')
                ->where('id_plant', $plantId)
                ->where('status', 1)
                ->first();
            if ($plant && $plant->code_3) {
                return $plant->code_3;
            }
        }
        return (string) $plantId;
    }

    public function createBalanceHeader(array $data): int
    {
        return DB::connection($this->connection)->table('t_balance_header')->insertGetId($data);
    }

    public function createBalanceDetail(array $data): int
    {
        return DB::connection($this->connection)->table('t_balance_detail')->insertGetId($data);
    }

    public function createAdjustmentHeader(array $data): int
    {
        return DB::connection($this->connection)->table('t_adjustment_header')->insertGetId($data);
    }

    public function createAdjustmentDetail(array $data): bool
    {
        return DB::connection($this->connection)->table('t_adjustment_detail')->insert($data);
    }


}
