<?php declare(strict_types=1);

namespace Modules\TsBlending\Repositories;

use Modules\TsBlending\Repositories\Contracts\BlendingRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BlendingRepository implements BlendingRepositoryInterface
{
    protected $connection = 'eudr_ts';

    private function formatTankName(?string $name): ?string
    {
        if (!$name) return $name;
        
        if (stripos($name, 'ADJUSTMENT') !== false) {
            return str_ireplace(
                ['ADJUSTMENT IN', 'ADJUSTMENT OUT'],
                ['Adjustment IN', 'Adjustment OUT'],
                strtoupper($name)
            );
        }

        if (preg_match('/^(EOB|EOMB)\s*(\d*)\s*(FEED|PRODUCT|WIP|STORAGE|MPR)\s*(TANK)?/i', $name, $matches)) {
            $plantType = strtoupper($matches[1]);
            $plantNum = $matches[2];
            $type = strtoupper($matches[3]);
            
            if ($type !== 'WIP' && $type !== 'MPR') {
                $type = ucfirst(strtolower($type));
            }
            
            $plant = $plantType . ($plantNum ? ' ' . $plantNum : '');
            return trim($type . ' ' . $plant);
        }

        return $name;
    }

    public function getActiveMaterials(): Collection
    {
        DB::connection($this->connection)->select(
            'SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))'
        );

        return collect(DB::connection($this->connection)->select(
            'SELECT a.id_material, CONCAT(UPPER(a.description), " (", a.code, ")") AS material
               FROM m_material a
              WHERE a.status = 1
                AND a.id_rundown <> "-"
              GROUP BY a.code
              ORDER BY a.description ASC'
        ));
    }

    public function generateBlendingEntryNo(int $materialId, int $plantId): ?string
    {
        $result = DB::connection($this->connection)->select(
            'SELECT a.entryNo
               FROM (SELECT b.trace_no + 1 AS entryNo
                       FROM m_material a
                       LEFT JOIN t_balance_header b
                         ON a.id_rundown = SUBSTRING(b.trace_no, 8,3) AND b.status = 1
                      WHERE a.id_material = ?
                        AND SUBSTRING(b.trace_no, 1, 7) = CONCAT(8, DATE_FORMAT(CURDATE(), "%y%m%d"))
                        AND SUBSTRING(b.trace_no, 11, 2) = LPAD(RIGHT(?, 2), 2, "0")
                        AND a.status = 1
                      ORDER BY b.id_balance_head DESC
                      LIMIT 1) a
              UNION ALL
              SELECT CONCAT("8", DATE_FORMAT(CURDATE(), "%y%m%d"), IF(a.id_rundown <> "-", a.id_rundown, "000"), LPAD(RIGHT(?, 2), 2, "0"), "01") AS entryNo
                FROM m_material a
               WHERE a.status = 1
                 AND a.id_material = ?
               LIMIT 1',
            [$materialId, $plantId, $plantId, $materialId]
        );

        return $result[0]->entryNo ?? null;
    }

    public function getTotalStockMaterial(int $materialId, int $plantId): float
    {
        $result = DB::connection($this->connection)->select(
            'SELECT IFNULL(SUM(c.qty),0) AS total
               FROM m_material a
               LEFT JOIN (SELECT b.code, b.id_material
                            FROM m_material b WHERE b.status = 1) b
                 ON a.code = b.code
               LEFT JOIN (SELECT c.id_material, c.qty
                            FROM m_tank cc
                            LEFT JOIN t_balance_header c
                              ON c.id_tank = cc.id_tank
                           WHERE c.status = 1
                             AND cc.status = 1
                             AND (SUBSTRING(c.trace_no,1,1) IN (1,2,7,8,9))
                             AND cc.id_plant = ?
                         ) c
                 ON b.id_material = c.id_material
              WHERE a.status = 1
                AND a.id_material = ?',
            [$plantId, $materialId]
        );

        return (float) ($result[0]->total ?? 0);
    }

    public function getTotalQtyMaterial(string $mode, string $entryNo, ?int $idHead, int $plantId): float
    {
        if ($mode === 'ADD') {
            $result = DB::connection($this->connection)->select(
                'SELECT FORMAT(SUM(a.qty),3) AS total
                   FROM t_balance_temporary a
                  WHERE a.entry_no = ?
                    AND a.status = 1
                    AND a.id_plant = ?',
                [$entryNo, $plantId]
            );
        } else {
            $result = DB::connection($this->connection)->select(
                'SELECT FORMAT(SUM(a.qty),3) AS total
                   FROM t_balance_detail a
                  WHERE a.id_balance_head = ?
                    AND a.status = 1
                    AND a.id_plant = ?',
                [$idHead, $plantId]
            );
        }

        return (float) ($result[0]->total ?? 0);
    }

    public function getMaterialList(string $mode, string $entryNo, ?int $idHead, int $plantId): Collection
    {
        if ($mode === 'ADD') {
            return collect(DB::connection($this->connection)->select(
                'SELECT FORMAT(a.qty,3) AS qty, a.id_material,
                        CONCAT(c.code, " :: ", c.description) AS material,
                        a.id_balance_temp AS idTail, a.entry_no, ? AS mode
                   FROM t_balance_temporary a
                   LEFT JOIN m_material c ON a.id_material = c.id_material
                  WHERE a.entry_no = ?
                    AND a.status = 1
                    AND a.id_plant = ?',
                [$mode, $entryNo, $plantId]
            ));
        }

        return collect(DB::connection($this->connection)->select(
            'SELECT FORMAT(a.qty,3) AS qty, a.id_material,
                    CONCAT(d.code, " :: ", d.description) AS material,
                    a.id_balance_tail AS idTail, c.trace_no AS entry_no, ? AS mode
               FROM t_balance_detail a
               LEFT JOIN t_balance_header c ON a.id_balance_head = c.id_balance_head
               LEFT JOIN m_material d ON a.id_material = d.id_material
              WHERE a.id_balance_head = ?
                AND a.status = 1
                AND a.id_plant = ?
                AND c.id_plant = ?',
            [$mode, $idHead, $plantId, $plantId]
        ));
    }

    public function getTanks(?int $plantId = null): Collection
    {
        $query = 'SELECT id_sloc AS id_tank, description AS tank, id_plant
                    FROM m_sloc
                   WHERE status = 1';
        $params = [];

        if ($plantId && $plantId > 0) {
            $query .= ' AND id_plant = ?';
            $params[] = $plantId;
        }

        $query .= ' ORDER BY description ASC, id_sloc ASC';

        DB::connection($this->connection)->select(
            'SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))'
        );

        $result = collect(DB::connection($this->connection)->select($query, $params));
        
        return $result->map(function($item) {
            $item->tank = $this->formatTankName($item->tank);
            return $item;
        });
    }

    public function getTankDetails(string $tankDescription, ?int $plantId = null): Collection
    {
        $query = 'SELECT sd.id_sloc_tail AS id_tank_tail, sd.tf_number AS tankNo
                    FROM m_sloc s
                    LEFT JOIN m_sloc_detail sd ON s.id_sloc = sd.id_sloc AND sd.status = 1
                   WHERE s.status = 1
                     AND s.description = ?';
        $params = [$tankDescription];

        if ($plantId && $plantId > 0) {
            $query .= ' AND s.id_plant = ?';
            $params[] = $plantId;
        }

        $query .= ' ORDER BY sd.tf_number ASC';

        return collect(DB::connection($this->connection)->select($query, $params));
    }

    public function getBlendingList(int $plantId): Collection
    {
        DB::connection($this->connection)->select(
            'SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))'
        );

        return collect(DB::connection($this->connection)->select(
            'SELECT a.entry_date, b.material_document, a.id_tank, a.id_tank_tail,
                    CAST(a.trace_no AS CHAR) AS trace_no, FORMAT(a.qty,3) AS qty,
                    FORMAT(a.init_qty,3) AS init_qty, a.id_balance_head AS idHead,
                    CONCAT(c.description, " (", c.code, ")") AS material,
                    p.code_2 AS plant_name,
                    GROUP_CONCAT(DISTINCT CONCAT(f.description, " / ", e.batch_sap,
                        " / Qty : ", FORMAT(e.init_qty,3), " MT / Qty : ", FORMAT(e.qty,3), " MT")
                        SEPARATOR " | ") AS supplier,
                    CAST(b.from_trace_no AS CHAR) AS from_trace_no, b.id_trace_head AS idTraceHead,
                    b.is_last_row, b.next_process,
                    COALESCE(
                        GROUP_CONCAT(DISTINCT COALESCE(h_sloc.id_tank, h.tf_number) ORDER BY COALESCE(h_sloc.id_tank, h.tf_number) ASC SEPARATOR " & "),
                        d.description
                    ) AS sloc,
                    FORMAT(ROUND(ee.init_qty,4),3) as balance_supplier
               FROM t_balance_header a
               LEFT JOIN (
                   SELECT b.id_balance_head, b.id_trace_head, c.from_trace_no, d.material_document,
                          CASE WHEN b.to_trace_no = (SELECT to_trace_no FROM t_trace_header
                                  WHERE SUBSTRING(to_trace_no, 1, 1) = 8
                                    AND SUBSTRING(to_trace_no, 9, 1) <> 0
                                    AND status = 1
                                  ORDER BY to_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS is_last_row,
                          CASE WHEN b.to_trace_no = (SELECT from_trace_no FROM t_trace_header
                                  WHERE from_trace_no = b.to_trace_no AND status = 1
                                  ORDER BY from_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS next_process
                     FROM t_trace_header b
                     LEFT JOIN (
                         SELECT c.to_trace_no, c.id_balance_head,
                                GROUP_CONCAT(CONCAT(c.from_trace_no, " :: ", cc.description,
                                    " (", cc.code, ") - Qty ", FORMAT(c.out_qty,3), " MT") SEPARATOR "|") AS from_trace_no
                           FROM t_trace_header c
                           LEFT JOIN m_material cc ON c.id_material = cc.id_material
                          WHERE c.status = 1
                            AND SUBSTRING(c.to_trace_no,1,1) = 8
                            AND SUBSTRING(c.to_trace_no,9,1) = 0
                          GROUP BY c.to_trace_no
                     ) c ON b.from_trace_no = c.to_trace_no
                     LEFT JOIN t_material_document d ON d.id_trace_head = b.id_trace_head
                    WHERE b.status = 1
                      AND SUBSTRING(b.to_trace_no,1,1) = 8
                      AND SUBSTRING(b.from_trace_no,1,1) = 8
               ) b ON a.id_balance_head = b.id_balance_head
               LEFT JOIN m_material c ON c.id_material = a.id_material
               LEFT JOIN m_tank d ON d.id_tank = a.id_tank AND d.id_plant = a.id_plant
               LEFT JOIN m_plant p ON a.id_plant = p.code_3 AND p.status = 1
               LEFT JOIN t_balance_detail e ON a.id_balance_head = e.id_balance_head
               LEFT JOIN (
                   SELECT ee1.trace_no, SUM(ee2.init_qty) AS init_qty
                     FROM t_balance_header ee1
                     LEFT JOIN t_balance_detail ee2 ON ee1.id_balance_head = ee2.id_balance_head
                    WHERE ee1.status = 1
                    GROUP BY ee1.trace_no
               ) ee ON a.trace_no = ee.trace_no
               LEFT JOIN m_supplier f ON e.id_supplier = f.id_supplier
               LEFT JOIN m_tank_detail h ON JSON_CONTAINS(IF(JSON_VALID(a.id_tank_tail), a.id_tank_tail, \'[]\'), JSON_QUOTE(CAST(h.id_tank_tail AS CHAR)))
               LEFT JOIN m_sloc h_sloc ON JSON_CONTAINS(IF(JSON_VALID(a.id_tank_tail), a.id_tank_tail, \'[]\'), JSON_QUOTE(CONCAT("S", CAST(h_sloc.id_sloc AS CHAR))))
              WHERE a.status = 1
                AND SUBSTRING(a.trace_no,1,1) = 8
                AND (a.id_plant = ? OR ? = 0)
              GROUP BY a.trace_no
              ORDER BY a.trace_no DESC',
            [$plantId, $plantId]
        ));
    }

    public function getActiveTanksRundown(int $materialId, int $plantId): Collection
    {
        DB::connection($this->connection)->select(
            'SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))'
        );

        return collect(DB::connection($this->connection)->select(
            'SELECT b.id_tank, b.description AS tank
               FROM m_material a
               LEFT JOIN m_tank b ON a.type = b.code_2 AND b.status = 1 AND b.id_plant = ?
              WHERE a.status = 1
                AND a.id_material = ?
              GROUP BY b.id_tank',
            [$plantId, $materialId]
        ));
    }

    public function getAllTanks(int $plantId): Collection
    {
        DB::connection($this->connection)->select(
            'SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))'
        );

        $query = 'SELECT a.id_tank,
                         COALESCE(s.description, a.description) AS tank,
                         a.id_plant
                    FROM m_tank a
                    LEFT JOIN m_sloc s ON a.id_tank = s.id_tank AND s.status = 1
                   WHERE a.status = 1';
        $params = [];

        if ($plantId > 0) {
            $query .= ' AND a.id_plant = ?';
            $params[] = $plantId;
        }

        $query .= ' ORDER BY COALESCE(s.description, a.description) ASC';

        $result = collect(DB::connection($this->connection)->select($query, $params));
        
        return $result->map(function($item) {
            $item->tank = $this->formatTankName($item->tank);
            return $item;
        });
    }

    public function getActiveSpecificTanksRundown(int $sloc): Collection
    {
        $tank = DB::connection($this->connection)->select('SELECT description, id_plant FROM m_tank WHERE id_tank = ?', [$sloc]);
        if (empty($tank)) {
            return collect([]);
        }
        $formattedName = $this->formatTankName($tank[0]->description);
        $plantId = $tank[0]->id_plant;

        return collect(DB::connection($this->connection)->select(
            'SELECT id_sloc AS id_sloc_tail, CONCAT("S", id_sloc) AS id_tank_tail, id_tank AS tankNo
               FROM m_sloc
              WHERE status = 1
                AND description = ?
                AND id_plant = ?
              ORDER BY id_tank ASC',
            [$formattedName, $plantId]
        ));
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
        $lockDateTime = new \DateTime($entryDate);
        $lockYear = $lockDateTime->format('Y');
        $lockMonth = $lockDateTime->format('m');

        $result = DB::connection($this->connection)->select(
            'SELECT lock_status
               FROM t_report_pspa_head
              WHERE status = 1
                AND YEAR(period) = ?
                AND MONTH(period) = ?
              UNION ALL
              SELECT "0" AS lock_status',
            [$lockYear, $lockMonth]
        );

        return $result[0]->lock_status == 1;
    }

    public function createMaterialDocument(string $user, int $idTraceHead, ?string $materialDoc, string $mode): array
    {
        if ($mode === 'ADD') {
            DB::connection($this->connection)->insert(
                'INSERT INTO t_material_document (id_trace_head, material_document, created_by)
                 VALUES (?, ?, ?)',
                [$idTraceHead, $materialDoc, $user]
            );

            $id = DB::connection($this->connection)->select(
                'SELECT id_matdoc FROM t_material_document ORDER BY id_matdoc DESC LIMIT 1'
            );

            $this->logTransaction('T_MATERIAL_DOCUMENT', 'ADD',
                'ID: ' . $id[0]->id_matdoc . ' | IDTRACEHEAD: ' . $idTraceHead . ' / DOC_NO: ' . $materialDoc,
                $user);

            return ['response' => 1];
        }

        $dat = DB::connection($this->connection)->select(
            'SELECT id_matdoc, material_document FROM t_material_document WHERE id_trace_head = ?',
            [$idTraceHead]
        );

        if (empty($dat)) {
            return ['response' => 0];
        }

        $id_matdoc = $dat[0]->id_matdoc;
        $old_materialDoc = $dat[0]->material_document;

        DB::connection($this->connection)->update(
            'UPDATE t_material_document SET material_document = ?, updated_by = ? WHERE id_trace_head = ?',
            [$materialDoc, $user, $idTraceHead]
        );

        $this->logTransaction('T_MATERIAL_DOCUMENT', 'UPDATE',
            'ID: ' . $id_matdoc . ' | IDTRACEHEAD: ' . $idTraceHead . ' / DOC_NO: ' . $old_materialDoc . ' >>> ' . $materialDoc,
            $user);

        return ['response' => 1];
    }

    public function deactivateBlending(string $id, string $user): array
    {
        $idTmp = explode("|", $id);
        $idHead = trim($idTmp[0]);
        $idTraceHead = trim($idTmp[1]);

        // Get entry date for lock check
        $entryDate = DB::connection($this->connection)->select(
            'SELECT entry_date FROM t_trace_header WHERE id_trace_head = ? AND status = 1',
            [$idTraceHead]
        );

        if (empty($entryDate)) {
            return ['response' => 98];
        }

        $curr_entryDate = $entryDate[0]->entry_date;
        $lockDateTime = new \DateTime($curr_entryDate);
        $lockYear = $lockDateTime->format('Y');
        $lockMonth = $lockDateTime->format('m');

        $datLock = DB::connection($this->connection)->select(
            'SELECT lock_status FROM t_report_pspa_head
              WHERE status = 1 AND YEAR(period) = ? AND MONTH(period) = ?
              UNION ALL SELECT "0" AS lock_status',
            [$lockYear, $lockMonth]
        );

        if ($datLock[0]->lock_status == 1) {
            return ['response' => 99];
        }

        $this->logTransaction('BLENDING_ENTRY', 'DE-ACTIVATE', 'IdBalHead: ' . $idHead . ' | Status: 1 >> 0', $user);

        DB::connection($this->connection)->update(
            'UPDATE t_balance_detail SET status = "0", updated_by = ? WHERE id_balance_head = ?',
            [$user, $idHead]
        );
        DB::connection($this->connection)->update(
            'UPDATE t_balance_header SET status = "0", updated_by = ? WHERE id_balance_head = ?',
            [$user, $idHead]
        );

        // Get and restore source blending
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

                // Restore trace details
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
                        'UPDATE t_trace_detail SET status = "0", updated_by = ? WHERE id_trace_tail = ?',
                        [$user, $tail->id_trace_tail]
                    );
                }

                DB::connection($this->connection)->update(
                    'UPDATE t_trace_header SET status = "0", updated_by = ? WHERE id_trace_head = ?',
                    [$user, $row->id_trace_head]
                );
            }
        }

        DB::connection($this->connection)->update(
            'UPDATE t_trace_header SET status = "0", updated_by = ? WHERE id_balance_head = ?',
            [$user, $idHead]
        );
        DB::connection($this->connection)->update(
            'UPDATE t_trace_detail SET status = "0", updated_by = ? WHERE id_trace_head = ?',
            [$user, $idTraceHead]
        );

        return ['response' => 1];
    }

    public function updateEntrySubTank(string $user, int $idHead, array $tails): array
    {
        if (!is_array($tails)) {
            return ['response' => 0, 'message' => 'INVALID SUBTANK DATA'];
        }

        $jsonTails = json_encode(array_values(array_unique($tails)));

        $row = DB::connection($this->connection)->selectOne(
            'SELECT id_tank_tail, trace_no FROM t_balance_header WHERE id_balance_head = ? AND status = 1',
            [$idHead]
        );

        if (!$row) {
            return ['response' => 0, 'message' => 'BALANCE HEAD NOT FOUND'];
        }

        DB::connection($this->connection)->update(
            'UPDATE t_balance_header SET id_tank_tail = ?, updated_by = ? WHERE id_balance_head = ?',
            [$jsonTails, $user, $idHead]
        );
        DB::connection($this->connection)->update(
            'UPDATE t_trace_header SET id_tank_tail = ?, updated_by = ? WHERE id_balance_head = ?',
            [$jsonTails, $user, $idHead]
        );
        DB::connection($this->connection)->update(
            'UPDATE t_balance_detail SET id_tank_tail = ?, updated_by = ? WHERE id_balance_head = ?',
            [$jsonTails, $user, $idHead]
        );

        $this->logTransaction('T_BALANCE_HEAD', 'UPDATE_SUBTANK',
            'IDHEAD: ' . $idHead . ' | TRACE: ' . $row->trace_no . ' | SUBTANKS: ' . implode(',', $tails),
            $user);

        return ['response' => 1];
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

    protected function logTransaction(string $module, string $type, string $description, string $user): void
    {
        DB::connection($this->connection)->insert(
            'INSERT INTO log_transactions (log_module, log_type, log_description, created_by)
             VALUES (?, ?, ?, ?)',
            [$module, $type, $description, $user]
        );
    }
}