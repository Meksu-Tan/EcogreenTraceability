<?php

namespace App\Services;

use App\Models\Tank;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class TransferService
{
    protected $movSeq = '000';
    protected $movType1 = '1';
    protected $movType2 = '9';

    /**
     * Get Storage Tank Log
     * Uses same query as monorepo getRmList (get_dtRmList)
     */
    public function getStorageLog($plantId)
    {
        $idTankStorage = Tank::where('status', 1)
            ->where('code_3', 'STORAGE')
            ->value('id_tank');

        if (!$idTankStorage) return [];

        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        return DB::connection('eudr_ts')->select(
            'SELECT a.id_balance_head, a.id_material, a.id_tank, a.id_tank_tail, a.id_plant,
                    COALESCE(p.code_2, p.code_3, a.id_plant) AS plant_code,
                    COALESCE(p.description, p.code_2, a.id_plant) AS plant_name,
                    a.status,
                    CAST(a.trace_no AS CHAR) AS trace_no,
                    FORMAT(SUM(DISTINCT a.qty),3) AS qty,
                    a.created_by, a.created_at,
                    CONCAT(c.code, " :: ", c.description) AS material,
                    FORMAT(SUM(DISTINCT a.init_qty),3) AS init_qty,
                    CONCAT(d.description,
                        IF(GROUP_CONCAT(DISTINCT h.tf_number ORDER BY h.tf_number ASC SEPARATOR ", ") IS NULL,
                            "",
                            CONCAT(" | ", GROUP_CONCAT(DISTINCT h.tf_number ORDER BY h.tf_number ASC SEPARATOR ", "))
                        )
                    ) AS tf_number,
                    a.entry_date, b.batch_sap,
                    GROUP_CONCAT(DISTINCT b.id_balance_tail SEPARATOR ",") AS id_balance_detail,
                    GROUP_CONCAT(DISTINCT CONCAT(e.code, " :: ", e.description, " / ", b.batch_sap, " / Qty : ", FORMAT(b.init_qty, 3), " MT / ", IF(b.out_qty = 0, "-", "BATCH TRANSFERRED")) SEPARATOR " | ") AS supplier,
                    IF(b.out_qty = 0, "N/A", "") AS traced,
                    f.material_document, f.po_so, f.id_trace_head,
                    FORMAT(bs.supplier_qty,3) AS balance_supplier
               FROM t_balance_header a
               LEFT JOIN t_balance_detail b
                 ON a.id_balance_head = b.id_balance_head AND b.status = 1
               LEFT JOIN m_material c
                 ON a.id_material = c.id_material
               LEFT JOIN m_plant p
                 ON p.code_3 = a.id_plant
               LEFT JOIN m_tank d
                 ON a.id_tank = d.id_tank AND d.status = 1 AND (d.code_3 = "STORAGE" OR d.id_plant = ? OR ? = 0)
               LEFT JOIN m_supplier e
                 ON e.id_supplier = b.id_supplier
               LEFT JOIN (SELECT f.id_balance_head, g.material_document, g.po_so, f.id_trace_head
                            FROM t_trace_header f
                            LEFT JOIN t_material_document g
                              ON f.id_trace_head = g.id_trace_head
                           WHERE f.status = 1
                           GROUP BY f.id_balance_head) f
                 ON f.id_balance_head = a.id_balance_head
               LEFT JOIN m_tank_detail h
                 ON (JSON_CONTAINS(a.id_tank_tail, JSON_QUOTE(CAST(h.id_tank_tail AS CHAR)))
                      OR JSON_CONTAINS(a.id_tank_tail, CAST(h.id_tank_tail AS JSON)))
               LEFT JOIN (
                   SELECT id_balance_head, SUM(init_qty) AS supplier_qty
                   FROM t_balance_detail
                   WHERE status = 1
                   GROUP BY id_balance_head
               ) bs ON bs.id_balance_head = a.id_balance_head
              WHERE c.type = "RM"
                AND (SUBSTRING(a.trace_no,1,1) = ? OR SUBSTRING(a.trace_no,1,1) = ?)
                AND SUBSTRING(a.trace_no,8,3) = ?
                AND a.status = 1
                AND (a.id_plant = ? OR ? = 0)
                AND a.id_tank = ?
              GROUP BY a.trace_no
              ORDER BY a.id_balance_head DESC',
            [
                $plantId, $plantId,
                $this->movType1, $this->movType2, $this->movSeq,
                $plantId, $plantId,
                $idTankStorage
            ]
        );
    }

    /**
     * Get Feed Tank Log
     * Based on monorepo get_dtRmListTrf (RawMaterial.php)
     */
    public function getFeedLog($plantId)
    {
        $idTankFeed = Tank::where('status', 1)
            ->where('code_3', 'FEED')
            ->when($plantId && (string)$plantId !== '0', fn($q) => $q->where('id_plant', $plantId))
            ->value('id_tank');

        if (!$idTankFeed) return [];

        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        return DB::connection('eudr_ts')->select(
            'SELECT a.id_balance_head, a.id_material, a.id_tank, a.id_tank_tail, a.status,
                    aa.qty, aa.init_qty, a.created_by, a.created_at,
                    CAST(a.trace_no AS CHAR) AS trace_nos,
                    GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                    CONCAT(d.description,
                        IF(GROUP_CONCAT(DISTINCT h.tf_number ORDER BY h.tf_number ASC SEPARATOR ", ") IS NULL,
                            "",
                            CONCAT(" | ", GROUP_CONCAT(DISTINCT h.tf_number ORDER BY h.tf_number ASC SEPARATOR ", "))
                        )
                    ) AS tf_number,
                    a.entry_date, b.batch_sap,
                    GROUP_CONCAT(DISTINCT b.id_balance_tail SEPARATOR ",") AS id_balance_detail,
                    GROUP_CONCAT(DISTINCT CONCAT(e.code, " :: ", e.description, " / ", b.batch_sap, " / Qty : ", FORMAT(b.init_qty, 3), " MT / ", IF(b.out_qty = 0, "-", "BATCH USED IN WIP")) SEPARATOR " | ") AS supplier,
                    IF(b.out_qty = 0, "N/A", "") AS traced,
                    f.material_document, f.po_so, f.id_trace_head,
                    IFNULL(f.trace_no, CONCAT(a.trace_no, "|")) AS trace_no,
                    FORMAT(bs.supplier_qty,3) AS balance_supplier,
                    a.id_plant,
                    COALESCE(p.code_2, p.code_3, a.id_plant) AS plant_code,
                    COALESCE(p.description, p.code_2, a.id_plant) AS plant_name
               FROM t_balance_header a
               LEFT JOIN (SELECT trace_no,
                                 FORMAT(SUM(qty),3) AS qty,
                                 FORMAT(SUM(init_qty),3) AS init_qty
                            FROM t_balance_header
                           WHERE `status` = 1
                             AND (SUBSTRING(trace_no,1,1) = ? OR SUBSTRING(trace_no,1,1) = ?)
                             AND id_tank = ?
                           GROUP BY trace_no) aa
                 ON a.trace_no = aa.trace_no
               LEFT JOIN t_balance_detail b
                 ON a.id_balance_head = b.id_balance_head AND b.status = 1
               LEFT JOIN m_material c
                 ON a.id_material = c.id_material
               LEFT JOIN m_tank d
                 ON a.id_tank = d.id_tank AND d.status = 1 AND (d.id_plant = ? OR ? = 0)
               LEFT JOIN m_supplier e
                 ON e.id_supplier = b.id_supplier
               LEFT JOIN (SELECT f.id_balance_head, g.material_document, g.po_so, f.id_trace_head,
                                 GROUP_CONCAT(DISTINCT CONCAT(CAST(h.from_trace_no AS CHAR), " >>> ", CAST(f.to_trace_no AS CHAR)) SEPARATOR " | ") AS trace_no
                            FROM t_trace_header f
                            LEFT JOIN t_material_document g
                              ON f.id_trace_head = g.id_trace_head
                            LEFT JOIN t_trace_header h
                              ON f.from_trace_no = h.to_trace_no
                           WHERE f.status = 1
                             AND (SUBSTRING(f.to_trace_no,1,1) = ? OR SUBSTRING(f.to_trace_no,1,1) = ?)
                           GROUP BY f.id_balance_head) f
                 ON f.id_balance_head = a.id_balance_head
               LEFT JOIN m_tank_detail h
                 ON JSON_CONTAINS(a.id_tank_tail, JSON_QUOTE(CAST(h.id_tank_tail AS CHAR)))
               LEFT JOIN (
                   SELECT h.trace_no, SUM(d.init_qty) AS supplier_qty
                   FROM t_balance_header h
                   JOIN t_balance_detail d
                       ON h.id_balance_head = d.id_balance_head
                   WHERE d.status = 1
                   GROUP BY h.trace_no
               ) bs ON bs.trace_no = a.trace_no
               LEFT JOIN m_plant p ON p.code_3 = a.id_plant
              WHERE c.type = "RM"
                AND (SUBSTRING(a.trace_no,1,1) = ? OR SUBSTRING(a.trace_no,1,1) = ?)
                AND a.id_tank = ?
                AND a.status = 1
              GROUP BY a.trace_no
              ORDER BY a.id_balance_head DESC',
            [
                $this->movType1, $this->movType2, $idTankFeed,
                $plantId, $plantId,
                $this->movType1, $this->movType2,
                $this->movType1, $this->movType2, $idTankFeed
            ]
        );
    }

    /**
     * Deactivate/Delete a Feed Tank (Transfer) entry.
     * Ported from monorepo Transfer::transfer_destroy($id, $user)
     * $id format: "idHead|idTraceHead"
     */
    public function deactivateTransfer($id, $user)
    {
        $idTmp       = explode('|', $id);
        $idHead      = trim($idTmp[0]);
        $idTraceHead = trim($idTmp[1] ?? $idTmp[0]);

        try {
            DB::connection('eudr_ts')->beginTransaction();

            /* CHECK LOCK PERIOD */
            $entryDate = DB::connection('eudr_ts')->select(
                'SELECT entry_date FROM t_trace_header WHERE id_trace_head = ? AND `status` = 1',
                [$idTraceHead]
            );
            if (count($entryDate) == 0) {
                DB::connection('eudr_ts')->rollBack();
                return ['response' => 98, 'message' => 'Entry data not found'];
            }
            $curr_entryDate  = $entryDate[0]->entry_date;
            $lockDateTime    = new \DateTime($curr_entryDate);
            $lockYear        = $lockDateTime->format('Y');
            $lockMonth       = $lockDateTime->format('m');

            $datLock = DB::connection('eudr_ts')->select(
                'SELECT lock_status FROM t_report_pspa_head
                  WHERE `status` = 1 AND YEAR(`period`) = ? AND MONTH(`period`) = ?
                  UNION ALL SELECT "0" AS lock_status',
                [$lockYear, $lockMonth]
            );
            if ($datLock[0]->lock_status == 1) {
                DB::connection('eudr_ts')->rollBack();
                return ['response' => 99, 'message' => 'Period Locked'];
            }

            $counter       = 0;
            $maxIterations = 100;

            do {
                /* LOG */
                DB::connection('eudr_ts')->insert(
                    'INSERT INTO log_transactions (log_module, log_type, log_description, created_by)
                     VALUES (?, ?, ?, ?)',
                    ['TRANSFER_ENTRY', 'DE-ACTIVATE', 'IdBalHead: ' . $idHead . ' | Status: 1 >> 0', $user]
                );

                /* DEACTIVATE DESTINATION BALANCE (FEED TANK) */
                DB::connection('eudr_ts')->update(
                    'UPDATE t_balance_detail SET `status` = "0", `updated_by` = ? WHERE id_balance_head = ?',
                    [$user, $idHead]
                );
                DB::connection('eudr_ts')->update(
                    'UPDATE t_balance_header SET `status` = "0", `updated_by` = ? WHERE id_balance_head = ?',
                    [$user, $idHead]
                );

                /* GET SOURCE (STORAGE TANK) AND RESTORE STOCK */
                $datTraceHead = DB::connection('eudr_ts')->select(
                    'SELECT b.id_balance_head, b.out_qty, b.id_trace_head, a.id_material, a.in_qty,
                            DATE_FORMAT(a.`created_at`, "%Y-%m-%d %H:%i") AS created_at
                       FROM t_trace_header a
                       LEFT JOIN t_trace_header b
                           ON a.from_trace_no = b.to_trace_no AND b.status = 1
                      WHERE a.id_balance_head = ? AND a.status = 1',
                    [$idHead]
                );
                $lenTraceHead = count($datTraceHead);
                $createdAt    = $datTraceHead[0]->created_at ?? null;
                $idMaterial   = $datTraceHead[0]->id_material ?? null;
                $inQty        = $datTraceHead[0]->in_qty ?? 0;

                for ($i = 0; $i < $lenTraceHead; $i++) {
                    $idBalHead   = $datTraceHead[$i]->id_balance_head;
                    $idTracHead  = $datTraceHead[$i]->id_trace_head;
                    $outQtyHead  = $datTraceHead[$i]->out_qty;

                    /* RESTORE SOURCE BALANCE HEADER */
                    $datBalHeadSource = DB::connection('eudr_ts')->select(
                        'SELECT a.qty, a.out_qty FROM t_balance_header a WHERE a.status = 1 AND a.id_balance_head = ?',
                        [$idBalHead]
                    );
                    if (!empty($datBalHeadSource)) {
                        $outQtyBalHeadSource  = $datBalHeadSource[0]->out_qty - $outQtyHead;
                        $onhandQtyBalHeadSource = $datBalHeadSource[0]->qty + $outQtyHead;
                        DB::connection('eudr_ts')->update(
                            'UPDATE t_balance_header a SET a.qty = ?, a.out_qty = ?, a.`updated_by` = ? WHERE a.id_balance_head = ?',
                            [$onhandQtyBalHeadSource, $outQtyBalHeadSource, $user, $idBalHead]
                        );
                    }

                    /* GET TRACE DETAIL AND RESTORE SOURCE DETAIL */
                    $datTraceTail = DB::connection('eudr_ts')->select(
                        'SELECT a.id_balance_tail, a.out_qty, a.id_trace_tail
                           FROM t_trace_detail a WHERE a.id_trace_head = ? AND a.status = 1',
                        [$idTracHead]
                    );
                    $lenTraceTail = count($datTraceTail);

                    for ($j = 0; $j < $lenTraceTail; $j++) {
                        $idBalTail  = $datTraceTail[$j]->id_balance_tail;
                        $outQtyTail = $datTraceTail[$j]->out_qty;
                        $idTracTail = $datTraceTail[$j]->id_trace_tail;

                        $datBalTailSource = DB::connection('eudr_ts')->select(
                            'SELECT a.qty, a.out_qty FROM t_balance_detail a WHERE a.status = 1 AND a.id_balance_tail = ?',
                            [$idBalTail]
                        );
                        if (!empty($datBalTailSource)) {
                            $outQtyBalTailSource   = $datBalTailSource[0]->out_qty - $outQtyTail;
                            $onhandQtyBalTailSource = $datBalTailSource[0]->qty + $outQtyTail;
                            DB::connection('eudr_ts')->update(
                                'UPDATE t_balance_detail a SET a.qty = ?, a.out_qty = ?, a.`updated_by` = ? WHERE a.id_balance_tail = ?',
                                [$onhandQtyBalTailSource, $outQtyBalTailSource, $user, $idBalTail]
                            );
                        }

                        DB::connection('eudr_ts')->update(
                            'UPDATE t_trace_detail SET `status` = "0", `updated_by` = ? WHERE id_trace_tail = ?',
                            [$user, $idTracTail]
                        );
                    }

                    DB::connection('eudr_ts')->update(
                        'UPDATE t_trace_header SET `status` = "0", `updated_by` = ? WHERE id_trace_head = ?',
                        [$user, $idTracHead]
                    );
                }

                /* DEACTIVATE DESTINATION TRACE HEADER */
                DB::connection('eudr_ts')->update(
                    'UPDATE t_trace_header SET `status` = "0", `updated_by` = ? WHERE id_balance_head = ?',
                    [$user, $idHead]
                );
                DB::connection('eudr_ts')->update(
                    'UPDATE t_trace_detail SET `status` = "0", `updated_by` = ? WHERE id_trace_head = ?',
                    [$user, $idTraceHead]
                );

                /* CHECK FOR AUTO ADJUSTMENT IN */
                $datAdjustIn = DB::connection('eudr_ts')->select(
                    'SELECT id_balance_head, id_trace_head FROM t_trace_header
                      WHERE `status` = 1 AND `from_trace_no` IS NULL
                        AND SUBSTRING(`to_trace_no`,1,1) = 9
                        AND DATE_FORMAT(`created_at`, "%Y-%m-%d %H:%i") = ?
                        AND `id_material` = ? AND `in_qty` = ?',
                    [$createdAt, $idMaterial, $inQty]
                );
                if (!empty($datAdjustIn)) {
                    $adjBalHead   = $datAdjustIn[0]->id_balance_head;
                    $adjTraceHead = $datAdjustIn[0]->id_trace_head;
                    DB::connection('eudr_ts')->update('UPDATE t_trace_header SET `status` = 0, `updated_by` = ? WHERE `status` = 1 AND `id_trace_head` = ?', [$user, $adjTraceHead]);
                    DB::connection('eudr_ts')->update('UPDATE t_trace_detail SET `status` = 0, `updated_by` = ? WHERE `status` = 1 AND `id_trace_head` = ?', [$user, $adjTraceHead]);
                    DB::connection('eudr_ts')->update('UPDATE t_balance_header SET `status` = 0, `updated_by` = ? WHERE `status` = 1 AND `id_balance_head` = ?', [$user, $adjBalHead]);
                    DB::connection('eudr_ts')->update('UPDATE t_balance_detail SET `status` = 0, `updated_by` = ? WHERE `status` = 1 AND `id_balance_head` = ?', [$user, $adjBalHead]);
                }

                /* CHECK FOR AUTO TRF TO ADJUSTMENT OUT */
                $datAdjustOut = DB::connection('eudr_ts')->select(
                    'SELECT id_balance_head, id_trace_head FROM t_trace_header
                      WHERE `status` = 1 AND SUBSTRING(`to_trace_no`,1,1) = 7
                        AND DATE_FORMAT(`created_at`, "%Y-%m-%d %H:%i") = ?
                        AND `id_material` = ? AND `in_qty` = ?',
                    [$createdAt, $idMaterial, $inQty]
                );
                $lenAdjustOut = count($datAdjustOut);

                if ($lenAdjustOut > 0) {
                    $idHead      = $datAdjustOut[0]->id_balance_head;
                    $idTraceHead = $datAdjustOut[0]->id_trace_head;
                } else {
                    break;
                }

                if (++$counter >= $maxIterations) {
                    throw new Exception('Infinite loop detected in deactivateTransfer');
                }

            } while ($lenAdjustOut > 0);

            DB::connection('eudr_ts')->commit();

            return ['response' => 1, 'message' => 'Transfer deactivated successfully'];

        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            Log::error('Transfer Deactivate Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Deactivate RM Entry (Storage Tank)
     * Ported from monorepo RawMaterial::deactivateRmEntry
     */
    public function deactivateRmEntry($id, $user)
    {
        /* CHECK LOCK PERIOD */
        $entryDate = DB::connection('eudr_ts')->select(
            'SELECT entry_date FROM t_trace_header WHERE id_balance_head = ? AND `status` = 1',
            [$id]
        );
        if (empty($entryDate)) {
            return ['response' => 98, 'message' => 'Entry data not found'];
        }
        $curr_entryDate = $entryDate[0]->entry_date;
        $lockDateTime   = new \DateTime($curr_entryDate);
        $lockYear       = $lockDateTime->format('Y');
        $lockMonth      = $lockDateTime->format('m');

        $datLock = DB::connection('eudr_ts')->select(
            'SELECT lock_status FROM t_report_pspa_head
              WHERE `status` = 1 AND YEAR(`period`) = ? AND MONTH(`period`) = ?
              UNION ALL SELECT "0" AS lock_status',
            [$lockYear, $lockMonth]
        );
        if ($datLock[0]->lock_status == 1) {
            return ['response' => 99, 'message' => 'Period Locked'];
        }

        /* CHECK IF USED (out_qty <> 0) */
        $dat = DB::connection('eudr_ts')->select(
            'SELECT COUNT(a.id_trace_head) AS used FROM t_trace_header a
              WHERE a.id_balance_head = ? AND a.out_qty <> 0 AND a.status = 1',
            [$id]
        );
        if ($dat[0]->used > 0) {
            return ['response' => 3, 'message' => 'RM Entry has been used'];
        }

        /* LOG */
        DB::connection('eudr_ts')->insert(
            'INSERT INTO log_transactions (log_module, log_type, log_description, created_by)
             VALUES (?, ?, ?, ?)',
            ['RM_ENTRY', 'DE-ACTIVATE', 'Id: ' . $id . ' | Status: 1 >> 0', $user]
        );

        DB::connection('eudr_ts')->update('UPDATE t_balance_detail SET `status` = "0", `updated_by` = ? WHERE id_balance_head = ?', [$user, $id]);
        DB::connection('eudr_ts')->update('UPDATE t_balance_header SET `status` = "0", `updated_by` = ? WHERE id_balance_head = ?', [$user, $id]);

        $datTraceHead = DB::connection('eudr_ts')->select(
            'SELECT id_trace_head FROM t_trace_header WHERE `status` = 1 AND id_balance_head = ?',
            [$id]
        );
        $idTraceHead = $datTraceHead[0]->id_trace_head ?? null;

        DB::connection('eudr_ts')->update('UPDATE t_trace_header SET `status` = "0", `updated_by` = ? WHERE id_balance_head = ?', [$user, $id]);
        if ($idTraceHead) {
            DB::connection('eudr_ts')->update('UPDATE t_trace_detail SET `status` = "0", `updated_by` = ? WHERE id_trace_head = ?', [$user, $idTraceHead]);
        }

        return ['response' => 1, 'message' => 'RM Entry deactivated successfully'];
    }

    /**
     * Update RM Entry (Storage Tank)
     * Ported from monorepo RawMaterial::post_rmEntry mode=UPDATE
     */
    public function updateRmEntry($data, $user)
    {
        $idHead       = $data['id_balance_head'];
        $id_tank      = $data['id_tank'];
        $id_tank_tail = json_encode($data['id_tank_tail']);
        $entry_date   = $data['entry_date'];
        $qty          = floatval(str_replace(',', '', $data['total_qty'] ?? $data['qty'] ?? 0));
        $materialDoc  = $data['material_document'] ?? null;
        $po           = $data['po_so'] ?? null;
        $id_material  = $data['id_material'];

        /* CHECK LOCK PERIOD */
        $lockDateTime = new \DateTime($entry_date);
        $lockYear     = $lockDateTime->format('Y');
        $lockMonth    = $lockDateTime->format('m');
        $datLock = DB::connection('eudr_ts')->select(
            'SELECT lock_status FROM t_report_pspa_head WHERE `status` = 1 AND YEAR(`period`) = ? AND MONTH(`period`) = ? UNION ALL SELECT "0" AS lock_status',
            [$lockYear, $lockMonth]
        );
        if ($datLock[0]->lock_status == 1) {
            return ['response' => 99, 'message' => 'Period Locked'];
        }

        DB::connection('eudr_ts')->update(
            'UPDATE t_balance_header
                SET id_tank = ?, id_tank_tail = ?, entry_date = ?, qty = ?, in_qty = ?, init_qty = ?, updated_by = ?
              WHERE id_balance_head = ?',
            [$id_tank, $id_tank_tail, $entry_date, $qty, $qty, $qty, $user, $idHead]
        );

        DB::connection('eudr_ts')->update(
            'UPDATE t_trace_header
                SET id_sloc = ?, id_tank_tail = ?, entry_date = ?, in_qty = ?, updated_by = ?
              WHERE id_balance_head = ?',
            [$id_tank, $id_tank_tail, $entry_date, $qty, $user, $idHead]
        );

        /* UPDATE MATERIAL DOCUMENT */
        $existing = DB::connection('eudr_ts')->select(
            'SELECT id_matdoc FROM t_material_document WHERE id_trace_head = (SELECT id_trace_head FROM t_trace_header WHERE id_balance_head = ? LIMIT 1)',
            [$idHead]
        );
        if (!empty($existing)) {
            DB::connection('eudr_ts')->update(
                'UPDATE t_material_document SET material_document = ?, po_so = ?, updated_by = ?
                  WHERE id_trace_head = (SELECT id_trace_head FROM t_trace_header WHERE id_balance_head = ? LIMIT 1)',
                [$materialDoc, $po, $user, $idHead]
            );
        } elseif ($materialDoc) {
            $idTraceHeadRow = DB::connection('eudr_ts')->select(
                'SELECT id_trace_head FROM t_trace_header WHERE id_balance_head = ? LIMIT 1',
                [$idHead]
            );
            if (!empty($idTraceHeadRow)) {
                DB::connection('eudr_ts')->insert(
                    'INSERT INTO t_material_document (id_trace_head, material_document, po_so, created_by) VALUES (?, ?, ?, ?)',
                    [$idTraceHeadRow[0]->id_trace_head, $materialDoc, $po, $user]
                );
            }
        }

        /* LOG */
        DB::connection('eudr_ts')->insert(
            'INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)',
            ['T_BALANCE_HEAD', 'UPDATE', 'IDHEAD: ' . $idHead . ' | MATERIAL: ' . $id_material . ' | Status: 1', $user]
        );

        return ['response' => 1, 'message' => 'RM Entry updated successfully'];
    }
}
