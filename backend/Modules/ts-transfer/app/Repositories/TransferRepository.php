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
        return collect(DB::connection($this->connection)->select(
            "SELECT a.id_material, CONCAT(UPPER(a.description), ' (', a.code, ' - ', a.type, ')') AS material
               FROM m_material a
              WHERE a.status = 1
                AND CAST(a.id_rundown AS TEXT) <> '-'
              GROUP BY a.code, a.id_material, a.description, a.type
              ORDER BY a.description ASC"
        ));
    }

    public function generateTransferEntryNo(int $materialId, int $plantId): ?string
    {
        $svc = app(\Modules\Shared\Services\TraceNumberService::class);
        $date = date('ymd');
        $plantCode = $svc->resolvePlantCode((string) $plantId);
        $section = $svc->resolveSection('7', $materialId);
        return $svc->generate('7', $date, $section, $plantCode);
    }

    public function getTotalStockMaterial(int $materialId, int $tankId, int $plantId): float
    {
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

        return (float) ($result[0]->total ?? 0);
    }

    public function getTransferList(int $plantId, int $page = 1, int $perPage = 5): array
    {
        $offset = ($page - 1) * $perPage;
        $slocJoin = "LEFT JOIN m_sloc t_from ON (th_from.id_sloc #>> '{}') = t_from.id_sloc::text";

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

        $traceNoList = DB::connection($this->connection)->select(
            "SELECT DISTINCT a.trace_no
               FROM t_balance_header a
               LEFT JOIN t_trace_header b ON a.id_balance_head = b.id_balance_head AND b.status = 1 AND CAST(SUBSTRING(b.to_trace_no,1,1) AS INTEGER) = 7 AND CAST(SUBSTRING(b.from_trace_no,1,1) AS INTEGER) = 7
               LEFT JOIN t_trace_header th_from ON th_from.to_trace_no = b.from_trace_no AND th_from.status = 1
               {$slocJoin}
              WHERE a.status = 1
                AND CAST(SUBSTRING(a.trace_no,1,1) AS INTEGER) = 7
                AND (t_from.id_plant = ? OR a.id_plant = ? OR ? = 0)
              ORDER BY a.trace_no DESC
              OFFSET ? LIMIT ?",
            [$plantId, $plantId, $plantId, $offset, $perPage]
        );

        if (empty($traceNoList)) {
            return ['data' => [], 'total' => $total];
        }

        $traceNoList = array_map(function($row) { return $row->trace_no; }, $traceNoList);

        $fmt3 = fn($col) => "TO_CHAR(ROUND(CAST({$col} AS numeric), 3), 'FM999999999999990.000')";
        $fmtOnly3 = fn($col) => "TO_CHAR(ROUND(CAST({$col} AS numeric), 3), 'FM999999999999990.000')";
        $rnd3 = fn($col) => "ROUND(CAST({$col} AS numeric),3)";

        $selectSql = "a.entry_date, b.material_document,
            th_from.id_balance_head AS fromIdHead, th_from.id_sloc AS from_id_sloc,
            CAST(a.trace_no AS TEXT) AS trace_no,
            {$fmt3('a.qty')} AS qty, {$fmt3('a.init_qty')} AS init_qty,
            a.id_balance_head AS idHead,
            CONCAT(c.description, ' (', c.code, ')') AS material,
            " . \Modules\Shared\Helpers\TraceHelper::plantNameExpression('a.trace_no') . " AS plant_name,
            " . \Modules\Shared\Helpers\TraceHelper::fromPlantNameExpression('b.from_trace_no') . " AS from_plant_name,
            " . \Modules\Shared\Helpers\TraceHelper::plantCodeExpression('a.trace_no') . " AS plant_code_from_trace,
            b.id_trace_head AS idTraceHead, b.is_last_row, b.next_process,
            {$fmt3('a.in_qty')} AS in_qty, {$fmt3('a.out_qty')} AS out_qty,
            sup_agg.supplier AS supplier,
            CASE WHEN ABS(COALESCE(bs.init_qty,0) - a.init_qty) > 0.005 THEN {$fmtOnly3('COALESCE(bs.init_qty,0)')} ELSE {$fmtOnly3('a.init_qty')} END AS balance_supplier,
            ' >>> ' AS raw_sloc,
            a.id_sloc AS raw_id_sloc_to, th_from.id_sloc AS raw_id_sloc_from,
            t_from.id_plant AS from_plant_id,
            a.id_plant AS to_plant_id,
            a.created_at, a.created_by";

        $bsSubquery = "(SELECT h.id_balance_head, ROUND(CAST(SUM(d.init_qty) AS numeric),3) AS init_qty, ROUND(CAST(SUM(d.qty) AS numeric),3) AS qty
                FROM t_balance_header h
                JOIN t_balance_detail d ON d.id_balance_head = h.id_balance_head
               WHERE d.status = 1 AND d.init_qty > 0.0001
               GROUP BY h.id_balance_head) bs";

        $supplierAggSubquery = "(SELECT e2.id_balance_head,
                   STRING_AGG(DISTINCT CONCAT(f2.description, ' / ', e2.batch_sap,
                       ' / Qty : ', ROUND(CAST(e2.init_qty AS numeric),3), ' MT / Qty : ', ROUND(CAST(e2.qty AS numeric),3), ' MT')
                       , ' | ') AS supplier
                  FROM t_balance_detail e2
                  LEFT JOIN m_supplier f2 ON f2.id_supplier = e2.id_supplier
                 WHERE e2.status = 1
                 GROUP BY e2.id_balance_head) sup_agg";

        $result = DB::connection($this->connection)->table('t_balance_header as a')
            ->selectRaw($selectSql)
            ->leftJoin(DB::raw("(SELECT b.id_balance_head,
                                 MIN(b.id_trace_head) AS id_trace_head,
                                 MIN(b.from_trace_no) AS from_trace_no,
                                 MIN(d.material_document) AS material_document,
                                 MAX(CASE WHEN b.to_trace_no = (SELECT c.to_trace_no FROM t_trace_header c WHERE CAST(SUBSTRING(c.to_trace_no,1,1) AS INTEGER) = 7 AND " . \Modules\Shared\Helpers\TraceHelper::only14Digit('c.to_trace_no') . " AND CAST(SUBSTRING(c.to_trace_no,9,1) AS INTEGER) <> 0 AND c.status = 1 ORDER BY c.to_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END) AS is_last_row,
                                 MAX(CASE WHEN b.to_trace_no = (SELECT c.from_trace_no FROM t_trace_header c WHERE c.from_trace_no = b.to_trace_no AND c.status = 1 ORDER BY c.from_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END) AS next_process
                            FROM t_trace_header b
                            LEFT JOIN t_material_document d ON d.id_trace_head = b.id_trace_head
                           WHERE b.status = 1 AND CAST(SUBSTRING(b.to_trace_no,1,1) AS INTEGER) = 7 AND CAST(SUBSTRING(b.from_trace_no,1,1) AS INTEGER) = 7
                           GROUP BY b.id_balance_head) b"), 'a.id_balance_head', '=', 'b.id_balance_head')
            ->leftJoin('m_material as c', 'c.id_material', '=', 'a.id_material')
            ->leftJoin('t_trace_header as th_from', function($join) {
                $join->on('th_from.to_trace_no', '=', 'b.from_trace_no')->where('th_from.status', 1);
            })
            ->leftJoin('m_plant as p', function($join) {
                $join->on(DB::raw(\Modules\Shared\Helpers\TraceHelper::plantCodeExpression('a.trace_no')), '=', DB::raw('RIGHT(p.code_3, 2)'))->where('p.status', 1);
            })
            ->leftJoin('m_sloc as t_from', function($join) {
                $join->whereRaw("(th_from.id_sloc #>> '{}') = t_from.id_sloc::text");
            })
            ->leftJoin('m_plant as p_from', function($join) {
                $join->on(DB::raw('t_from.id_plant'), '=', DB::raw('p_from.code_3'))->where('p_from.status', 1);
            })
            ->leftJoin(DB::raw($supplierAggSubquery), 'sup_agg.id_balance_head', '=', 'a.id_balance_head')
            ->leftJoin(DB::raw($bsSubquery), 'bs.id_balance_head', '=', 'a.id_balance_head')
            ->where('a.status', 1)
            ->whereRaw('CAST(SUBSTRING(a.trace_no,1,1) AS INTEGER) = 7')
            ->whereIn('a.trace_no', $traceNoList)
            ->orderByDesc('a.trace_no')
            ->get();

        // Deduplicate by trace_no (merge multiple balance_heads per trace)
        $result = $result->groupBy('trace_no')->map(function ($rows) {
            $base = $rows->first();
            $totalBsInit = (float) ($base->balance_supplier ?? 0);
            foreach ($rows->skip(1) as $row) {
                $base->qty = number_format((float)$base->qty + (float)$row->qty, 3, '.', '');
                $base->init_qty = number_format((float)$base->init_qty + (float)$row->init_qty, 3, '.', '');
                $base->in_qty = number_format((float)$base->in_qty + (float)$row->in_qty, 3, '.', '');
                $base->out_qty = number_format((float)$base->out_qty + (float)$row->out_qty, 3, '.', '');
                $totalBsInit += (float) ($row->balance_supplier ?? 0);

                if ($row->entry_date > $base->entry_date) {
                    $base->entry_date = $row->entry_date;
                }

                // Merge JSON sloc arrays (handles JSON arrays and single IDs)
                $decodeTo = function($v) {
                    if ($v === null || $v === '' || $v === 'null') return [];
                    $decoded = json_decode((string)$v, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return $decoded;
                    }
                    return [(string)$v];
                };
                $toArr = $decodeTo($base->raw_id_sloc_to);
                $rowToArr = $decodeTo($row->raw_id_sloc_to);
                $base->raw_id_sloc_to = json_encode(array_values(array_unique(array_merge($toArr, $rowToArr))));

                $fromArr = $decodeTo($base->raw_id_sloc_from);
                $rowFromArr = $decodeTo($row->raw_id_sloc_from);
                $base->raw_id_sloc_from = json_encode(array_values(array_unique(array_merge($fromArr, $rowFromArr))));

                // Merge supplier
                if (!empty($row->supplier)) {
                    $existing = $base->supplier ?? '';
                    $add = $row->supplier;
                    $parts = array_unique(array_merge(
                        array_filter(explode(' | ', $existing)),
                        array_filter(explode(' | ', $add))
                    ));
                    $base->supplier = implode(' | ', $parts);
                }
            }
            // Re-derive balance_supplier: use merged init_qty if within tolerance
            $initQty = (float) $base->init_qty;
            if (abs($totalBsInit - $initQty) > 0.005) {
                $base->balance_supplier = number_format($totalBsInit, 3, '.', '');
            } else {
                $base->balance_supplier = number_format($initQty, 3, '.', '');
            }
            return $base;
        })->values();

        $slocs = \Illuminate\Support\Facades\DB::connection('eudr_ts')
            ->table('m_sloc')
            ->select('id_sloc', 'description', 'tf_number')
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
                            if ($slocs[$id]->tf_number) $tanks[] = $slocs[$id]->tf_number;
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
                        $item->from_tf_number = $slocs[$item->raw_id_sloc_from]->tf_number;
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
                            if ($slocs[$id]->tf_number) $tanks[] = $slocs[$id]->tf_number;
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
                        $item->to_tf_number = $slocs[$item->raw_id_sloc_to]->tf_number;
                    }
                }
            }

            $fromTf = !empty($item->from_tf_number) ? $item->from_tf_number : ($item->raw_from_desc ?: '-');
            $toTf   = !empty($item->to_tf_number)   ? $item->to_tf_number   : ($item->raw_to_desc ?: '-');
            $item->sloc = $fromTf . " >>> " . $toTf;

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
        return \Modules\Shared\Services\PeriodLockService::isLocked($entryDate);
    }

    public function getUpdateSupplierMaterial(int $idMaterial, int $idSloc, int $plantId): ?object
    {
        $castSeq = 'CAST(SUBSTRING(a.batch_sap,7,2) AS INTEGER)';
        $dateFmtSql = "TO_CHAR(NOW(), 'YYMMDD')";
        $traceNoCond = 'CAST(SUBSTRING(b.trace_no,1,1) AS INTEGER) = 7';
        $lpadExpr = "LPAD(CAST(COALESCE(MAX({$castSeq}) + 1, 1) AS TEXT), 2, '0')";
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

        $result = DB::connection($this->connection)->select(
            'SELECT CONCAT(' . $dateFmtSql . ', CAST(? AS TEXT), b.code_4, UPPER(a.code_matl_supplier)) AS supplierCode,
                    COALESCE(c.id_supplier, 0) AS idSupplier
               FROM (SELECT a.code_matl_supplier FROM m_material a WHERE a.status = 1 AND a.id_material = ?) a
               CROSS JOIN (SELECT s.code_4 FROM m_sloc s WHERE s.status = 1 AND s.id_sloc = ?) b
               LEFT JOIN (SELECT c.id_supplier FROM m_supplier c WHERE c.status = 1 AND CAST(c.type AS INTEGER) = ?) c ON 1=1
              LIMIT 1',
            [$seqNo, $idMaterial, $idSloc, $idSloc]
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

    /**
     * Delegate to TransactionCancellationService — single source of truth for transfer deactivation.
     */
    public function deactivateTransfer(string $id, string $user): array
    {
        return app(\Modules\Shared\Services\TransactionCancellationService::class)
            ->deactivateTransfer($id, $user);
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

    public function getNextSequence(string $ymd, string $rundownCode, string $plantCode): string
    {
        $sql = "SELECT MAX(CAST(SUBSTRING(to_trace_no, 13, 2) AS INTEGER)) AS max_seq
                  FROM t_trace_header
                 WHERE status = 1
                   AND " . \Modules\Shared\Helpers\TraceHelper::only14Digit('to_trace_no') . "
                   AND CAST(SUBSTRING(to_trace_no, 1, 1) AS INTEGER) = 7
                   AND SUBSTRING(to_trace_no, 2, 6) = ?
                   AND SUBSTRING(to_trace_no, 8, 3) = ?
                   AND SUBSTRING(to_trace_no, 11, 2) = ?";
        $result = DB::connection($this->connection)->select($sql, [$ymd, $rundownCode, $plantCode]);
        $maxSeq = (int) ($result[0]->max_seq ?? 0);
        return str_pad((string) ($maxSeq + 1), 2, '0', STR_PAD_LEFT);
    }

    public function getSlocPlant(int $sloc): ?int
    {
        $result = DB::connection($this->connection)->table('m_sloc')
            ->where('id_sloc', $sloc)->value('id_plant');

        return $result !== null ? (int) $result : null;
    }

    public function findOrphanHeads(int $idMaterial, int $sloc, int $plantId): array
    {
        $jsonCond = "(CASE WHEN bh.id_sloc::text LIKE '[%' THEN bh.id_sloc::text::jsonb @> to_jsonb(CAST(? AS INTEGER)) ELSE bh.id_sloc = CAST(? AS INTEGER) END)";

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
            [$idMaterial, $sloc, $sloc, $plantId]
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
        return app(\Modules\Shared\Services\TransferBalanceService::class)->createBalanceHeader($data);
    }

    public function createBalanceDetail(array $data): int
    {
        return DB::connection($this->connection)->table('t_balance_detail')->insertGetId($data, 'id_balance_tail');
    }

    public function createAdjustmentHeader(array $data): int
    {
        return DB::connection($this->connection)->table('t_adjustment_header')->insertGetId($data, 'id_adjust_head');
    }

    public function createAdjustmentDetail(array $data): bool
    {
        return DB::connection($this->connection)->table('t_adjustment_detail')->insert($data);
    }

    public function findMaterialRundown(int $idMaterial): string
    {
        $material = DB::connection($this->connection)->table('m_material')
            ->where('id_material', $idMaterial)
            ->first();

        if ($material && isset($material->id_rundown) && $material->id_rundown !== '-') {
            return str_pad((string)$material->id_rundown, 3, '0', STR_PAD_LEFT);
        }
        return '000';
    }

    public function generateAdjSequence(string $prefix12): string
    {
        $lastTrace = DB::connection($this->connection)->table('t_balance_header')
            ->where('trace_no', 'LIKE', $prefix12 . '%')
            ->orderBy('id_balance_head', 'desc')
            ->value('trace_no');

        if ($lastTrace) {
            $seq = (int) substr($lastTrace, 12, 2) + 1;
            return str_pad((string)$seq, 2, '0', STR_PAD_LEFT);
        }

        return '01';
    }
}
