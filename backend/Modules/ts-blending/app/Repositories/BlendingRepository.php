<?php declare(strict_types=1);

namespace Modules\TsBlending\Repositories;

use Modules\TsBlending\Repositories\Contracts\BlendingRepositoryInterface;
use Modules\Shared\Services\PeriodLockService;
use Modules\Shared\Traits\DbCompatTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Traits\TankNameFormatterTrait;
use Modules\Shared\Traits\TransactionLoggerTrait;

class BlendingRepository implements BlendingRepositoryInterface
{
    use TankNameFormatterTrait, TransactionLoggerTrait, DbCompatTrait;

    protected $connection = 'eudr_ts';

    public function getActiveMaterials(): Collection
    {
        return DB::connection($this->connection)->table('m_material')
            ->select('id_material', DB::raw("CONCAT(UPPER(description), ' (', code, ')') AS material"))
            ->where('status', 1)
            ->where('id_rundown', '<>', '-')
            ->groupBy('code', 'id_material', 'description')
            ->orderBy('description', 'asc')
            ->get();
    }

    public function generateBlendingEntryNo(int $materialId, int $plantId): ?string
    {
        $dateFmt = $this->dbDateFormat($this->dbCurDate(), '%y%m%d');

        $lpadRight = $this->isPgsql()
            ? "LPAD(CAST(RIGHT(CAST(? AS TEXT), 2) AS TEXT), 2, '0')"
            : "LPAD(RIGHT(?, 2), 2, '0')";
        $traceNoInc = $this->isPgsql()
            ? 'CAST(b.trace_no AS BIGINT) + 1'
            : 'b.trace_no + 1';

        $result = DB::connection($this->connection)->select(
            "SELECT a.entryNo
               FROM (SELECT {$traceNoInc} AS entryNo
                       FROM m_material a
                       LEFT JOIN t_balance_header b
                         ON a.id_rundown = SUBSTRING(b.trace_no FROM 8 FOR 3) AND b.status = 1
                      WHERE a.id_material = ?
                        AND SUBSTRING(b.trace_no FROM 1 FOR 7) = CONCAT(8, {$dateFmt})
                        AND SUBSTRING(b.trace_no FROM 11 FOR 2) = {$lpadRight}
                        AND a.status = 1
                      ORDER BY b.id_balance_head DESC
                      LIMIT 1) a
              UNION ALL
              SELECT CONCAT('8', {$dateFmt},
                     CASE WHEN a.id_rundown <> '-' THEN a.id_rundown ELSE '000' END,
                     {$lpadRight}, '01') AS entryNo
                FROM m_material a
               WHERE a.status = 1
                 AND a.id_material = ?
               LIMIT 1",
            [$materialId, $plantId, $plantId, $materialId]
        );

        return $result[0]->entryNo ?? null;
    }

    public function getTotalStockMaterial(int $materialId, int $plantId): float
    {
        $resolvedPlant = DB::connection($this->connection)->table('m_plant')
            ->where('id_plant', $plantId)
            ->value('code_3') ?: $plantId;

        $slocMatch = $this->isPgsql()
            ? 'CAST(c.id_sloc AS TEXT) = CAST(cc.id_sloc AS TEXT)'
            : "c.id_sloc = cc.id_sloc
                 OR c.id_sloc LIKE CONCAT('%\"', cc.id_sloc, '\"%')
                 OR c.id_sloc LIKE CONCAT('%[', cc.id_sloc, ',%')
                 OR c.id_sloc LIKE CONCAT('%, ', cc.id_sloc, ']%')
                 OR c.id_sloc LIKE CONCAT('%[', cc.id_sloc, ']%')";

        $result = DB::connection($this->connection)->select(
            "SELECT COALESCE(SUM(c.qty),0) AS total
               FROM m_material a
               LEFT JOIN (SELECT b.code, b.id_material
                            FROM m_material b WHERE b.status = 1) b
                 ON a.code = b.code
               LEFT JOIN (SELECT c.id_material, c.qty
                            FROM m_sloc cc
                            LEFT JOIN t_balance_header c
                              ON {$slocMatch}
                           WHERE c.status = 1
                             AND cc.status = 1
                             AND (SUBSTRING(c.trace_no FROM 1 FOR 1) IN ('1','2','7','8','9'))
                             AND cc.id_plant = ?
                         ) c
                 ON b.id_material = c.id_material
              WHERE a.status = 1
                AND a.id_material = ?",
            [$resolvedPlant, $materialId]
        );

        return (float) ($result[0]->total ?? 0);
    }

