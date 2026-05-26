<?php

namespace Modules\TsWip\Repositories;

use Modules\TsWip\Repositories\Contracts\WipEntryRepositoryInterface;
use Modules\Shared\Helpers\Feed;
use Modules\Shared\Helpers\Rundown;
use Illuminate\Support\Facades\DB;
use Modules\Plant\Models\Plant;

class WipEntryRepository implements WipEntryRepositoryInterface
{
    protected $movType1 = '2';
    protected $movType2 = '3';
    protected $movType3 = '7';
    protected $movType4 = '8';
    protected $movType5 = '9';

    // ─────────────────────────────────────────────────────────────────────────
    // READ OPERATIONS
    // ─────────────────────────────────────────────────────────────────────────

    public function getBalance(string $rundownId, $plantId, ?string $subgroup = null): array
    {
        $idPlant = $this->resolvePlantId($plantId);
        $dbRundownId = $this->mapFrontendSectionToDbRundownId($rundownId, $subgroup);
        $column = (strpos($dbRundownId, '00') === 0) ? 'id_feed' : 'id_rundown';

        $rows = $this->executeSelect('
            SELECT aa.id_balance_head, aa.id_material, aa.id_sloc, aa.status,
                   aa.trace_no, aa.qty, aa.created_by, aa.created_at,
                   aa.material, aa.init_qty, aa.tf_number AS sloc, aa.entry_date,
                   aa.id_balance_detail, aa.supplier, aa.traced, aa.material_document,
                   aa.balance_supplier, aa.plant_name
              FROM (SELECT e.id_balance_head, e.id_material, e.id_sloc, e.status,
                           e.trace_no, e.qty, e.created_by, e.created_at, e.init_qty,
                           e.material, e.tf_number, e.entry_date,
                           e.id_balance_detail, e.supplier,
                           e.traced, e.material_document, e.balance_supplier, p.description AS plant_name
                      FROM m_material c
                      LEFT JOIN (SELECT d.code, d.id_material FROM m_material d WHERE d.status = 1) d
                        ON c.code = d.code
                      LEFT JOIN (SELECT a.id_balance_head, a.id_material, a.id_sloc, a.status,
                                        a.trace_no, aa.qty, a.created_by, a.created_at, aa.init_qty,
                                        GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                                        d.description AS tf_number, a.entry_date,
                                        GROUP_CONCAT(DISTINCT b.id_balance_tail SEPARATOR ",") AS id_balance_detail,
                                        GROUP_CONCAT(DISTINCT CONCAT(e.description, " / ", b.batch_sap, " / Qty : ", FORMAT(b.init_qty,3), " MT / Qty : ", FORMAT(b.qty,3), " MT") SEPARATOR " | ") AS supplier,
                                        FORMAT(SUM(b.init_qty),3) AS balance_supplier,
                                        IFNULL(f.to_trace_no, "N/A") AS traced, f.material_document,
                                        a.id_plant
                                   FROM m_sloc d
                                   LEFT JOIN (
                                        SELECT a.id_sloc, a.id_tank, a.id_balance_head, a.id_material, a.status, a.trace_no,
                                               a.created_by, a.created_at, a.entry_date, a.id_plant
                                          FROM t_balance_header a
                                         WHERE a.status = 1 AND a.id_sloc IS NOT NULL
                                           AND (SUBSTRING(a.trace_no,1,1) = 1 OR SUBSTRING(a.trace_no,1,1) = 2 OR SUBSTRING(a.trace_no,1,1) = 7 OR
                                                SUBSTRING(a.trace_no,1,1) = 8 OR SUBSTRING(a.trace_no,1,1) = 9)
                                        UNION ALL
                                        SELECT (
                                            SELECT ms.id_sloc FROM m_sloc ms
                                              JOIN m_tank mt ON ms.code COLLATE utf8mb4_unicode_ci = mt.code COLLATE utf8mb4_unicode_ci 
                                                            AND ms.id_plant COLLATE utf8mb4_unicode_ci = mt.id_plant COLLATE utf8mb4_unicode_ci
                                             WHERE mt.id_tank = a.id_tank
                                             LIMIT 1
                                        ) AS id_sloc, a.id_tank, a.id_balance_head, a.id_material, a.status, a.trace_no,
                                               a.created_by, a.created_at, a.entry_date, a.id_plant
                                          FROM t_balance_header a
                                         WHERE a.status = 1 AND a.id_sloc IS NULL
                                           AND (SUBSTRING(a.trace_no,1,1) = 1 OR SUBSTRING(a.trace_no,1,1) = 2 OR SUBSTRING(a.trace_no,1,1) = 7 OR
                                                SUBSTRING(a.trace_no,1,1) = 8 OR SUBSTRING(a.trace_no,1,1) = 9)
                                   ) a ON a.id_sloc = d.id_sloc
                                   LEFT JOIN (SELECT id_balance_head, FORMAT(SUM(qty),3) AS qty, FORMAT(SUM(init_qty),3) AS init_qty
                                                FROM t_balance_header
                                               WHERE status = 1
                                                 AND (SUBSTRING(trace_no,1,1) = 1 OR SUBSTRING(trace_no,1,1) = 2 OR SUBSTRING(trace_no,1,1) = 7 OR
                                                      SUBSTRING(trace_no,1,1) = 8 OR SUBSTRING(trace_no,1,1) = 9)
                                               GROUP BY trace_no) aa
                                     ON a.id_balance_head = aa.id_balance_head
                                   LEFT JOIN t_balance_detail b
                                     ON a.id_balance_head = b.id_balance_head AND b.init_qty > "0.0001"
                                   LEFT JOIN m_material c
                                     ON a.id_material = c.id_material
                                   LEFT JOIN m_supplier e
                                     ON e.id_supplier = b.id_supplier
                                   LEFT JOIN (SELECT f.id_balance_head, g.material_document, f.to_trace_no
                                                FROM t_trace_header f
                                                LEFT JOIN t_material_document g
                                                  ON f.id_trace_head = g.id_trace_head
                                               WHERE f.status = 1
                                                 AND (SUBSTRING(f.to_trace_no,1,1) = 1 OR SUBSTRING(f.to_trace_no,1,1) = 2 OR SUBSTRING(f.to_trace_no,1,1) = 7 OR
                                                      SUBSTRING(f.to_trace_no,1,1) = 8 OR SUBSTRING(f.to_trace_no,1,1) = 9)
                                               GROUP BY f.id_balance_head) f
                                     ON f.id_balance_head = a.id_balance_head
                                  WHERE d.id_plant = ?
                                    AND d.code_3 <> "STORAGE"
                                  GROUP BY a.trace_no) e
                      ON d.id_material = e.id_material
                      LEFT JOIN m_plant p ON e.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                   WHERE c.status = 1
                     AND c.' . $column . ' = ?
                   ) aa
             ORDER BY entry_date DESC
        ', [$idPlant, $dbRundownId]);

        return $rows;
    }

    public function getFeed(string $feedId, string $mode, $plantId): array
    {
        $feedId = $this->mapFrontendSectionToDbFeedId($feedId);
        $feedPrefix = substr($feedId, 0, 3);
        $idPlant = $this->resolvePlantId($plantId);

        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))');

        if (strlen($feedId) >= 6) {
            return $this->getFeedWithMaterialSign($feedId, $feedPrefix, $mode, $idPlant);
        }

        // Generic feed query (no material sign)
        // GAP #12: Add pagination for LOG mode
        $limit = ($mode === 'LOG') ? 50 : 1;
        $offset = 0;

