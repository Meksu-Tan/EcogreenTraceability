<?php declare(strict_types=1);
namespace Modules\TsWip\Repositories\Traits;

use Illuminate\Support\Facades\DB;
use Modules\Plant\Models\Plant;

trait WipEntryQueryTrait
{
    public function getBalance(string $rundownId, $plantId, ?string $subgroup = null): array
    {
        $idPlant = $this->resolvePlantId($plantId);
        $dbRundownId = $this->mapFrontendSectionToDbRundownId($rundownId, $subgroup);
        $column = (strpos($dbRundownId, '00') === 0) ? 'id_feed' : 'id_rundown';

        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))');

        // Handle "all plants" case
        $plantFilter = ($idPlant === '0' || $idPlant === 0 || $idPlant === null) ? '1=1' : 'd.id_plant = ?';
        $bindings = ($idPlant === '0' || $idPlant === 0 || $idPlant === null) ? [$dbRundownId] : [$idPlant, $dbRundownId];

        $rows = DB::connection('eudr_ts')->select('
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
                                    ) a ON a.id_sloc = d.id_sloc OR (JSON_VALID(a.id_sloc) AND (JSON_CONTAINS(a.id_sloc, CAST(d.id_sloc AS CHAR)) OR JSON_CONTAINS(a.id_sloc, JSON_QUOTE(CAST(d.id_sloc AS CHAR)))))
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
                                   WHERE ' . $plantFilter . '
                                     AND d.code_3 <> "STORAGE"
                                   GROUP BY a.trace_no) e
                       ON d.id_material = e.id_material
                       LEFT JOIN m_plant p ON e.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                    WHERE c.status = 1
                      AND c.' . $column . ' = ?
                    ) aa
              ORDER BY entry_date DESC
        ', $bindings);

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

        $limit = ($mode === 'LOG') ? 50 : 1;
        $offset = 0;

        if ($mode === 'LATEST') {
            // Handle "all plants" case
            $plantFilter = ($idPlant === '0' || $idPlant === 0 || $idPlant === null) ? '1=1' : 'a.id_plant = ?';
            $subqueryPlantFilter = ($idPlant === '0' || $idPlant === 0 || $idPlant === null) ? '1=1' : 'a.id_plant = ?';
            $bindings = ($idPlant === '0' || $idPlant === 0 || $idPlant === null) 
                ? [$feedPrefix, $this->movType2] 
                : [$idPlant, $feedPrefix, $this->movType2, $idPlant];

            $rows = DB::connection('eudr_ts')->select('
                 SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no,
                        a.id_balance_head, a.id_material, g.material_document, a.id_sloc, a.id_sloc_tail,
                        FORMAT(ROUND(h.out_qty,3),3) AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                        GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                        b.batch_sap, FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf,
                        GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(ROUND(b.out_qty,3),3), " MT") SEPARATOR " | ") AS supplier,
                        IF(ABS(ROUND(bs.supplier_qty,3) - ROUND(h.out_qty,3)) > 0.005, FORMAT(ROUND(bs.supplier_qty,3),3), FORMAT(ROUND(h.out_qty,3),3)) AS balance_supplier,
                        CONCAT(
                            COALESCE(i.description, ""),
                            IF(
                                GROUP_CONCAT(DISTINCT COALESCE(j.id_tank, jd.tf_number) ORDER BY COALESCE(j.id_tank, jd.tf_number) ASC SEPARATOR " & ") IS NULL,
                                "",
                                CONCAT(" | ", GROUP_CONCAT(DISTINCT COALESCE(j.id_tank, jd.tf_number) ORDER BY COALESCE(j.id_tank, jd.tf_number) ASC SEPARATOR " & "))
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
                               WHERE a.status = 1 AND ' . $subqueryPlantFilter . '
                               GROUP BY a.to_trace_no) h ON a.to_trace_no = h.to_trace_no
                   LEFT JOIN m_sloc i ON 
                       (a.id_sloc IS NOT NULL AND (i.id_sloc = a.id_sloc OR (JSON_VALID(a.id_sloc) AND (JSON_CONTAINS(a.id_sloc, CAST(i.id_sloc AS CHAR)) OR JSON_CONTAINS(a.id_sloc, JSON_QUOTE(CAST(i.id_sloc AS CHAR)))))))
                       OR
                       (a.id_sloc IS NULL AND i.id_sloc = (
                           SELECT ms.id_sloc FROM m_sloc ms 
                           JOIN m_tank mt ON ms.code COLLATE utf8mb4_unicode_ci = mt.code COLLATE utf8mb4_unicode_ci AND ms.id_plant COLLATE utf8mb4_unicode_ci = mt.id_plant COLLATE utf8mb4_unicode_ci 
                           WHERE mt.id_tank = a.id_tank 
                           LIMIT 1
                       ))
                   LEFT JOIN m_sloc j ON 
                       (a.id_sloc IS NOT NULL AND (j.id_sloc = a.id_sloc OR (JSON_VALID(a.id_sloc) AND (JSON_CONTAINS(a.id_sloc, CAST(j.id_sloc AS CHAR)) OR JSON_CONTAINS(a.id_sloc, JSON_QUOTE(CAST(j.id_sloc AS CHAR)))))))
                   LEFT JOIN m_sloc_detail jd ON JSON_CONTAINS(a.id_sloc_tail, JSON_QUOTE(CAST(jd.id_sloc_tail AS CHAR)))
                   LEFT JOIN (SELECT h.to_trace_no, SUM(d.out_qty) AS supplier_qty
                                FROM t_trace_header h
                                JOIN t_trace_detail d ON h.id_trace_head = d.id_trace_head
                               WHERE d.out_qty > 0 AND h.status = 1
                               GROUP BY h.to_trace_no) bs ON bs.to_trace_no = a.to_trace_no
                   LEFT JOIN m_plant p ON a.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                  WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                    AND a.out_qty > 0 AND b.out_qty > 0
                    AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                    AND a.status = 1 AND ' . $plantFilter . '
                  GROUP BY a.to_trace_no
                  ORDER BY a.to_trace_no DESC
                  LIMIT 5
            ', $bindings);
        } else {
            // Handle "all plants" case
            $plantFilter = ($idPlant === '0' || $idPlant === 0 || $idPlant === null) ? '1=1' : 'a.id_plant = ?';
            $subqueryPlantFilter = ($idPlant === '0' || $idPlant === 0 || $idPlant === null) ? '1=1' : 'a.id_plant = ?';
            
            // Build bindings based on plant filter
            if ($idPlant === '0' || $idPlant === 0 || $idPlant === null) {
                $bindings = [$feedPrefix, $this->movType2, 
                    $this->movType2, substr($feedPrefix, 1, 1),
                    $feedPrefix, $this->movType2];
            } else {
                $bindings = [$feedPrefix, $this->movType2, $idPlant,
                    $this->movType2, substr($feedPrefix, 1, 1), $idPlant,
                    $idPlant, $feedPrefix, $this->movType2, $idPlant];
            }

            $rows = DB::connection('eudr_ts')->select('
                 SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no,
                        a.id_balance_head, a.id_material,
                        FORMAT(ROUND(h.out_qty,3),3) AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                        GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                        FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf,
                        g.material_document, b.batch_sap,
                        GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(ROUND(b.out_qty,3),3), " MT") SEPARATOR " | ") AS supplier,
                        IF(ABS(ROUND(SUM(b.out_qty),3) - ROUND(h.out_qty,3)) > 0.005, FORMAT(ROUND(SUM(b.out_qty),3),3), FORMAT(ROUND(h.out_qty,3),3)) AS balance_supplier,
                        CONCAT(
                            COALESCE(i.description, ""),
                            IF(
                                GROUP_CONCAT(DISTINCT COALESCE(j.id_tank, jd.tf_number) ORDER BY COALESCE(j.id_tank, jd.tf_number) ASC SEPARATOR " & ") IS NULL,
                                "",
                                CONCAT(" | ", GROUP_CONCAT(DISTINCT COALESCE(j.id_tank, jd.tf_number) ORDER BY COALESCE(j.id_tank, jd.tf_number) ASC SEPARATOR " & "))
                            )
                        ) AS sloc,
                        CASE WHEN a.to_trace_no = (SELECT to_trace_no FROM t_trace_header
                                                    WHERE SUBSTRING(to_trace_no, 8, 3) = ?
                                                      AND SUBSTRING(to_trace_no, 1, 1) = ?
                                                      AND status = 1 AND ' . $plantFilter . '
                                                    ORDER BY to_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS is_last_row,
                        CASE WHEN a.to_trace_no = (SELECT from_trace_no FROM t_trace_header
                                                    WHERE SUBSTRING(from_trace_no, 1, 1) = ?
                                                      AND SUBSTRING(from_trace_no, 10, 1) = ?
                                                      AND status = 1 AND ' . $plantFilter . '
                                                    ORDER BY from_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS next_process,
                        a.id_plant, p.description AS plant_name
                   FROM t_trace_header a
                   LEFT JOIN t_trace_detail b ON a.id_trace_head = b.id_trace_head
                   LEFT JOIN m_material c ON a.id_material = c.id_material
                   LEFT JOIN m_supplier e ON e.id_supplier = b.id_supplier
                   LEFT JOIN t_material_document g ON a.id_trace_head = g.id_trace_head
                   LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                                FROM t_trace_header a
                               WHERE a.status = 1 AND ' . $subqueryPlantFilter . '
                               GROUP BY a.to_trace_no) h ON a.to_trace_no = h.to_trace_no
                   LEFT JOIN m_sloc i ON 
                       (a.id_sloc IS NOT NULL AND (i.id_sloc = a.id_sloc OR (JSON_VALID(a.id_sloc) AND (JSON_CONTAINS(a.id_sloc, CAST(i.id_sloc AS CHAR)) OR JSON_CONTAINS(a.id_sloc, JSON_QUOTE(CAST(i.id_sloc AS CHAR)))))))
                       OR
                       (a.id_sloc IS NULL AND i.id_sloc = (
                           SELECT ms.id_sloc FROM m_sloc ms 
                           JOIN m_tank mt ON ms.code COLLATE utf8mb4_unicode_ci = mt.code COLLATE utf8mb4_unicode_ci AND ms.id_plant COLLATE utf8mb4_unicode_ci = mt.id_plant COLLATE utf8mb4_unicode_ci 
                           WHERE mt.id_tank = a.id_tank 
                           LIMIT 1
                       ))
                   LEFT JOIN m_sloc j ON 
                       (a.id_sloc IS NOT NULL AND (j.id_sloc = a.id_sloc OR (JSON_VALID(a.id_sloc) AND (JSON_CONTAINS(a.id_sloc, CAST(j.id_sloc AS CHAR)) OR JSON_CONTAINS(a.id_sloc, JSON_QUOTE(CAST(j.id_sloc AS CHAR)))))))
                   LEFT JOIN m_sloc_detail jd ON JSON_CONTAINS(a.id_sloc_tail, JSON_QUOTE(CAST(jd.id_sloc_tail AS CHAR)))
                   LEFT JOIN m_plant p ON a.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                  WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                    AND a.out_qty > 0 AND b.out_qty > 0
                    AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                    AND a.status = 1 AND ' . $plantFilter . '
                  GROUP BY a.to_trace_no
                  ORDER BY a.id_trace_head DESC
                    LIMIT ' . $limit . ' OFFSET ' . $offset . '
            ', $bindings);
        }

        return $rows;
    }

    protected function getFeedWithMaterialSign(string $feedId, string $feedPrefix, string $mode, $idPlant): array
    {
        $idMatlSign = substr($feedId, 4, 2);
        $feedIdShort = $feedPrefix;

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
                       CONCAT(
                           COALESCE(i.description, ""),
                           IF(
                               GROUP_CONCAT(DISTINCT COALESCE(j.id_tank, jd.tf_number) ORDER BY COALESCE(j.id_tank, jd.tf_number) ASC SEPARATOR " & ") IS NULL,
                               "",
                               CONCAT(" | ", GROUP_CONCAT(DISTINCT COALESCE(j.id_tank, jd.tf_number) ORDER BY COALESCE(j.id_tank, jd.tf_number) ASC SEPARATOR " & "))
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
                  LEFT JOIN m_sloc i ON 
                      (a.id_sloc IS NOT NULL AND (i.id_sloc = a.id_sloc OR (JSON_VALID(a.id_sloc) AND (JSON_CONTAINS(a.id_sloc, CAST(i.id_sloc AS CHAR)) OR JSON_CONTAINS(a.id_sloc, JSON_QUOTE(CAST(i.id_sloc AS CHAR)))))))
                      OR
                      (a.id_sloc IS NULL AND i.id_sloc = (
                          SELECT ms.id_sloc FROM m_sloc ms 
                          JOIN m_tank mt ON ms.code COLLATE utf8mb4_unicode_ci = mt.code COLLATE utf8mb4_unicode_ci AND ms.id_plant COLLATE utf8mb4_unicode_ci = mt.id_plant COLLATE utf8mb4_unicode_ci 
                          WHERE mt.id_tank = a.id_tank 
                          LIMIT 1
                      ))
                  LEFT JOIN m_sloc j ON 
                      (a.id_sloc IS NOT NULL AND (j.id_sloc = a.id_sloc OR (JSON_VALID(a.id_sloc) AND (JSON_CONTAINS(a.id_sloc, CAST(j.id_sloc AS CHAR)) OR JSON_CONTAINS(a.id_sloc, JSON_QUOTE(CAST(j.id_sloc AS CHAR)))))))
                  LEFT JOIN m_sloc_detail jd ON JSON_CONTAINS(a.id_sloc_tail, JSON_QUOTE(CAST(jd.id_sloc_tail AS CHAR)))
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
                       CONCAT(
                           COALESCE(i.description, ""),
                           IF(
                               GROUP_CONCAT(DISTINCT COALESCE(j.id_tank, jd.tf_number) ORDER BY COALESCE(j.id_tank, jd.tf_number) ASC SEPARATOR " & ") IS NULL,
                               "",
                               CONCAT(" | ", GROUP_CONCAT(DISTINCT COALESCE(j.id_tank, jd.tf_number) ORDER BY COALESCE(j.id_tank, jd.tf_number) ASC SEPARATOR " & "))
                           )
                       ) AS sloc,
                       CASE WHEN a.to_trace_no = (SELECT to_trace_no FROM t_trace_header
                                                   WHERE SUBSTRING(to_trace_no, 8, 3) = ?
                                                     AND SUBSTRING(to_trace_no, 1, 1) = ?
                                                     AND ' . $matlWhere . '
                                                     AND status = 1 AND $plantFilter
                                                   ORDER BY to_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS is_last_row,
                       CASE WHEN a.to_trace_no = (SELECT from_trace_no FROM t_trace_header
                                                   WHERE from_trace_no = a.to_trace_no
                                                     AND ' . $matlWhere . '
                                                     AND status = 1 AND $plantFilter
                                                   ORDER BY from_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS next_process,
                       a.id_plant, p.description AS plant_name
                  FROM t_trace_header a
                  LEFT JOIN t_trace_detail b ON a.id_trace_head = b.id_trace_head
                  LEFT JOIN m_material c ON a.id_material = c.id_material
                  LEFT JOIN m_supplier e ON e.id_supplier = b.id_supplier
                  LEFT JOIN t_material_document g ON a.id_trace_head = g.id_trace_head
                  LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                               FROM t_trace_header a WHERE a.status = 1 AND a.id_plant = ? GROUP BY a.to_trace_no) h ON a.to_trace_no = h.to_trace_no
                  LEFT JOIN m_sloc i ON 
                      (a.id_sloc IS NOT NULL AND (i.id_sloc = a.id_sloc OR (JSON_VALID(a.id_sloc) AND (JSON_CONTAINS(a.id_sloc, CAST(i.id_sloc AS CHAR)) OR JSON_CONTAINS(a.id_sloc, JSON_QUOTE(CAST(i.id_sloc AS CHAR)))))))
                      OR
                      (a.id_sloc IS NULL AND i.id_sloc = (
                          SELECT ms.id_sloc FROM m_sloc ms 
                          JOIN m_tank mt ON ms.code COLLATE utf8mb4_unicode_ci = mt.code COLLATE utf8mb4_unicode_ci AND ms.id_plant COLLATE utf8mb4_unicode_ci = mt.id_plant COLLATE utf8mb4_unicode_ci 
                          WHERE mt.id_tank = a.id_tank 
                          LIMIT 1
                      ))
                  LEFT JOIN m_sloc j ON 
                      (a.id_sloc IS NOT NULL AND (j.id_sloc = a.id_sloc OR (JSON_VALID(a.id_sloc) AND (JSON_CONTAINS(a.id_sloc, CAST(j.id_sloc AS CHAR)) OR JSON_CONTAINS(a.id_sloc, JSON_QUOTE(CAST(j.id_sloc AS CHAR)))))))
                  LEFT JOIN m_sloc_detail jd ON JSON_CONTAINS(a.id_sloc_tail, JSON_QUOTE(CAST(jd.id_sloc_tail AS CHAR)))
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

        // Handle "all plants" case
        $plantFilter = ($idPlant === '0' || $idPlant === 0 || $idPlant === null) ? '1=1' : 'a.id_plant = ?';
        $subqueryPlantFilter = ($idPlant === '0' || $idPlant === 0 || $idPlant === null) ? '1=1' : 'a.id_plant = ?';
        
        // Replace plant filters in SQL
        $sql = str_replace('AND a.id_plant = ?', 'AND ' . $plantFilter, $sql);
        $sql = str_replace('WHERE a.status = 1 AND a.id_plant = ?', 'WHERE a.status = 1 AND ' . $subqueryPlantFilter, $sql);
        $sql = str_replace('AND status = 1 AND $plantFilter', 'AND status = 1 AND ' . $plantFilter, $sql);
        
        // Filter bindings to remove plantId when showing all plants
        if ($idPlant === '0' || $idPlant === 0 || $idPlant === null) {
            $filteredParams = [];
            foreach ($params as $param) {
                if ($param !== $idPlant && $param !== '0' && $param !== 0) {
                    $filteredParams[] = $param;
                }
            }
            $params = $filteredParams;
        }
        
        return DB::connection('eudr_ts')->select($sql, $params);
    }

    public function getRundown(string $rundownId, string $mode, $plantId): array
    {
        $rundownId = $this->mapFrontendSectionToDbRundownId($rundownId);
        $idPlant = $this->resolvePlantId($plantId);
        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))');

        $limit = ($mode === 'LOG') ? 50 : 1;
        $offset = 0;

        if ($mode === 'LATEST') {
            // Handle "all plants" case
            $plantFilter = ($idPlant === '0' || $idPlant === 0 || $idPlant === null) ? '1=1' : 'a.id_plant = ?';
            $bindings = ($idPlant === '0' || $idPlant === 0 || $idPlant === null) 
                ? [$rundownId, $this->movType1] 
                : [$rundownId, $this->movType1, $idPlant];

            $rows = DB::connection('eudr_ts')->select('
                SELECT a.id_trace_head, a.entry_date, a.to_trace_no AS rundown_trace_no,
                       a.id_balance_head, a.id_material, a.id_sloc, a.id_sloc_tail,
                       FORMAT(ROUND(h.in_qty,3),3) AS in_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                       CONCAT(c.code, " :: ", c.description) AS material, g.material_document,
                       FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf, b.batch_sap,
                       GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(b.in_qty,3), " MT") SEPARATOR " | ") AS supplier,
                       FORMAT(ROUND(bs.supplier_qty,3),3) AS balance_supplier,
                       CONCAT(
                           COALESCE(i.description, ""),
                           IF(
                               GROUP_CONCAT(DISTINCT COALESCE(j.id_tank, jd.tf_number) ORDER BY COALESCE(j.id_tank, jd.tf_number) ASC SEPARATOR " & ") IS NULL,
                               "",
                               CONCAT(" | ", GROUP_CONCAT(DISTINCT COALESCE(j.id_tank, jd.tf_number) ORDER BY COALESCE(j.id_tank, jd.tf_number) ASC SEPARATOR " & "))
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
                  LEFT JOIN m_sloc i ON 
                      (a.id_sloc IS NOT NULL AND (i.id_sloc = a.id_sloc OR (JSON_VALID(a.id_sloc) AND (JSON_CONTAINS(a.id_sloc, CAST(i.id_sloc AS CHAR)) OR JSON_CONTAINS(a.id_sloc, JSON_QUOTE(CAST(i.id_sloc AS CHAR)))))))
                      OR
                      (a.id_sloc IS NULL AND i.id_sloc = (
                          SELECT ms.id_sloc FROM m_sloc ms 
                          JOIN m_tank mt ON ms.code COLLATE utf8mb4_unicode_ci = mt.code COLLATE utf8mb4_unicode_ci AND ms.id_plant COLLATE utf8mb4_unicode_ci = mt.id_plant COLLATE utf8mb4_unicode_ci 
                          WHERE mt.id_tank = a.id_tank 
                          LIMIT 1
                      ))
                  LEFT JOIN m_sloc j ON 
                      (a.id_sloc IS NOT NULL AND (j.id_sloc = a.id_sloc OR (JSON_VALID(a.id_sloc) AND (JSON_CONTAINS(a.id_sloc, CAST(j.id_sloc AS CHAR)) OR JSON_CONTAINS(a.id_sloc, JSON_QUOTE(CAST(j.id_sloc AS CHAR)))))))
                  LEFT JOIN m_sloc_detail jd ON JSON_CONTAINS(a.id_sloc_tail, JSON_QUOTE(CAST(jd.id_sloc_tail AS CHAR)))
                  LEFT JOIN (SELECT id_trace_head, SUM(in_qty) AS supplier_qty
                               FROM t_trace_detail WHERE in_qty > 0 GROUP BY id_trace_head) bs ON bs.id_trace_head = a.id_trace_head
                  LEFT JOIN m_plant p ON a.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                 WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                   AND a.in_qty > 0 AND b.in_qty > 0
                   AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                   AND a.status = 1 AND ' . $plantFilter . '
                 GROUP BY a.to_trace_no
                 ORDER BY a.to_trace_no DESC
                 LIMIT 1
            ', $bindings);
        } else {
            // Handle "all plants" case
            $plantFilter = ($idPlant === '0' || $idPlant === 0 || $idPlant === null) ? '1=1' : 'a.id_plant = ?';
            $bindings = ($idPlant === '0' || $idPlant === 0 || $idPlant === null) 
                ? [$this->movType1, $this->movType2, $rundownId, $rundownId, $this->movType1]
                : [$this->movType1, $this->movType2, $rundownId, $idPlant, $idPlant, $rundownId, $this->movType1, $idPlant];

            $rows = DB::connection('eudr_ts')->select('
                SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no,
                       a.id_balance_head, a.id_material,
                       FORMAT(ROUND(h.in_qty,3),3) AS in_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                       CONCAT(c.code, " :: ", c.description) AS material, g.material_document,
                       FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf, b.batch_sap,
                       GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(b.in_qty,3), " MT") SEPARATOR " | ") AS supplier,
                       FORMAT(ROUND(SUM(b.in_qty),3),3) AS balance_supplier,
                       CONCAT(
                           COALESCE(i.description, ""),
                           IF(
                               GROUP_CONCAT(DISTINCT COALESCE(j.id_tank, jd.tf_number) ORDER BY COALESCE(j.id_tank, jd.tf_number) ASC SEPARATOR " & ") IS NULL,
                               "",
                               CONCAT(" | ", GROUP_CONCAT(DISTINCT COALESCE(j.id_tank, jd.tf_number) ORDER BY COALESCE(j.id_tank, jd.tf_number) ASC SEPARATOR " & "))
                           )
                       ) AS sloc,
                       CASE WHEN a.to_trace_no = (SELECT to_trace_no FROM t_trace_header
                                                   WHERE (SUBSTRING(to_trace_no, 1, 1) = ? OR SUBSTRING(to_trace_no, 1, 1) = ?)
                                                     AND SUBSTRING(to_trace_no, 8, 3) = ?
                                                     AND status = 1 AND $plantFilter
                                                   ORDER BY to_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS is_last_row,
                       CASE WHEN a.to_trace_no = (SELECT from_trace_no FROM t_trace_header
                                                   WHERE from_trace_no = a.to_trace_no
                                                     AND status = 1 AND $plantFilter
                                                   ORDER BY from_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS next_process,
                       a.id_plant, p.description AS plant_name
                  FROM t_trace_header a
                  LEFT JOIN t_trace_detail b ON a.id_trace_head = b.id_trace_head
                  LEFT JOIN m_material c ON a.id_material = c.id_material
                  LEFT JOIN m_supplier e ON e.id_supplier = b.id_supplier
                  LEFT JOIN t_material_document g ON a.id_trace_head = g.id_trace_head
                  LEFT JOIN (SELECT a.to_trace_no, SUM(a.in_qty) AS in_qty
                               FROM t_trace_header a WHERE a.status = 1 GROUP BY a.to_trace_no) h ON a.to_trace_no = h.to_trace_no
                  LEFT JOIN m_sloc i ON 
                      (a.id_sloc IS NOT NULL AND (i.id_sloc = a.id_sloc OR (JSON_VALID(a.id_sloc) AND (JSON_CONTAINS(a.id_sloc, CAST(i.id_sloc AS CHAR)) OR JSON_CONTAINS(a.id_sloc, JSON_QUOTE(CAST(i.id_sloc AS CHAR)))))))
                      OR
                      (a.id_sloc IS NULL AND i.id_sloc = (
                          SELECT ms.id_sloc FROM m_sloc ms 
                          JOIN m_tank mt ON ms.code COLLATE utf8mb4_unicode_ci = mt.code COLLATE utf8mb4_unicode_ci AND ms.id_plant COLLATE utf8mb4_unicode_ci = mt.id_plant COLLATE utf8mb4_unicode_ci 
                          WHERE mt.id_tank = a.id_tank 
                          LIMIT 1
                      ))
                  LEFT JOIN m_sloc j ON 
                      (a.id_sloc IS NOT NULL AND (j.id_sloc = a.id_sloc OR (JSON_VALID(a.id_sloc) AND (JSON_CONTAINS(a.id_sloc, CAST(j.id_sloc AS CHAR)) OR JSON_CONTAINS(a.id_sloc, JSON_QUOTE(CAST(j.id_sloc AS CHAR)))))))
                  LEFT JOIN m_sloc_detail jd ON JSON_CONTAINS(a.id_sloc_tail, JSON_QUOTE(CAST(jd.id_sloc_tail AS CHAR)))
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
            SELECT id_sloc, id_sloc AS id_tank, description AS tank, id_plant,
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
            SELECT id_sloc AS id_sloc_tail, id_sloc AS id_tank_tail, id_tank AS tankNo
              FROM m_sloc
             WHERE status = 1
               AND description = (SELECT description FROM m_sloc WHERE id_sloc = ?)
               AND id_plant = (SELECT id_plant FROM m_sloc WHERE id_sloc = ?)
             ORDER BY id_tank ASC
        ', [$slocId, $slocId]);
    }

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

    public function getWipTree($plantId): array
    {
        $idPlant = $this->resolvePlantId($plantId);
        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))');

        // Handle "all plants" case
        $plantFilter = ($idPlant === '0' || $idPlant === 0 || $idPlant === null) ? '1=1' : 'id_plant = ?';
        $bindings = ($idPlant === '0' || $idPlant === 0 || $idPlant === null) 
            ? [] 
            : [$idPlant, $idPlant, $idPlant, $idPlant, $idPlant, $idPlant, $idPlant];

        $sections = DB::connection('eudr_ts')->select("
            SELECT
                m.id_rundown AS section_id,
                m.code AS section_code,
                m.description AS section_name,
                (SELECT to_trace_no FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '3'
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND $plantFilter
                 ORDER BY id_trace_head DESC LIMIT 1) AS latest_feed_trace,
                (SELECT entry_date FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '3'
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND $plantFilter
                 ORDER BY id_trace_head DESC LIMIT 1) AS latest_feed_date,
                (SELECT curr_qtf FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '3'
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND $plantFilter
                 ORDER BY id_trace_head DESC LIMIT 1) AS latest_feed_qty,
                (SELECT to_trace_no FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '2'
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND $plantFilter
                 ORDER BY id_trace_head DESC LIMIT 1) AS latest_rundown_trace,
                (SELECT entry_date FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '2'
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND $plantFilter
                 ORDER BY id_trace_head DESC LIMIT 1) AS latest_rundown_date,
                (SELECT curr_qtf FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '2'
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND $plantFilter
                 ORDER BY id_trace_head DESC LIMIT 1) AS latest_rundown_qty
            FROM m_material m
            WHERE m.status = 1
              AND m.type IN ('WIP', 'RM')
            ORDER BY m.id_rundown
        ", $bindings);

        return $sections;
    }

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

    public function checkPeriodLock(string $date): bool
    {
        // Use shared PeriodLockService for consistent date lock mechanism
        return \Modules\Shared\Services\PeriodLockService::isLocked($date);
    }

    protected function mapSectionToMaterialId(string $sectionId, string $type = 'feed'): ?int
    {
        $sectionMap = [
            'feed_101' => 1,
            'feed_102' => 2,
            'feed_103' => 3,
            'feed_104' => 4,
            'feed_105' => 5,
            'feed_111' => 11,
            'feed_112' => 12,
            'feed_114' => 14,
            'rundown_101' => 1,
            'rundown_102' => 11,
            'rundown_103' => 12,
            'rundown_104' => 13,
            'rundown_110' => 21,
            'rundown_111' => 22,
            'rundown_114' => 24,
        ];

        $key = $type . '_' . ltrim($sectionId, '0');
        return $sectionMap[$key] ?? null;
    }

    protected function mapFrontendSectionToDbFeedId(string $sectionId): string
    {
        $map = [
            '101' => '001',
            '103' => '002',
            '104' => '003',
            '105' => '006-02',
            '110' => '004',
            '111' => '007',
            '112' => '009-01',
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

    protected function mapRundownToFeedSectionId(string $rundownId): string
    {
        $rundownToFeedMap = [
            '101' => '101',
            '102' => '103',
            '103' => '104',
            '104' => '105',
            '110' => '111',
            '111' => '112',
            '114' => '114',
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

    protected function getMaterialIdBySection(string $sectionId, string $type = 'feed', ?string $subgroup = null): ?int
    {
        $dbId = ($type === 'feed')
            ? $this->mapFrontendSectionToDbFeedId($sectionId)
            : $this->mapFrontendSectionToDbRundownId($sectionId, $subgroup);

        $column = ($type === 'feed') ? 'id_feed' : 'id_rundown';
        $rows = DB::connection('eudr_ts')->select(
            "SELECT id_material FROM m_material WHERE {$column} = ? AND status = 1 LIMIT 1",
            [$dbId]
        );

        return $rows[0]->id_material ?? null;
    }

    protected function resolvePlantId($plantId): ?string
    {
        if ($plantId === null || $plantId === '' || $plantId === 0 || $plantId === '0') {
            // When plantId is 0, return '0' to indicate "all plants"
            // The executeSelect method will handle this by removing plant filters
            return '0';
        }

        if ($plantId && is_numeric($plantId)) {
            $plant = Plant::find($plantId);
            if ($plant && $plant->code_3) {
                return $plant->code_3;
            }
        }

        return $plantId !== null ? (string) $plantId : null;
    }

    protected function executeSelect(string $sql, array $bindings, $idPlant)
    {
        return DB::connection('eudr_ts')->select($sql, $bindings);
    }
}