    public function getTotalQtyMaterial(?string $mode, string $entryNo, ?int $idHead, int $plantId): float
    {
        if ($mode === 'ADD') {
            $total = DB::connection($this->connection)->table('t_balance_temporary as a')
                ->where('a.entry_no', $entryNo)
                ->where('a.status', 1)
                ->where('a.id_plant', $plantId)
                ->sum('a.qty');
        } else {
            $total = DB::connection($this->connection)->table('t_balance_detail as a')
                ->where('a.id_balance_head', $idHead)
                ->where('a.status', 1)
                ->where('a.id_plant', $plantId)
                ->sum('a.qty');
        }

        return (float) $total;
    }

    public function getMaterialList(?string $mode, string $entryNo, ?int $idHead, int $plantId): Collection
    {
        if ($mode === 'ADD') {
            return DB::connection($this->connection)->table('t_balance_temporary as a')
                ->leftJoin('m_material as c', 'a.id_material', '=', 'c.id_material')
                ->select(
                    DB::raw($this->dbNumberFormat('a.qty', 3) . ' AS qty'),
                    'a.id_material',
                    DB::raw("CONCAT(c.code, ' :: ', c.description) AS material"),
                    'a.id_balance_temp AS idTail',
                    'a.entry_no',
                    DB::raw("'" . $mode . "' AS mode")
                )
                ->where('a.entry_no', $entryNo)
                ->where('a.status', 1)
                ->where('a.id_plant', $plantId)
                ->get();
        }

        return DB::connection($this->connection)->table('t_balance_detail as a')
            ->leftJoin('t_balance_header as c', 'a.id_balance_head', '=', 'c.id_balance_head')
            ->leftJoin('m_material as d', 'a.id_material', '=', 'd.id_material')
            ->select(
                DB::raw($this->dbNumberFormat('a.qty', 3) . ' AS qty'),
                'a.id_material',
                DB::raw("CONCAT(d.code, ' :: ', d.description) AS material"),
                'a.id_balance_tail AS idTail',
                'c.trace_no AS entry_no',
                DB::raw("'" . $mode . "' AS mode")
            )
            ->where('a.id_balance_head', $idHead)
            ->where('a.status', 1)
            ->where('a.id_plant', $plantId)
            ->where('c.id_plant', $plantId)
            ->get();
    }

    public function getTanks(?int $plantId = null): Collection
    {
        $query = DB::connection($this->connection)->table('m_sloc')
            ->select('id_sloc as id_tank', 'description as tank', 'id_plant')
            ->where('status', 1)
            ->whereNotNull('description')
            ->where('description', '!=', '');

        if ($plantId && $plantId > 0) {
            $query->where('id_plant', $plantId);
        }

        $result = $query->orderBy('description', 'asc')
            ->orderBy('id_sloc', 'asc')
            ->get();

        return $result->map(function($item) {
            $item->tank = $this->formatTankName($item->tank);
            return $item;
        });
    }

    public function getTankDetails(string $tankDescription, ?int $plantId = null): Collection
    {
        $query = DB::connection($this->connection)->table('m_sloc as s')
            ->select('s.id_sloc as id_tank_tail', 's.description as tankNo')
            ->where('s.status', 1)
            ->where('s.description', $tankDescription);

        if ($plantId && $plantId > 0) {
            $query->where('s.id_plant', $plantId);
        }

        return $query->orderBy('s.description', 'asc')->get();
    }

