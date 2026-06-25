<?php
declare(strict_types=1);
namespace Modules\Adjustment\Repositories;

use Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;
use Modules\Shared\Helpers\QuantityDistributionHelper;
use Modules\Shared\Repositories\Traits\PlantFilterTrait;
use Modules\Shared\Services\Contracts\PlantContextServiceInterface;
use Modules\Shared\Traits\DbCompatTrait;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdjustmentRepository implements AdjustmentRepositoryInterface
{
    use PlantFilterTrait;
    use DbCompatTrait;

    protected string $connection = 'eudr_ts';

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  Existing
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â

    public function getAdjustmentList(mixed $plantId, ?int $userId = null, string $adjType = 'wip', array $filters = []): array
    {
        $isWh        = ($adjType === 'wh');
        $plantFilter = $this->buildTablePlantFilter('a', $plantId, $userId);
        $substrFilter = $isWh ? '6' : '9';

        $materialJoin = $isWh
            ? "LEFT JOIN m_material_pck b ON a.id_material = b.id_materialpck"
            : "LEFT JOIN m_material b ON a.id_material = b.id_material";

        $balanceJoin = $isWh
            ? "LEFT JOIN t_warehouse_header c ON a.id_balance_head = c.id_whx_head AND c.status = 1"
            : "LEFT JOIN t_balance_header c ON a.id_balance_head = c.id_balance_head AND c.status = 1";

        $slocJoinG = $isWh
            ? "LEFT JOIN m_warehouse g ON g.id_warehouse = CAST(a.id_sloc AS bigint)"
            : "LEFT JOIN m_sloc g ON ({$this->dbSlocColumnClause('a.id_sloc', 'g.id_sloc')}) AND g.status = 1";

        $slocJoinH = $isWh
            ? ""
            : "LEFT JOIN m_sloc h ON ({$this->dbSlocColumnClause('c.id_sloc', 'h.id_sloc')}) AND h.status = 1";

        $slocColumn = $isWh
            ? "COALESCE(g.description, '') AS sloc"
            : "CONCAT(
                  COALESCE(MIN(g.description), ''),
                  CASE WHEN {$this->dbGroupConcat('DISTINCT h.description', ', ', true, 'h.description ASC')} IS NULL
                      THEN ''
                      ELSE CONCAT(' | ', {$this->dbGroupConcat('DISTINCT h.description', ', ', true, 'h.description ASC')})
                  END
               ) AS sloc";

        $whxColumns  = $isWh ? ", c.batch_no, c.po_no" : ", NULL AS batch_no, NULL AS po_no";
        $whxGroupBy  = $isWh ? ", c.batch_no, c.po_no, g.id_warehouse" : ", c.id_sloc, g.id_sloc";

        $sql = "
            SELECT a.entry_date, CAST(a.adjust_no AS TEXT) AS adjust_no,
                   CONCAT(b.code, ' :: ', b.description) AS material,
                   a.id_sloc,
                   CAST(c.trace_no AS TEXT) AS trace_no,
                   CONCAT('Qty: ', a.before_adjust, ' >>> ', a.after_adjust, ' MT') AS adjustment,
                   a.id_adjust_head,
                   {$this->dbGroupConcat("DISTINCT CONCAT(e.description, ' / ', d.batch_sap, ' / Qty: ', " . $this->dbNumberFormat('d.before_adjust', 3) . ", ' >>> ', " . $this->dbNumberFormat('d.after_adjust', 3) . ", ' MT')", ' | ', true)} AS supplier,
                   a.created_by, a.created_at, a.status, a.after_adjust,
                   {$slocColumn},
                   CASE WHEN c.qty IS NOT NULL AND a.after_adjust <> c.qty THEN 0 ELSE 1 END AS adjust_flag,
                   f.id_matdoc, f.material_document, f.id_trace_head,
                   CASE a.status
                     WHEN 1 THEN 'DRAFT'
                     WHEN 2 THEN 'APPROVED'
                     WHEN 3 THEN 'REJECTED'
                     WHEN 4 THEN 'EXECUTED'
                     ELSE 'UNKNOWN'
                   END AS status_label
                   {$whxColumns}
              FROM t_adjustment_header a
              {$materialJoin}
              {$balanceJoin}
              LEFT JOIN t_adjustment_detail d ON a.id_adjust_head = d.id_adjust_head
              LEFT JOIN m_supplier e ON e.id_supplier = d.id_supplier
              LEFT JOIN (
                  SELECT f.to_trace_no, ff.id_matdoc, ff.material_document, f.id_trace_head
                    FROM t_trace_header f
                    LEFT JOIN t_material_document ff ON f.id_trace_head = ff.id_trace_head AND ff.status = 1
                   WHERE f.status = 1
              ) f ON CAST(a.adjust_no AS TEXT) = f.to_trace_no
              {$slocJoinG}
              {$slocJoinH}
             WHERE a.status IN (1, 2, 3, 4)
               AND SUBSTRING(CAST(a.adjust_no AS TEXT), 1, 1) = '{$substrFilter}'
               AND ({$plantFilter['sql']})
            GROUP BY a.id_adjust_head, a.entry_date, a.adjust_no, a.id_material, a.id_sloc,
                     a.before_adjust, a.after_adjust, a.created_by, a.created_at, a.status,
                     a.id_balance_head, b.code, b.description,
                     c.trace_no, c.qty, g.description,
                     f.id_matdoc, f.material_document, f.id_trace_head
                     {$whxGroupBy}
            ORDER BY a.entry_date DESC, a.id_adjust_head DESC
        ";

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) AS total FROM ({$sql}) AS count_query";
        $paginatedSql = $sql . " LIMIT ? OFFSET ?";

        $bindings = array_merge($plantFilter['bindings'], [$perPage, $offset]);

        $results = DB::connection($this->connection)->select($paginatedSql, $bindings);
        $countResult = DB::connection($this->connection)->select($countSql, $plantFilter['bindings']);
        $total = (int) ($countResult[0]->total ?? 0);

        return [
            'data' => $results,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / max($perPage, 1)),
        ];
    }

    public function getSupplierList(array $data, ?int $userId = null): array
    {
        $mode = $data['mode'] ?? 'ADD';

        if ($mode == 'ADD') {
            return DB::connection($this->connection)->select(
                "SELECT {$this->dbNumberFormat('a.qty', 3)} AS qty, a.id_supplier, c.code AS material,
                        CONCAT(b.code, ' :: ', b.description) AS supplier,
                        a.id_balance_temp AS idTail, a.entry_no, ? AS mode, a.batch_sap
                   FROM t_balance_temporary a
                   LEFT JOIN m_supplier b ON a.id_supplier = b.id_supplier
                   LEFT JOIN m_material c ON a.id_material = c.id_material
                  WHERE a.entry_no = ?
                    AND a.status = 1",
                [$mode, $data['number'] ?? '']
            );
        } else {
            return DB::connection($this->connection)->select(
                "SELECT {$this->dbNumberFormat('a.qty', 3)} AS qty, a.id_supplier, d.code AS material,
                        CONCAT(b.code, ' :: ', b.description) AS supplier,
                        a.id_balance_tail AS idTail, c.trace_no AS entry_no, ? AS mode, a.batch_sap
                   FROM t_balance_detail a
                   LEFT JOIN m_supplier b ON a.id_supplier = b.id_supplier
                   LEFT JOIN t_balance_header c ON a.id_balance_head = c.id_balance_head
                   LEFT JOIN m_material d ON a.id_material = d.id_material
                  WHERE a.id_balance_head = ?
                    AND a.status = 1",
                [$mode, $data['id_balance_head'] ?? 0]
            );
        }
    }

    public function getTotalQtySupplier(array $data, ?int $userId = null): ?float
    {
        $mode = $data['mode'] ?? 'ADD';

        if ($mode == 'ADD') {
            $result = DB::connection($this->connection)->select(
                "SELECT {$this->dbNumberFormat('SUM(a.qty)', 3)} AS total FROM t_balance_temporary a
                  WHERE a.entry_no = ? AND a.status = 1",
                [$data['number'] ?? '']
            );
        } else {
            $result = DB::connection($this->connection)->select(
                "SELECT {$this->dbNumberFormat('SUM(a.qty)', 3)} AS total FROM t_balance_detail a
                  WHERE a.id_balance_head = ? AND a.status = 1",
                [$data['id_balance_head'] ?? 0]
            );
        }

        return $result[0]->total ?? 0;
    }

    public function getActiveSuppliers(string $search, ?int $userId = null): array
    {
        return DB::connection($this->connection)->select(
            "SELECT CONCAT(a.code, ' :: ', a.description) AS supplier, a.id_supplier
               FROM m_supplier a
              WHERE a.status = 1
                AND (a.code LIKE ? OR a.description LIKE ?)
              ORDER BY a.description ASC
              LIMIT 50",
            ["%{$search}%", "%{$search}%"]
        );
    }

    public function generateEntryNo(?string $entryDate, mixed $plantId): ?string
    {
        $resolvedCode = app(PlantContextServiceInterface::class)->resolvePlantId($plantId);

        $pp = str_pad(substr((string) ($resolvedCode ?? '01'), -2), 2, '0', STR_PAD_LEFT);
        if ($entryDate == null) {
            $result = DB::connection($this->connection)->select(
                "SELECT a.adj_number
                   FROM (SELECT CAST(CAST(a.adjust_no AS BIGINT) + 1 AS TEXT) AS adj_number
                           FROM t_adjustment_header a
                          WHERE SUBSTRING(CAST(a.adjust_no AS TEXT),1,7) = CONCAT('9', {$this->dbDateFormat($this->dbCurDate(), '%y%m%d')})
                            AND a.status IN (1,2,3,4)
                          ORDER BY a.id_adjust_head DESC
                          LIMIT 1) a
                  UNION ALL
                   SELECT CONCAT('9', {$this->dbDateFormat($this->dbCurDate(), '%y%m%d')}, '000', ?, '01') AS adj_number
                  LIMIT 1",
                [$pp]
            );
        } else {
            $dateStr = Carbon::parse($entryDate)->format('Y-m-d');
            $formatted = Carbon::parse($entryDate)->format('ymd');
            $result = DB::connection($this->connection)->select(
                "SELECT a.adj_number
                   FROM (SELECT CAST(CAST(a.adjust_no AS BIGINT) + 1 AS TEXT) AS adj_number
                           FROM t_adjustment_header a
                          WHERE SUBSTRING(CAST(a.adjust_no AS TEXT), 1, 7) = CONCAT('9', '{$formatted}')
                            AND a.status IN (1,2,3,4)
                          ORDER BY a.id_adjust_head DESC
                          LIMIT 1) a
                  UNION ALL
                   SELECT CONCAT('9', '{$formatted}', '000', CAST(? AS TEXT), '01') AS adj_number
                  LIMIT 1",
                [$pp]
            );
        }

        return $result[0]->adj_number ?? null;
    }

    public function getAdjustmentHeader(int $headerId): ?object
    {
        return DB::connection($this->connection)->selectOne(
            'SELECT * FROM t_adjustment_header WHERE id_adjust_head = ? AND status IN (1, 2, 3, 4)',
            [$headerId]
        );
    }

    public function createAdjustmentHeader(string $user, array $data, mixed $plantId): array
    {
        $resolvedCode = app(PlantContextServiceInterface::class)->resolvePlantId($plantId);
        $idSlocVal = !empty($data['id_sloc']) ? (is_array($data['id_sloc']) ? $data['id_sloc'] : [$data['id_sloc']]) : [$data['tf_number'] ?? null];
        $idSlocJson = json_encode(array_values(array_filter($idSlocVal)));

        $id = DB::connection($this->connection)->table('t_adjustment_header')->insertGetId([
            'entry_date' => $data['entry_date'],
            'adjust_no' => $data['adjust_no'],
            'id_material' => $data['id_material'],
            'id_sloc' => $idSlocJson,
            'id_balance_head' => $data['id_balance_head'] ?? null,
            'adjustment_type' => $data['adjustment_type'] ?? 'physical',
            'before_adjust' => $data['before_adjust'] ?? 0,
            'after_adjust' => $data['after_adjust'] ?? 0,
            'reason' => $data['reason'] ?? '',
            'id_plant' => $resolvedCode,
            'status' => 1,
            'created_by' => $user,
        ], 'id_adjust_head');

        return ['response' => 1, 'id' => $id];
    }

    public function createAdjustmentDetail(string $user, int $headerId, array $data): array
    {
        $id = DB::connection($this->connection)->table('t_adjustment_detail')->insertGetId([
            'id_adjust_head' => $headerId,
            'id_supplier' => $data['id_supplier'],
            'id_material' => $data['id_material'],
            'id_sloc' => $data['tf_number'],
            'batch_sap' => $data['batch_sap'] ?? '',
            'before_adjust' => $data['before_adjust'] ?? 0,
            'after_adjust' => $data['after_adjust'] ?? 0,
            'status' => 1,
            'created_by' => $user,
        ], 'id_adjust_tail');

        return ['response' => 1, 'id' => $id];
    }

    public function approveAdjustment(int $headerId, int $status, string $user): array
    {
        DB::connection($this->connection)->table('t_adjustment_header')
            ->where('id_adjust_head', $headerId)
            ->update([
                'status' => $status,
                'approved_by' => $user,
                'approved_at' => now(),
                'updated_by' => $user,
            ]);

        return ['response' => 1];
    }

    public function executeAdjustment(int $headerId): array
    {
        $header = $this->getAdjustmentHeader($headerId);
        if (!$header) {
            return ['response' => 0, 'message' => 'Adjustment not found'];
        }

        DB::connection($this->connection)->table('t_balance_header')
            ->where('id_balance_head', $header->id_balance_head)
            ->update([
                'qty' => $header->after_adjust,
                'updated_by' => $header->approved_by ?? 'system',
            ]);

        DB::connection($this->connection)->table('t_balance_detail')
            ->where('id_balance_head', $header->id_balance_head)
            ->where('status', 1)
            ->update([
                'qty' => DB::raw('after_adjust'),
                'updated_by' => $header->approved_by ?? 'system',
            ]);

        DB::connection($this->connection)->table('t_adjustment_header')
            ->where('id_adjust_head', $headerId)
            ->update(['status' => 4]);

        return ['response' => 1];
    }

    public function cancelAdjustment(int $headerId, string $reason, string $user): array
    {
        DB::connection($this->connection)->table('t_adjustment_header')
            ->where('id_adjust_head', $headerId)
            ->update([
                'status' => 5,
                'reason' => $reason,
                'updated_by' => $user,
            ]);

        return ['response' => 1];
    }

    public function getAdjustmentDetail(int $headerId): ?array
    {
        $header = $this->getAdjustmentHeader($headerId);
        if (!$header) {
            return null;
        }

        $details = DB::connection($this->connection)->select(
            "SELECT d.*, CONCAT(s.code, ' :: ', s.description) AS supplier
               FROM t_adjustment_detail d
               LEFT JOIN m_supplier s ON d.id_supplier = s.id_supplier
              WHERE d.id_adjust_head = ?
               ORDER BY d.id_adjust_detail ASC",
            [$headerId]
        );

        return [
            'header' => (array) $header,
            'details' => $details,
        ];
    }

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  Lookups
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â

    public function getActiveMaterials(): array
    {
        return DB::connection($this->connection)->select(
            'SELECT a.id_material,
                    CONCAT(UPPER(a.description), \' (\', a.code, \' / \', a.type,
                           \' / Feed: \', a.qtf_feed, \' / Rundown: \', a.qtf_rundown, \')\') AS material
               FROM m_material a
              WHERE a.status = 1
                AND a.type <> \'FG\'
              ORDER BY a.description ASC'
        );
    }

    public function getActiveMaterialWhx(): array
    {
        return DB::connection($this->connection)->select(
            'SELECT a.id_materialpck,
                    CONCAT(UPPER(a.description), \' (\', a.code, \')\') AS material
               FROM m_material_pck a
              WHERE a.status = 1
              ORDER BY a.description ASC'
        );
    }

    public function getActiveTanks(mixed $plantId): array
    {
        $resolved = app(PlantContextServiceInterface::class)->resolvePlantId($plantId);

        $query = DB::connection($this->connection)
            ->table('m_sloc')
            ->where('status', 1)
            ->selectRaw("MIN(id_sloc) AS tf_number, COALESCE(MAX(NULLIF(code_3,'')), '') AS code_3, COALESCE(MIN(NULLIF(description,'')), MIN(code_3)) AS tank, id_plant")
            ->groupByRaw("COALESCE(NULLIF(code_3,''), description), id_plant")
            ->orderByRaw("COALESCE(MIN(NULLIF(description,'')), MIN(code_3)) ASC");

        if ($resolved !== null) {
            $query->where('id_plant', $resolved);
        }

        return $query->get()->all();
    }

    public function getActiveSpecificTanks(int $sloc): array
    {
        $tank = DB::connection($this->connection)->select(
            'SELECT code_3, id_plant, description FROM m_sloc WHERE id_sloc = ?',
            [$sloc]
        );

        if (empty($tank)) {
            return [];
        }

        $code3   = $tank[0]->code_3 ?? '';
        $plantId = $tank[0]->id_plant ?? '';
        $desc    = $tank[0]->description ?? '';

        if (!empty($code3)) {
            $rows = DB::connection($this->connection)->select(
                'SELECT a.id_sloc AS id_sloc_tail, a.tf_number, a.description, a.code_3
                   FROM m_sloc a
                  WHERE a.status = 1
                    AND a.code_3 = ?
                    AND a.id_plant = ?
                  ORDER BY a.id_sloc ASC',
                [$code3, $plantId]
            );
        } else {
            $rows = DB::connection($this->connection)->select(
                'SELECT a.id_sloc AS id_sloc_tail, a.tf_number, a.description, a.code_3
                   FROM m_sloc a
                  WHERE a.status = 1
                    AND a.description = ?
                    AND a.id_plant = ?
                  ORDER BY a.id_sloc ASC',
                [$desc, $plantId]
            );
        }

        foreach ($rows as $item) {
            $tfNumber = $item->tf_number ?? '';
            $item->tankName = $tfNumber !== ''
                ? ($item->description . ' (' . $tfNumber . ')')
                : ($item->description . ' [' . $item->id_sloc_tail . ']');
        }

        return $rows;
    }

    public function getActiveWhx(): array
    {
        return DB::connection($this->connection)->select(
            'SELECT a.id_warehouse AS tf_number,
                    CONCAT(a.id_batch, \' - \', a.description) AS tank
               FROM m_warehouse a
              WHERE a.status = 1
              ORDER BY a.id_batch, a.description ASC'
        );
    }

    // ===================================================================================================================
    //  Supplier / batch lookups
    // ===================================================================================================================

    public function getLockStatus(string $entryDate): array
    {
        $dt = new \DateTime($entryDate);
        $year = $dt->format('Y');
        $month = $dt->format('m');

        $result = DB::connection($this->connection)->select(
            'SELECT lock_status
               FROM t_report_pspa_head
              WHERE status = 1
                AND EXTRACT(YEAR FROM period) = ?
                AND EXTRACT(MONTH FROM period) = ?
              UNION ALL
              SELECT \'0\' AS lock_status',
            [$year, $month]
        );

        $locked = ($result[0]->lock_status ?? '0') === '1';
        return ['response' => $locked ? 99 : 0];
    }

    public function getSupplierByFilter(int $idMaterial, int $idSloc): array
    {
        return DB::connection($this->connection)->select(
            "SELECT CONCAT(a.code, ' :: ', a.description) AS supplier, a.id_supplier, SUM(b.qty) AS total_qty
               FROM t_balance_detail b
               LEFT JOIN m_supplier a ON a.id_supplier = b.id_supplier
              WHERE b.id_material = ?
                AND b.id_sloc = ?
                AND b.qty > 0
                AND a.status = 1
              GROUP BY a.id_supplier, a.description, a.code
              ORDER BY a.description ASC",
            [$idMaterial, $idSloc]
        );
    }

    public function getBatchBySupplier(int $idMaterial, int $idSloc, int $idSupplier): array
    {
        return DB::connection($this->connection)->select(
            'SELECT b.batch_sap, SUM(b.qty) AS qty, MIN(b.created_at) AS first_created
               FROM t_balance_detail b
              WHERE b.id_material = ?
                AND b.id_sloc = ?
                AND b.id_supplier = ?
                AND b.qty > 0
                AND b.status = 1
              GROUP BY b.batch_sap
              ORDER BY first_created ASC',
            [$idMaterial, $idSloc, $idSupplier]
        );
    }

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  Store adjustment  (the main balance adjustment)
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â

    public function storeAdjustment(string $user, array $data, mixed $plantId): array
    {
        $idPlant = app(PlantContextServiceInterface::class)->resolvePlantId($plantId);
        $lastTwoDigitIdPlant = substr($idPlant, 2, 2);
        $idMaterial = (int) $data['id_material'];
        $adjustQty = (float) $data['qty'];
        $entryDate = $data['entry_date'];
        $idSloc = (int) $data['tf_number'];

        /* Ã¢"â‚¬Ã¢"â‚¬ Generate adjustment number Ã¢"â‚¬Ã¢"â‚¬ */
        $batchMoveType = 9;
        $batchEntryDate = substr(str_replace('-', '', $entryDate), 2);

        $matl = DB::connection($this->connection)->selectOne(
            'SELECT id_rundown FROM m_material WHERE id_material = ?', [$idMaterial]
        );
        if (!$matl) return [['response' => 4]];
        $batchId = $matl->id_rundown;
        $batchMapping = $batchMoveType . $batchEntryDate . $batchId . $lastTwoDigitIdPlant;

        $datBatch = DB::connection($this->connection)->select(
            'SELECT a.adjust_no, COUNT(a.adjust_no) AS flag
               FROM (SELECT a.adjust_no
                       FROM t_adjustment_header a
                       WHERE SUBSTRING(CAST(a.adjust_no AS TEXT),1,12) = ?
                      ORDER BY a.adjust_no DESC
                      LIMIT 1) a',
            [$batchMapping]
        );
        if ($datBatch[0]->flag > 0) {
            $batchNo = $datBatch[0]->adjust_no + 1;
        } else {
            $batchNo = $batchMapping . '01';
        }

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Get existing balance Ã¢â€â‚¬Ã¢â€â‚¬ */
        $datBal = DB::connection($this->connection)->select(
            'SELECT a.qty, a.trace_no, a.id_balance_head, a.in_qty, a.out_qty,
                    b.from_trace_no, a.entry_date, b.id_trace_head
               FROM m_material b
               LEFT JOIN (
                   SELECT c.code, a.qty, a.trace_no, a.id_balance_head, a.in_qty, a.out_qty,
                          b.from_trace_no, a.entry_date, b.id_trace_head
                     FROM m_material c
                     LEFT JOIN t_balance_header a ON c.id_material = a.id_material
                     LEFT JOIN t_trace_header b ON a.id_balance_head = b.id_balance_head AND b.status = 1 AND b.out_qty = 0
                    WHERE a.status = 1 AND a.id_sloc = ?
               ) a ON b.code = a.code
              WHERE b.id_material = ? AND b.status = 1
              ORDER BY a.entry_date DESC, a.id_balance_head DESC
              LIMIT 1',
            [$idSloc, $idMaterial]
        );

        if (count($datBal) === 0) return [['response' => 4]];

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Get total balance Ã¢â€â‚¬Ã¢â€â‚¬ */
        $datTotalBal = DB::connection($this->connection)->select(
            'SELECT SUM(a.qty) AS total_qty
               FROM m_material b
               LEFT JOIN (
                   SELECT a.code, b.qty
                     FROM m_material a
                     LEFT JOIN t_balance_header b ON a.id_material = b.id_material AND b.status = 1
                    WHERE a.status = 1 AND b.id_sloc = ?
               ) a ON b.code = a.code
              WHERE b.id_material = ? AND b.status = 1
              LIMIT 1',
            [$idSloc, $idMaterial]
        );

        $totalBal = (float) ($datTotalBal[0]->total_qty ?? 0);
        if ($totalBal == 0) return [['response' => 5]];

        $traceNo = $datBal[0]->trace_no;
        $idHead = (int) $datBal[0]->id_balance_head;
        $inBalQty = (float) ($datBal[0]->in_qty ?? 0);
        $headBalQty = (float) ($datBal[0]->qty ?? 0);
        $idTraceHead = (int) ($datBal[0]->id_trace_head ?? 0);

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Calculate diff Ã¢â€â‚¬Ã¢â€â‚¬ */
        $diffQty = $totalBal - $adjustQty;

        if ($diffQty > 0) {
            $diffQtyLastTrans = $headBalQty - $diffQty;
            if ($diffQtyLastTrans < 0) return [['response' => 9]];
            $beforeAdjust = $headBalQty;
            $inQty = $inBalQty - $diffQty;
            $afterAdjust = $beforeAdjust - $diffQty;
            $adjustType = 'OUT';
        } elseif ($diffQty < 0) {
            $beforeAdjust = $headBalQty;
            $inQty = $inBalQty + (-1 * $diffQty);
            $afterAdjust = $beforeAdjust + (-1 * $diffQty);
            $adjustType = 'IN';
        } else {
            return [['response' => 10]];
        }

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Check previous header transaction Ã¢â€â‚¬Ã¢â€â‚¬ */
        $prevTraceBalance = DB::connection($this->connection)->select(
            'SELECT a.qty, a.in_qty, a.out_qty, bb.id_trace_head,
                    bb.in_qty AS traceInQty, bb.out_qty AS traceOutQty,
                    a.id_balance_head
               FROM t_trace_header b
               LEFT JOIN t_trace_header bb ON b.from_trace_no = bb.to_trace_no
               LEFT JOIN t_balance_header a ON a.id_balance_head = bb.id_balance_head AND b.status = 1
              WHERE b.to_trace_no = ?
                AND a.status = 1
                AND b.id_sloc = ?',
            [$traceNo, $idSloc]
        );

        if (empty($prevTraceBalance)) return [['response' => 11]];

        $prevBalIdHead = (int) $prevTraceBalance[0]->id_balance_head;
        $prevBalQty = (float) ($prevTraceBalance[0]->qty ?? 0);
        $prevBalInQty = (float) ($prevTraceBalance[0]->in_qty ?? 0);
        $prevBalOutQty = (float) ($prevTraceBalance[0]->out_qty ?? 0);
        $prevTraceIdHead = (int) ($prevTraceBalance[0]->id_trace_head ?? 0);
        $prevTraceInQty = (float) ($prevTraceBalance[0]->traceInQty ?? 0);
        $prevTraceOutQty = (float) ($prevTraceBalance[0]->traceOutQty ?? 0);

        if ($adjustType === 'IN') {
            $diffPrevBalanceQty = $prevBalQty - $prevTraceOutQty + (-1 * $diffQty);
            if ($diffPrevBalanceQty < 0) return [['response' => 9]];
            $newPrevTraceOutQty = $prevTraceOutQty + (-1 * $diffQty);
        } else {
            $newPrevTraceOutQty = $prevTraceOutQty - $diffQty;
        }

        $newPrevBalOutQty = $prevBalOutQty - $prevTraceOutQty + $newPrevTraceOutQty;
        $newPrevBalQty = $prevBalQty + $prevTraceOutQty - $newPrevTraceOutQty;
        if ($newPrevBalQty < 0) return [['response' => 9]];

        $idSlocVal = !empty($data['id_sloc']) ? (is_array($data['id_sloc']) ? $data['id_sloc'] : [$data['id_sloc']]) : [$data['tf_number'] ?? $idSloc];
        $idSlocJson = json_encode(array_values(array_filter($idSlocVal)));

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Insert adjustment header Ã¢â€â‚¬Ã¢â€â‚¬ */
        $idAdjustHead = DB::connection($this->connection)->table('t_adjustment_header')->insertGetId([
            'entry_date' => $entryDate,
            'adjust_no' => $batchNo,
            'id_balance_head' => $idHead,
            'id_material' => $idMaterial,
            'id_sloc' => $idSlocJson,
            'id_plant' => $idPlant,
            'in_qty' => $inQty,
            'out_qty' => 0,
            'before_adjust' => $beforeAdjust,
            'after_adjust' => $afterAdjust,
            'created_by' => $user,
        ], 'id_adjust_head');

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Update balance header Ã¢â€â‚¬Ã¢â€â‚¬ */
        DB::connection($this->connection)->update(
            'UPDATE t_balance_header SET qty = ?, in_qty = ?, updated_by = ? WHERE id_balance_head = ?',
            [$afterAdjust, $inQty, $user, $idHead]
        );

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Update trace header Ã¢â€â‚¬Ã¢â€â‚¬ */
        DB::connection($this->connection)->update(
            'UPDATE t_trace_header SET in_qty = ?, updated_by = ? WHERE id_trace_head = ?',
            [$inQty, $user, $idTraceHead]
        );

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Update previous balance header Ã¢â€â‚¬Ã¢â€â‚¬ */
        DB::connection($this->connection)->update(
            'UPDATE t_balance_header SET qty = ?, out_qty = ?, updated_by = ? WHERE id_balance_head = ?',
            [$newPrevBalQty, $newPrevBalOutQty, $user, $prevBalIdHead]
        );

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Update previous trace header Ã¢â€â‚¬Ã¢â€â‚¬ */
        DB::connection($this->connection)->update(
            'UPDATE t_trace_header SET out_qty = ?, updated_by = ? WHERE id_trace_head = ?',
            [$newPrevTraceOutQty, $user, $prevTraceIdHead]
        );

        /* Ã¢â€â‚¬Ã¢â€â‚¬ HEADER LOGGING Ã¢â€â‚¬Ã¢â€â‚¬ */
        DB::connection($this->connection)->insert(
            'INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)',
            ['T_BALANCE_HEAD', 'ADJUST BALANCE',
             'IDHEAD: ' . $idHead . ' | DATE: ' . $entryDate .
             ' / MATERIAL: ' . $idMaterial . ' / QTY: ' . $beforeAdjust . ' >>> ' . $afterAdjust .
             ' / IN_QTY: ' . $inBalQty . ' >>> ' . $inQty . ' | Status: 1', $user]
        );

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Get supplier detail Ã¢â€â‚¬Ã¢â€â‚¬ */
        $datDet = DB::connection($this->connection)->select(
            'SELECT a.id_supplier, a.batch_sap, a.qty, a.in_qty, a.out_qty, a.init_qty,
                    a.id_balance_tail, b.id_trace_tail
               FROM t_trace_detail b
               LEFT JOIN t_balance_detail a ON a.id_balance_tail = b.id_balance_tail
              WHERE b.id_trace_head = ?
                AND a.status = 1
                AND b.status = 1
                AND b.out_qty = 0
                AND a.qty > 0.0001',
            [$idTraceHead]
        );

        $idSupplier = [];
        $batchSap = [];
        $balQty = [];
        $balInitQty = [];
        $balInQty = [];
        $balOutQty = [];
        $idTail = [];
        $idTraceTail = [];

        foreach ($datDet as $row) {
            if ((float) ($row->init_qty ?? 0) > 0.0009) {
                $idSupplier[] = $row->id_supplier;
                $batchSap[] = $row->batch_sap;
                $balQty[] = (float) $row->qty;
                $balInitQty[] = (float) $row->init_qty;
                $balInQty[] = (float) $row->in_qty;
                $balOutQty[] = (float) $row->out_qty;
                $idTail[] = (int) $row->id_balance_tail;
                $idTraceTail[] = (int) ($row->id_trace_tail ?? 0);
            }
        }

        $lenDet = count($balQty);

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Distribute diff across suppliers (proportional) Ã¢â€â‚¬Ã¢â€â‚¬ */
        if ($lenDet > 0) {
            $dataPerHead = ['det' => []];
            foreach ($balQty as $bal) {
                $dataPerHead['det'][] = ['qty' => $bal];
            }
            $this->adjustAmtToTotal($dataPerHead, $diffQty);
            $diffDetQty = array_map(fn($r) => $r['qty'], $dataPerHead['det']);
        } else {
            $diffDetQty = [];
        }

        for ($i = 0; $i < $lenDet; $i++) {
            $d = $diffDetQty[$i] ?? 0;

            if ($d >= 0) {
                $beforeAdjustDet = $balQty[$i];
                $inDetQty = $balInQty[$i] - $d;
                $afterAdjustDet = $beforeAdjustDet - $d;
            } else {
                $beforeAdjustDet = $balQty[$i];
                $inDetQty = $balInQty[$i] + (-1 * $d);
                $afterAdjustDet = $beforeAdjustDet + (-1 * $d);
            }

            /* Insert adjustment detail */
            DB::connection($this->connection)->table('t_adjustment_detail')->insertGetId([
                'id_adjust_head' => $idAdjustHead,
                'id_balance_tail' => $idTail[$i],
                'id_supplier' => $idSupplier[$i],
                'id_material' => $idMaterial,
                'batch_sap' => $batchSap[$i],
                'in_qty' => $inDetQty,
                'out_qty' => 0,
                'before_adjust' => $beforeAdjustDet,
                'after_adjust' => $afterAdjustDet,
                'id_sloc' => $idSloc,
                'id_plant' => $idPlant,
                'created_by' => $user,
            ], 'id_adjust_tail');

            /* Update balance detail */
            DB::connection($this->connection)->update(
                'UPDATE t_balance_detail SET qty = ?, in_qty = ?, updated_by = ? WHERE id_balance_tail = ?',
                [$afterAdjustDet, $inDetQty, $user, $idTail[$i]]
            );

            /* Update trace detail */
            DB::connection($this->connection)->update(
                'UPDATE t_trace_detail SET in_qty = ?, updated_by = ? WHERE id_balance_tail = ?',
                [$inDetQty, $user, $idTail[$i]]
            );

            /* Detail logging */
            DB::connection($this->connection)->insert(
                'INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)',
                ['T_BALANCE_TAIL', 'ADJUST BALANCE',
                 ' IDTAIL: ' . $idTail[$i] . ' / SUPPLIER: ' . $idSupplier[$i] .
                 ' / MATERIAL: ' . $idMaterial . ' / QTY: ' . $balQty[$i] . ' >>> ' . $afterAdjustDet .
                 ' / IN_QTY: ' . $balInQty[$i] . ' >>> ' . $inDetQty . ' | Status: 1', $user]
            );
        }

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Get PREVIOUS supplier detail Ã¢â€â‚¬Ã¢â€â‚¬ */
        $datDet2 = DB::connection($this->connection)->select(
            'SELECT a.id_supplier, a.batch_sap, a.qty, a.in_qty, a.out_qty, a.init_qty,
                    a.id_balance_tail, b.id_trace_tail,
                    b.in_qty AS traceInQty, b.out_qty AS traceOutQty
               FROM t_trace_detail b
               LEFT JOIN t_balance_detail a ON a.id_balance_tail = b.id_balance_tail AND b.status = 1
              WHERE b.id_trace_head = ?
                AND a.status = 1',
            [$prevTraceIdHead]
        );

        $idSupplier2 = [];
        $batchSap2 = [];
        $balQty2 = [];
        $balInQty2 = [];
        $balOutQty2 = [];
        $idTail2 = [];
        $traceOutQty2 = [];

        foreach ($datDet2 as $row) {
            if ((float) ($row->init_qty ?? 0) > 0.0009) {
                $idSupplier2[] = $row->id_supplier;
                $batchSap2[] = $row->batch_sap;
                $balQty2[] = (float) $row->qty;
                $balInQty2[] = (float) $row->in_qty;
                $balOutQty2[] = (float) $row->out_qty;
                $idTail2[] = (int) $row->id_balance_tail;
                $traceOutQty2[] = (float) ($row->traceOutQty ?? 0);
            }
        }

        $lenDet2 = count($balQty2);

        if ($lenDet2 > 0) {
            $dataPerHead2 = ['det' => []];
            foreach ($balQty2 as $bal) {
                $dataPerHead2['det'][] = ['qty' => $bal];
            }
            $this->adjustAmtToTotal($dataPerHead2, $diffQty);
            $diffDetQty2 = array_map(fn($r) => $r['qty'], $dataPerHead2['det']);
        } else {
            $diffDetQty2 = [];
        }

        for ($i = 0; $i < $lenDet2; $i++) {
            $d = $diffDetQty2[$i] ?? 0;

            if ($d > 0) {
                $newPrevTraceOutQtyI = $traceOutQty2[$i] - $d;
            } elseif ($d < 0) {
                $newPrevTraceOutQtyI = $traceOutQty2[$i] + (-1 * $d);
            } else {
                $newPrevTraceOutQtyI = $traceOutQty2[$i];
            }

            $newPrevDetBalOutQty = $balOutQty2[$i] - $traceOutQty2[$i] + $newPrevTraceOutQtyI;
            $newPrevDetBalQty = $balQty2[$i] + $traceOutQty2[$i] - $newPrevTraceOutQtyI;

            DB::connection($this->connection)->update(
                'UPDATE t_balance_detail SET qty = ?, out_qty = ?, updated_by = ? WHERE id_balance_tail = ?',
                [$newPrevDetBalQty, $newPrevDetBalOutQty, $user, $idTail2[$i]]
            );
            DB::connection($this->connection)->update(
                'UPDATE t_trace_detail SET out_qty = ?, updated_by = ? WHERE id_trace_tail = ?',
                [$newPrevTraceOutQtyI, $user, $idTail2[$i]]
            );
        }

        return [['response' => 1]];
    }

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  Destroy (reverse draft) adjustment
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â

    public function destroyAdjustment(int $id, string $user): array
    {
        /* Ã¢â€â‚¬Ã¢â€â‚¬ Check lock period Ã¢â€â‚¬Ã¢â€â‚¬ */
        $entryDateResult = DB::connection($this->connection)->select(
            'SELECT entry_date FROM t_adjustment_header WHERE id_adjust_head = ? AND status = 1',
            [$id]
        );
        if (empty($entryDateResult)) return [['response' => 2]];
        $currEntryDate = $entryDateResult[0]->entry_date;
        $lockStatus = $this->getLockStatus($currEntryDate);
        if (($lockStatus['response'] ?? 0) === 99) return [['response' => 99]];

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Get header data Ã¢â€â‚¬Ã¢â€â‚¬ */
        $dat = DB::connection($this->connection)->select(
            'SELECT a.before_adjust, a.id_balance_head, a.in_qty, a.out_qty, a.adjust_no, b.id_trace_head
               FROM t_adjustment_header a
               LEFT JOIN t_trace_header b ON b.to_trace_no = a.adjust_no AND b.status = 1
              WHERE a.id_adjust_head = ? AND a.status = 1',
            [$id]
        );
        if (empty($dat)) return [['response' => 2]];
        if ($dat[0]->id_trace_head === null) return [['response' => 3]];

        $idHead = (int) $dat[0]->id_balance_head;
        $beforeAdjust = (float) $dat[0]->before_adjust;
        $adjustInQty = (float) ($dat[0]->in_qty ?? 0);
        $adjustOutQty = (float) ($dat[0]->out_qty ?? 0);
        $traceNo = $dat[0]->adjust_no;
        $idTraceHead = (int) $dat[0]->id_trace_head;

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Get balance header data Ã¢â€â‚¬Ã¢â€â‚¬ */
        $datHead = DB::connection($this->connection)->select(
            'SELECT qty, in_qty, out_qty FROM t_balance_header WHERE id_balance_head = ? AND status = 1',
            [$idHead]
        );
        if (empty($datHead)) return [['response' => 2]];

        $newBalQty = $beforeAdjust;
        $newBalInQty = (float) $datHead[0]->in_qty - $adjustInQty;
        $newBalOutQty = (float) $datHead[0]->out_qty - $adjustOutQty;

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Storage init check Ã¢â€â‚¬Ã¢â€â‚¬ */
        $flagStoreInitDat = DB::connection($this->connection)->select(
            'SELECT COALESCE(a.from_trace_no, 1) AS init
               FROM t_trace_header a WHERE a.id_trace_head = ? AND a.status = 1',
            [$idTraceHead]
        );
        $flagStoreInit = $flagStoreInitDat[0]->init ?? 1;

        if ($flagStoreInit == 1) {
            DB::connection($this->connection)->update(
                'UPDATE t_balance_header SET qty = ?, in_qty = ?, out_qty = ?, status = 0
                  WHERE id_balance_head = ? AND status = 1',
                [$newBalQty, $newBalInQty, $newBalOutQty, $idHead]
            );
        } else {
            DB::connection($this->connection)->update(
                'UPDATE t_balance_header SET qty = ?, in_qty = ?, out_qty = ?
                  WHERE id_balance_head = ? AND status = 1',
                [$newBalQty, $newBalInQty, $newBalOutQty, $idHead]
            );
        }

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Deactivate trace header Ã¢â€â‚¬Ã¢â€â‚¬ */
        DB::connection($this->connection)->update(
            'UPDATE t_trace_header SET status = 0, updated_by = ?
              WHERE to_trace_no = ? AND status = 1',
            [$user, $traceNo]
        );

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Deactivate adjustment header Ã¢â€â‚¬Ã¢â€â‚¬ */
        DB::connection($this->connection)->update(
            'UPDATE t_adjustment_header SET status = 0, updated_by = ?
              WHERE id_adjust_head = ? AND status = 1',
            [$user, $id]
        );

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Get adjustment detail data Ã¢â€â‚¬Ã¢â€â‚¬ */
        $datAdjustTail = DB::connection($this->connection)->select(
            'SELECT before_adjust, id_balance_tail, in_qty, out_qty, id_adjust_tail
               FROM t_adjustment_detail
              WHERE id_adjust_head = ? AND status = 1',
            [$id]
        );

        foreach ($datAdjustTail as $d) {
            $idTail = (int) $d->id_balance_tail;
            $idAdjustTail = (int) $d->id_adjust_tail;
            $beforeAdjustTail = (float) $d->before_adjust;
            $adjustInQtyTail = (float) ($d->in_qty ?? 0);
            $adjustOutQtyTail = (float) ($d->out_qty ?? 0);

            /* Ã¢â€â‚¬Ã¢â€â‚¬ Get balance detail data Ã¢â€â‚¬Ã¢â€â‚¬ */
            $datBalTail = DB::connection($this->connection)->select(
                'SELECT in_qty, out_qty FROM t_balance_detail WHERE id_balance_tail = ? AND status = 1',
                [$idTail]
            );

            if (!empty($datBalTail)) {
                $balInQty = (float) $datBalTail[0]->in_qty;
                $balOutQty = (float) $datBalTail[0]->out_qty;

                DB::connection($this->connection)->update(
                    'UPDATE t_adjustment_detail SET status = 0, updated_by = ?
                      WHERE id_adjust_tail = ? AND status = 1',
                    [$user, $idAdjustTail]
                );

                if ($flagStoreInit == 1) {
                    DB::connection($this->connection)->update(
                        'UPDATE t_balance_detail SET qty = ?, in_qty = ?, out_qty = ?, status = 0
                          WHERE id_balance_tail = ?',
                        [$beforeAdjustTail, $balInQty - $adjustInQtyTail, $balOutQty - $adjustOutQtyTail, $idTail]
                    );
                } else {
                    DB::connection($this->connection)->update(
                        'UPDATE t_balance_detail SET qty = ?, in_qty = ?, out_qty = ?
                          WHERE id_balance_tail = ?',
                        [$beforeAdjustTail, $balInQty - $adjustInQtyTail, $balOutQty - $adjustOutQtyTail, $idTail]
                    );
                }
            }
        }

        DB::connection($this->connection)->update(
            'UPDATE t_trace_detail SET status = 0, updated_by = ?
              WHERE id_trace_head = ? AND status = 1',
            [$user, $idTraceHead]
        );

        return [['response' => 1]];
    }

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  Add / edit entry supplier
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â

    public function addEntrySupplier(string $user, array $data, mixed $plantId): array
    {
        $mode = $data['mode'] ?? 'ADD';
        $adjNumber = $data['adjNumber'] ?? '';
        $idSupplier = (int) ($data['id_supplier'] ?? 0);
        $qty = (float) str_replace(',', '', $data['qty'] ?? '0');
        $idHead = (int) ($data['id_head'] ?? 0);
        $idTail = (int) ($data['id_tail'] ?? 0);
        $batchSap = $data['batch_sap'] ?? '';
        $idMaterial = (int) ($data['id_material'] ?? 0);
        $idPlant = app(PlantContextServiceInterface::class)->resolvePlantId($plantId);

        if ($mode === 'ADD') {
            $ok = DB::connection($this->connection)->insert(
                'INSERT INTO t_balance_temporary (entry_no, id_supplier, id_material, qty, batch_sap, id_plant, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$adjNumber, $idSupplier, $idMaterial, $qty, $batchSap, $idPlant, $user]
            );
            return [['response' => $ok ? 1 : 0]];
        }

        /* UPDATE mode */
        $flag = DB::connection($this->connection)->select(
            'SELECT COUNT(a.id_balance_head) AS dat
               FROM t_balance_detail a WHERE a.id_balance_tail = ? AND a.status = 1',
            [$idTail]
        );

        if ((int) ($flag[0]->dat ?? 0) > 0) {
            $dat = DB::connection($this->connection)->select(
                'SELECT id_supplier, qty, id_material FROM t_balance_detail WHERE id_balance_tail = ?',
                [$idTail]
            );
            $idSupplierOld = $dat[0]->id_supplier;
            $qtyOld = (float) $dat[0]->qty;
            $idMaterialOld = $dat[0]->id_material;

            DB::connection($this->connection)->insert(
                'INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)',
                ['T_BALANCE_TAIL', 'UPDATE',
                 'IDHEAD: ' . $idHead . ' IDTAIL: ' . $idTail .
                 ' | ID_SUPPLIER: ' . $idSupplierOld . ' >>> ' . $idSupplier .
                 ' / QTY: ' . $qtyOld . ' >>> ' . $qty . ' ' . $idMaterialOld . ' >>> ' . $idMaterial .
                 ' | Status: 1', $user]
            );

            DB::connection($this->connection)->update(
                'UPDATE t_trace_detail SET id_supplier = ?, id_material = ?, in_qty = ?, batch_sap = ?, updated_by = ?
                  WHERE id_balance_tail = ?',
                [$idSupplier, $idMaterial, $qty, $batchSap, $user, $idTail]
            );
            $ok = DB::connection($this->connection)->update(
                'UPDATE t_balance_detail SET id_supplier = ?, id_material = ?, qty = ?, init_qty = ?, batch_sap = ?, updated_by = ?
                  WHERE id_balance_tail = ?',
                [$idSupplier, $idMaterial, $qty, $qty, $batchSap, $user, $idTail]
            );

            $this->syncBalanceHeaderFromDetails($idHead, $user);
            return [['response' => $ok ? 1 : 0]];
        }

        /* Supplier not in detail yet Ã¢â‚¬â€ check if exists by id_supplier */
        $flag2 = DB::connection($this->connection)->select(
            'SELECT COUNT(a.id_balance_head) AS dat
               FROM t_balance_detail a
              WHERE a.id_supplier = ? AND a.status = 1 AND a.id_balance_head = ?',
            [$idSupplier, $idHead]
        );

        if ((int) ($flag2[0]->dat ?? 0) > 0) {
            $dat2 = DB::connection($this->connection)->select(
                'SELECT id_supplier, qty, id_balance_tail, batch_sap, id_material
                   FROM t_balance_detail WHERE id_supplier = ? AND status = 1',
                [$idSupplier]
            );
            $idTail = (int) ($dat2[0]->id_balance_tail ?? 0);

            DB::connection($this->connection)->insert(
                'INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)',
                ['T_BALANCE_TAIL', 'UPDATE',
                 'IDHEAD: ' . $idHead . ' IDTAIL: ' . $idTail .
                 ' | ID_SUPPLIER: ' . $idSupplier . ' / QTY: ' . $qty .
                 ' / BATCH_SAP: ' . $batchSap . ' / ID_MATERIAL: ' . $idMaterial . ' | Status: 1', $user]
            );

            DB::connection($this->connection)->update(
                'UPDATE t_trace_detail SET id_material = ?, in_qty = ?, batch_sap = ?, updated_by = ?
                  WHERE id_balance_tail = ?',
                [$idMaterial, $qty, $batchSap, $user, $idTail]
            );
            $ok = DB::connection($this->connection)->update(
                'UPDATE t_balance_detail SET id_material = ?, qty = ?, init_qty = ?, batch_sap = ?, updated_by = ?
                  WHERE id_supplier = ? AND id_balance_head = ?',
                [$idMaterial, $qty, $qty, $batchSap, $user, $idSupplier, $idHead]
            );

            $this->syncBalanceHeaderFromDetails($idHead, $user);
            return [['response' => $ok ? 1 : 0]];
        }

        /* New supplier entry Ã¢â‚¬â€ INSERT */
        $idTailNew = DB::connection($this->connection)->table('t_balance_detail')->insertGetId([
            'id_balance_head' => $idHead,
            'id_supplier' => $idSupplier,
            'id_material' => $idMaterial,
            'qty' => $qty,
            'init_qty' => $qty,
            'batch_sap' => $batchSap,
            'id_plant' => $idPlant,
            'created_by' => $user,
        ], 'id_balance_tail');

        $traceHead = DB::connection($this->connection)->selectOne(
            'SELECT id_trace_head FROM t_trace_header WHERE id_balance_head = ?', [$idHead]
        );
        if ($traceHead) {
            DB::connection($this->connection)->table('t_trace_detail')->insert([
                'id_trace_head' => $traceHead->id_trace_head,
                'id_balance_tail' => $idTailNew,
                'id_supplier' => $idSupplier,
                'id_material' => $idMaterial,
                'in_qty' => $qty,
                'batch_sap' => $batchSap,
                'id_plant' => $idPlant,
                'created_by' => $user,
            ]);
        }

        DB::connection($this->connection)->insert(
            'INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)',
            ['T_BALANCE_TAIL', 'UPDATE',
             'IDHEAD: ' . $idHead . ' IDTAIL: ' . $idTailNew .
             ' | ID_SUPPLIER: ' . $idSupplier . ' / QTY: ' . $qty . ' / BATCH_SAP: ' . $batchSap .
             ' | Status: 1', $user]
        );

        $this->syncBalanceHeaderFromDetails($idHead, $user);
        return [['response' => 1]];
    }

    /**
     * Recalculate and sync balance header / trace header qty from details.
     */
    private function syncBalanceHeaderFromDetails(int $idHead, string $user): void
    {
        $dat = DB::connection($this->connection)->select(
            'SELECT SUM(a.init_qty) AS qty
               FROM t_balance_detail a WHERE a.id_balance_head = ? AND a.status = 1',
            [$idHead]
        );
        $newTotalQty = (float) ($dat[0]->qty ?? 0);

        DB::connection($this->connection)->update(
            'UPDATE t_balance_header SET init_qty = ?, qty = ?, updated_by = ? WHERE id_balance_head = ?',
            [$newTotalQty, $newTotalQty, $user, $idHead]
        );

        DB::connection($this->connection)->update(
            'UPDATE t_trace_header SET in_qty = ?, updated_by = ? WHERE id_balance_head = ?',
            [$newTotalQty, $user, $idHead]
        );
    }

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  Delete temp supplier
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â

    public function deleteSupplierTemp(int $id): array
    {
        DB::connection($this->connection)->delete(
            'DELETE FROM t_balance_temporary WHERE id_balance_temp = ?', [$id]
        );
        return [['response' => 1]];
    }

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  Adjustment init  (stock-in from zero)
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â

    public function adjustmentInit(string $user, array $data, mixed $plantId): array
    {
        $idPlant = app(PlantContextServiceInterface::class)->resolvePlantId($plantId);
        $lastTwoDigitIdPlant = substr($idPlant, 2, 2);
        $entryNo = $data['entry_no'] ?? '';
        $entryDate = $data['entry_date'] ?? '';
        $idSloc = (int) ($data['tf_number'] ?? 0);
        $qty = (float) str_replace(',', '', $data['qty'] ?? '0');
        $idMaterial = (int) ($data['id_material'] ?? 0);
        $materialDoc = $data['material_doc'] ?? null;
        $mode = $data['mode'] ?? 'ADD';
        $idSlocTail = $data['tankNo'] ?? [];
        $idSlocTailJson = is_array($idSlocTail) ? json_encode($idSlocTail) : (string) $idSlocTail;
        $idSlocVal = !empty($data['tankNo']) ? (is_array($data['tankNo']) ? $data['tankNo'] : [$data['tankNo']]) : [$data['tank'] ?? $idSloc];
        $idSlocJson = json_encode(array_values(array_filter($idSlocVal)));

        $suppliers = DB::connection($this->connection)->select(
            'SELECT id_supplier, qty AS qty_tail, batch_sap FROM t_balance_temporary WHERE entry_no = ?',
            [$entryNo]
        );
        if (count($suppliers) === 0) return [['response' => 6]];

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Generate new entry number Ã¢â€â‚¬Ã¢â€â‚¬ */
        $batchMoveType = substr($entryNo, 0, 1);
        $batchEntryDate = substr($entryNo, 1, 6);
        $batchSequence = substr($entryNo, -2);

        $matl = DB::connection($this->connection)->selectOne(
            'SELECT id_rundown, type FROM m_material WHERE id_material = ?', [$idMaterial]
        );
        $batchId = $matl->id_rundown ?? '00';
        $newEntryNo = $batchMoveType . $batchEntryDate . $batchId . $lastTwoDigitIdPlant . $batchSequence;

        if ($mode === 'ADD') {
            /* Ã¢â€â‚¬Ã¢â€â‚¬ Insert balance header Ã¢â€â‚¬Ã¢â€â‚¬ */
            $idHead = DB::connection($this->connection)->table('t_balance_header')->insertGetId([
                'trace_no' => $newEntryNo,
                'id_material' => $idMaterial,
                'id_sloc' => $idSlocJson,
                'id_sloc_tail' => $idSlocTailJson,
                'entry_date' => $entryDate,
                'qty' => $qty,
                'in_qty' => $qty,
                'init_qty' => $qty,
                'id_plant' => $idPlant,
                'created_by' => $user,
            ], 'id_balance_head');

            /* Ã¢â€â‚¬Ã¢â€â‚¬ Insert trace header Ã¢â€â‚¬Ã¢â€â‚¬ */
            $idTraceHead = DB::connection($this->connection)->table('t_trace_header')->insertGetId([
                'to_trace_no' => $newEntryNo,
                'id_balance_head' => $idHead,
                'id_material' => $idMaterial,
                'entry_date' => $entryDate,
                'id_sloc' => $idSlocJson,
                'id_sloc_tail' => $idSlocTailJson,
                'in_qty' => $qty,
                'id_plant' => $idPlant,
                'created_by' => $user,
            ], 'id_trace_head');

            /* Ã¢â€â‚¬Ã¢â€â‚¬ Insert adjustment header Ã¢â€â‚¬Ã¢â€â‚¬ */
            DB::connection($this->connection)->table('t_adjustment_header')->insertGetId([
                'entry_date' => $entryDate,
                'adjust_no' => $newEntryNo,
                'id_balance_head' => $idHead,
                'id_material' => $idMaterial,
                'id_sloc' => $idSlocJson,
                'in_qty' => $qty,
                'before_adjust' => 0,
                'after_adjust' => $qty,
                'id_plant' => $idPlant,
                'created_by' => $user,
            ], 'id_adjust_head');

            /* Ã¢â€â‚¬Ã¢â€â‚¬ Logging Ã¢â€â‚¬Ã¢â€â‚¬ */
            DB::connection($this->connection)->insert(
                'INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)',
                ['T_ADJUST_HEAD', 'ADD ADJUST',
                 'IDADJUSTHEAD: ' . $idHead . ' | IDHEAD: ' . $idHead .
                 ' | DATE: ' . $entryDate . ' / MATERIAL: ' . $idMaterial .
                 ' / IN_QTY: ' . $qty . ' / OUT_QTY: 0' .
                 ' / BEFORE_ADJUST: 0 / AFTER_ADJUST: ' . $qty . ' | Status: 1', $user]
            );

            /* Ã¢â€â‚¬Ã¢â€â‚¬ Process suppliers Ã¢â€â‚¬Ã¢â€â‚¬ */
            foreach ($suppliers as $s) {
                $idTail = DB::connection($this->connection)->table('t_balance_detail')->insertGetId([
                    'id_balance_head' => $idHead,
                    'id_supplier' => $s->id_supplier,
                    'id_material' => $idMaterial,
                    'qty' => $s->qty_tail,
                    'in_qty' => $s->qty_tail,
                    'init_qty' => $s->qty_tail,
                    'batch_sap' => $s->batch_sap,
                    'tf_number' => $idSloc,
                    'id_sloc_tail' => $idSlocTailJson,
                    'id_plant' => $idPlant,
                    'created_by' => $user,
                ], 'id_balance_tail');

                DB::connection($this->connection)->table('t_trace_detail')->insertGetId([
                    'id_trace_head' => $idTraceHead,
                    'id_balance_tail' => $idTail,
                    'id_supplier' => $s->id_supplier,
                    'id_material' => $idMaterial,
                    'batch_sap' => $s->batch_sap,
                    'in_qty' => $s->qty_tail,
                    'id_sloc' => $idSloc,
                    'id_sloc_tail' => $idSlocTailJson,
                    'id_plant' => $idPlant,
                    'created_by' => $user,
                ], 'id_trace_head');

                DB::connection($this->connection)->table('t_adjustment_detail')->insertGetId([
                    'id_adjust_head' => $idHead,
                    'id_balance_tail' => $idTail,
                    'id_supplier' => $s->id_supplier,
                    'id_material' => $idMaterial,
                    'batch_sap' => $s->batch_sap,
                    'in_qty' => $s->qty_tail,
                    'out_qty' => 0,
                    'before_adjust' => 0,
                    'after_adjust' => $s->qty_tail,
                    'tf_number' => $idSloc,
                    'id_sloc_tail' => $idSlocTailJson,
                    'id_plant' => $idPlant,
                    'created_by' => $user,
                ], 'id_adjust_tail');

                DB::connection($this->connection)->insert(
                    'INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)',
                    ['T_BALANCE_TAIL', 'ADD',
                     'IDHEAD: ' . $idHead . ' IDTAIL: ' . $idTail .
                     ' | DATE: ' . $entryDate . ' / BATCH: ' . $entryNo . ' >>> ' . $newEntryNo .
                     ' / TANK: ' . $idSloc . ' / SUPPLIER: ' . $s->id_supplier .
                     ' / QTY_TAIL: ' . $s->qty_tail . ' / BATCH_SAP: ' . $s->batch_sap . ' | Status: 1', $user]
                );
            }

            /* Ã¢â€â‚¬Ã¢â€â‚¬ Cleanup temp & create material doc Ã¢â€â‚¬Ã¢â€â‚¬ */
            DB::connection($this->connection)->delete(
                'DELETE FROM t_balance_temporary WHERE entry_no = ?', [$entryNo]
            );

            DB::connection($this->connection)->insert(
                'INSERT INTO t_material_document (id_trace_head, material_document, created_by) VALUES (?, ?, ?)',
                [$idTraceHead, $materialDoc, $user]
            );
        }

        return [['response' => 1]];
    }

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  Direct supplier adjustment  (IN / OUT per supplier)
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â

    public function adjustmentSupplier(string $user, array $data, mixed $plantId): array
    {
        $idPlant = app(PlantContextServiceInterface::class)->resolvePlantId($plantId);
        $entryDate = $data['entry_date'] ?? '';
        $entryNo = $data['entry_no'] ?? '';
        $idHead = (int) ($data['id_head'] ?? 0);
        $idMaterial = (int) ($data['id_material'] ?? 0);
        $idSloc = (int) ($data['tf_number'] ?? 0);
        $idSupplier = (int) ($data['id_supplier'] ?? 0);
        $adjustQty = (float) ($data['qty'] ?? 0);
        $batchSap = $data['batch_sap'] ?? '';
        $adjustType = $data['adjust_type'] ?? '';

        if (!$idMaterial || !$idSloc || !$idSupplier || $adjustQty <= 0) {
            return [['response' => 14]];
        }

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Resolve idHead if not provided Ã¢â€â‚¬Ã¢â€â‚¬ */
        if (!$idHead && $batchSap) {
            $dat = DB::connection($this->connection)->select(
                'SELECT DISTINCT id_balance_head
                   FROM t_balance_detail
                  WHERE id_material = ? AND tf_number = ? AND id_supplier = ? AND batch_sap = ? AND status = 1
                  LIMIT 1',
                [$idMaterial, $idSloc, $idSupplier, $batchSap]
            );
            if (count($dat) > 0) {
                $idHead = (int) $dat[0]->id_balance_head;
            } else {
                $dat2 = DB::connection($this->connection)->select(
                    'SELECT id_balance_head
                       FROM t_balance_header
                      WHERE id_material = ? AND tf_number = ? AND status = 1
                      ORDER BY id_balance_head DESC
                      LIMIT 1',
                    [$idMaterial, $idSloc]
                );
                if (count($dat2) === 0) return [['response' => 12]];
                $idHead = (int) $dat2[0]->id_balance_head;
            }
        }

        $trace = DB::connection($this->connection)->select(
            'SELECT id_trace_head FROM t_trace_header
              WHERE id_balance_head = ? AND status = 1
              ORDER BY id_trace_head DESC LIMIT 1',
            [$idHead]
        );
        if (count($trace) === 0) return [['response' => 13]];
        $idTraceHead = (int) $trace[0]->id_trace_head;

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Check available qty (OUT) Ã¢â€â‚¬Ã¢â€â‚¬ */
        if ($adjustType === 'out') {
            $dat3 = DB::connection($this->connection)->select(
                'SELECT SUM(qty) AS total_qty
                   FROM t_balance_detail
                  WHERE id_balance_head = ? AND id_material = ? AND tf_number = ? AND id_supplier = ? AND status = 1',
                [$idHead, $idMaterial, $idSloc, $idSupplier]
            );
            $available = (float) ($dat3[0]->total_qty ?? 0);
            if ($available < $adjustQty) return [['response' => 15]];
        }

        $idSlocVal = !empty($data['id_sloc']) ? (is_array($data['id_sloc']) ? $data['id_sloc'] : [$data['id_sloc']]) : [$data['tf_number'] ?? $idSloc];
        $idSlocJson = json_encode(array_values(array_filter($idSlocVal)));

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Insert adjustment header Ã¢â€â‚¬Ã¢â€â‚¬ */
        $idAdjustHead = DB::connection($this->connection)->table('t_adjustment_header')->insertGetId([
            'entry_date' => $entryDate,
            'adjust_no' => $entryNo,
            'id_balance_head' => $idHead,
            'id_material' => $idMaterial,
            'id_sloc' => $idSlocJson,
            'in_qty' => $adjustType === 'in' ? $adjustQty : 0,
            'out_qty' => $adjustType === 'out' ? $adjustQty : 0,
            'before_adjust' => 0,
            'after_adjust' => 0,
            'id_plant' => $idPlant,
            'created_by' => $user,
        ], 'id_adjust_head');

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Get before qty Ã¢â€â‚¬Ã¢â€â‚¬ */
        $dat4 = DB::connection($this->connection)->select(
            'SELECT SUM(qty) AS total_qty
               FROM t_balance_detail
              WHERE id_balance_head = ? AND id_material = ? AND tf_number = ? AND id_supplier = ? AND status = 1',
            [$idHead, $idMaterial, $idSloc, $idSupplier]
        );
        $beforeSupplierQty = (float) ($dat4[0]->total_qty ?? 0);

        /* Ã¢â€â‚¬Ã¢â€â‚¬ LOGGING Ã¢â€â‚¬Ã¢â€â‚¬ */
        DB::connection($this->connection)->insert(
            'INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)',
            ['T_ADJUST_HEAD', 'ADD SUPPLIER ADJUST',
             'IDADJUSTHEAD: ' . $idAdjustHead . ' | IDHEAD: ' . $idHead .
             ' | SUPPLIER: ' . $idSupplier . ' | TYPE: ' . $adjustType . ' | QTY: ' . $adjustQty, $user]
        );

        /* Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â ADJUST OUT (FIFO) Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â */
        if ($adjustType === 'out') {
            $qtyToDeduct = $adjustQty;

            $rows = DB::connection($this->connection)->select(
                'SELECT id_balance_tail, qty, batch_sap
                   FROM t_balance_detail
                  WHERE id_balance_head = ? AND id_material = ? AND tf_number = ? AND id_supplier = ?
                    AND qty > 0 AND status = 1
                  ORDER BY id_balance_tail ASC',
                [$idHead, $idMaterial, $idSloc, $idSupplier]
            );

            foreach ($rows as $row) {
                if ($qtyToDeduct <= 0) break;

                $takeQty = min((float) $row->qty, $qtyToDeduct);
                $afterQty = (float) $row->qty - $takeQty;

                DB::connection($this->connection)->update(
                    'UPDATE t_balance_detail SET qty = ?, out_qty = ?, updated_by = ? WHERE id_balance_tail = ?',
                    [$afterQty, $takeQty, $user, $row->id_balance_tail]
                );

                DB::connection($this->connection)->table('t_adjustment_detail')->insert([
                    'id_adjust_head' => $idAdjustHead,
                    'id_balance_tail' => $row->id_balance_tail,
                    'id_supplier' => $idSupplier,
                    'id_material' => $idMaterial,
                    'batch_sap' => $row->batch_sap,
                    'out_qty' => $takeQty,
                    'before_adjust' => (float) $row->qty,
                    'after_adjust' => $afterQty,
                    'tf_number' => $idSloc,
                    'id_plant' => $idPlant,
                    'created_by' => $user,
                ], 'id_trace_tail');

                DB::connection($this->connection)->table('t_trace_detail')->insert([
                    'id_trace_head' => $idTraceHead,
                    'id_balance_tail' => $row->id_balance_tail,
                    'id_supplier' => $idSupplier,
                    'id_material' => $idMaterial,
                    'batch_sap' => $row->batch_sap,
                    'out_qty' => $takeQty,
                    'id_sloc' => $idSloc,
                    'id_plant' => $idPlant,
                    'created_by' => $user,
                ]);

                $qtyToDeduct -= $takeQty;
            }

            DB::connection($this->connection)->update(
                'UPDATE t_trace_header SET out_qty = ?, updated_by = ? WHERE id_trace_head = ?',
                [$adjustQty, $user, $idTraceHead]
            );
        }

        /* Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â ADJUST IN Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â */
        if ($adjustType === 'in') {
            $dat5 = DB::connection($this->connection)->select(
                'SELECT id_balance_tail, qty
                   FROM t_balance_detail
                  WHERE id_balance_head = ? AND id_material = ? AND tf_number = ? AND id_supplier = ? AND batch_sap = ? AND status = 1
                  LIMIT 1',
                [$idHead, $idMaterial, $idSloc, $idSupplier, $batchSap]
            );

            if (count($dat5) > 0) {
                $idTail = (int) $dat5[0]->id_balance_tail;
                $beforeQty = (float) $dat5[0]->qty;
                $afterQty = $beforeQty + $adjustQty;

                DB::connection($this->connection)->update(
                    'UPDATE t_balance_detail SET qty = ?, in_qty = ?, updated_by = ? WHERE id_balance_tail = ?',
                    [$afterQty, $adjustQty, $user, $idTail]
                );
            } else {
                $beforeQty = 0.0;
                $afterQty = $adjustQty;

                $idTail = DB::connection($this->connection)->table('t_balance_detail')->insertGetId([
                    'id_balance_head' => $idHead,
                    'id_supplier' => $idSupplier,
                    'id_material' => $idMaterial,
                    'batch_sap' => $batchSap,
                    'qty' => $adjustQty,
                    'in_qty' => $adjustQty,
                    'tf_number' => $idSloc,
                    'id_plant' => $idPlant,
                    'created_by' => $user,
                ], 'id_balance_tail');
            }

            DB::connection($this->connection)->table('t_adjustment_detail')->insert([
                'id_adjust_head' => $idAdjustHead,
                'id_balance_tail' => $idTail,
                'id_supplier' => $idSupplier,
                'id_material' => $idMaterial,
                'batch_sap' => $batchSap,
                'in_qty' => $adjustQty,
                'before_adjust' => $beforeQty,
                'after_adjust' => $afterQty,
                'tf_number' => $idSloc,
                'id_plant' => $idPlant,
                'created_by' => $user,
            ]);

            DB::connection($this->connection)->table('t_trace_detail')->insert([
                'id_trace_head' => $idTraceHead,
                'id_balance_tail' => $idTail,
                'id_supplier' => $idSupplier,
                'id_material' => $idMaterial,
                'batch_sap' => $batchSap,
                'in_qty' => $adjustQty,
                'id_sloc' => $idSloc,
                'id_plant' => $idPlant,
                'created_by' => $user,
            ]);

            DB::connection($this->connection)->update(
                'UPDATE t_trace_header SET in_qty = ?, updated_by = ? WHERE id_trace_head = ?',
                [$adjustQty, $user, $idTraceHead]
            );
        }

        /* Ã¢â€â‚¬Ã¢â€â‚¬ Recalculate header totals Ã¢â€â‚¬Ã¢â€â‚¬ */
        $sum = DB::connection($this->connection)->select(
            'SELECT SUM(qty) AS total_qty
               FROM t_balance_detail WHERE id_balance_head = ? AND status = 1',
            [$idHead]
        );
        $totalQty = (float) ($sum[0]->total_qty ?? 0);

        DB::connection($this->connection)->update(
            'UPDATE t_balance_header SET qty = ?, in_qty = ?, out_qty = ?, updated_by = ?
              WHERE id_balance_head = ?',
            [$totalQty,
             $adjustType === 'in' ? $adjustQty : 0,
             $adjustType === 'out' ? $adjustQty : 0,
             $user, $idHead]
        );

        $afterSupplierQty = $adjustType === 'in'
            ? $beforeSupplierQty + $adjustQty
            : $beforeSupplierQty - $adjustQty;

        DB::connection($this->connection)->update(
            'UPDATE t_adjustment_header SET before_adjust = ?, after_adjust = ? WHERE id_adjust_head = ?',
            [$beforeSupplierQty, $afterSupplierQty, $idAdjustHead]
        );

        return [['response' => 1]];
    }

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  Material document
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â

    public function adjustMaterialDocument(int $idAdjustHead, ?string $materialDoc, string $user): array
    {
        $header = DB::connection($this->connection)->selectOne(
            'SELECT a.adjust_no, f.id_trace_head
               FROM t_adjustment_header a
               LEFT JOIN (SELECT to_trace_no, id_trace_head FROM t_trace_header WHERE status = 1) f
                 ON a.adjust_no = f.to_trace_no
              WHERE a.id_adjust_head = ?',
            [$idAdjustHead]
        );
        if (!$header || !$header->id_trace_head) return [['response' => 0]];

        $existing = DB::connection($this->connection)->selectOne(
            'SELECT id_matdoc FROM t_material_document
              WHERE id_trace_head = ? AND status = 1',
            [$header->id_trace_head]
        );

        if ($existing) {
            DB::connection($this->connection)->update(
                'UPDATE t_material_document SET material_document = ?, updated_by = ? WHERE id_matdoc = ?',
                [$materialDoc, $user, $existing->id_matdoc]
            );
        } else {
            DB::connection($this->connection)->insert(
                'INSERT INTO t_material_document (id_trace_head, material_document, created_by) VALUES (?, ?, ?)',
                [$header->id_trace_head, $materialDoc, $user]
            );
        }

        return [['response' => 1]];
    }

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  Helper: proportional distribution
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â

    private function adjustAmtToTotal(array &$dataPerHead, float $targetTotal): void
    {
        $flat = $dataPerHead['det'] ?? [];
        $dataPerHead['det'] = QuantityDistributionHelper::adjustToTotal($flat, $targetTotal, 'qty');
    }

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  Period Adjustment Methods
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â

    public function getPeriodHeaders(array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT a.id_report_head AS id_pspa_head, a.period, a.status, a.created_by, a.created_at,
                    b.count_rows, b.total_physical, b.total_book,
                    CASE a.status
                      WHEN 1 THEN 'DRAFT'
                      WHEN 2 THEN 'CALCULATED'
                      WHEN 3 THEN 'LOCKED'
                      ELSE 'UNKNOWN'
                    END AS status_label
               FROM t_report_pspa_head a
               LEFT JOIN (
                   SELECT id_pspa_head, COUNT(*) AS count_rows,
                          SUM(physical_stock) AS total_physical,
                          SUM(book_stock) AS total_book
                     FROM t_report_pspa_detail
                    WHERE status = 1
                    GROUP BY id_pspa_head
               ) b ON a.id_report_head = b.id_pspa_head
              WHERE a.status IN (1, 2, 3)
              ORDER BY a.period DESC";

        $countSql = "SELECT COUNT(*) AS total FROM ({$sql}) AS count_query";
        $paginatedSql = $sql . " LIMIT ? OFFSET ?";

        $results = DB::connection($this->connection)->select($paginatedSql, [$perPage, $offset]);
        $countResult = DB::connection($this->connection)->select($countSql);
        $total = (int) ($countResult[0]->total ?? 0);

        return [
            'data' => $results,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / max($perPage, 1)),
        ];
    }

    public function getPeriodViewData(int $idHead): array
    {
        return DB::connection($this->connection)->select(
            'SELECT a.*, b.description AS tank_name, c.description AS material_name
               FROM t_report_pspa_detail a
               LEFT JOIN m_sloc b ON a.id_sloc = b.id_sloc
               LEFT JOIN m_material c ON a.id_material = c.id_material
              WHERE a.id_pspa_head = ? AND a.status = 1
              ORDER BY a.id_pspa_detail ASC',
            [$idHead]
        );
    }

    public function periodHeadersUpload(string $user, array $data, $file): array
    {
        $mode = $data['mode'] ?? 'UPLOAD';

        if ($mode === 'ADD') {
            $period = $data['period'] ?? '';
            $batch = $data['batch'] ?? '';

            if (!$period) {
                return ['response' => 0, 'message' => 'Period is required'];
            }

            // Check if period already exists
            $exists = DB::connection($this->connection)->table('t_report_pspa_head')
                ->where('period', $period)
                ->whereIn('status', [1, 2])
                ->exists();

            if ($exists) {
                return ['response' => 2, 'message' => 'Period already exists'];
            }

            $idHead = DB::connection($this->connection)->table('t_report_pspa_head')->insertGetId([
                'period' => $period,
                'batch_sap' => $batch,
                'status' => 1,
                'created_by' => $user,
                'created_at' => now(),
            ], 'id_report_head');

            return [
                'response' => 1,
                'message' => 'Period created successfully',
                'id_head' => $idHead
            ];

        } elseif ($mode === 'UPDATE') {
            $idHead = $data['id_head'] ?? null;
            $batch = $data['batch'] ?? '';

            if (!$idHead) {
                return ['response' => 0, 'message' => 'ID Head is required for update'];
            }

            DB::connection($this->connection)->table('t_report_pspa_head')
                ->where('id_report_head', $idHead)
                ->update([
                    'batch_sap' => $batch,
                    'updated_by' => $user,
                    'updated_at' => now(),
                ]);

            return [
                'response' => 1,
                'message' => 'Period updated successfully'
            ];

        } elseif ($mode === 'UPLOAD') {
            $idHead = $data['id_head'] ?? null;
            if (!$idHead || !$file) {
                return ['response' => 0, 'message' => 'ID Head and file are required for upload'];
            }

            // Parse Excel file
            $rows = \Maatwebsite\Excel\Facades\Excel::toArray(new \Modules\Adjustment\Imports\PeriodTailImport, $file);

            $importedCount = 0;
            // Clear existing details
            DB::connection($this->connection)->table('t_report_pspa_detail')
                ->where('id_pspa_head', $idHead)
                ->delete();

            foreach ($rows as $sheet) {
                foreach ($sheet as $index => $row) {
                    if ($index === 0) continue; // Skip header row
                    if (empty($row)) continue;

                    // Map columns based on Excel structure
                    $idSloc = (int) ($row[0] ?? 0);
                    $idMaterial = (int) ($row[1] ?? 0);
                    $physicalStock = (float) ($row[2] ?? 0);
                    $bookStock = (float) ($row[3] ?? 0);
                    $tfNumber = $row[4] ?? '';

                    if ($idSloc && $idMaterial) {
                        DB::connection($this->connection)->table('t_report_pspa_detail')->insert([
                            'id_pspa_head' => $idHead,
                            'tf_number' => $tfNumber,
                            'id_material' => $idMaterial,
                            'id_sloc' => $idSloc, // wait, $idSloc maps to tf_number or id_sloc? Wait, the old code had an issue here! Let's check the old code.
                            'physical_stock' => $physicalStock,
                            'book_stock' => $bookStock,
                            'status' => 1,
                            'created_by' => $user,
                        ]);
                        $importedCount++;
                    }
                }
            }

            // Mark header as uploaded
            DB::connection($this->connection)->table('t_report_pspa_head')
                ->where('id_report_head', $idHead)
                ->update([
                    'uploaded_file' => 1,
                    'updated_at' => now(),
                    'updated_by' => $user,
                ]);

            return [
                'response' => 1,
                'id_head' => $idHead,
                'imported_count' => $importedCount,
                'message' => "Successfully imported {$importedCount} rows"
            ];
        }

        return ['response' => 0, 'message' => 'Invalid mode'];
    }

    public function periodViewOnHand(string $user, int $idHead): array
    {
        // Get period header info
        $header = DB::connection($this->connection)->selectOne(
            'SELECT *, id_report_head AS id_pspa_head FROM t_report_pspa_head WHERE id_report_head = ?',
            [$idHead]
        );

        if (!$header) {
            return ['response' => 0, 'message' => 'Period header not found'];
        }

        // Get details and calculate on-hand from t_balance
        return DB::connection($this->connection)->select(
            'SELECT a.id_pspa_detail, a.id_sloc, a.id_material,
                    b.description AS tank_name, c.description AS material_name,
                    a.physical_stock, a.book_stock,
                    COALESCE(d.on_hand, 0) AS system_on_hand,
                    (COALESCE(d.on_hand, 0) - a.physical_stock) AS variance
               FROM t_report_pspa_detail a
               LEFT JOIN m_sloc b ON a.id_sloc = b.id_sloc
               LEFT JOIN m_material c ON a.id_material = c.id_material
               LEFT JOIN (
                   SELECT b.id_sloc, b.id_material, SUM(b.qty) AS on_hand
                     FROM t_balance_header a
                     JOIN t_balance_detail b ON a.id_balance_head = b.id_balance_head
                    WHERE a.status = 1 AND b.status = 1
                    GROUP BY b.id_sloc, b.id_material
               ) d ON a.id_sloc = d.id_sloc AND a.id_material = d.id_material
              WHERE a.id_pspa_head = ? AND a.status = 1
              ORDER BY b.description, c.description',
            [$idHead]
        );
    }

    public function periodViewAdjustment(string $user, int $idHead): array
    {
        // Update status to CALCULATED
        DB::connection($this->connection)->table('t_report_pspa_head')
            ->where('id_report_head', $idHead)
            ->update(['status' => 2, 'updated_by' => $user]);

        return ['response' => 1, 'message' => 'Period adjustment calculated'];
    }

    public function periodHeaderLock(string $user, int $idHead): array
    {
        DB::connection($this->connection)->table('t_report_pspa_head')
            ->where('id_report_head', $idHead)
            ->update(['status' => 3, 'updated_by' => $user]);

        return ['response' => 1, 'message' => 'Period locked successfully'];
    }

    public function destroyAdjustmentPeriod(int $id, string $user): array
    {
        // Check if already locked
        $header = DB::connection($this->connection)->selectOne(
            'SELECT status FROM t_report_pspa_head WHERE id_report_head = ?',
            [$id]
        );

        if ($header && $header->status == 3) {
            return ['response' => 0, 'message' => 'Cannot delete locked period'];
        }

        // Delete details first
        DB::connection($this->connection)->delete(
            'DELETE FROM t_report_pspa_detail WHERE id_pspa_head = ?',
            [$id]
        );

        // Delete header
        DB::connection($this->connection)->delete(
            'DELETE FROM t_report_pspa_head WHERE id_report_head = ?',
            [$id]
        );

        return ['response' => 1, 'message' => 'Period deleted successfully'];
    }

    public function getLastAdjustmentRecord(mixed $plantId): array
    {
        $idPlant = app(PlantContextServiceInterface::class)->resolvePlantId($plantId);

        return DB::connection($this->connection)->select(
            "SELECT a.id_adjust_head, a.adjust_no, a.entry_date, a.status,
                    CONCAT(c.code, ' :: ', c.description) AS material,
                    MIN(g.description) AS tank,
                    a.before_adjust, a.after_adjust,
                    CASE a.status
                      WHEN 1 THEN 'DRAFT'
                      WHEN 2 THEN 'APPROVED'
                      WHEN 3 THEN 'REJECTED'
                      WHEN 4 THEN 'EXECUTED'
                      ELSE 'UNKNOWN'
                    END AS status_label
               FROM t_adjustment_header a
               LEFT JOIN m_material c ON a.id_material = c.id_material
               LEFT JOIN m_sloc g ON (
                   {$this->dbSlocColumnClause('a.id_sloc', 'g.id_sloc')}
               ) AND g.status = 1
              WHERE a.status IN (1, 2)
                AND a.id_plant = ?
               GROUP BY a.id_adjust_head
               ORDER BY a.id_adjust_head DESC
               LIMIT 10",
            [$idPlant]
        );
    }

    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
    //  WHX (Warehouse) Methods
    // Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â

    public function getAdjustStatus(?string $adjustNo, ?int $idAdjustHead): array
    {
        if (!$adjustNo && !$idAdjustHead) {
            return ['response' => 0, 'message' => 'Either adjust_no or id_adjust_head is required'];
        }

        $sql = "SELECT a.id_adjust_head, a.adjust_no, a.status,
                       CASE a.status
                           WHEN 1 THEN 'DRAFT'
                           WHEN 2 THEN 'APPROVED'
                           WHEN 3 THEN 'REJECTED'
                           WHEN 4 THEN 'EXECUTED'
                           WHEN 9 THEN 'CANCELLED'
                           ELSE 'UNKNOWN'
                       END AS status_label,
                       a.entry_date, a.id_material, a.id_sloc AS tf_number
                  FROM t_adjustment_header a
                 WHERE " . ($adjustNo ? 'a.adjust_no = ?' : 'a.id_adjust_head = ?') . "
                 LIMIT 1";
        $param = $adjustNo ?? $idAdjustHead;

        $row = DB::connection($this->connection)->selectOne($sql, [$param]);

        if (!$row) {
            return ['response' => 0, 'message' => 'Adjustment not found'];
        }

        return [
            'response' => 1,
            'data' => (array) $row,
        ];
    }

    public function storeAdjustmentWhx(string $user, array $data, mixed $plantId): array
    {
        $idPlant = app(PlantContextServiceInterface::class)->resolvePlantId($plantId);
        $idMaterialPck = (int) ($data['id_material'] ?? 0);
        $idWarehouse = (int) ($data['tf_number'] ?? 0);
        $adjustQty = (float) ($data['qty'] ?? 0);
        $entryDate = $data['entry_date'] ?? date('Y-m-d');

        if (!$idMaterialPck || !$idWarehouse) {
            return ['response' => 0, 'message' => 'id_material and tf_number (warehouse) are required'];
        }

        $material = DB::connection($this->connection)->selectOne(
            'SELECT id_materialpck, code, description FROM m_material_pck WHERE id_materialpck = ? AND status = 1',
            [$idMaterialPck]
        );
        if (!$material) {
            return ['response' => 0, 'message' => 'WHX material not found or inactive'];
        }

        $warehouse = DB::connection($this->connection)->selectOne(
            'SELECT id_warehouse, id_batch FROM m_warehouse WHERE id_warehouse = ? AND status = 1',
            [$idWarehouse]
        );
        if (!$warehouse) {
            return ['response' => 0, 'message' => 'Warehouse not found or inactive'];
        }

        $batchId = $warehouse->id_batch;
        $batchEntryDate = substr(str_replace('-', '', $entryDate), 2);
        $lastTwoDigitIdPlant = substr((string) $idPlant, 2, 2);
        $batchMapping = '6' . $batchEntryDate . $batchId . $lastTwoDigitIdPlant;

        $existing = DB::connection($this->connection)->selectOne(
            'SELECT adjust_no FROM t_adjustment_header
              WHERE SUBSTRING(CAST(adjust_no AS TEXT), 1, 12) = ?
              ORDER BY id_adjust_head DESC LIMIT 1',
            [$batchMapping]
        );
        $nextSeq = 1;
        if ($existing) {
            $nextSeq = ((int) substr($existing->adjust_no, 12, 4)) + 1;
        }
        $adjustNo = $batchMapping . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);

        $idSlocJson = json_encode([(string) $idWarehouse]);

        $idHead = DB::connection($this->connection)->table('t_adjustment_header')->insertGetId([
            'adjust_no' => $adjustNo,
            'entry_date' => $entryDate,
            'id_material' => $idMaterialPck,
            'id_sloc' => $idSlocJson,
            'id_plant' => $idPlant,
            'before_adjust' => 0,
            'after_adjust' => $adjustQty,
            'status' => 1,
            'source' => 'WHX',
            'created_by' => $user,
            'created_at' => now(),
        ], 'id_adjust_head');

        DB::connection($this->connection)->table('log_transactions')->insert([
            'log_module' => 'T_ADJ_HEAD',
            'log_type' => 'CREATE_WHX',
            'log_description' => 'ID: ' . $idHead . ' | ADJUST_NO: ' . $adjustNo . ' | QTY: ' . $adjustQty,
            'created_by' => $user,
            'created_at' => now(),
        ]);

        return ['response' => 1, 'message' => 'WHX adjustment header created', 'id' => $idHead, 'adjust_no' => $adjustNo];
    }

    public function adjustmentInitWhx(string $user, array $data, mixed $plantId): array
    {
        $idPlant = app(PlantContextServiceInterface::class)->resolvePlantId($plantId);
        $idMaterialPck = (int) ($data['id_material'] ?? 0);
        $idWarehouse = (int) ($data['tf_number'] ?? 0);
        $entryDate = $data['entry_date'] ?? date('Y-m-d');
        $value = (float) ($data['value'] ?? 0);
        $remark = $data['remark'] ?? '';

        if (!$idMaterialPck || !$idWarehouse) {
            return ['response' => 0, 'message' => 'id_material and tf_number (warehouse) are required'];
        }

        $id = DB::connection($this->connection)->table('t_balance_temporary')->insertGetId([
            'id_plant' => $idPlant,
            'id_material' => $idMaterialPck,
            'tf_number' => $idWarehouse,
            'entry_date' => $entryDate,
            'value' => $value,
            'remark' => $remark,
            'source' => 'WHX',
            'status' => 1,
            'created_by' => $user,
            'created_at' => now(),
        ], 'id_balance_temp');

        DB::connection($this->connection)->table('log_transactions')->insert([
            'log_module' => 'T_BAL_TEMP',
            'log_type' => 'INIT_WHX',
            'log_description' => 'ID: ' . $id . ' | MATERIAL: ' . $idMaterialPck . ' | WAREHOUSE: ' . $idWarehouse . ' | VALUE: ' . $value,
            'created_by' => $user,
            'created_at' => now(),
        ]);

        return ['response' => 1, 'message' => 'WHX init adjustment stored', 'id' => $id];
    }
}
