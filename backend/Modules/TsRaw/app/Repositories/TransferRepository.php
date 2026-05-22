<?php

namespace Modules\TsRaw\Repositories;

use Modules\TsRaw\Repositories\Contracts\TransferRepositoryInterface;
use Modules\TsRaw\Models\BalanceHeader;
use Modules\TsRaw\Models\TraceHeader;
use Modules\Plant\Models\Plant;
use Illuminate\Support\Facades\DB;

class TransferRepository implements TransferRepositoryInterface
{
    public function getStorageLog($plantId): array
    {
        $plantId = $this->resolvePlantCode($plantId);
        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        $query = "SELECT a.id_trace_head, a.id_balance_head, a.entry_date,
                         a.from_trace_no, a.to_trace_no,
                         c.code AS material_code, c.description AS material_name,
                         CONCAT(d.description,
                            IF(
                                COALESCE(a.id_sloc_tail, a.id_tank_tail) IS NOT NULL
                                AND COALESCE(a.id_sloc_tail, a.id_tank_tail) != ''
                                AND COALESCE(a.id_sloc_tail, a.id_tank_tail) != '[]',
                                CONCAT(' | ',
                                    COALESCE(
                                        (
                                            SELECT GROUP_CONCAT(h.tf_number ORDER BY h.tf_number ASC SEPARATOR ' & ')
                                            FROM m_sloc_detail h
                                            WHERE FIND_IN_SET(h.id_sloc_tail, REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(a.id_sloc_tail, a.id_tank_tail), '[', ''), ']', ''), '\"', ''), ' ', '')) > 0
                                              AND h.status = 1
                                        ),
                                        (
                                            SELECT GROUP_CONCAT(h.id_tank ORDER BY h.id_tank ASC SEPARATOR ' & ')
                                            FROM m_sloc h
                                            WHERE FIND_IN_SET(h.id_sloc, REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(a.id_sloc_tail, a.id_tank_tail), '[', ''), ']', ''), '\"', ''), ' ', '')) > 0
                                              AND h.status = 1
                                        ),
                                        REPLACE(REPLACE(REPLACE(COALESCE(a.id_sloc_tail, a.id_tank_tail), '[', ''), ']', ''), '\"', '')
                                    )
                                ),
                                ''
                            )
                         ) AS tank_name,
                         FORMAT(a.in_qty, 3) AS in_qty,
                         FORMAT(a.out_qty, 3) AS out_qty,
                         a.created_by, a.created_at,
                         a.id_tank_tail,
                         md.material_document, md.po_so, p.code AS plant_code
                    FROM t_trace_header a
                    JOIN m_material c ON a.id_material = c.id_material
                    JOIN m_sloc d ON a.id_sloc = d.id_sloc
                    LEFT JOIN t_material_document md ON a.id_trace_head = md.id_trace_head
                    LEFT JOIN m_plant p ON d.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                   WHERE a.status = 1
                     AND d.description LIKE '%Storage%'
                     AND (CAST(? AS CHAR) = '0' OR CAST(d.id_plant AS CHAR) = CAST(? AS CHAR))
                   GROUP BY a.id_trace_head
                   ORDER BY a.id_trace_head DESC";

        return DB::connection('eudr_ts')->select($query, [$plantId, $plantId]);
    }

    public function getFeedLog($plantId): array
    {
        return $this->getTankLog($plantId, 'FEED');
    }

    public function debugFeedLog($plantId): array
    {
        $plantId = $this->resolvePlantCode($plantId);
        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        $query = "SELECT a.id_trace_head, a.id_balance_head, a.entry_date,
                         a.from_trace_no, a.to_trace_no,
                         c.code AS material_code, c.description AS material_name,
                         d.description AS tank_description,
                         d.id_tank AS tank_number,
                         a.id_tank_tail,
                         FORMAT(a.in_qty, 3) AS in_qty,
                         FORMAT(a.out_qty, 3) AS out_qty,
                         a.created_by, a.created_at,
                         md.material_document, md.po_so,
                         (
                            SELECT GROUP_CONCAT(CONCAT(h.tf_number, ' - ', h.description) ORDER BY h.tf_number ASC SEPARATOR ' | ')
                            FROM m_sloc_detail h
                            WHERE FIND_IN_SET(h.id_sloc_tail, REPLACE(REPLACE(REPLACE(REPLACE(a.id_sloc_tail, '[', ''), ']', ''), '\"', ''), ' ', '')) > 0
                              AND h.status = 1
                         ) AS sub_slocs_raw, p.code AS plant_code
                    FROM t_trace_header a
                    JOIN m_material c ON a.id_material = c.id_material
                    JOIN m_sloc d ON a.id_sloc = d.id_sloc
                    LEFT JOIN t_material_document md ON a.id_trace_head = md.id_trace_head
                    LEFT JOIN m_plant p ON d.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                   WHERE a.status = 1
                     AND d.description LIKE '%FEED%'
                     AND (CAST(? AS CHAR) = '0' OR CAST(d.id_plant AS CHAR) = CAST(? AS CHAR))
                   ORDER BY a.id_trace_head DESC";

        $rawData = DB::connection('eudr_ts')->select($query, [$plantId, $plantId]);

        $groupedData = [];
        foreach ($rawData as $row) {
            $key = $row->id_trace_head;

            if (!isset($groupedData[$key])) {
                $groupedData[$key] = [
                    'id_trace_head' => $row->id_trace_head,
                    'id_balance_head' => $row->id_balance_head,
                    'entry_date' => $row->entry_date,
                    'from_trace_no' => $row->from_trace_no,
                    'to_trace_no' => $row->to_trace_no,
                    'material_code' => $row->material_code,
                    'material_name' => $row->material_name,
                    'tank_description' => $row->tank_description,
                    'tank_number' => $row->tank_number,
                    'id_tank_tail' => $row->id_tank_tail,
                    'in_qty' => $row->in_qty,
                    'out_qty' => $row->out_qty,
                    'created_by' => $row->created_by,
                    'created_at' => $row->created_at,
                    'material_document' => $row->material_document,
                    'po_so' => $row->po_so,
                    'plant_code' => $row->plant_code,
                    'sub_slocs' => []
                ];
            }

            if ($row->sub_slocs_raw) {
                $slocParts = explode(' | ', $row->sub_slocs_raw);
                foreach ($slocParts as $part) {
                    $parts = explode(' - ', $part, 2);
                    if (count($parts) === 2) {
                        $groupedData[$key]['sub_slocs'][] = [
                            'tf_number' => trim($parts[0]),
                            'description' => trim($parts[1])
                        ];
                    }
                }
            }
        }

        return array_values($groupedData);
    }

    protected function getTankLog($plantId, string $tankType): array
    {
        $plantId = $this->resolvePlantCode($plantId);
        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        $query = "SELECT a.id_trace_head, a.id_balance_head, a.entry_date,
                         a.from_trace_no, a.to_trace_no,
                         c.code AS material_code, c.description AS material_name,
                         CONCAT(d.description,
                            IF(
                                COALESCE(a.id_sloc_tail, a.id_tank_tail) IS NOT NULL
                                AND COALESCE(a.id_sloc_tail, a.id_tank_tail) != ''
                                AND COALESCE(a.id_sloc_tail, a.id_tank_tail) != '[]',
                                CONCAT(' | ',
                                    COALESCE(
                                        (
                                            SELECT GROUP_CONCAT(h.tf_number ORDER BY h.tf_number ASC SEPARATOR ' & ')
                                            FROM m_sloc_detail h
                                            WHERE FIND_IN_SET(h.id_sloc_tail, REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(a.id_sloc_tail, a.id_tank_tail), '[', ''), ']', ''), '\"', ''), ' ', '')) > 0
                                              AND h.status = 1
                                        ),
                                        (
                                            SELECT GROUP_CONCAT(h.id_tank ORDER BY h.id_tank ASC SEPARATOR ' & ')
                                            FROM m_sloc h
                                            WHERE FIND_IN_SET(h.id_sloc, REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(a.id_sloc_tail, a.id_tank_tail), '[', ''), ']', ''), '\"', ''), ' ', '')) > 0
                                              AND h.status = 1
                                        ),
                                        REPLACE(REPLACE(REPLACE(COALESCE(a.id_sloc_tail, a.id_tank_tail), '[', ''), ']', ''), '\"', '')
                                    )
                                ),
                                ''
                            )
                         ) AS tank_name,
                         FORMAT(a.in_qty, 3) AS in_qty,
                         FORMAT(a.out_qty, 3) AS out_qty,
                         a.created_by, a.created_at,
                         a.id_tank_tail,
                         md.material_document, md.po_so, p.code AS plant_code
                    FROM t_trace_header a
                    JOIN m_material c ON a.id_material = c.id_material
                    JOIN m_sloc d ON a.id_sloc = d.id_sloc
                    LEFT JOIN t_material_document md ON a.id_trace_head = md.id_trace_head
                    LEFT JOIN m_plant p ON d.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                   WHERE a.status = 1
                     AND d.description LIKE CONCAT('%', ?, '%')
                     AND (CAST(? AS CHAR) = '0' OR CAST(d.id_plant AS CHAR) = CAST(? AS CHAR))
                   GROUP BY a.id_trace_head
                   ORDER BY a.id_trace_head DESC";

        return DB::connection('eudr_ts')->select($query, [$tankType, $plantId, $plantId]);
    }

    public function generateTransferNumber($plantId, string $movSeq = '000'): ?string
    {
        $plantId = $this->resolvePlantCode($plantId);
        $result = DB::connection('eudr_ts')->select(
            'SELECT a.trace_no
               FROM (SELECT a.trace_no+1 AS trace_no
                       FROM t_balance_header a
                      WHERE SUBSTRING(a.trace_no,1,7) = CONCAT("7", DATE_FORMAT(CURDATE(), "%y%m%d"))
                        AND SUBSTRING(a.trace_no,8,3) = ?
                        AND a.status = 1
                        AND a.id_plant = ?
                      ORDER BY a.id_balance_head DESC
                      LIMIT 1) a
             UNION ALL
            SELECT CONCAT("7", DATE_FORMAT(CURDATE(), "%y%m%d"), ?, LPAD(RIGHT(?, 2), 2, "0"), "01") AS trace_no
               LIMIT 1',
            [$movSeq, $plantId, $movSeq, $plantId]
        );

        return $result[0]->trace_no ?? null;
    }

    public function findBalanceByTraceNo(string $traceNo): ?object
    {
        $result = DB::connection('eudr_ts')->select(
            'SELECT * FROM t_balance_header WHERE trace_no = ? AND status = 1 LIMIT 1',
            [$traceNo]
        );
        return $result[0] ?? null;
    }

    public function findTraceByBalanceHeadId(int $balanceHeadId): ?object
    {
        return DB::connection('eudr_ts')->table('t_trace_header')
            ->where('id_balance_head', $balanceHeadId)
            ->where('status', 1)
            ->first();
    }

    public function createTransferBalance(array $data): object
    {
        $result = DB::connection('eudr_ts')->table('t_balance_header')->insertGetId([
            'entry_date' => $data['entry_date'],
            'trace_no' => $data['trace_no'],
            'id_material' => $data['id_material'],
            'id_sloc' => $data['id_sloc'],
            'id_sloc_tail' => $data['id_sloc_tail'] ?? null,
            'id_tank' => null,
            'id_tank_tail' => null,
            'id_plant' => $data['id_plant'],
            'qty' => $data['qty'],
            'in_qty' => $data['qty'],
            'out_qty' => 0,
            'init_qty' => $data['qty'],
            'status' => 1,
            'created_by' => $data['created_by'],
        ]);

        return (object) ['id_balance_head' => $result];
    }

    public function createTransferTrace(array $data): object
    {
        $result = DB::connection('eudr_ts')->table('t_trace_header')->insertGetId([
            'id_balance_head' => $data['id_balance_head'],
            'entry_date' => $data['entry_date'],
            'from_trace_no' => $data['from_trace_no'],
            'to_trace_no' => $data['to_trace_no'],
            'id_material' => $data['id_material'],
            'id_sloc' => $data['id_sloc'],
            'id_tank_tail' => $data['id_tank_tail'] ?? null,
            'id_plant' => $data['id_plant'],
            'in_qty' => $data['qty'],
            'out_qty' => 0,
            'status' => 1,
            'created_by' => $data['created_by'],
        ]);

        return (object) ['id_trace_head' => $result];
    }

    public function updateSourceBalance(int $balanceId, float $qty): bool
    {
        return (bool) DB::connection('eudr_ts')->table('t_balance_header')
            ->where('id_balance_head', $balanceId)
            ->update([
                'qty' => DB::raw('qty - ' . $qty),
                'out_qty' => DB::raw('out_qty + ' . $qty),
            ]);
    }

    public function updateSourceTrace(int $balanceHeadId, float $qty): bool
    {
        return (bool) DB::connection('eudr_ts')->table('t_trace_header')
            ->where('id_balance_head', $balanceHeadId)
            ->where('status', 1)
            ->update([
                'out_qty' => DB::raw('out_qty + ' . $qty),
            ]);
    }

    public function findTransferById(int $id): ?object
    {
        return DB::connection('eudr_ts')->table('t_trace_header')
            ->where('id_trace_head', $id)
            ->where('status', 1)
            ->first();
    }

    public function deactivateBalance(int $balanceId, string $user): bool
    {
        return (bool) DB::connection('eudr_ts')->table('t_balance_header')
            ->where('id_balance_head', $balanceId)
            ->update(['status' => 0, 'updated_by' => $user]);
    }

    public function deactivateTrace(int $traceId, string $user): bool
    {
        return (bool) DB::connection('eudr_ts')->table('t_trace_header')
            ->where('id_trace_head', $traceId)
            ->update(['status' => 0, 'updated_by' => $user]);
    }

    public function revertSourceBalance(string $traceNo, float $qty): bool
    {
        return (bool) DB::connection('eudr_ts')->table('t_balance_header')
            ->where('trace_no', $traceNo)
            ->where('status', 1)
            ->update([
                'qty' => DB::raw('qty + ' . $qty),
                'out_qty' => DB::raw('out_qty - ' . $qty),
            ]);
    }

    public function revertSourceTrace(int $balanceHeadId, float $qty): bool
    {
        return (bool) DB::connection('eudr_ts')->table('t_trace_header')
            ->where('id_balance_head', $balanceHeadId)
            ->where('status', 1)
            ->update([
                'out_qty' => DB::raw('out_qty - ' . $qty),
            ]);
    }

    public function logTransaction(string $module, string $type, string $description, string $user): void
    {
        DB::connection('eudr_ts')->table('log_transactions')->insert([
            'log_module' => $module,
            'log_type' => $type,
            'log_description' => $description,
            'created_by' => $user,
            'created_at' => now(),
        ]);
    }

    public function getSourceEntries(int $plantId): array
    {
        $entries = DB::connection('eudr_ts')->select(
            "SELECT bh.id_balance_head, bh.trace_no, m.description AS material,
                    sl.description AS tank, FORMAT(bh.qty, 3) AS qty
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

        return $entries;
    }

    public function getDestTanks(int $plantId): array
    {
        $plantId = $this->resolvePlantCode($plantId);

        $tanks = DB::connection('eudr_ts')->select(
            "SELECT DISTINCT description AS tank
               FROM m_sloc
              WHERE status = 1
                AND description LIKE '%FEED%'
                AND (CAST(? AS CHAR) = '0' OR CAST(id_plant AS CHAR) = CAST(? AS CHAR))
              ORDER BY description",
            [$plantId, $plantId]
        );

        return $tanks;
    }

    protected function resolvePlantCode($plantId)
    {
        if ($plantId) {
            $plant = Plant::find($plantId);
            if ($plant && $plant->code_3) {
                return $plant->code_3;
            }
        }
        return $plantId;
    }
}