    public function getBlendingList(int $plantId, int $page = 1, int $perPage = 5): array
    {
        $offset = ($page - 1) * $perPage;

        $supplierConcat = $this->dbGroupConcat(
            "CONCAT(f.description, ' / ', e.batch_sap, ' / Qty : ', " . $this->dbNumberFormat('e.init_qty', 3) . ", ' MT / Qty : ', " . $this->dbNumberFormat('e.qty', 3) . ", ' MT')",
            ' | ',
            true
        );

        $slocExpr = $this->buildSlocExpression();

        $castTrace = $this->isPgsql() ? 'CAST(a.trace_no AS TEXT)' : 'CAST(a.trace_no AS CHAR)';
        $castFromTrace = $this->isPgsql() ? 'CAST(b.from_trace_no AS TEXT)' : 'CAST(b.from_trace_no AS CHAR)';

        $selectFields = "a.entry_date, b.material_document, a.id_sloc,
            {$castTrace} AS trace_no, {$this->dbNumberFormat('a.qty', 3)} AS qty,
            {$this->dbNumberFormat('a.init_qty', 3)} AS init_qty, a.id_balance_head AS idHead,
            CONCAT(c.description, ' (', c.code, ')') AS material,
            p.code_2 AS plant_name,
            {$supplierConcat} AS supplier,
            {$castFromTrace} AS from_trace_no, b.id_trace_head AS idTraceHead,
            b.is_last_row, b.next_process,
            {$slocExpr} AS sloc,
            {$this->dbNumberFormat('ROUND(ee.init_qty,4)', 3)} as balance_supplier";

        $subFromTraceConcat = $this->dbGroupConcat(
            "CONCAT(c.from_trace_no, ' :: ', cc.description, ' (', cc.code, ') - Qty ', " . $this->dbNumberFormat('c.out_qty', 3) . ", ' MT')",
            '|'
        );

        $traceSubquery = "(SELECT b.id_balance_head, b.id_trace_head, c.from_trace_no, d.material_document,
                  CASE WHEN b.to_trace_no = (SELECT to_trace_no FROM t_trace_header WHERE SUBSTRING(to_trace_no FROM 1 FOR 1) = '8' AND SUBSTRING(to_trace_no FROM 9 FOR 1) <> '0' AND status = 1 ORDER BY to_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS is_last_row,
                  CASE WHEN b.to_trace_no = (SELECT from_trace_no FROM t_trace_header WHERE from_trace_no = b.to_trace_no AND status = 1 ORDER BY from_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS next_process
             FROM t_trace_header b
             LEFT JOIN (SELECT c.to_trace_no, c.id_balance_head, {$subFromTraceConcat} AS from_trace_no
                        FROM t_trace_header c LEFT JOIN m_material cc ON c.id_material = cc.id_material
                       WHERE c.status = 1 AND SUBSTRING(c.to_trace_no FROM 1 FOR 1) = '8' AND SUBSTRING(c.to_trace_no FROM 9 FOR 1) = '0' GROUP BY c.to_trace_no, c.id_balance_head
             ) c ON b.from_trace_no = c.to_trace_no
             LEFT JOIN t_material_document d ON d.id_trace_head = b.id_trace_head
            WHERE b.status = 1 AND SUBSTRING(b.to_trace_no FROM 1 FOR 1) = '8' AND SUBSTRING(b.from_trace_no FROM 1 FOR 1) = '8') b";

        $query = DB::connection($this->connection)->table('t_balance_header as a')
            ->select(DB::raw($selectFields))
            ->leftJoin(DB::raw($traceSubquery), 'a.id_balance_head', '=', 'b.id_balance_head')
            ->leftJoin('m_material as c', 'c.id_material', '=', 'a.id_material')
            ->leftJoin('m_sloc as d', function($join) {
                if ($this->isPgsql()) {
                    $join->on(DB::raw('CAST(a.id_sloc AS TEXT)'), '=', DB::raw('CAST(d.id_sloc AS TEXT)'))
                         ->on('d.id_plant', '=', 'a.id_plant');
                } else {
                    $join->on('d.id_sloc', '=', 'a.id_sloc')
                         ->on('d.id_plant', '=', 'a.id_plant');
                }
            })
            ->leftJoin('m_plant as p', function($join) {
                $join->on('a.id_plant', '=', 'p.code_3')
                     ->where('p.status', 1);
            })
            ->leftJoin('t_balance_detail as e', 'a.id_balance_head', '=', 'e.id_balance_head')
            ->leftJoin(DB::raw('(SELECT ee1.trace_no, SUM(ee2.init_qty) AS init_qty FROM t_balance_header ee1 LEFT JOIN t_balance_detail ee2 ON ee1.id_balance_head = ee2.id_balance_head WHERE ee1.status = 1 GROUP BY ee1.trace_no) ee'), 'a.trace_no', '=', 'ee.trace_no')
            ->leftJoin('m_supplier as f', 'e.id_supplier', '=', 'f.id_supplier')
            ->leftJoin('m_sloc as h', function($join) {
                if ($this->isPgsql()) {
                    $join->on(DB::raw('CAST(a.id_sloc AS TEXT)'), '=', DB::raw('CAST(h.id_sloc AS TEXT)'));
                } else {
                    $join->on('h.id_sloc', '=', 'a.id_sloc');
                }
            })
            ->leftJoin('m_sloc as h_sloc', function($join) {
                if ($this->isPgsql()) {
                    $join->on(DB::raw('CAST(a.id_sloc AS TEXT)'), '=', DB::raw('CAST(h_sloc.id_sloc AS TEXT)'));
                } else {
                    $join->on('h_sloc.id_sloc', '=', 'a.id_sloc');
                }
            })
            ->where('a.status', 1)
            ->whereRaw("SUBSTRING(a.trace_no FROM 1 FOR 1) = '8'");

        if ($plantId != 0) {
            $query->where('a.id_plant', $plantId);
        }

        $groupByCols = ['a.trace_no'];
        if ($this->isPgsql()) {
            $groupByCols = array_merge($groupByCols, [
                'a.entry_date', 'a.id_sloc',
                'a.qty', 'a.init_qty', 'a.id_balance_head',
                'c.description', 'c.code', 'p.code_2',
            ]);
        }
        $query->groupBy($groupByCols)->orderByDesc('a.trace_no');

        $total = DB::connection($this->connection)->table('t_balance_header as a')
            ->where('a.status', 1)
            ->whereRaw("SUBSTRING(a.trace_no FROM 1 FOR 1) = '8'")
            ->when($plantId != 0, function($q) use ($plantId) { return $q->where('a.id_plant', $plantId); })
            ->distinct('a.trace_no')->count('a.trace_no');

        $sliced = $query->offset($offset)->limit($perPage)->get();

        return ['data' => $sliced, 'total' => $total];
    }

    public function getActiveTanksRundown(int $materialId, int $plantId): Collection
    {
        return app(\Modules\Shared\Repositories\TankQueryRepository::class)
            ->getActiveTanksRundown($materialId, $plantId);
    }

    public function getAllTanks(int $plantId): Collection
    {
        $query = 'SELECT a.id_sloc AS id_tank,
                         a.description AS tank,
                         a.id_plant
                    FROM m_sloc a
                   WHERE a.status = 1';
        $params = [];

        if ($plantId > 0) {
            $query .= ' AND a.id_plant = ?';
            $params[] = $plantId;
        }

        $query .= ' ORDER BY a.description ASC';

        $result = collect(DB::connection($this->connection)->select($query, $params));

        return $result->map(function($item) {
            $item->tank = $this->formatTankName($item->tank);
            return $item;
        });
    }

    public function getActiveSpecificTanksRundown(int $sloc): Collection
    {
        return app(\Modules\Shared\Repositories\TankQueryRepository::class)
            ->getActiveSpecificTanksRundown($sloc);
    }

    public function addBlendingEntryMaterial(string $user, string $entryNo, int $idMaterial, float $qty, int $idTank, int $plantId): array
    {
        DB::connection($this->connection)->insert(
            'INSERT INTO t_balance_temporary (entry_no, id_material, qty, created_by, id_tank, id_plant)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$entryNo, $idMaterial, $qty, $user, $idTank, $plantId]
        );

        return ['response' => 1];
    }

    public function deleteBlendingMaterial(int $id): bool
    {
        return (bool) DB::connection($this->connection)->delete(
            'DELETE FROM t_balance_temporary WHERE id_balance_temp = ?',
            [$id]
        );
    }

    public function getLockStatus(string $entryDate): bool
    {
        // Use shared PeriodLockService for consistent date lock mechanism
        return PeriodLockService::isLocked($entryDate);
    }

    public function createMaterialDocument(string $user, int $idTraceHead, ?string $materialDoc, string $mode): array
    {
        return app(\Modules\Shared\Services\TransactionCoreService::class)
            ->createMaterialDocument($user, $idTraceHead, $materialDoc, $mode);
    }

    public function deactivateBlending(string $id, string $user): array
    {
        return app(\Modules\Shared\Services\TransactionCancellationService::class)
            ->deactivateBlending($id, $user);
    }

    public function updateEntrySubTank(string $user, int $idHead, array $tails): array
    {
        return app(\Modules\Shared\Services\TransactionCoreService::class)
            ->updateEntrySubTank($user, $idHead, $tails);
    }

    public function getMaterialDocument(int $idTraceHead): ?object
    {
        $result = DB::connection($this->connection)->select(
            'SELECT * FROM t_material_document WHERE id_trace_head = ? LIMIT 1',
            [$idTraceHead]
        );

        return $result[0] ?? null;
    }

    public function checkMaterialInTemporary(int $idMaterial, string $entryNo, int $plantId): bool
    {
        $result = DB::connection($this->connection)->select(
            'SELECT COUNT(entry_no) AS flag FROM t_balance_temporary
              WHERE id_material = ? AND entry_no = ? AND id_plant = ?',
            [$idMaterial, $entryNo, $plantId]
        );

        return ($result[0]->flag ?? 0) > 0;
    }

    public function getTemporaryItemCount(string $entryNo): int
    {
        $result = DB::connection($this->connection)->select(
            'SELECT COUNT(a.entry_no) AS itemCnt FROM t_balance_temporary a WHERE a.entry_no = ?',
            [$entryNo]
        );

        return $result[0]->itemCnt ?? 0;
    }

    public function getTemporaryEntries(string $entryNo): Collection
    {
        return collect(DB::connection($this->connection)->select(
            'SELECT id_material, qty, id_tank FROM t_balance_temporary WHERE entry_no = ?',
            [$entryNo]
        ));
    }

    private function buildSlocExpression(): string
    {
        if ($this->isPgsql()) {
            return "CONCAT(COALESCE(d.description, ''), COALESCE(' | ' || STRING_AGG(DISTINCT COALESCE(h_sloc.description, h.description), ' & ' ORDER BY COALESCE(h_sloc.description, h.description)), ''))";
        }
        $gc = $this->dbGroupConcat("COALESCE(h_sloc.description, h.description)", ' & ', true, 'COALESCE(h_sloc.description, h.description) ASC');
        return "CONCAT(COALESCE(d.description, ''), COALESCE(CONCAT(' | ', {$gc}), ''))";
    }
}