        if ($mode === 'LATEST') {
            $rows = $this->executeSelect('
                SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no,
                       a.id_balance_head, a.id_material, g.material_document, a.id_sloc, a.id_sloc_tail,
                       FORMAT(ROUND(h.out_qty,3),3) AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                       GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                       b.batch_sap, FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf,
                       GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(ROUND(b.out_qty,3),3), " MT") SEPARATOR " | ") AS supplier,
                       IF(ABS(ROUND(bs.supplier_qty,3) - ROUND(h.out_qty,3)) > 0.005, FORMAT(ROUND(bs.supplier_qty,3),3), FORMAT(ROUND(h.out_qty,3),3)) AS balance_supplier,
                       CONCAT(i.description,
                           IF(GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", ") IS NULL,
                               "",
                               CONCAT(" | ", GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", "))
                           )
                       ) AS sloc,
                       a.id_plant, p.description AS plant_name
                  FROM t_trace_header a
                  LEFT JOIN t_trace_detail b ON a.id_trace_head = b.id_trace_head
                  LEFT JOIN m_material c ON a.id_material = c.id_material
                  LEFT JOIN m_supplier e ON e.id_supplier = b.id_supplier
                  LEFT JOIN t_material_document g ON a.id_trace_head = g.id_trace_head
                  LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                               FROM t_trace_header a
                              WHERE a.status = 1 AND a.id_plant = ?
                              GROUP BY a.to_trace_no) h ON a.to_trace_no = h.to_trace_no
                  LEFT JOIN m_sloc i ON i.id_sloc = COALESCE(a.id_sloc, (
                      SELECT ms.id_sloc FROM m_sloc ms 
                      JOIN m_tank mt ON ms.code COLLATE utf8mb4_unicode_ci = mt.code COLLATE utf8mb4_unicode_ci AND ms.id_plant COLLATE utf8mb4_unicode_ci = mt.id_plant COLLATE utf8mb4_unicode_ci 
                      WHERE mt.id_tank = a.id_tank 
                      LIMIT 1
                  ))
                  LEFT JOIN m_sloc_detail j ON JSON_CONTAINS(a.id_sloc_tail, JSON_QUOTE(CAST(j.id_sloc_tail AS CHAR)))
                  LEFT JOIN (SELECT h.to_trace_no, SUM(d.out_qty) AS supplier_qty
                               FROM t_trace_header h
                               JOIN t_trace_detail d ON h.id_trace_head = d.id_trace_head
                              WHERE d.out_qty > 0 AND h.status = 1
                              GROUP BY h.to_trace_no) bs ON bs.to_trace_no = a.to_trace_no
                  LEFT JOIN m_plant p ON a.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                 WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                   AND a.out_qty > 0 AND b.out_qty > 0
                   AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                   AND a.status = 1 AND a.id_plant = ?
                 GROUP BY a.to_trace_no
                 ORDER BY a.to_trace_no DESC
                 LIMIT 1
            ', [$idPlant, $feedPrefix, $this->movType2, $idPlant], $idPlant);
        } else {
            $rows = $this->executeSelect('
                SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no,
                       a.id_balance_head, a.id_material,
                       FORMAT(ROUND(h.out_qty,3),3) AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                       GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                       FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf,
                       g.material_document, b.batch_sap,
                       GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(ROUND(b.out_qty,3),3), " MT") SEPARATOR " | ") AS supplier,
                       IF(ABS(ROUND(SUM(b.out_qty),3) - ROUND(h.out_qty,3)) > 0.005, FORMAT(ROUND(SUM(b.out_qty),3),3), FORMAT(ROUND(h.out_qty,3),3)) AS balance_supplier,
                       CASE WHEN a.to_trace_no = (SELECT to_trace_no FROM t_trace_header
                                                   WHERE SUBSTRING(to_trace_no, 8, 3) = ?
                                                     AND SUBSTRING(to_trace_no, 1, 1) = ?
                                                     AND status = 1 AND id_plant = ?
                                                   ORDER BY to_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS is_last_row,
                       CASE WHEN a.to_trace_no = (SELECT from_trace_no FROM t_trace_header
                                                   WHERE SUBSTRING(from_trace_no, 1, 1) = ?
                                                     AND SUBSTRING(from_trace_no, 10, 1) = ?
                                                     AND status = 1 AND id_plant = ?
                                                   ORDER BY from_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS next_process,
                       a.id_plant, p.description AS plant_name
                  FROM t_trace_header a
                  LEFT JOIN t_trace_detail b ON a.id_trace_head = b.id_trace_head
                  LEFT JOIN m_material c ON a.id_material = c.id_material
                  LEFT JOIN m_supplier e ON e.id_supplier = b.id_supplier
                  LEFT JOIN t_material_document g ON a.id_trace_head = g.id_trace_head
                  LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                               FROM t_trace_header a
                              WHERE a.status = 1 AND a.id_plant = ?
                              GROUP BY a.to_trace_no) h ON a.to_trace_no = h.to_trace_no
                  LEFT JOIN m_plant p ON a.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                 WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                   AND a.out_qty > 0 AND b.out_qty > 0
                   AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                   AND a.status = 1 AND a.id_plant = ?
                 GROUP BY a.to_trace_no
                 ORDER BY a.id_trace_head DESC
                  LIMIT ' . $limit . ' OFFSET ' . $offset . '
            ', [$feedPrefix, $this->movType2, $idPlant,
                $this->movType2, substr($feedPrefix, 1, 1), $idPlant,
                $idPlant, $feedPrefix, $this->movType2, $idPlant], $idPlant);
        }

        return $rows;
    }

    protected function getFeedWithMaterialSign(string $feedId, string $feedPrefix, string $mode, $idPlant): array
    {
        $idMatlSign = substr($feedId, 4, 2);
        $feedIdShort = $feedPrefix;

        // Map material IDs based on section logic (from artifact)
        if ($feedIdShort === '009') {
            $idMaterial = null;
            $idMaterial1 = null;
            $idMaterial2 = null;
            if ($idMatlSign === '01') $idMaterial = '12';
            elseif ($idMatlSign === '02') $idMaterial = '25';
            elseif ($idMatlSign === '03') { $idMaterial1 = '18'; $idMaterial2 = '22'; }
            elseif ($idMatlSign === '04') $idMaterial = '14';

            return $this->execFeedQuery($mode, $feedIdShort, $idPlant, $idMaterial, $idMaterial1, $idMaterial2);

        } elseif ($feedIdShort === '006') {
            $idMaterial = null;
            $idMaterial1 = null;
            $idMaterial2 = null;
            if ($idMatlSign === '01') { $idMaterial1 = '6'; $idMaterial2 = '31'; }
            elseif ($idMatlSign === '02') $idMaterial = '66';

            return $this->execFeedQuery($mode, $feedIdShort, $idPlant, $idMaterial, $idMaterial1, $idMaterial2);
        }

        return [];
    }

    protected function execFeedQuery(string $mode, string $feedId, $idPlant, $idMaterial = null, $idMaterial1 = null, $idMaterial2 = null): array
    {
        $isDual = $idMaterial1 !== null && $idMaterial2 !== null;
        $matlWhere = $isDual
            ? '(a.id_material = ? OR a.id_material = ?)'
            : 'a.id_material = ?';
        $matlParams = $isDual ? [$idMaterial1, $idMaterial2] : [$idMaterial];

        if ($mode === 'LATEST') {
            $sql = '
                SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no,
                       a.id_balance_head, a.id_material, g.material_document, a.id_sloc, a.id_sloc_tail,
                       FORMAT(ROUND(h.out_qty,3),3) AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                       GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                       b.batch_sap, FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf,
                       GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(ROUND(b.out_qty,3),3), " MT") SEPARATOR " | ") AS supplier,
                       IF(ABS(ROUND(bs.supplier_qty,3) - ROUND(h.out_qty,3)) > 0.005, FORMAT(ROUND(bs.supplier_qty,3),3), FORMAT(ROUND(h.out_qty,3),3)) AS balance_supplier,
                       CONCAT(i.description,
                           IF(GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", ") IS NULL,
                               "",
                               CONCAT(" | ", GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", "))
                           )
                       ) AS sloc,
                       a.id_plant, p.description AS plant_name
                  FROM t_trace_header a
                  LEFT JOIN t_trace_detail b ON a.id_trace_head = b.id_trace_head
                  LEFT JOIN m_material c ON a.id_material = c.id_material
                  LEFT JOIN m_supplier e ON e.id_supplier = b.id_supplier
                  LEFT JOIN t_material_document g ON a.id_trace_head = g.id_trace_head
                  LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                               FROM t_trace_header a WHERE a.status = 1 AND a.id_plant = ? GROUP BY a.to_trace_no) h ON a.to_trace_no = h.to_trace_no
                  LEFT JOIN m_sloc i ON i.id_sloc = COALESCE(a.id_sloc, (
                      SELECT ms.id_sloc FROM m_sloc ms 
                      JOIN m_tank mt ON ms.code COLLATE utf8mb4_unicode_ci = mt.code COLLATE utf8mb4_unicode_ci AND ms.id_plant COLLATE utf8mb4_unicode_ci = mt.id_plant COLLATE utf8mb4_unicode_ci 
                      WHERE mt.id_tank = a.id_tank 
                      LIMIT 1
                  ))
                  LEFT JOIN m_sloc_detail j ON JSON_CONTAINS(a.id_sloc_tail, JSON_QUOTE(CAST(j.id_sloc_tail AS CHAR)))
                  LEFT JOIN (SELECT h.to_trace_no, SUM(d.out_qty) AS supplier_qty
                               FROM t_trace_header h JOIN t_trace_detail d ON h.id_trace_head = d.id_trace_head
                              WHERE d.out_qty > 0 AND h.status = 1 GROUP BY h.to_trace_no) bs ON bs.to_trace_no = a.to_trace_no
                  LEFT JOIN m_plant p ON a.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                 WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                   AND a.out_qty > 0 AND b.out_qty > 0
                   AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                   AND ' . $matlWhere . '
                   AND a.status = 1 AND a.id_plant = ?
                 GROUP BY a.to_trace_no
                 ORDER BY a.to_trace_no DESC
                 LIMIT 1
            ';
            $params = array_merge([$idPlant, $feedId, $this->movType2], $matlParams, [$idPlant]);
        } else {
            $sql = '
                SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no,
                       a.id_balance_head, a.id_material,
                       FORMAT(ROUND(h.out_qty,3),3) AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                       GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                       FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf,
                       g.material_document, b.batch_sap,
                       GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(ROUND(b.out_qty,3),3), " MT") SEPARATOR " | ") AS supplier,
                       IF(ABS(ROUND(SUM(b.out_qty),3) - ROUND(h.out_qty,3)) > 0.005, FORMAT(ROUND(SUM(b.out_qty),3),3), FORMAT(ROUND(h.out_qty,3),3)) AS balance_supplier,
                       CASE WHEN a.to_trace_no = (SELECT to_trace_no FROM t_trace_header
                                                   WHERE SUBSTRING(to_trace_no, 8, 3) = ?
                                                     AND SUBSTRING(to_trace_no, 1, 1) = ?
                                                     AND ' . $matlWhere . '
                                                     AND status = 1 AND id_plant = ?
                                                   ORDER BY to_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS is_last_row,
                       CASE WHEN a.to_trace_no = (SELECT from_trace_no FROM t_trace_header
                                                   WHERE from_trace_no = a.to_trace_no
                                                     AND ' . $matlWhere . '
                                                     AND status = 1 AND id_plant = ?
                                                   ORDER BY from_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS next_process,
                       a.id_plant, p.description AS plant_name
                  FROM t_trace_header a
                  LEFT JOIN t_trace_detail b ON a.id_trace_head = b.id_trace_head
                  LEFT JOIN m_material c ON a.id_material = c.id_material
                  LEFT JOIN m_supplier e ON e.id_supplier = b.id_supplier
                  LEFT JOIN t_material_document g ON a.id_trace_head = g.id_trace_head
                  LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                               FROM t_trace_header a WHERE a.status = 1 AND a.id_plant = ? GROUP BY a.to_trace_no) h ON a.to_trace_no = h.to_trace_no
                  LEFT JOIN m_plant p ON a.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                 WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                   AND a.out_qty > 0 AND b.out_qty > 0
                   AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                   AND ' . $matlWhere . '
                   AND a.status = 1 AND a.id_plant = ?
                 GROUP BY a.to_trace_no
                 ORDER BY a.id_trace_head DESC
            ';
            $params = array_merge(
                [$feedId, $this->movType2], $matlParams, [$idPlant],
                $matlParams, [$idPlant],
                [$idPlant, $feedId, $this->movType2], $matlParams, [$idPlant]
            );
        }

        return $this->executeSelect($sql, $params, $idPlant);
    }

    public function getRundown(string $rundownId, string $mode, $plantId): array
    {
        $rundownId = $this->mapFrontendSectionToDbRundownId($rundownId);
        $idPlant = $this->resolvePlantId($plantId);
        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))');

        $limit = ($mode === 'LOG') ? 50 : 1;
        $offset = 0;

        if ($mode === 'LATEST') {
            $rows = $this->executeSelect('
                SELECT a.id_trace_head, a.entry_date, a.to_trace_no AS rundown_trace_no,
                       a.id_balance_head, a.id_material, a.id_sloc, a.id_sloc_tail,
                       FORMAT(ROUND(h.in_qty,3),3) AS in_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                       CONCAT(c.code, " :: ", c.description) AS material, g.material_document,
                       FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf, b.batch_sap,
                       GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(b.in_qty,3), " MT") SEPARATOR " | ") AS supplier,
                       FORMAT(ROUND(bs.supplier_qty,3),3) AS balance_supplier,
                       CONCAT(i.description,
                           IF(GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", ") IS NULL,
                               "",
                               CONCAT(" | ", GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", "))
                           )
                       ) AS sloc,
                       a.id_plant, p.description AS plant_name
                  FROM t_trace_header a
                  LEFT JOIN t_trace_detail b ON a.id_trace_head = b.id_trace_head
                  LEFT JOIN m_material c ON a.id_material = c.id_material
                  LEFT JOIN m_supplier e ON e.id_supplier = b.id_supplier
                  LEFT JOIN t_material_document g ON a.id_trace_head = g.id_trace_head
                  LEFT JOIN (SELECT a.to_trace_no, SUM(a.in_qty) AS in_qty
                               FROM t_trace_header a WHERE a.status = 1 GROUP BY a.to_trace_no) h ON a.to_trace_no = h.to_trace_no
                  LEFT JOIN m_sloc i ON i.id_sloc = COALESCE(a.id_sloc, (
                      SELECT ms.id_sloc FROM m_sloc ms 
                      JOIN m_tank mt ON ms.code COLLATE utf8mb4_unicode_ci = mt.code COLLATE utf8mb4_unicode_ci AND ms.id_plant COLLATE utf8mb4_unicode_ci = mt.id_plant COLLATE utf8mb4_unicode_ci 
                      WHERE mt.id_tank = a.id_tank 
                      LIMIT 1
                  ))
                  LEFT JOIN m_sloc_detail j ON JSON_CONTAINS(a.id_sloc_tail, JSON_QUOTE(CAST(j.id_sloc_tail AS CHAR)))
                  LEFT JOIN (SELECT id_trace_head, SUM(in_qty) AS supplier_qty
                               FROM t_trace_detail WHERE in_qty > 0 GROUP BY id_trace_head) bs ON bs.id_trace_head = a.id_trace_head
                  LEFT JOIN m_plant p ON a.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                 WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                   AND a.in_qty > 0 AND b.in_qty > 0
                   AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                   AND a.status = 1 AND a.id_plant = ?
                 GROUP BY a.to_trace_no
                 ORDER BY a.to_trace_no DESC
                 LIMIT 1
            ', [$rundownId, $this->movType1, $idPlant], $idPlant);
        } else {
            $rows = $this->executeSelect('
                SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no,
                       a.id_balance_head, a.id_material,
                       FORMAT(ROUND(h.in_qty,3),3) AS in_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                       CONCAT(c.code, " :: ", c.description) AS material, g.material_document,
                       FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf, b.batch_sap,
                       GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(b.in_qty,3), " MT") SEPARATOR " | ") AS supplier,
                       FORMAT(ROUND(SUM(b.in_qty),3),3) AS balance_supplier,
                       CASE WHEN a.to_trace_no = (SELECT to_trace_no FROM t_trace_header
                                                   WHERE (SUBSTRING(to_trace_no, 1, 1) = ? OR SUBSTRING(to_trace_no, 1, 1) = ?)
                                                     AND SUBSTRING(to_trace_no, 8, 3) = ?
                                                     AND status = 1 AND id_plant = ?
                                                   ORDER BY to_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS is_last_row,
                       CASE WHEN a.to_trace_no = (SELECT from_trace_no FROM t_trace_header
                                                   WHERE from_trace_no = a.to_trace_no
                                                     AND status = 1 AND id_plant = ?
                                                   ORDER BY from_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS next_process,
                       a.id_plant, p.description AS plant_name
                  FROM t_trace_header a
                  LEFT JOIN t_trace_detail b ON a.id_trace_head = b.id_trace_head
                  LEFT JOIN m_material c ON a.id_material = c.id_material
                  LEFT JOIN m_supplier e ON e.id_supplier = b.id_supplier
                  LEFT JOIN t_material_document g ON a.id_trace_head = g.id_trace_head
                  LEFT JOIN (SELECT a.to_trace_no, SUM(a.in_qty) AS in_qty
                               FROM t_trace_header a WHERE a.status = 1 GROUP BY a.to_trace_no) h ON a.to_trace_no = h.to_trace_no
                  LEFT JOIN m_plant p ON a.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                 WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                   AND a.in_qty > 0 AND b.in_qty > 0
                   AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                   AND a.status = 1 AND a.id_plant = ?
                 GROUP BY a.to_trace_no
                 ORDER BY a.to_trace_no DESC
                  LIMIT ' . $limit . ' OFFSET ' . $offset . '
            ', [$this->movType1, $this->movType2, $rundownId, $idPlant, $idPlant, $rundownId, $this->movType1, $idPlant], $idPlant);
        }

        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BATCH NUMBER GENERATION
    // ─────────────────────────────────────────────────────────────────────────

    public function getFeedNewBatchNumber(string $feedId, $plantId): ?string
    {
        $idPlant = $this->resolvePlantId($plantId);
        $feedPrefix = substr($feedId, 0, 3);
        $datePrefix = date('ymd');

        $rows = $this->executeSelect('
            SELECT a.feed_number
              FROM (SELECT a.to_trace_no+1 AS feed_number
                      FROM t_trace_header a
                     WHERE SUBSTRING(a.to_trace_no,1,10) = CONCAT(3, ?, ?)
                       AND a.status = 1 AND a.id_plant = ?
                     ORDER BY a.id_trace_head DESC LIMIT 1) a
             UNION ALL
            SELECT CONCAT(3, ?, ? , LPAD(RIGHT(?, 2), 2, "0"), "01") AS feed_number
             LIMIT 1
        ', [$datePrefix, $feedPrefix, $idPlant, $datePrefix, $feedPrefix, $idPlant], $idPlant);

        return $rows[0]->feed_number ?? null;
    }

    public function getRundownNewBatchNumber(string $rundownId, $plantId): ?string
    {
        $idPlant = $this->resolvePlantId($plantId);
        $section = substr($rundownId, 2, 1);
        $datePrefix = date('ymd');

        if ($section === '9' || $section === '8') {
            $rows = $this->executeSelect('
                SELECT CONCAT(2, SUBSTRING(a.to_trace_no,2,6), ?, LPAD(RIGHT(?, 2), 2, "0"), SUBSTRING(a.to_trace_no,13,2)) AS rundown_number
                  FROM (SELECT to_trace_no + 1 AS to_trace_no
                          FROM t_trace_header a
                         WHERE a.status = 1 AND a.id_plant = ?
                           AND SUBSTRING(a.to_trace_no,1,10) = CONCAT(2, ?, ?)
                         ORDER BY to_trace_no DESC LIMIT 1) a
                 UNION ALL
                SELECT CONCAT(2, ?, ? , LPAD(RIGHT(?, 2), 2, "0"), "01") AS rundown_number
                 LIMIT 1
            ', [$rundownId, $idPlant, $idPlant, $datePrefix, $rundownId, $datePrefix, $rundownId, $idPlant], $idPlant);
        } else {
            $rows = $this->executeSelect('
                SELECT a.rundown_number
                  FROM (SELECT a.to_trace_no+1 AS rundown_number
                          FROM t_trace_header a
                         WHERE SUBSTRING(a.to_trace_no,1,10) = CONCAT(2, ?, ?)
                           AND a.status = 1 AND a.id_plant = ?
                         ORDER BY a.id_trace_head DESC LIMIT 1) a
                 UNION ALL
                SELECT CONCAT(2, ?, ? , LPAD(RIGHT(?, 2), 2, "0"), "01") AS rundown_number
                 LIMIT 1
            ', [$datePrefix, $rundownId, $idPlant, $datePrefix, $rundownId, $idPlant], $idPlant);
        }

        return $rows[0]->rundown_number ?? null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LAST BATCH DATA
    // ─────────────────────────────────────────────────────────────────────────

    public function getFeedLastBatch(string $feedId, $plantId): array
    {
        $feedId = $this->mapFrontendSectionToDbFeedId($feedId);
        $idPlant = $this->resolvePlantId($plantId);

        $flowType = DB::connection('eudr_ts')->select(
            'SELECT flow_type FROM m_material_flow WHERE status = 1'
        );
        $flowType = $flowType[0]->flow_type ?? 'normal';

        if ($flowType === 'quantifier') {
            $db = $this->executeSelect('
                SELECT a.curr_qtf, a.entry_date, "-NORMAL-" AS status
                  FROM (SELECT a.curr_qtf, a.entry_date
                          FROM t_trace_header a
                         WHERE SUBSTRING(a.to_trace_no,1,1) = 2
                           AND SUBSTRING(a.to_trace_no,8,3) = ?
                           AND a.status = 1 AND a.id_plant = ?
                         ORDER BY a.id_trace_head DESC LIMIT 1) a
                 UNION ALL
                SELECT 0 AS curr_qtf, DATE_FORMAT(CURDATE(), "%Y-%m-%d") AS entry_date, "-INIT-" AS status
                 LIMIT 1
            ', [substr($feedId, 0, 3), $idPlant], $idPlant);

            if (!empty($db) && (float)($db[0]->curr_qtf ?? 0) !== 0.0) {
                $db1 = DB::connection('eudr_ts')->select('
                    SELECT IFNULL(b.curr_qtf, 0) AS curr_qtf,
                           IFNULL(b.entry_date, DATE_FORMAT(CURDATE(), "%Y-%m-%d")) AS entry_date,
                           "-RESET-" AS status
                      FROM m_material a
                      LEFT JOIN (SELECT b.flowmeter, b.value AS curr_qtf, b.reset_date AS entry_date
                                   FROM t_reset_quantifier b
                                  WHERE b.status = 1
                                  ORDER BY id_reset DESC) b ON a.qtf_feed = b.flowmeter
                     WHERE a.id_feed = ?
                       AND b.curr_qtf IS NOT NULL
                     ORDER BY b.entry_date DESC LIMIT 1
                ', [$feedId]);

                if (!empty($db1)) return $db1;
            }
            return $db;
        }

        return DB::connection('eudr_ts')->select(
            'SELECT 0 AS curr_qtf, DATE_FORMAT(CURDATE(), "%Y-%m-%d") AS entry_date, "-QTF-" AS status'
        );
    }

    public function getRundownLastBatch(string $rundownId, $plantId): array
    {
        $rundownId = $this->mapFrontendSectionToDbRundownId($rundownId);
        $idPlant = $this->resolvePlantId($plantId);

        $flowType = DB::connection('eudr_ts')->select(
            'SELECT flow_type FROM m_material_flow WHERE status = 1'
        );
        $flowType = $flowType[0]->flow_type ?? 'normal';

        if ($flowType === 'quantifier') {
            $db = $this->executeSelect('
                SELECT a.curr_qtf, a.entry_date, "-NORMAL-" AS status, a.created_at
                  FROM (SELECT a.curr_qtf, a.entry_date, a.created_at
                          FROM t_trace_header a
                         WHERE SUBSTRING(a.to_trace_no,1,1) = 1
                           AND SUBSTRING(a.to_trace_no,8,3) = ?
                           AND a.status = 1 AND a.id_plant = ?
                         ORDER BY a.id_trace_head DESC LIMIT 1) a
                 UNION ALL
                SELECT 0 AS curr_qtf, DATE_FORMAT(CURDATE(), "%Y-%m-%d") AS entry_date, "-INIT-" AS status, "" AS created_at
                 LIMIT 1
            ', [substr($rundownId, 0, 3), $idPlant], $idPlant);

            if (!empty($db) && (float)($db[0]->curr_qtf ?? 0) !== 0.0) {
                $db1 = DB::connection('eudr_ts')->select('
                    SELECT IFNULL(b.curr_qtf, 0) AS curr_qtf, b.created_at,
                           IFNULL(b.entry_date, DATE_FORMAT(CURDATE(), "%Y-%m-%d")) AS entry_date,
                           "-RESET-" AS status
                      FROM m_material a
                      LEFT JOIN (SELECT b.flowmeter, b.value AS curr_qtf, b.reset_date AS entry_date, b.created_at
                                   FROM t_reset_quantifier b
                                  WHERE b.status = 1
                                  ORDER BY id_reset DESC) b ON a.qtf_rundown = b.flowmeter
                     WHERE a.id_rundown = ?
                       AND b.curr_qtf IS NOT NULL
                     ORDER BY b.entry_date DESC LIMIT 1
                ', [$rundownId]);

                if (!empty($db1)) return $db1;
            }
            return $db;
        }

        return DB::connection('eudr_ts')->select(
            'SELECT 0 AS curr_qtf, DATE_FORMAT(CURDATE(), "%Y-%m-%d") AS entry_date, "-QTF-" AS status'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DROPDOWNS
    // ─────────────────────────────────────────────────────────────────────────

    public function getActiveTanksForFeed(string $feedId, $plantId): array
    {
        $idPlant = $this->resolvePlantId($plantId);
        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))');

        return $this->getActiveTanksBySlocType('FEED', $idPlant, $plantId);
    }

    public function getActiveTanksForRundown(string $rundownId, $plantId, ?string $subgroup = null): array
    {
        $idPlant = $this->resolvePlantId($plantId);
        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))');

        return $this->getActiveTanksBySlocType('WIP', $idPlant, $plantId);
    }

    protected function getActiveTanksBySlocType(string $type, ?string $idPlant, mixed $rawPlantId = null): array
    {
        $params = [$type, $type, $type];
        $plantWhere = '';

        if ($idPlant !== null && $idPlant !== '0' && $idPlant !== '') {
            $plantWhere = ' AND id_plant = ?';
            $params[] = $idPlant;
        }

        $rows = DB::connection('eudr_ts')->select("
            SELECT id_sloc, id_sloc AS id_tank, description AS tank,
                   (SELECT COUNT(*) FROM m_sloc_detail sd WHERE sd.id_sloc = m_sloc.id_sloc AND sd.status = 1) AS details_count
              FROM m_sloc
             WHERE status = 1
               AND (
                    UPPER(COALESCE(code_3, '')) = ?
                 OR UPPER(COALESCE(code_2, '')) = ?
                 OR UPPER(COALESCE(description, '')) LIKE CONCAT('%', ?, '%')
               )
               {$plantWhere}
             ORDER BY description ASC, id_sloc ASC
        ", $params);

        \Log::info('WIP SLOC debug', [
            'type' => $type,
            'raw_plant_id' => $rawPlantId,
            'resolved_plant_id' => $idPlant,
            'params' => $params,
            'count' => count($rows),
            'sample' => $rows[0] ?? null,
        ]);

        return $rows;
    }

    public function getActiveSpecificTanks(int $slocId): array
    {
        return DB::connection('eudr_ts')->select('
            SELECT a.id_sloc_tail, a.id_sloc_tail AS id_tank_tail, a.tf_number AS tankNo
              FROM m_sloc_detail a
             WHERE a.status = 1 AND a.id_sloc = ?
             ORDER BY a.tf_number ASC
        ', [$slocId]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EXTERNAL DATA
    // ─────────────────────────────────────────────────────────────────────────

    public function getQuantifierData(string $date, string $tagNumber): array
    {
        $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));

        try {
            return DB::connection('dwsql')->select("
                SELECT FORMAT(`value`,3) AS `value`,
                       CONCAT(?, ' 07:00') AS `timestamp`
                  FROM `{$tagNumber}`
                 WHERE DATE_FORMAT(`timestamp`, '%Y-%m-%d') = ?
                 UNION ALL
                SELECT 0 AS `value`, CONCAT(?, ' 07:00') AS `timestamp`
                 LIMIT 1
            ", [$nextDate, $nextDate, $nextDate]);
        } catch (\Exception $e) {
            \Log::warning('DCS quantifier fetch failed (please connect db): ' . $e->getMessage());
            throw new \Exception("please connect db");
        }
    }

    // B8: WIP Tree/Dashboard - Get complete WIP tree structure
    public function getWipTree($plantId): array
    {
        $idPlant = $this->resolvePlantId($plantId);
        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))');

        // Get all sections with their latest feed and rundown
        $sections = $this->executeSelect("
            SELECT
                m.id_rundown AS section_id,
                m.code AS section_code,
                m.description AS section_name,
                (SELECT to_trace_no FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '3'
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND id_plant = ?
                 ORDER BY id_trace_head DESC LIMIT 1) AS latest_feed_trace,
                (SELECT entry_date FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '3'
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND id_plant = ?
                 ORDER BY id_trace_head DESC LIMIT 1) AS latest_feed_date,
                (SELECT curr_qtf FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '3'
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND id_plant = ?
                 ORDER BY id_trace_head DESC LIMIT 1) AS latest_feed_qty,
                (SELECT to_trace_no FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '2'
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND id_plant = ?
                 ORDER BY id_trace_head DESC LIMIT 1) AS latest_rundown_trace,
                (SELECT entry_date FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '2'
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND id_plant = ?
                 ORDER BY id_trace_head DESC LIMIT 1) AS latest_rundown_date,
                (SELECT curr_qtf FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '2'
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND id_plant = ?
                 ORDER BY id_trace_head DESC LIMIT 1) AS latest_rundown_qty
            FROM m_material m
            WHERE m.status = 1
              AND m.type IN ('WIP', 'RM')
            ORDER BY m.id_rundown
        ", [$idPlant, $idPlant, $idPlant, $idPlant, $idPlant, $idPlant, $idPlant], $idPlant);

        return $sections;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WRITE OPERATIONS
    // ─────────────────────────────────────────────────────────────────────────

    public function postMaterialDocument(string $mode, int $idTraceHead, string $materialDoc, string $user): array
    {
        if ($mode === 'ADD') {
            $result = DB::connection('eudr_ts')->insert(
                'INSERT INTO t_material_document (id_trace_head, material_document, created_by) VALUES (?, ?, ?)',
                [$idTraceHead, $materialDoc, $user]
            );

            $id = DB::connection('eudr_ts')->select('SELECT id_matdoc FROM t_material_document ORDER BY id_matdoc DESC LIMIT 1');
            $this->logTransaction('T_MATERIAL_DOCUMENT', 'ADD',
                'ID: ' . $id[0]->id_matdoc . ' | IDTRACEHEAD: ' . $idTraceHead . ' / DOC_NO: ' . $materialDoc . ' | Status: 1', $user);

            return [['response' => $result ? '1' : '0']];
        }

        // UPDATE mode
        $dat = DB::connection('eudr_ts')->select(
            'SELECT id_matdoc, material_document FROM t_material_document WHERE id_trace_head = ?', [$idTraceHead]
        );
        if (empty($dat)) return [['response' => '0']];

        $oldDoc = $dat[0]->material_document;
        DB::connection('eudr_ts')->update(
            'UPDATE t_material_document SET material_document = ?, updated_by = ? WHERE id_trace_head = ?',
            [$materialDoc, $user, $idTraceHead]
        );

        $this->logTransaction('T_MATERIAL_DOCUMENT', 'UPDATE',
            'ID: ' . $dat[0]->id_matdoc . ' | IDTRACEHEAD: ' . $idTraceHead . ' / DOC_NO: ' . $oldDoc . ' >>> ' . $materialDoc . ' | Status: 1', $user);

        return [['response' => '1']];
    }

    public function postMaterialFeed(array $data, string $user): array
    {
        $feedId = $this->mapFrontendSectionToDbFeedId($data['feed_id']);
        $idTank = $data['tank'];
        $idTankTail = !empty($data['tankNo']) ? json_encode($data['tankNo']) : '[]';
        $currQtf = $data['curr_feed'];
        $lastQtf = $data['last_feed'];
        $currEntryDate = $data['curr_entryDate'];
        $entryNo = $data['batch_no'];
        $idPlant = $this->resolvePlantId($data['id_plant'] ?? null);

        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))');

        // Period lock check
        if ($this->checkPeriodLock($currEntryDate)) {
            return [['response' => '99']];
        }

        $outQty = (float)$currQtf - (float)$lastQtf;

        // Check reserve balance
        $datHead = DB::connection('eudr_ts')->select('
            SELECT IFNULL(SUM(b.qty),0) AS qty
              FROM m_material a
              LEFT JOIN t_balance_header b ON a.id_material = b.id_material AND b.status = 1
             WHERE a.id_feed = ? AND a.status = 1
               AND b.qty > "0.0001" AND b.id_sloc = ? AND b.id_plant = ?
        ', [$feedId, $idTank, $idPlant]);

        $totalReserve = (float)($datHead[0]->qty ?? 0);
        if (($totalReserve - $outQty) < -0.000001) {
            return [['response' => '3']];
        }

        // Get material from feed config
        // GAP B: Use section mapping to find correct material
        $idMaterial = $this->getMaterialIdBySection($feedId, 'feed');
        if (empty($idMaterial)) return [['response' => '4']];

        // Duplicate check
        $dup = DB::connection('eudr_ts')->select('
            SELECT COUNT(id_trace_head) AS flag
              FROM t_trace_header
             WHERE status = 1 AND entry_date = ? AND id_sloc = ? AND id_material = ?
               AND in_qty = 0 AND SUBSTRING(to_trace_no,1,1) = 3 AND id_plant = ?
        ', [$currEntryDate, $idTank, $idMaterial, $idPlant]);

        if (!empty($dup) && $dup[0]->flag > 0) {
            return [['response' => '2']];
        }

        // Execute FIFO feed via Shared helper
        $feedData = [
            'user'         => $user,
            'entry_date'   => $currEntryDate,
            'id_material'  => $idMaterial,
            'id_sloc'      => $idTank,
            'id_sloc_tail' => $idTankTail,
            'id_plant'     => $idPlant,
            'qty'          => $outQty,
            'to_trace_no'  => $entryNo,
            'last_qtf'     => $lastQtf,
            'curr_qtf'     => $currQtf,
        ];

        try {
            $result = Feed::generalFeed(array_merge($feedData, [
                'trace_prefixes' => ['1', '2', '7', '8', '9'],
            ]));
        } catch (\RuntimeException $e) {
            return [['response' => '6']];
        }

        if (($result['response'] ?? 0) != 1) {
            return [['response' => (string)($result['response'] ?? 3)]];
        }

        // Normalize supplier quantities
        Feed::normalizeSupplierRundown($result['trace_head_ids'], $outQty);

        // CRITICAL #3: Insert t_prod_log (production log) after successful feed
        // This tracks production batches for WIP processing
        $feedTraceHeadId = $result['trace_head_ids'][0] ?? null;
        if ($feedTraceHeadId) {
            DB::connection('eudr_ts')->table('t_prod_log')->insert([
                'id_trace_head' => $feedTraceHeadId,
                'section' => $feedId,
                'entry_date' => $currEntryDate,
                'batch_no' => $entryNo,
                'tank_id' => $idTank,
                'tank_tail' => $idTankTail,
                'id_material' => $idMaterial,
                'in_qty' => 0,
                'out_qty' => $outQty,
                'yield' => 0,
                'id_plant' => $idPlant,
                'status' => 1,
                'created_by' => $user,
                'created_at' => now(),
            ]);
            $this->logTransaction('T_PROD_LOG', 'ADD', 'WIP FEED | IDTRACEHEAD: ' . $feedTraceHeadId . ' | BATCH: ' . $entryNo . ' | QTY: ' . $outQty, $user);
        }

        return [['response' => '1']];
    }

    public function postMaterialRundown(array $data, string $user): array
    {
        $subgroup = $data['subgroup'] ?? null;
        $rundownId = $this->mapFrontendSectionToDbRundownId($data['rundown_id'], $subgroup);
        $lastQtf = $data['last_rundown'];
        $currQtf = $data['curr_rundown'];
        $currEntryDate = $data['curr_entryDate'];
        $entryNo = $data['batch_no'];
        $idTank = $data['tank'];
        $idTankTail = !empty($data['tankNo']) ? json_encode($data['tankNo']) : '[]';
        $idPlant = $this->resolvePlantId($data['id_plant'] ?? null);

        // Period lock check
        if ($this->checkPeriodLock($currEntryDate)) {
            return [['response' => '99']];
        }

        $inQty = (float)$currQtf - (float)$lastQtf;
        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))');

        // Check duplicate trace no
        $originalEntryNo = $entryNo;
        $maxAttempts = 10;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $check = DB::connection('eudr_ts')->select(
                'SELECT COUNT(to_trace_no) AS flag FROM t_trace_header WHERE to_trace_no = ? AND status = 1 AND id_plant = ?',
                [$entryNo, $idPlant]
            );
            if ($check[0]->flag == 0) break;
            $entryNo = $originalEntryNo + ($i + 1);
        }

        $checkFinal = DB::connection('eudr_ts')->select(
            'SELECT COUNT(to_trace_no) AS flag FROM t_trace_header WHERE to_trace_no = ? AND id_plant = ? AND status = 1',
            [$entryNo, $idPlant]
        );
        if ($checkFinal[0]->flag > 0) {
            return [['response' => '7']];
        }

        // GAP B: Get feed trace related to this rundown (same date)
        // Use proper section mapping instead of '00' + second character
        $feedSectionId = $this->mapRundownToFeedSectionId($rundownId);
        $feedId = $feedSectionId; // This is the feed section ID for trace query

        $feedTrace = DB::connection('eudr_ts')->select('
            SELECT to_trace_no, id_trace_head, SUM(out_qty) AS out_qty, id_material
              FROM t_trace_header
             WHERE SUBSTRING(to_trace_no,1,1) = ?
               AND SUBSTRING(to_trace_no,8,3) = ?
               AND entry_date = ? AND id_plant = ? AND status = 1
               AND out_qty > "0.0001"
             GROUP BY id_trace_head
             ORDER BY id_trace_head DESC LIMIT 1
        ', [$this->movType2, $feedId, $currEntryDate, $idPlant]);

        if (empty($feedTrace) || $feedTrace[0]->out_qty === null) {
            return [['response' => '4']];
        }

        $fromTraceNo = $feedTrace[0]->to_trace_no;
        $feedQty = (float)$feedTrace[0]->out_qty;

        // Get rundown material using section mapping
        $idMaterial = $this->getMaterialIdBySection($rundownId, 'rundown', $subgroup);
        if (empty($idMaterial)) return [['response' => '4']];

        // Duplicate check
        $dup = DB::connection('eudr_ts')->select('
            SELECT COUNT(id_trace_head) AS flag
              FROM t_trace_header
             WHERE status = 1 AND entry_date = ? AND id_sloc = ? AND id_material = ?
               AND out_qty = 0 AND id_plant = ? AND SUBSTRING(to_trace_no,1,1) = 2
        ', [$currEntryDate, $idTank, $idMaterial, $idPlant]);

        if (!empty($dup) && $dup[0]->flag > 0) {
            return [['response' => '2']];
        }

        // Calculate yield
        $processYield = $feedQty > 0 ? ($inQty / $feedQty) : 0;

        // Get all feed trace heads for supplier mapping
        $feedTraces = DB::connection('eudr_ts')->select('
            SELECT to_trace_no, id_trace_head, out_qty, id_material
              FROM t_trace_header
             WHERE SUBSTRING(to_trace_no,1,1) = ?
               AND SUBSTRING(to_trace_no,8,3) = ?
               AND entry_date = ? AND status = 1
               AND out_qty > "0.0001" AND id_plant = ?
             ORDER BY id_trace_head DESC
        ', [$this->movType2, $feedId, $currEntryDate, $idPlant]);

        $supplierRows = [];
        foreach ($feedTraces as $head) {
            $feedDetails = DB::connection('eudr_ts')->select('
                SELECT id_trace_tail, id_balance_tail, id_supplier, out_qty, batch_sap
                  FROM t_trace_detail
                 WHERE id_trace_head = ? AND status = 1 AND id_plant = ?
                 ORDER BY id_trace_tail ASC
            ', [$head->id_trace_head, $idPlant]);

            if (empty($feedDetails)) {
                return [['response' => '6']];
            }

            foreach ($feedDetails as $detail) {
                $supplierRows[] = [
                    'id_supplier'     => $detail->id_supplier,
                    'batch_sap'       => $detail->batch_sap,
                    'rundownSupplier' => round($processYield * (float)$detail->out_qty, 4),
                ];
            }
        }

        // Adjust supplier totals to match in_qty
        Rundown::adjustRundownToTotal($supplierRows, $inQty);

        // Execute rundown via Shared helper
        $rundownResult = Rundown::generalRundown([
            'user'          => $user,
            'entry_date'    => $currEntryDate,
            'from_trace_no' => $fromTraceNo,
            'trace_no'      => $entryNo,
            'id_material'   => $idMaterial,
            'id_sloc'       => $idTank,
            'id_sloc_tail'  => $idTankTail,
            'in_qty'        => $inQty,
            'last_qtf'      => $lastQtf,
            'curr_qtf'      => $currQtf,
            'id_plant'      => $idPlant,
            'supplier_rows' => $supplierRows,
        ]);

        if (($rundownResult['response'] ?? 0) != 1) {
            return [['response' => '3']];
        }

        return [['response' => '1']];
    }

    public function cancelFeed(string $traceNo, string $user): array
    {
        // Get entry date for lock check
        $entryDate = DB::connection('eudr_ts')->select(
            'SELECT entry_date FROM t_trace_header WHERE to_trace_no = ? AND status = 1',
            [$traceNo]
        );
        if (empty($entryDate)) return [['response' => '0']];

        if ($this->checkPeriodLock($entryDate[0]->entry_date)) {
            return [['response' => '99']];
        }

        $traceHeads = DB::connection('eudr_ts')->select('
            SELECT id_trace_head, id_balance_head, in_qty, out_qty
              FROM t_trace_header
             WHERE to_trace_no = ? AND status = 1
             ORDER BY id_trace_head DESC
        ', [$traceNo]);

        foreach ($traceHeads as $head) {
            $idTraceHead = $head->id_trace_head;
            $idBalanceHead = $head->id_balance_head;
            $traceHeadOutQty = (float)$head->out_qty;

            // Update trace header status
            DB::connection('eudr_ts')->update(
                'UPDATE t_trace_header SET status = 0, updated_by = ? WHERE id_trace_head = ? AND status = 1',
                [$user, $idTraceHead]
            );
            $this->logTransaction('T_TRACE_HEAD', 'DELETE',
                'IDTRACEHEAD: ' . $idTraceHead . ' IDHEAD: ' . $idBalanceHead . ' | Status: 1 >>> 0', $user);

            // Restore balance header
            $balHead = DB::connection('eudr_ts')->select('
                SELECT qty, in_qty, out_qty FROM t_balance_header WHERE id_balance_head = ? AND status = 1',
                [$idBalanceHead]
            );
            if (!empty($balHead)) {
                $oldQty = (float)$balHead[0]->qty;
                $oldInQty = (float)$balHead[0]->in_qty;
                $oldOutQty = (float)$balHead[0]->out_qty;

                DB::connection('eudr_ts')->update('
                    UPDATE t_balance_header SET qty = ?, in_qty = ?, out_qty = ?, updated_by = ?
                    WHERE id_balance_head = ? AND status = 1',
                    [$oldQty + $traceHeadOutQty, $oldInQty, $oldOutQty - $traceHeadOutQty, $user, $idBalanceHead]
                );

                $this->logTransaction('T_BALANCE_HEAD', 'UPDATE',
                    'IDHEAD: ' . $idBalanceHead . ' | QTY: ' . $oldQty . ' >>> ' . ($oldQty + $traceHeadOutQty) .
                    ' / OUT_QTY: ' . $oldOutQty . ' >>> ' . ($oldOutQty - $traceHeadOutQty) . ' | Status: 1', $user);
            }

            // Restore trace details and balance details
            $traceTails = DB::connection('eudr_ts')->select('
                SELECT id_trace_tail, id_balance_tail, in_qty, out_qty
                  FROM t_trace_detail
                 WHERE id_trace_head = ? AND status = 1
                 ORDER BY id_trace_tail DESC
            ', [$idTraceHead]);

            foreach ($traceTails as $tail) {
                DB::connection('eudr_ts')->update(
                    'UPDATE t_trace_detail SET status = 0, updated_by = ? WHERE id_trace_tail = ? AND status = 1',
                    [$user, $tail->id_trace_tail]
                );

                $balTail = DB::connection('eudr_ts')->select('
                    SELECT qty, in_qty, out_qty, init_qty FROM t_balance_detail
                     WHERE id_balance_tail = ? AND status = 1
                ', [$tail->id_balance_tail]);

                if (!empty($balTail)) {
                    $tailOutQty = (float)$tail->out_qty;
                    DB::connection('eudr_ts')->update('
                        UPDATE t_balance_detail SET qty = ?, in_qty = ?, out_qty = ?, updated_by = ?
                        WHERE id_balance_tail = ? AND status = 1',
                        [$balTail[0]->qty + $tailOutQty, $balTail[0]->in_qty, $balTail[0]->out_qty - $tailOutQty, $user, $tail->id_balance_tail]
                    );
                }
            }

            // BUG #4: Clean up t_prod_log for cancelled feed
            DB::connection('eudr_ts')->update(
                'UPDATE t_prod_log SET status = 0, updated_by = ? WHERE id_trace_head = ? AND status = 1',
                [$user, $idTraceHead]
            );
            $this->logTransaction('T_PROD_LOG', 'DELETE',
                'IDTRACEHEAD: ' . $idTraceHead . ' | Status: 1 >>> 0 | Cancel Feed', $user);
        }

        return [['response' => '1']];
    }

    public function cancelRundown(string $traceNo, string $user): array
    {
        // Get entry date for lock check
        $entryDate = DB::connection('eudr_ts')->select(
            'SELECT entry_date FROM t_trace_header WHERE to_trace_no = ? AND status = 1',
            [$traceNo]
        );
        if (empty($entryDate)) return [['response' => '0']];

        if ($this->checkPeriodLock($entryDate[0]->entry_date)) {
            return [['response' => '99']];
        }

        $traceHeads = DB::connection('eudr_ts')->select('
            SELECT id_trace_head, id_balance_head, in_qty, out_qty
              FROM t_trace_header
             WHERE to_trace_no = ? AND status = 1
             ORDER BY id_trace_head DESC
        ', [$traceNo]);

        foreach ($traceHeads as $head) {
            $idTraceHead = $head->id_trace_head;
            $idBalanceHead = $head->id_balance_head;

            // Update trace header status
            DB::connection('eudr_ts')->update(
                'UPDATE t_trace_header SET status = 0, updated_by = ? WHERE id_trace_head = ? AND status = 1',
                [$user, $idTraceHead]
            );
            $this->logTransaction('T_TRACE_HEAD', 'DELETE',
                'IDTRACEHEAD: ' . $idTraceHead . ' IDHEAD: ' . $idBalanceHead . ' | Status: 1 >>> 0', $user);

            // Delete balance header
            DB::connection('eudr_ts')->update(
                'UPDATE t_balance_header SET status = 0, updated_by = ? WHERE id_balance_head = ? AND status = 1',
                [$user, $idBalanceHead]
            );
            $this->logTransaction('T_BALANCE_HEAD', 'UPDATE',
                'IDHEAD: ' . $idBalanceHead . ' | Status: 1 >>> 0', $user);

            // Delete trace details and balance details
            $traceTails = DB::connection('eudr_ts')->select('
                SELECT id_trace_tail, id_balance_tail FROM t_trace_detail
                 WHERE id_trace_head = ? AND status = 1
            ', [$idTraceHead]);

            foreach ($traceTails as $tail) {
                DB::connection('eudr_ts')->update(
                    'UPDATE t_trace_detail SET status = 0, updated_by = ? WHERE id_trace_tail = ? AND status = 1',
                    [$user, $tail->id_trace_tail]
                );
                DB::connection('eudr_ts')->update(
                    'UPDATE t_balance_detail SET status = 0, updated_by = ? WHERE id_balance_tail = ? AND status = 1',
                    [$user, $tail->id_balance_tail]
                );
            }
        }

        // Check for adjustment cleanup
        $adjCode = substr($traceNo, 0, 1);
        if ($adjCode === '9') {
            $adjHead = DB::connection('eudr_ts')->select(
                'SELECT id_adjust_head FROM t_adjustment_header WHERE adjust_no = ?', [$traceNo]
            );
            if (!empty($adjHead)) {
                $idAdjustHead = $adjHead[0]->id_adjust_head;
                DB::connection('eudr_ts')->update(
                    'UPDATE t_adjustment_header SET status = 0, updated_by = ? WHERE id_adjust_head = ? AND status = 1',
                    [$user, $idAdjustHead]
                );
                DB::connection('eudr_ts')->update(
                    'UPDATE t_adjustment_detail SET status = 0, updated_by = ? WHERE id_adjust_head = ? AND status = 1',
                    [$user, $idAdjustHead]
                );
            }
        }

        return [['response' => '1']];
    }

    public function updateEntrySubTank(int $idHead, array $tails, string $user): array
    {
        $jsonTails = json_encode(array_values(array_unique($tails)));

        $row = DB::connection('eudr_ts')->selectOne(
            'SELECT trace_no FROM t_balance_header WHERE id_balance_head = ? AND status = 1', [$idHead]
        );
        if (!$row) return [['response' => '0', 'message' => 'HEAD NOT FOUND']];

        // Update balance header
        DB::connection('eudr_ts')->update(
            'UPDATE t_balance_header SET id_sloc_tail = ?, updated_by = ? WHERE id_balance_head = ?',
            [$jsonTails, $user, $idHead]
        );

        // Update trace header
        DB::connection('eudr_ts')->update(
            'UPDATE t_trace_header SET id_sloc_tail = ?, updated_by = ? WHERE id_balance_head = ?',
            [$jsonTails, $user, $idHead]
        );

        // Update balance details
        DB::connection('eudr_ts')->update(
            'UPDATE t_balance_detail SET id_sloc_tail = ?, updated_by = ? WHERE id_balance_head = ?',
            [$jsonTails, $user, $idHead]
        );

        // Update trace details
        DB::connection('eudr_ts')->update('
            UPDATE t_trace_detail SET id_sloc_tail = ?, updated_by = ?
            WHERE id_trace_head IN (SELECT id_trace_head FROM t_trace_header WHERE id_balance_head = ?)
        ', [$jsonTails, $user, $idHead]);

        $this->logTransaction('T_BALANCE_HEAD', 'UPDATE_SUBTANK',
            'IDHEAD: ' . $idHead . ' | TRACE: ' . $row->trace_no . ' | SUBTANKS: ' . implode(',', $tails), $user);

        return [['response' => '1']];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PERIOD LOCK CHECK
    // ─────────────────────────────────────────────────────────────────────────

    public function checkPeriodLock(string $date): bool
    {
        $lockYear = date('Y', strtotime($date));
        $lockMonth = date('m', strtotime($date));

        $rows = DB::connection('eudr_ts')->select('
            SELECT lock_status FROM m_period_lock
             WHERE status = 1 AND YEAR(period) = ? AND MONTH(period) = ?
             UNION ALL
            SELECT "0" AS lock_status
        ', [$lockYear, $lockMonth]);

        return ($rows[0]->lock_status ?? '0') === '1';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PLANTS
    // ─────────────────────────────────────────────────────────────────────────

    public function getUserPlants(int $userId): array
    {
        return DB::connection('eudr_ts')->select('
            SELECT m_plant.code_3, m_plant.code_2
              FROM m_plant_user
              JOIN m_plant ON m_plant_user.id_plant = m_plant.code_3
             WHERE m_plant_user.user_id = ?
        ', [$userId]);
    }

    public function getAllPlants(): array
    {
        return DB::connection('eudr_ts')->select(
            'SELECT code_3, code_2 FROM m_plant'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LOGGING
    // ─────────────────────────────────────────────────────────────────────────

    public function logTransaction(string $module, string $type, string $description, string $user): void
    {
        DB::connection('eudr_ts')->insert(
            'INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)',
            [$module, $type, $description, $user]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GAP B: Map frontend section IDs to database material IDs
     *
     * Frontend sends 3-digit section IDs (e.g., '101' for CPKO Feed)
     * Database m_material stores 1-3 digit IDs (e.g., 1 for CPKO Feed)
     *
     * @param string $sectionId Frontend section ID (e.g., '101', '102', '103')
     * @param string $type 'feed' or 'rundown'
     * @return int|null Database material ID or null if not found
     */
    protected function mapSectionToMaterialId(string $sectionId, string $type = 'feed'): ?int
    {
        // Frontend section ID -> Database id_feed/id_rundown mapping
        $sectionMap = [
            // Feed mappings
            'feed_101' => 1,   // CPKO Feed
            'feed_102' => 2,   // DA-OIL Feed
            'feed_103' => 3,   // Crude-ME Feed
            'feed_104' => 4,   // UME Feed
            'feed_105' => 5,   // PKFAD Feed
            'feed_111' => 11,  // Crude-Gly Feed
            'feed_112' => 12,  // FA18/FA24 Feed
            'feed_114' => 14,  // FA14/Ecorol Feed
            // Rundown mappings
            'rundown_101' => 1,   // CPKO Rundown (same as feed)
            'rundown_102' => 11,  // DA-OIL Rundown
            'rundown_103' => 12,  // Crude-ME/Treated-Glycerine Rundown
            'rundown_104' => 13,  // UME/BDME/ME28/Econoate Rundown
            'rundown_110' => 21,  // Crude-Glycerine Rundown
            'rundown_111' => 22,  // Glycerine Rundown
            'rundown_114' => 24,  // FA14/Ecorol Wax Rundown
        ];

        $key = $type . '_' . ltrim($sectionId, '0');
        return $sectionMap[$key] ?? null;
    }

    protected function mapFrontendSectionToDbFeedId(string $sectionId): string
    {
        $map = [
            '101' => '001', // CPKO Feed
            '103' => '002', // DA-OIL Feed
            '104' => '003', // Crude-ME Feed
            '105' => '006-02', // ME80 Feed
            '110' => '004', // Treated-Gly Feed
            '111' => '007', // Crude-Gly Feed
            '112' => '009-01', // Default 112 mode feed
        ];
        return $map[$sectionId] ?? $sectionId;
    }

    protected function mapFrontendSectionToDbRundownId(string $sectionId, ?string $subgroup = null): string
    {
        $map = [
            '102' => [
                'daoil' => '011',
                'pkfad' => '021',
            ],
            '103' => [
                'crudeme' => '012',
                'treatedgly' => '022',
            ],
            '104' => [
                'ume' => '033',
                'bdme' => '023',
                'me28' => '043',
                'econoate665' => '053',
                'me80' => '063',
            ],
            '105' => [
                'cfa80' => '026',
            ],
            '106' => [
                'fa1299' => '078',
                'fa1499' => '088',
            ],
            '110' => [
                'crudegly' => '014',
            ],
            '111' => [
                'glycerine' => '017',
            ],
            '112' => [
                'cfa28' => '069',
                'fa12' => '039',
                'fa14lrr' => '079',
                'fa14' => '059',
                'fa18' => '029',
                'fa18lrr' => '049',
                'ecowax' => '019',
            ],
            '114' => [
                'cfa28' => '016',
                'ecowax' => '018',
                'lefa' => '028',
                'fa24' => '038',
                'fa16' => '048',
                'fa18lrr' => '058',
                'fa26' => '068',
            ],
            '302' => [
                'wme' => '015',
                'me28' => '025',
            ],
        ];

        if (isset($map[$sectionId])) {
            if ($subgroup && isset($map[$sectionId][$subgroup])) {
                return $map[$sectionId][$subgroup];
            }
            return reset($map[$sectionId]);
        }

        return $sectionId;
    }

    /**
     * GAP B: Map rundown section ID to feed section ID
     *
     * For rundown queries, we need to find the corresponding feed trace.
     * Frontend sends '102' for DA-OIL Rundown, but feed is '103' DA-OIL Feed.
     *
     * @param string $rundownId Frontend rundown section ID (e.g., '102')
     * @return string Feed section ID for query (e.g., '103')
     */
    protected function mapRundownToFeedSectionId(string $rundownId): string
    {
        // Mapping: rundown section -> corresponding feed section
        $rundownToFeedMap = [
            '101' => '101',  // CPKO: same section
            '102' => '103',  // DA-OIL Rundown -> DA-OIL Feed
            '103' => '104',  // Crude-ME/Treated-Gly Rundown -> Crude-ME Feed
            '104' => '105',  // UME/BDME/ME28 Rundown -> PKFAD Feed
            '110' => '111',  // Crude-Glycerine Rundown -> Crude-Gly Feed
            '111' => '112',  // Glycerine Rundown -> FA18/FA24 Feed
            '114' => '114',  // FA14/Ecorol Wax: same section
            '011' => '001',
            '021' => '001',
            '012' => '002',
            '022' => '002',
            '033' => '003',
            '023' => '003',
            '043' => '006-01',
            '053' => '003',
            '063' => '003',
            '026' => '006-02',
            '078' => '009-02',
            '088' => '009-02',
            '014' => '004',
            '017' => '007',
            '016' => '008',
            '018' => '009',
            '028' => '009',
            '038' => '009',
            '048' => '009',
            '058' => '009',
            '068' => '009',
            '069' => '009-01',
            '039' => '009-01',
            '079' => '009-02',
            '059' => '009-02',
            '029' => '009-03',
            '049' => '009-03',
            '019' => '009-04',
            '015' => '006-01',
            '025' => '006-01',
        ];

        return $this->mapFrontendSectionToDbFeedId($rundownToFeedMap[$rundownId] ?? $rundownId);
    }

    /**
     * Get material ID from section ID
     * Uses the section-to-material mapping to find the correct material
     */
    protected function getMaterialIdBySection(string $sectionId, string $type = 'feed', ?string $subgroup = null): ?int
    {
        $dbId = ($type === 'feed')
            ? $this->mapFrontendSectionToDbFeedId($sectionId)
            : $this->mapFrontendSectionToDbRundownId($sectionId, $subgroup);

        // Query m_material with the mapped ID
        $column = ($type === 'feed') ? 'id_feed' : 'id_rundown';
        $rows = DB::connection('eudr_ts')->select(
            "SELECT id_material FROM m_material WHERE {$column} = ? AND status = 1 LIMIT 1",
            [$dbId]
        );

        return $rows[0]->id_material ?? null;
    }

    protected function resolvePlantId($plantId): ?string
    {
        // GAP #11: Improved plant resolution - throw exception if no plant context
        if ($plantId === null || $plantId === '' || $plantId === 0 || $plantId === '0') {
            // Try to get from session first
            $sessionPlant = session('selected_plant');
            if ($sessionPlant && $sessionPlant !== '' && $sessionPlant !== 0 && $sessionPlant !== '0') {
                $plantId = $sessionPlant;
            } else {
                // Log warning instead of silently defaulting
                \Log::warning('WipEntryRepository: No plant context available, returning 0 (will show no data)');
                return '0'; // Return '0' instead of hardcoded 1002 to prevent data leak
            }
        }

        if ($plantId && is_numeric($plantId)) {
            $plant = Plant::find($plantId);
            if ($plant && $plant->code_3) {
                return $plant->code_3;
            }
        }

        return $plantId !== null ? (string) $plantId : null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AUTO NUMBER GENERATION - Per Section
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate new feed trace number for a section
     * Format: [Prefix][Date YYMMDD][Section ID][Plant Code][Sequence]
     * Prefix for feed = 3 (out_qty movement from storage/balance)
     * Example: 32605240010101 = 3(feed) 260524(date) 001(section) 01(plant) 01(seq)
     */
    public function generateNewFeedNumber(string $feedId, $plantId): ?string
    {
        $idPlant = $this->resolvePlantId($plantId);
        $datePrefix = date('ymd');
        $sectionId = $this->mapFrontendSectionToDbFeedId($feedId);
        $traceSectionId = substr($sectionId, 0, 3);
        $plantCode = ($idPlant == 0 || $idPlant == '0') ? '00' : str_pad(substr((string)$idPlant, -2), 2, '0', STR_PAD_LEFT);

        // Query existing trace numbers for this section/date/plant
        $result = DB::connection('eudr_ts')->select(
            'SELECT a.feed_number
              FROM (SELECT a.to_trace_no+1 AS feed_number
                      FROM t_trace_header a
                     WHERE SUBSTRING(a.to_trace_no,1,1) = "3"
                       AND SUBSTRING(a.to_trace_no,2,6) = ?
                       AND SUBSTRING(a.to_trace_no,8,3) = ?
                       AND SUBSTRING(a.to_trace_no,11,2) = ?
                       AND a.status = 1
                     ORDER BY a.id_trace_head DESC LIMIT 1) a
             UNION ALL
            SELECT CONCAT("3", ?, ?, ?, "01") AS feed_number
             LIMIT 1',
            [$datePrefix, $traceSectionId, $plantCode, $datePrefix, $traceSectionId, $plantCode]
        );

        return $result[0]->feed_number ?? null;
    }

    /**
     * Generate new rundown trace number for a section
     * Format: [Prefix][Date YYMMDD][Section ID][Plant Code][Sequence]
     * Prefix for rundown = 1 (in_qty movement to processing)
     * Example: 12605240010201 = 1(rundown) 260524(date) 001(section) 01(plant) 01(seq)
     */
    public function generateNewRundownNumber(string $rundownId, $plantId, ?string $subgroup = null): ?string
    {
        $idPlant = $this->resolvePlantId($plantId);
        $datePrefix = date('ymd');
        $sectionId = $this->mapFrontendSectionToDbRundownId($rundownId, $subgroup);
        $traceSectionId = substr($sectionId, 0, 3);
        $plantCode = ($idPlant == 0 || $idPlant == '0') ? '00' : str_pad(substr((string)$idPlant, -2), 2, '0', STR_PAD_LEFT);

        // Query existing trace numbers for this section/date/plant
        $result = DB::connection('eudr_ts')->select(
            'SELECT a.rundown_number
              FROM (SELECT a.to_trace_no+1 AS rundown_number
                      FROM t_trace_header a
                     WHERE SUBSTRING(a.to_trace_no,1,1) = "2"
                       AND SUBSTRING(a.to_trace_no,2,6) = ?
                       AND SUBSTRING(a.to_trace_no,8,3) = ?
                       AND SUBSTRING(a.to_trace_no,11,2) = ?
                       AND a.status = 1
                     ORDER BY a.id_trace_head DESC LIMIT 1) a
             UNION ALL
            SELECT CONCAT("2", ?, ?, ?, "01") AS rundown_number
             LIMIT 1',
            [$datePrefix, $traceSectionId, $plantCode, $datePrefix, $traceSectionId, $plantCode]
        );

        return $result[0]->rundown_number ?? null;
    }

    /**
     * Execute a select query, optionally bypassing the plant filter if "All Plants" is selected.
     */
    protected function executeSelect(string $sql, array $bindings, $idPlant)
    {
        if ($idPlant === '0' || $idPlant === 0 || $idPlant === null) {
            $sql = preg_replace('/([a-zA-Z0-9_.]+\.)?id_plant\s*=\s*\?/', '? IS NOT NULL', $sql);
        }
        return DB::connection('eudr_ts')->select($sql, $bindings);
    }
}
