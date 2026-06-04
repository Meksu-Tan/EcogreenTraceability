<?php declare(strict_types=1);
namespace Modules\TsTransfer\Repositories;

use Modules\TsTransfer\Repositories\Contracts\TransferRepositoryInterface;
use Modules\Shared\Services\PeriodLockService;
use Modules\Shared\Services\AuditService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransferRepository implements TransferRepositoryInterface
{
    protected string $connection = 'eudr_ts';

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
            'SELECT a.id_material, CONCAT(UPPER(a.description), " (", a.code, " - ", a.type, ")") AS material
               FROM m_material a
              WHERE a.status = 1
                AND a.id_rundown <> "-"
              GROUP BY a.code
              ORDER BY a.description ASC'
        ));
    }

    public function generateTransferEntryNo(int $materialId, int $plantId): ?string
    {
        $result = DB::connection($this->connection)->select(
            'SELECT a.trace_no AS entryNo
               FROM (SELECT b.to_trace_no + 1 AS trace_no
                       FROM m_material a
                       LEFT JOIN t_trace_header b
                         ON a.id_rundown = SUBSTRING(b.to_trace_no, 8, 3)
                        AND b.to_trace_no = b.to_trace_no
                        AND b.status = 1
                      WHERE a.id_material = ?
                        AND SUBSTRING(b.to_trace_no, 1, 1) = 7
                        AND SUBSTRING(b.to_trace_no, 2, 6) = DATE_FORMAT(CURDATE(), "%y%m%d")
                        AND SUBSTRING(b.to_trace_no, 11, 2) = LPAD(RIGHT(?, 2), 2, "0")
                        AND a.status = 1
                      ORDER BY b.id_trace_head DESC
                      LIMIT 1) a
              UNION ALL
              SELECT CONCAT("7", DATE_FORMAT(CURDATE(), "%y%m%d"),
                      IF(a.id_rundown <> "-", a.id_rundown, "000"),
                      LPAD(RIGHT(?, 2), 2, "0"), "01") AS trace_no
                FROM m_material a
               WHERE a.status = 1
                 AND a.id_material = ?
               LIMIT 1',
            [$materialId, $plantId, $plantId, $materialId]
        );

        return $result[0]->entryNo ?? null;
    }

    public function getTotalStockMaterial(int $materialId, int $tankId, int $plantId): float
    {
        $result = DB::connection($this->connection)->select(
            'SELECT IFNULL(ROUND(SUM(c.in_qty) - SUM(c.out_qty), 3), 0) AS total
               FROM m_material a
               LEFT JOIN (SELECT b.code, b.id_material
                            FROM m_material b
                           WHERE b.status = 1) b
                 ON a.code = b.code
               LEFT JOIN (SELECT c.id_material, c.in_qty, c.out_qty
                            FROM t_trace_header c
                           WHERE c.status = 1
                             AND c.id_sloc = ?
                          ) c
                 ON b.id_material = c.id_material
              WHERE a.status = 1
                AND a.id_material = ?',
            [$tankId, $materialId]
        );

        return (float) ($result[0]->total ?? 0);
    }

    public function getTransferList(int $plantId): Collection
    {
        DB::connection($this->connection)->select(
            'SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))'
        );

        $result = collect(DB::connection($this->connection)->select(
            'SELECT a.entry_date, b.material_document,
                    th_from.id_balance_head AS fromIdHead, th_from.id_sloc AS from_id_tank,
                    t_from.description AS from_sloc_name,
                    GROUP_CONCAT(DISTINCT h_from.tf_number ORDER BY h_from.tf_number SEPARATOR ", ") AS from_tf_number,
                    t_to.description AS to_sloc_name,
                    GROUP_CONCAT(DISTINCT h_to.tf_number ORDER BY h_to.tf_number SEPARATOR ", ") AS to_tf_number,
                    CAST(a.trace_no AS CHAR) AS trace_no,
                    FORMAT(ROUND(a.qty,3),3) AS qty, FORMAT(ROUND(a.init_qty,3),3) AS init_qty,
                    a.id_balance_head AS idHead,
                    CONCAT(c.description, " (", c.code, ")") AS material,
                    CASE SUBSTRING(a.trace_no, 11, 2)
                        WHEN "01" THEN "EOMB"
                        WHEN "02" THEN "EOB1"
                        WHEN "03" THEN "EOB2"
                        WHEN "05" THEN "EOB5"
                        WHEN "07" THEN "EOB3"
                        ELSE p.code_2
                    END AS plant_name,
                    SUBSTRING(a.trace_no, 11, 2) AS plant_code_from_trace,
                    b.id_trace_head AS idTraceHead, b.is_last_row, b.next_process,
                    FORMAT(ROUND(a.in_qty,3),3) AS in_qty, FORMAT(ROUND(a.out_qty,3),3) AS out_qty,
                    GROUP_CONCAT(DISTINCT CONCAT(f.description, " / ", e.batch_sap,
                        " / Qty : ", ROUND(e.init_qty,3), " MT / Qty : ", ROUND(e.qty,3), " MT")
                        SEPARATOR " | ") AS supplier,
                    IF(ABS(COALESCE(bs.init_qty,0) - a.init_qty) > 0.005, FORMAT(COALESCE(bs.init_qty,0),3), FORMAT(a.init_qty,3)) AS balance_supplier,
                    CONCAT(
                        COALESCE(GROUP_CONCAT(DISTINCT h_from.tf_number ORDER BY h_from.tf_number SEPARATOR " & "), t_from.description, ""),
                        " >>> ",
                        COALESCE(GROUP_CONCAT(DISTINCT h_to.tf_number ORDER BY h_to.tf_number SEPARATOR " & "), t_to.description, "")
                    ) AS raw_sloc,
                    t_from.description AS raw_from_desc,
                    t_to.description AS raw_to_desc,
                    t_from.id_plant AS from_plant_id,
                    t_to.id_plant AS to_plant_id
               FROM t_balance_header a
               LEFT JOIN (SELECT b.id_balance_head, b.id_trace_head, b.from_trace_no,
                                 d.material_document,
                                 CASE
                                   WHEN b.to_trace_no = (SELECT c.to_trace_no
                                                           FROM t_trace_header c
                                                          WHERE SUBSTRING(c.to_trace_no,1,1) = 7
                                                            AND SUBSTRING(c.to_trace_no,9,1) <> 0
                                                            AND c.status = 1
                                                          ORDER BY c.to_trace_no DESC LIMIT 1) THEN 1
                                   ELSE NULL
                                 END AS is_last_row,
                                 CASE
                                   WHEN b.to_trace_no = (SELECT c.from_trace_no
                                                           FROM t_trace_header c
                                                          WHERE c.from_trace_no = b.to_trace_no
                                                            AND c.status = 1
                                                          ORDER BY c.from_trace_no DESC LIMIT 1) THEN 1
                                   ELSE NULL
                                 END AS next_process
                            FROM t_trace_header b
                            LEFT JOIN t_material_document d ON d.id_trace_head = b.id_trace_head
                           WHERE b.status = 1
                             AND SUBSTRING(b.to_trace_no,1,1) = 7
                             AND SUBSTRING(b.from_trace_no,1,1) = 7
                           GROUP BY b.id_balance_head) b
                 ON a.id_balance_head = b.id_balance_head
               LEFT JOIN m_material c ON c.id_material = a.id_material
               LEFT JOIN t_trace_header th_from ON th_from.to_trace_no = b.from_trace_no AND th_from.status = 1
               LEFT JOIN m_tank t_to ON t_to.id_tank = a.id_tank
               LEFT JOIN m_tank t_from ON t_from.id_tank = th_from.id_sloc
               LEFT JOIN m_plant p ON (t_from.id_plant = p.code_3 OR (t_from.id_plant IS NULL AND SUBSTRING(a.trace_no, 11, 2) = RIGHT(p.code_3, 2))) AND p.status = 1
               LEFT JOIN t_balance_detail e ON a.id_balance_head = e.id_balance_head AND e.status = 1
               LEFT JOIN m_supplier f ON e.id_supplier = f.id_supplier
               LEFT JOIN (SELECT h.trace_no, ROUND(SUM(d.init_qty),3) AS init_qty, ROUND(SUM(d.qty),3) AS qty
                            FROM t_balance_header h
                            JOIN t_balance_detail d ON d.id_balance_head = h.id_balance_head
                           WHERE d.status = 1 AND d.init_qty > 0.0001
                           GROUP BY h.trace_no) bs ON bs.trace_no = a.trace_no
               LEFT JOIN m_sloc_detail h_to ON JSON_CONTAINS(IF(JSON_VALID(a.id_tank_tail), a.id_tank_tail, IF(JSON_VALID(a.id_sloc_tail), a.id_sloc_tail, \'[]\')), JSON_QUOTE(CONCAT("S", CAST(h_to.id_sloc_tail AS CHAR))))
               LEFT JOIN m_sloc_detail h_from ON JSON_CONTAINS(IF(JSON_VALID(th_from.id_sloc_tail), th_from.id_sloc_tail, IF(JSON_VALID(th_from.id_tank_tail), th_from.id_tank_tail, \'[]\')), JSON_QUOTE(CONCAT("S", CAST(h_from.id_sloc_tail AS CHAR))))
              WHERE a.status = 1
                AND SUBSTRING(a.trace_no,1,1) = 7
                AND (t_from.id_plant = ? OR a.id_plant = ? OR ? = 0)
              GROUP BY a.trace_no
              ORDER BY a.trace_no DESC',
            [$plantId, $plantId, $plantId]
        ));

        $result = $result->map(function ($item) {
            $fromFormatted = $this->formatTankName($item->raw_from_desc);
            $toFormatted = $this->formatTankName($item->raw_to_desc);
            
            // Debug: Log current values
            \Log::info('TransferList Debug', [
                'trace_no' => $item->trace_no,
                'trace_digits_11_12' => substr($item->trace_no ?? '', 10, 2),
                'from_desc' => $item->raw_from_desc,
                'to_desc' => $item->raw_to_desc,
                'from_plant_id' => $item->from_plant_id ?? null,
                'to_plant_id' => $item->to_plant_id ?? null,
                'from_tf_number' => $item->from_tf_number ?? null,
                'to_tf_number' => $item->to_tf_number ?? null,
                'current_plant_name' => $item->plant_name ?? null,
                'raw_sloc' => $item->raw_sloc
            ]);
            
            // Build from and to parts using tf_number and description
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
            
            // Check if this is TRF IN to EOMB (destination is EOMB plant 1001)
            if (($item->to_plant_id ?? 0) == 1001) {
                // TRF IN to EOMB: specific sloc >>> EOMB
                $item->sloc = trim($fromPart) . " >>> EOMB";
            }
            // Check if this is TRF OUT from EOMB (source is EOMB plant 1001)
            elseif (($item->from_plant_id ?? 0) == 1001) {
                // TRF OUT from EOMB: EOMB >>> specific sloc
                $item->sloc = "EOMB >>> " . trim($toPart);
            }
            else {
                // Default: use original formatting
                $item->sloc = str_replace(
                    [
                        $item->raw_from_desc ?? 'UNKNOWN_FROM', 
                        $item->raw_to_desc ?? 'UNKNOWN_TO'
                    ],
                    [
                        $fromFormatted ?? '', 
                        $toFormatted ?? ''
                    ],
                    $item->raw_sloc
                );
            }
            
            return $item;
        });

        return $result;
    }

    public function getActiveTanksRundown(?int $materialId, int $plantId): Collection
    {
        DB::connection($this->connection)->select(
            'SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))'
        );

        if ($materialId === null) {
            $result = collect(DB::connection($this->connection)->select(
                'SELECT b.id_tank, b.description AS tank, b.id_plant
                   FROM m_tank b
                  WHERE b.status = 1
                    AND b.id_plant <> ?
                  GROUP BY b.id_tank
                  ORDER BY b.description ASC',
                [$plantId]
            ));
        } else {
            $result = collect(DB::connection($this->connection)->select(
                'SELECT b.id_tank, b.description AS tank, b.id_plant
                   FROM m_material a
                   LEFT JOIN m_tank b ON a.type = b.code_2 AND b.status = 1 AND b.id_plant = ?
                  WHERE a.status = 1
                    AND a.id_material = ?
                  GROUP BY b.id_tank',
                [$plantId, $materialId]
            ));
        }

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

    public function getLockStatus(string $entryDate): bool
    {
        // Use shared PeriodLockService for consistent date lock mechanism
        return PeriodLockService::isLocked($entryDate);
    }

    public function getUpdateSupplierMaterial(int $idMaterial, int $idTank, int $plantId): ?object
    {
        $datSeq = DB::connection($this->connection)->select(
            'SELECT LPAD(COALESCE(MAX(CAST(SUBSTRING(a.batch_sap,7,2) AS UNSIGNED)) + 1, 1), 2, 0) AS seq_no
               FROM t_balance_detail a
               LEFT JOIN t_balance_header b ON a.id_balance_head = b.id_balance_head
              WHERE a.status = 1
                AND SUBSTRING(a.batch_sap,1,6) = DATE_FORMAT(NOW(), "%y%m%d")
                AND SUBSTRING(b.trace_no,1,1) = 7',
            []
        );

        $seqNo = $datSeq[0]->seq_no ?? '01';

        $plantCode = DB::connection($this->connection)->table('m_plant')
            ->where('id_plant', $plantId)
            ->value('code_4');

        $result = DB::connection($this->connection)->select(
            'SELECT CONCAT(DATE_FORMAT(NOW(), "%y%m%d"), ?, b.code_4, UCASE(a.code_matl_supplier)) AS supplierCode,
                    COALESCE(c.id_supplier, 0) AS idSupplier
               FROM (SELECT a.code_matl_supplier FROM m_material a WHERE a.status = 1 AND a.id_material = ?) a
               JOIN (SELECT b.code_4 FROM m_tank b WHERE b.status = 1 AND b.id_tank = ?) b
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
                'ID: ' . ($id[0]->id_matdoc ?? '') . ' | IDTRACEHEAD: ' . $idTraceHead . ' / DOC_NO: ' . $materialDoc,
                $user
            );

            return ['response' => 1];
        }

        $dat = DB::connection($this->connection)->select(
            'SELECT id_matdoc, material_document FROM t_material_document WHERE id_trace_head = ?',
            [$idTraceHead]
        );

        if (empty($dat)) {
            return ['response' => 0];
        }

        $idMatdoc = $dat[0]->id_matdoc;
        $oldMaterialDoc = $dat[0]->material_document;

        DB::connection($this->connection)->update(
            'UPDATE t_material_document SET material_document = ?, updated_by = ? WHERE id_trace_head = ?',
            [$materialDoc, $user, $idTraceHead]
        );

        $this->logTransaction('T_MATERIAL_DOCUMENT', 'UPDATE',
            'ID: ' . $idMatdoc . ' | IDTRACEHEAD: ' . $idTraceHead . ' / DOC_NO: ' . $oldMaterialDoc . ' >>> ' . $materialDoc,
            $user
        );

        return ['response' => 1];
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
                    'UPDATE t_balance_detail SET status = "0", updated_by = ? WHERE id_balance_head = ?',
                    [$user, $idHead]
                );
                DB::connection($this->connection)->update(
                    'UPDATE t_balance_header SET status = "0", updated_by = ? WHERE id_balance_head = ?',
                    [$user, $idHead]
                );

                $datTraceHead = DB::connection($this->connection)->select(
                    'SELECT b.id_balance_head, b.out_qty, b.id_trace_head, a.id_material, a.in_qty,
                            DATE_FORMAT(a.created_at, "%Y-%m-%d %H:%i") AS created_at
                       FROM t_trace_header a
                       LEFT JOIN t_trace_header b ON a.from_trace_no = b.to_trace_no AND b.status = 1
                      WHERE a.id_balance_head = ? AND a.status = 1',
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
                            'UPDATE t_trace_detail SET status = "0", updated_by = ? WHERE id_trace_tail = ?',
                            [$user, $tail->id_trace_tail]
                        );
                    }

                    DB::connection($this->connection)->update(
                        'UPDATE t_trace_header SET status = "0", updated_by = ? WHERE id_trace_head = ?',
                        [$user, $idTracHead]
                    );
                }

                DB::connection($this->connection)->update(
                    'UPDATE t_trace_header SET status = "0", updated_by = ? WHERE id_balance_head = ?',
                    [$user, $idHead]
                );
                DB::connection($this->connection)->update(
                    'UPDATE t_trace_detail SET status = "0", updated_by = ? WHERE id_trace_head = ?',
                    [$user, $idTraceHead]
                );

                /* DESTROYING AUTO ADJUSTMENT IN (prefix 9) */
                $datAdjustIn = DB::connection($this->connection)->select(
                    'SELECT id_balance_head, id_trace_head
                       FROM t_trace_header
                      WHERE status = 1
                        AND from_trace_no IS NULL
                        AND SUBSTRING(to_trace_no,1,1) = 9
                        AND DATE_FORMAT(created_at, "%Y-%m-%d %H:%i") = ?
                        AND id_material = ?
                        AND in_qty = ?',
                    [$createdAt, $idMaterial, $inQty]
                );

                if (count($datAdjustIn) > 0) {
                    $idBalHead = $datAdjustIn[0]->id_balance_head;
                    $idTraceHead = $datAdjustIn[0]->id_trace_head;

                    DB::connection($this->connection)->update(
                        'UPDATE t_trace_header SET status = "0", updated_by = ? WHERE status = 1 AND id_trace_head = ?',
                        [$user, $idTraceHead]
                    );
                    DB::connection($this->connection)->update(
                        'UPDATE t_trace_detail SET status = "0", updated_by = ? WHERE status = 1 AND id_trace_head = ?',
                        [$user, $idTraceHead]
                    );
                    DB::connection($this->connection)->update(
                        'UPDATE t_balance_header SET status = "0", updated_by = ? WHERE status = 1 AND id_balance_head = ?',
                        [$user, $idBalHead]
                    );
                    DB::connection($this->connection)->update(
                        'UPDATE t_balance_detail SET status = "0", updated_by = ? WHERE status = 1 AND id_balance_head = ?',
                        [$user, $idBalHead]
                    );
                }

                /* DESTROYING AUTO TRF TO ADJUSTMENT OUT (prefix 7) */
                $datAdjustOut = DB::connection($this->connection)->select(
                    'SELECT id_balance_head, id_trace_head
                       FROM t_trace_header
                      WHERE status = 1
                        AND SUBSTRING(to_trace_no,1,1) = 7
                        AND DATE_FORMAT(created_at, "%Y-%m-%d %H:%i") = ?
                        AND id_material = ?
                        AND in_qty = ?',
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

        DB::connection($this->connection)->update(
            'UPDATE t_trace_detail SET id_tank_tail = ?, updated_by = ?
              WHERE id_trace_head IN (SELECT id_trace_head FROM t_trace_header WHERE id_balance_head = ?)',
            [$jsonTails, $user, $idHead]
        );

        $this->logTransaction('T_BALANCE_HEAD', 'UPDATE_SUBTANK',
            'IDHEAD: ' . $idHead . ' | TRACE: ' . $row->trace_no . ' | SUBTANKS: ' . implode(',', $tails),
            $user);

        return ['response' => 1];
    }

    public function checkTraceNoExists(string $traceNo): bool
    {
        $result = DB::connection($this->connection)->select(
            'SELECT COUNT(to_trace_no) AS cnt FROM t_trace_header WHERE status = 1 AND to_trace_no = ?',
            [$traceNo]
        );
        return ($result[0]->cnt ?? 0) > 0;
    }

    public function logTransaction(string $module, string $type, string $description, string $user): void
    {
        DB::connection($this->connection)->insert(
            'INSERT INTO log_transactions (log_module, log_type, log_description, created_by)
             VALUES (?, ?, ?, ?)',
            [$module, $type, $description, $user]
        );
    }
}
