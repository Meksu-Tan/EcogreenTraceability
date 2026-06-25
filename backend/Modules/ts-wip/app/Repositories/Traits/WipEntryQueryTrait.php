<?php
declare(strict_types=1);
namespace Modules\TsWip\Repositories\Traits;

use Illuminate\Support\Facades\DB;
use Modules\Plant\Models\Plant;
use Modules\Shared\Traits\DbCompatTrait;

trait WipEntryQueryTrait
{
    use DbCompatTrait;

    public function getBalance(string $rundownId, $plantId, ?string $subgroup = null, int $page = 1, int $perPage = 5): array
    {
        $idPlant = $this->resolvePlantId($plantId);
        $dbRundownId = $this->mapFrontendSectionToDbRundownId($rundownId, $subgroup);
        $dbRundownIdStripped = ltrim(substr($dbRundownId, 0, 3), '0') ?: $dbRundownId;
        $column = (strpos($dbRundownId, '00') === 0) ? 'id_feed' : 'id_rundown';

        // Handle "all plants" case
        $plantFilter = ($idPlant === '0' || $idPlant === null) ? '1=1' : 'd.id_plant = ?';
        $bindings = ($idPlant === '0' || $idPlant === null) ? [$dbRundownIdStripped] : [$idPlant, $dbRundownIdStripped];

        $offset = ($page - 1) * $perPage;

        $materialConcat = $this->dbGroupConcat("CONCAT(c.code, ' :: ', c.description)", ' | ', true);
        $detailConcat = $this->dbGroupConcat('CAST(b.id_balance_tail AS TEXT)', ',', true);
        $supplierConcat = $this->dbGroupConcat(
            "CONCAT(e.description, ' / ', b.batch_sap, ' / Qty : ', {$this->dbNumberFormat('b.init_qty', 3)}, ' MT / Qty : ', {$this->dbNumberFormat('b.qty', 3)}, ' MT')",
            ' | ',
            true
        );
        $sumFormat = $this->dbNumberFormat('SUM(b.init_qty)', 3);
        $qtyFormat1 = $this->dbNumberFormat('SUM(qty)', 3);
        $qtyFormat2 = $this->dbNumberFormat('SUM(init_qty)', 3);
        $slocJoinClause = $this->dbSlocJsonClause('a.id_sloc', 'd.id_sloc');

        $baseSql = <<<SQL
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
                                         {$materialConcat} AS material,
                                         a.id_sloc AS tf_number, a.entry_date,
                                         {$detailConcat} AS id_balance_detail,
                                         {$supplierConcat} AS supplier,
                                         {$sumFormat} AS balance_supplier,
                                         COALESCE(f.to_trace_no, 'N/A') AS traced, f.material_document,
                                         a.id_plant
                                    FROM m_sloc d
                                    LEFT JOIN (
                                         SELECT a.id_sloc, a.id_balance_head, a.id_material, a.status, a.trace_no,
                                                a.created_by, a.created_at, a.entry_date, a.id_plant
                                           FROM t_balance_header a
                                          WHERE a.status = 1 AND a.id_sloc IS NOT NULL
                                            AND (SUBSTRING(a.trace_no::text,1,1) = '1' OR SUBSTRING(a.trace_no::text,1,1) = '2' OR SUBSTRING(a.trace_no::text,1,1) = '7' OR
                                                 SUBSTRING(a.trace_no::text,1,1) = '8' OR SUBSTRING(a.trace_no::text,1,1) = '9')
                                         UNION ALL
                                         SELECT a.id_sloc, a.id_balance_head, a.id_material, a.status, a.trace_no,
                                                a.created_by, a.created_at, a.entry_date, a.id_plant
                                           FROM t_balance_header a
                                          WHERE a.status = 1 AND a.id_sloc IS NULL
                                            AND (SUBSTRING(a.trace_no::text,1,1) = '1' OR SUBSTRING(a.trace_no::text,1,1) = '2' OR SUBSTRING(a.trace_no::text,1,1) = '7' OR
                                                 SUBSTRING(a.trace_no::text,1,1) = '8' OR SUBSTRING(a.trace_no::text,1,1) = '9')
                                    ) a ON {$slocJoinClause}
                                    LEFT JOIN (SELECT id_balance_head, {$qtyFormat1} AS qty, {$qtyFormat2} AS init_qty
                                                 FROM t_balance_header
                                                WHERE status = 1
                                                  AND (SUBSTRING(trace_no::text,1,1) = '1' OR SUBSTRING(trace_no::text,1,1) = '2' OR SUBSTRING(trace_no::text,1,1) = '7' OR
                                                       SUBSTRING(trace_no::text,1,1) = '8' OR SUBSTRING(trace_no::text,1,1) = '9')
                                                GROUP BY trace_no, id_balance_head) aa
                                      ON a.id_balance_head = aa.id_balance_head
                                    LEFT JOIN t_balance_detail b
                                      ON a.id_balance_head = b.id_balance_head AND b.init_qty > 0.0001
                                    LEFT JOIN m_material c
                                      ON a.id_material = c.id_material
                                    LEFT JOIN m_supplier e
                                      ON e.id_supplier = b.id_supplier
                                    LEFT JOIN (SELECT f.id_balance_head,
                                                      MAX(g.material_document) AS material_document,
                                                      MAX(f.to_trace_no) AS to_trace_no
                                                 FROM t_trace_header f
                                                 LEFT JOIN t_material_document g
                                                   ON f.id_trace_head = g.id_trace_head
                                                WHERE f.status = 1
                                                  AND (SUBSTRING(f.to_trace_no::text,1,1) = '1' OR SUBSTRING(f.to_trace_no::text,1,1) = '2' OR SUBSTRING(f.to_trace_no::text,1,1) = '7' OR
                                                       SUBSTRING(f.to_trace_no::text,1,1) = '8' OR SUBSTRING(f.to_trace_no::text,1,1) = '9')
                                                GROUP BY f.id_balance_head) f
                                      ON f.id_balance_head = a.id_balance_head
                                    WHERE {$plantFilter}
                                     AND 1=1
                                    GROUP BY a.trace_no, a.id_balance_head, a.id_material, a.id_sloc, a.status, aa.qty, a.created_by, a.created_at, aa.init_qty, a.entry_date, f.to_trace_no, f.material_document, a.id_plant) e
                       ON d.id_material = e.id_material
                       LEFT JOIN m_plant p ON e.id_plant = p.code_3
                    WHERE c.status = 1
                      AND c.{$column} = ?
                    ) aa
SQL;

        $total = (int) (DB::connection('eudr_ts')->selectOne(
            'SELECT COUNT(*) AS total FROM (' . $baseSql . ') AS counted',
            $bindings
        )->total ?? 0);

        $rows = DB::connection('eudr_ts')->select(
            $baseSql . ' ORDER BY entry_date DESC LIMIT ? OFFSET ?',
            array_merge($bindings, [$perPage, $offset])
        );

        $result = $this->mapSlocDescriptions($rows);
        return ['data' => $result, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function getFeed(string $feedId, string $mode, $plantId, int $page = 1, int $perPage = 5): array
    {
        $feedId = $this->mapFrontendSectionToDbFeedId($feedId);
        $feedPrefix = substr($feedId, 0, 3);
        $idPlant = $this->resolvePlantId($plantId);

        /** @var array<string, mixed> $feedMaterialMap */
        $feedMaterialMap = config('wip_material_mapping.feed_material_map', []);
        if (strlen($feedId) >= 6 && isset($feedMaterialMap[$feedPrefix])) {
            $result = $this->getFeedWithMaterialSign($feedId, $feedPrefix, $mode, $idPlant);
            if ($mode === 'LOG') {
                return ['data' => $result, 'total' => count($result), 'page' => $page, 'per_page' => $perPage];
            }
            return $result;
        }

        $feedPrefixStripped = ltrim($feedPrefix, '0') ?: $feedPrefix;
        $materialRows = DB::connection('eudr_ts')->select(
            "SELECT id_material FROM m_material WHERE id_feed = ? AND status = 1 LIMIT 1",
            [$feedPrefixStripped]
        );
        $idMaterial = $materialRows[0]->id_material ?? null;

        $limit = ($mode === 'LOG') ? 50 : 1;
        $offset = 0;

        if ($mode === 'LATEST') {
            $hasPlant = !($idPlant === '0' || $idPlant === null);
            $plantFilter = $hasPlant ? 'a.id_plant = ?' : '1=1';
            $subqueryPlantFilter = $hasPlant ? 'a.id_plant = ?' : '1=1';
            $matFilter = $idMaterial ? 'AND a.id_material = ?' : '';
            $subPlantFilter = $hasPlant ? 'id_plant = ?' : '1=1';
            $subMatFilter = $idMaterial ? 'AND id_material = ?' : '';

            // Build bindings matching SQL `?` order:
            // is_last_row: feedPrefix, movType2 [, idPlant] [, idMaterial]
            // next_process: movType2 [, idPlant] [, idMaterial]
            // h subquery: [, idPlant]
            // Main WHERE: feedPrefix, movType2 [, idPlant] [, idMaterial]
            $bindings = [$feedPrefix, $this->movType2];
            if ($hasPlant) $bindings[] = $idPlant;
            if ($idMaterial) $bindings[] = $idMaterial;

            $bindings[] = $this->movType2;
            if ($hasPlant) $bindings[] = $idPlant;
            if ($idMaterial) $bindings[] = $idMaterial;

            if ($hasPlant) $bindings[] = $idPlant;

            $bindings[] = $feedPrefix;
            $bindings[] = $this->movType2;
            if ($hasPlant) $bindings[] = $idPlant;
            if ($idMaterial) $bindings[] = $idMaterial;

            $matConcat = $this->dbGroupConcat("CONCAT(c.code, ' :: ', c.description)", ' | ', true);
            $qtyFmt = $this->dbNumberFormat('ROUND(MAX(h.out_qty),3)', 3);
            $lastFmt = $this->dbNumberFormat('MAX(a.last_qtf)', 3);
            $currFmt = $this->dbNumberFormat('MAX(a.curr_qtf)', 3);
            $suppConcat = $this->dbGroupConcat(
                "CONCAT(a.from_trace_no, ' / ', e.description, ' / ', b.batch_sap, ' / Qty: ', {$this->dbNumberFormat('ROUND(b.out_qty,3)', 3)}, ' MT')",
                ' | ',
                true
            );
            $bsFmtSum = $this->dbNumberFormat('ROUND(SUM(b.out_qty),3)', 3);
            $bsFmt2 = $this->dbNumberFormat('ROUND(MAX(h.out_qty),3)', 3);

            $rows = DB::connection('eudr_ts')->select("
                 SELECT MIN(a.id_trace_head) AS id_trace_head, MAX(a.entry_date) AS entry_date, CAST(a.to_trace_no AS TEXT) AS to_trace_no,
                        MIN(a.id_balance_head) AS id_balance_head, MIN(a.id_material) AS id_material,
                        {$qtyFmt} AS out_qty, MIN(a.created_by) AS created_by, MIN(a.updated_by) AS updated_by, MAX(a.created_at) AS created_at, MAX(a.updated_at) AS updated_at,
                        {$matConcat} AS material,
                        {$lastFmt} AS last_qtf, {$currFmt} AS curr_qtf,
                        MAX(g.material_document) AS material_document,
                        {$suppConcat} AS supplier,
                        CASE WHEN ABS(ROUND(CAST(SUM(b.out_qty) AS numeric),3) - ROUND(CAST(MAX(h.out_qty) AS numeric),3)) > 0.005 THEN {$bsFmtSum} ELSE {$bsFmt2} END AS balance_supplier,
                        MIN(CAST(a.id_sloc AS TEXT)) AS sloc,
                        CASE WHEN a.to_trace_no = (SELECT to_trace_no FROM t_trace_header
                                                    WHERE " . \Modules\Shared\Helpers\TraceHelper::warehouseCondition('to_trace_no', '=', $feedPrefix) . "
                                                      AND SUBSTRING(to_trace_no, 1, 1) = ?
                                                      AND status = 1 AND {$subPlantFilter} {$subMatFilter}
                                                    ORDER BY to_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS is_last_row,
                        CASE WHEN a.to_trace_no = (SELECT from_trace_no FROM t_trace_header
                                                    WHERE SUBSTRING(from_trace_no, 1, 1) = ?
                                                      AND " . \Modules\Shared\Helpers\TraceHelper::warehouseConditionFor('from_trace_no', $feedPrefix) . "
                                                      AND status = 1 AND {$subPlantFilter} {$subMatFilter}
                                                    ORDER BY from_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS next_process,
                        MIN(a.id_plant) AS id_plant, MAX(p.description) AS plant_name
                   FROM t_trace_header a
                   LEFT JOIN t_trace_detail b ON a.id_trace_head = b.id_trace_head
                   LEFT JOIN m_material c ON a.id_material = c.id_material
                   LEFT JOIN m_supplier e ON e.id_supplier = b.id_supplier
                   LEFT JOIN t_material_document g ON a.id_trace_head = g.id_trace_head
                   LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                                FROM t_trace_header a
                               WHERE a.status = 1 AND {$subqueryPlantFilter}
                               GROUP BY a.to_trace_no) h ON a.to_trace_no = h.to_trace_no

                   LEFT JOIN m_plant p ON a.id_plant = p.code_3
                  WHERE " . \Modules\Shared\Helpers\TraceHelper::warehouseCondition('a.to_trace_no', '=', $feedPrefix) . "
                    AND a.out_qty > 0 AND b.out_qty > 0
                    AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                    AND a.status = 1 AND {$plantFilter} {$matFilter}
                  GROUP BY a.to_trace_no
                  ORDER BY MAX(a.id_trace_head) DESC
                    LIMIT {$limit} OFFSET {$offset}
            ", $bindings);
        } else {
            $plantFilter = ($idPlant === '0' || $idPlant === null) ? '1=1' : 'a.id_plant = ?';
            $subqueryPlantFilter = ($idPlant === '0' || $idPlant === null) ? '1=1' : 'a.id_plant = ?';
            $matFilter = $idMaterial ? 'AND a.id_material = ?' : '';

            $hasPlant = !($idPlant === '0' || $idPlant === null);
            $bindings = [];
            $bindings[] = $feedPrefix;
            $bindings[] = $this->movType2;
            if ($hasPlant) $bindings[] = $idPlant;
            if ($idMaterial) $bindings[] = $idMaterial;

            $bindings[] = $this->movType2;
            if ($hasPlant) $bindings[] = $idPlant;
            if ($idMaterial) $bindings[] = $idMaterial;

            if ($hasPlant) $bindings[] = $idPlant;

            $bindings[] = $feedPrefix;
            $bindings[] = $this->movType2;
            if ($hasPlant) $bindings[] = $idPlant;
            if ($idMaterial) $bindings[] = $idMaterial;

            $matConcat = $this->dbGroupConcat("CONCAT(c.code, ' :: ', c.description)", ' | ', true);
            $qtyFmt = $this->dbNumberFormat('ROUND(MAX(h.out_qty),3)', 3);
            $lastFmt = $this->dbNumberFormat('MAX(a.last_qtf)', 3);
            $currFmt = $this->dbNumberFormat('MAX(a.curr_qtf)', 3);
            $suppConcat = $this->dbGroupConcat(
                "CONCAT(a.from_trace_no, ' / ', e.description, ' / ', b.batch_sap, ' / Qty: ', {$this->dbNumberFormat('ROUND(b.out_qty,3)', 3)}, ' MT')",
                ' | ',
                true
            );
            $bsFmtSum = $this->dbNumberFormat('ROUND(SUM(b.out_qty),3)', 3);
            $bsFmt2 = $this->dbNumberFormat('ROUND(MAX(h.out_qty),3)', 3);

            $rows = DB::connection('eudr_ts')->select("
                 SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS TEXT) AS to_trace_no,
                        a.id_balance_head, a.id_material,
                        {$qtyFmt} AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                        {$matConcat} AS material,
                        {$lastFmt} AS last_qtf, {$currFmt} AS curr_qtf,
                        g.material_document,
                        {$suppConcat} AS supplier,
                        CASE WHEN ABS(ROUND(CAST(SUM(b.out_qty) AS numeric),3) - ROUND(CAST(MAX(h.out_qty) AS numeric),3)) > 0.005 THEN {$bsFmtSum} ELSE {$bsFmt2} END AS balance_supplier,
                        a.id_sloc AS sloc,
                        CASE WHEN a.to_trace_no = (SELECT to_trace_no FROM t_trace_header
                                                    WHERE " . \Modules\Shared\Helpers\TraceHelper::warehouseCondition('to_trace_no', '=', $feedPrefix) . "
                                                      AND SUBSTRING(to_trace_no, 1, 1) = ?
                                                      AND status = 1 AND {$plantFilter} {$matFilter}
                                                    ORDER BY to_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS is_last_row,
                        CASE WHEN a.to_trace_no = (SELECT from_trace_no FROM t_trace_header
                                                    WHERE SUBSTRING(from_trace_no, 1, 1) = ?
                                                      AND " . \Modules\Shared\Helpers\TraceHelper::warehouseConditionFor('from_trace_no', $feedPrefix) . "
                                                      AND status = 1 AND {$plantFilter} {$matFilter}
                                                    ORDER BY from_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS next_process,
                        a.id_plant, p.description AS plant_name
                   FROM t_trace_header a
                   LEFT JOIN t_trace_detail b ON a.id_trace_head = b.id_trace_head
                   LEFT JOIN m_material c ON a.id_material = c.id_material
                   LEFT JOIN m_supplier e ON e.id_supplier = b.id_supplier
                   LEFT JOIN t_material_document g ON a.id_trace_head = g.id_trace_head
                   LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                                FROM t_trace_header a
                               WHERE a.status = 1 AND {$subqueryPlantFilter}
                               GROUP BY a.to_trace_no) h ON a.to_trace_no = h.to_trace_no

                   LEFT JOIN m_plant p ON a.id_plant = p.code_3
                  WHERE " . \Modules\Shared\Helpers\TraceHelper::warehouseCondition('a.to_trace_no', '=', $feedPrefix) . "
                    AND a.out_qty > 0 AND b.out_qty > 0
                    AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                    AND a.status = 1 AND {$plantFilter} {$matFilter}
                  GROUP BY a.to_trace_no, a.id_trace_head, a.id_balance_head, a.id_material, g.material_document, a.created_by, a.updated_by, a.created_at, a.updated_at, a.last_qtf, a.curr_qtf, a.id_sloc, a.id_plant, p.description, h.out_qty, a.entry_date
                  ORDER BY a.id_trace_head DESC
                    LIMIT {$limit} OFFSET {$offset}
            ", $bindings);
        }

        $result = $this->mapSlocDescriptions($rows);
        if ($mode === 'LOG') {
            return ['data' => $result, 'total' => count($result), 'page' => $page, 'per_page' => $perPage];
        }
        return $result;
    }


    protected function mapSlocDescriptions(array $rows, string $slocField = 'sloc'): array
    {
        if (empty($rows)) return $rows;

        $slocs = \Illuminate\Support\Facades\DB::connection('eudr_ts')
            ->table('m_sloc')
            ->select('id_sloc', 'description', 'tf_number', 'code_3', 'id_plant')
            ->get()
            ->keyBy('id_sloc');

        $plants = \Illuminate\Support\Facades\DB::connection()
            ->table('m_plant')
            ->select('code_3', 'code_2')
            ->get()
            ->keyBy('code_3');

        foreach ($rows as $row) {
            $row->{$slocField} = '';

            $sourceField = isset($row->tf_number) ? 'tf_number' : $slocField;
            $slocVal = $row->{$sourceField} ?? null;

            if ($slocVal !== null && $slocVal !== '') {
                $decoded = json_decode((string)$slocVal, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $firstDesc = '';
                    $tanks = [];
                    foreach ($decoded as $id) {
                        if (!isset($slocs[$id])) continue;
                        $s = $slocs[$id];
                        if (!$firstDesc) {
                            $firstDesc = $this->buildSlocLabel($s, $plants);
                        }
                        if ($s->tf_number) $tanks[] = $s->tf_number;
                    }
                    if (!empty($tanks)) {
                        sort($tanks);
                        $tanks = array_unique($tanks);
                        $row->{$slocField} = $firstDesc . ' | ' . implode(' & ', $tanks);
                    } elseif ($firstDesc) {
                        $row->{$slocField} = $firstDesc;
                    }
                } else {
                    if (isset($slocs[$slocVal])) {
                        $s = $slocs[$slocVal];
                        $label = $this->buildSlocLabel($s, $plants);
                        $row->{$slocField} = $s->tf_number ? ($label . ' | ' . $s->tf_number) : $label;
                    }
                }
            }
        }
        return $rows;
    }

    private function buildSlocLabel(object $sloc, \Illuminate\Support\Collection $plants): string
    {
        $desc = $sloc->description ?? '';
        if (!empty($desc)) return $desc;

        $code3    = strtoupper($sloc->code_3 ?? '');
        $label    = ($code3 === 'PRD') ? 'PRODUCT' : $code3;
        $plantAbbr = $plants[$sloc->id_plant ?? '']->code_2 ?? '';
        return trim($label . ($plantAbbr ? ' ' . $plantAbbr : ''));
    }

    protected function getFeedWithMaterialSign(string $feedId, string $feedPrefix, string $mode, $idPlant): array
    {
        $idMatlSign = substr($feedId, 4, 2);
        $feedIdShort = $feedPrefix;

        /** @var array<string, array<string, array{id_material: string|null, id_material1: string|null, id_material2: string|null}>> $feedMaterialMap */
        $feedMaterialMap = config('wip_material_mapping.feed_material_map', []);

        if (isset($feedMaterialMap[$feedIdShort][$idMatlSign])) {
            $entry = $feedMaterialMap[$feedIdShort][$idMatlSign];
            return $this->execFeedQuery(
                $mode,
                $feedIdShort,
                $idPlant,
                $entry['id_material'],
                $entry['id_material1'],
                $entry['id_material2']
            );
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

        $matConcat = $this->dbGroupConcat("CONCAT(c.code, ' :: ', c.description)", ' | ', true);
        $qtyFmt = $this->dbNumberFormat('ROUND(MAX(h.out_qty),3)', 3);
        $lastFmt = $this->dbNumberFormat('MAX(a.last_qtf)', 3);
        $currFmt = $this->dbNumberFormat('MAX(a.curr_qtf)', 3);
        $suppConcat = $this->dbGroupConcat(
            "CONCAT(a.from_trace_no, ' / ', e.description, ' / ', b.batch_sap, ' / Qty: ', {$this->dbNumberFormat('ROUND(b.out_qty,3)', 3)}, ' MT')",
            ' | ',
            true
        );
        $bsFmt1 = $this->dbNumberFormat('ROUND(MAX(bs.supplier_qty),3)', 3);
        $bsFmt2 = $this->dbNumberFormat('ROUND(MAX(h.out_qty),3)', 3);

        if ($mode === 'LATEST') {
            $plantFilter = ($idPlant === '0' || $idPlant === null) ? '1=1' : 'a.id_plant = ?';
            $subPlantFilter = str_replace('a.', '', $plantFilter);
            $subMatlWhere = str_replace('a.', '', $matlWhere);
            $sql = "
                SELECT MIN(a.id_trace_head) AS id_trace_head, MAX(a.entry_date) AS entry_date, CAST(a.to_trace_no AS TEXT) AS to_trace_no,
                       MIN(a.id_balance_head) AS id_balance_head, MIN(a.id_material) AS id_material, MAX(g.material_document) AS material_document, MIN(CAST(a.id_sloc AS TEXT)) AS id_sloc, MIN(CAST(a.id_sloc AS TEXT)) AS id_sloc_tail,
                       {$qtyFmt} AS out_qty, MIN(a.created_by) AS created_by, MIN(a.updated_by) AS updated_by, MAX(a.created_at) AS created_at, MAX(a.updated_at) AS updated_at,
                       {$matConcat} AS material,
                       {$lastFmt} AS last_qtf, {$currFmt} AS curr_qtf,
                       {$suppConcat} AS supplier,
                       CASE WHEN ABS(ROUND(CAST(MAX(bs.supplier_qty) AS numeric),3) - ROUND(CAST(MAX(h.out_qty) AS numeric),3)) > 0.005 THEN {$bsFmt1} ELSE {$bsFmt2} END AS balance_supplier,
                       MIN(CAST(a.id_sloc AS TEXT)) AS sloc,
                       MIN(a.id_plant) AS id_plant, MAX(p.description) AS plant_name
                  FROM t_trace_header a
                  LEFT JOIN t_trace_detail b ON a.id_trace_head = b.id_trace_head
                  LEFT JOIN m_material c ON a.id_material = c.id_material
                  LEFT JOIN m_supplier e ON e.id_supplier = b.id_supplier
                  LEFT JOIN t_material_document g ON a.id_trace_head = g.id_trace_head
                  LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                               FROM t_trace_header a WHERE a.status = 1 AND a.id_plant = ? GROUP BY a.to_trace_no) h ON a.to_trace_no = h.to_trace_no

                  LEFT JOIN (SELECT h.to_trace_no, SUM(d.out_qty) AS supplier_qty
                               FROM t_trace_header h JOIN t_trace_detail d ON h.id_trace_head = d.id_trace_head
                              WHERE d.out_qty > 0 AND h.status = 1 GROUP BY h.to_trace_no) bs ON bs.to_trace_no = a.to_trace_no
                  LEFT JOIN m_plant p ON a.id_plant = p.code_3
                 WHERE " . \Modules\Shared\Helpers\TraceHelper::warehouseCondition('a.to_trace_no', '=', $feedId) . "
                   AND a.out_qty > 0 AND b.out_qty > 0
                   AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                   AND {$matlWhere}
                   AND a.status = 1 AND a.id_plant = ?
                 GROUP BY a.to_trace_no
                 ORDER BY a.to_trace_no DESC
                 LIMIT 1
            ";
            $params = array_merge([$idPlant, $feedId, $this->movType2], $matlParams, [$idPlant]);
        } else {
            $bsFmtSum = $this->dbNumberFormat('ROUND(SUM(b.out_qty),3)', 3);

            $plantFilter = ($idPlant === '0' || $idPlant === null) ? '1=1' : 'a.id_plant = ?';
            $sql = "
                SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS TEXT) AS to_trace_no,
                       a.id_balance_head, a.id_material,
                       {$qtyFmt} AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                       {$matConcat} AS material,
                       {$lastFmt} AS last_qtf, {$currFmt} AS curr_qtf,
                       g.material_document,
                       {$suppConcat} AS supplier,
                       CASE WHEN ABS(ROUND(CAST(SUM(b.out_qty) AS numeric),3) - ROUND(CAST(MAX(h.out_qty) AS numeric),3)) > 0.005 THEN {$bsFmtSum} ELSE {$bsFmt2} END AS balance_supplier,
                       a.id_sloc AS sloc,
                       CASE WHEN a.to_trace_no = (SELECT to_trace_no FROM t_trace_header
                                                    WHERE " . \Modules\Shared\Helpers\TraceHelper::warehouseCondition('to_trace_no', '=', $feedId) . "
                                                      AND SUBSTRING(to_trace_no, 1, 1) = ?
                                                      AND {$subMatlWhere}
                                                      AND status = 1 AND {$subPlantFilter}
                                                    ORDER BY to_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS is_last_row,
                       CASE WHEN a.to_trace_no = (SELECT from_trace_no FROM t_trace_header
                                                   WHERE from_trace_no = a.to_trace_no
                                                     AND {$subMatlWhere}
                                                     AND status = 1 AND {$subPlantFilter}
                                                   ORDER BY from_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS next_process,
                       a.id_plant, p.description AS plant_name
                  FROM t_trace_header a
                  LEFT JOIN t_trace_detail b ON a.id_trace_head = b.id_trace_head
                  LEFT JOIN m_material c ON a.id_material = c.id_material
                  LEFT JOIN m_supplier e ON e.id_supplier = b.id_supplier
                  LEFT JOIN t_material_document g ON a.id_trace_head = g.id_trace_head
                  LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                               FROM t_trace_header a WHERE a.status = 1 AND a.id_plant = ? GROUP BY a.to_trace_no) h ON a.to_trace_no = h.to_trace_no

                  LEFT JOIN m_plant p ON a.id_plant = p.code_3
                 WHERE " . \Modules\Shared\Helpers\TraceHelper::warehouseCondition('a.to_trace_no', '=', $feedId) . "
                   AND a.out_qty > 0 AND b.out_qty > 0
                   AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                   AND {$matlWhere}
                   AND a.status = 1 AND a.id_plant = ?
                 GROUP BY a.to_trace_no, a.id_trace_head, a.id_balance_head, a.id_material, g.material_document, a.created_by, a.updated_by, a.created_at, a.updated_at, a.last_qtf, a.curr_qtf, a.id_sloc, a.id_plant, p.description, h.out_qty, a.entry_date
                 ORDER BY a.id_trace_head DESC
            ";
            $params = array_merge(
                [$feedId, $this->movType2], $matlParams, [$idPlant],
                $matlParams, [$idPlant],
                [$idPlant, $feedId, $this->movType2], $matlParams, [$idPlant]
            );
        }

        // Handle "all plants" case
        $plantFilter = ($idPlant === '0' || $idPlant === null) ? '1=1' : 'a.id_plant = ?';
        $subqueryPlantFilter = ($idPlant === '0' || $idPlant === null) ? '1=1' : 'a.id_plant = ?';

        // Replace plant filters in SQL
        $sql = str_replace('AND a.id_plant = ?', 'AND ' . $plantFilter, $sql);
        $sql = str_replace('WHERE a.status = 1 AND a.id_plant = ?', 'WHERE a.status = 1 AND ' . $subqueryPlantFilter, $sql);
        $sql = str_replace('AND status = 1 AND $plantFilter', 'AND status = 1 AND ' . $plantFilter, $sql);

        // Filter bindings to remove plantId when showing all plants
        if ($idPlant === '0' || $idPlant === null) {
            $filteredParams = [];
            foreach ($params as $param) {
                if ($param !== $idPlant && $param !== '0' && $param !== 0) {
                    $filteredParams[] = $param;
                }
            }
            $params = $filteredParams;
        }

        return $this->mapSlocDescriptions(DB::connection('eudr_ts')->select($sql, $params));
    }

    public function getRundown(string $rundownId, string $mode, $plantId, int $page = 1, int $perPage = 5): array
    {
        $rundownId = $this->mapFrontendSectionToDbRundownId($rundownId);
        $idPlant = $this->resolvePlantId($plantId);

        $limit = ($mode === 'LOG') ? 50 : 1;
        $offset = 0;

        if ($mode === 'LATEST') {
            // Handle "all plants" case
            $plantFilter = ($idPlant === '0' || $idPlant === null) ? '1=1' : 'a.id_plant = ?';
            $subPlantFilter = str_replace('a.', '', $plantFilter);
            $bindings = ($idPlant === '0' || $idPlant === null)
                ? [$rundownId, $this->movType1]
                : [$rundownId, $this->movType1, $idPlant];

            $qtyFmt = $this->dbNumberFormat('ROUND(MAX(h.in_qty),3)', 3);
            $lastFmt = $this->dbNumberFormat('MAX(a.last_qtf)', 3);
            $currFmt = $this->dbNumberFormat('MAX(a.curr_qtf)', 3);
            $suppConcat = $this->dbGroupConcat(
                "CONCAT(a.from_trace_no, ' / ', e.description, ' / ', b.batch_sap, ' / Qty: ', {$this->dbNumberFormat('b.in_qty', 3)}, ' MT')",
                ' | ',
                true
            );
            $balFmt = $this->dbNumberFormat('ROUND(MAX(bs.supplier_qty),3)', 3);

            $rows = DB::connection('eudr_ts')->select("
                SELECT MIN(a.id_trace_head) AS id_trace_head, MAX(a.entry_date) AS entry_date, a.to_trace_no AS rundown_trace_no,
                       MIN(a.id_balance_head) AS id_balance_head, MIN(a.id_material) AS id_material, MIN(CAST(a.id_sloc AS TEXT)) AS id_sloc, MIN(CAST(a.id_sloc AS TEXT)) AS id_sloc_tail,
                       {$qtyFmt} AS in_qty, MIN(a.created_by) AS created_by, MIN(a.updated_by) AS updated_by, MAX(a.created_at) AS created_at, MAX(a.updated_at) AS updated_at,
                       MAX(CONCAT(c.code, ' :: ', c.description)) AS material, MAX(g.material_document) AS material_document,
                       {$lastFmt} AS last_qtf, {$currFmt} AS curr_qtf,
                       {$suppConcat} AS supplier,
                       {$balFmt} AS balance_supplier,
                       MIN(CAST(a.id_sloc AS TEXT)) AS sloc,
                       MIN(a.id_plant) AS id_plant, MAX(p.description) AS plant_name
                  FROM t_trace_header a
                  LEFT JOIN t_trace_detail b ON a.id_trace_head = b.id_trace_head
                  LEFT JOIN m_material c ON a.id_material = c.id_material
                  LEFT JOIN m_supplier e ON e.id_supplier = b.id_supplier
                  LEFT JOIN t_material_document g ON a.id_trace_head = g.id_trace_head
                  LEFT JOIN (SELECT a.to_trace_no, SUM(a.in_qty) AS in_qty
                               FROM t_trace_header a WHERE a.status = 1 GROUP BY a.to_trace_no) h ON a.to_trace_no = h.to_trace_no

                  LEFT JOIN (SELECT id_trace_head, SUM(in_qty) AS supplier_qty
                               FROM t_trace_detail WHERE in_qty > 0 GROUP BY id_trace_head) bs ON bs.id_trace_head = a.id_trace_head
                  LEFT JOIN m_plant p ON a.id_plant = p.code_3
                 WHERE " . \Modules\Shared\Helpers\TraceHelper::warehouseCondition('a.to_trace_no', '=', $rundownId) . "
                   AND a.in_qty > 0 AND b.in_qty > 0
                   AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                   AND a.status = 1 AND {$subPlantFilter}
                 GROUP BY a.to_trace_no
                 ORDER BY a.to_trace_no DESC
                 LIMIT 1
            ", $bindings);
        } else {
            // Handle "all plants" case
            $plantFilter = ($idPlant === '0' || $idPlant === null) ? '1=1' : 'a.id_plant = ?';
            $subPlantFilter = str_replace('a.', '', $plantFilter);
            $bindings = ($idPlant === '0' || $idPlant === null)
                ? [$this->movType1, $this->movType2, $rundownId, $rundownId, $this->movType1]
                : [$this->movType1, $this->movType2, $rundownId, $idPlant, $idPlant, $rundownId, $this->movType1, $idPlant];

            $qtyFmt = $this->dbNumberFormat('ROUND(h.in_qty,3)', 3);
            $lastFmt = $this->dbNumberFormat('MAX(a.last_qtf)', 3);
            $currFmt = $this->dbNumberFormat('MAX(a.curr_qtf)', 3);
            $suppConcat = $this->dbGroupConcat(
                "CONCAT(a.from_trace_no, ' / ', e.description, ' / ', b.batch_sap, ' / Qty: ', {$this->dbNumberFormat('b.in_qty', 3)}, ' MT')",
                ' | ',
                true
            );
            $balFmt = $this->dbNumberFormat('ROUND(SUM(b.in_qty),3)', 3);

            $rows = DB::connection('eudr_ts')->select("
                SELECT MIN(a.id_trace_head) AS id_trace_head, MAX(a.entry_date) AS entry_date, CAST(a.to_trace_no AS TEXT) AS to_trace_no,
                       MIN(a.id_balance_head) AS id_balance_head, MIN(a.id_material) AS id_material,
                       {$qtyFmt} AS in_qty, MIN(a.created_by) AS created_by, MIN(a.updated_by) AS updated_by, MAX(a.created_at) AS created_at, MAX(a.updated_at) AS updated_at,
                       MAX(CONCAT(c.code, ' :: ', c.description)) AS material, MAX(g.material_document) AS material_document,
                       {$lastFmt} AS last_qtf, {$currFmt} AS curr_qtf,
                       {$suppConcat} AS supplier,
                       {$balFmt} AS balance_supplier,
                       MIN(CAST(a.id_sloc AS TEXT)) AS sloc,
                       CASE WHEN a.to_trace_no = (SELECT to_trace_no FROM t_trace_header
                                                    WHERE (SUBSTRING(to_trace_no, 1, 1) = ? OR SUBSTRING(to_trace_no, 1, 1) = ?)
                                                      AND " . \Modules\Shared\Helpers\TraceHelper::warehouseCondition('to_trace_no', '=', $rundownId) . "
                                                      AND status = 1 AND {$subPlantFilter}
                                                    ORDER BY to_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS is_last_row,
                       CASE WHEN a.to_trace_no = (SELECT from_trace_no FROM t_trace_header
                                                   WHERE from_trace_no = a.to_trace_no
                                                     AND status = 1 AND {$subPlantFilter}
                                                   ORDER BY from_trace_no DESC LIMIT 1) THEN 1 ELSE NULL END AS next_process,
                       MIN(a.id_plant) AS id_plant, MAX(p.description) AS plant_name
                  FROM t_trace_header a
                  LEFT JOIN t_trace_detail b ON a.id_trace_head = b.id_trace_head
                  LEFT JOIN m_material c ON a.id_material = c.id_material
                  LEFT JOIN m_supplier e ON e.id_supplier = b.id_supplier
                  LEFT JOIN t_material_document g ON a.id_trace_head = g.id_trace_head
                  LEFT JOIN (SELECT a.to_trace_no, SUM(a.in_qty) AS in_qty
                               FROM t_trace_header a WHERE a.status = 1 GROUP BY a.to_trace_no) h ON a.to_trace_no = h.to_trace_no

                  LEFT JOIN m_plant p ON a.id_plant = p.code_3
                 WHERE " . \Modules\Shared\Helpers\TraceHelper::warehouseCondition('a.to_trace_no', '=', $rundownId) . "
                   AND a.in_qty > 0 AND b.in_qty > 0
                   AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                   AND a.status = 1 AND {$plantFilter}
                 GROUP BY a.to_trace_no
                 ORDER BY a.to_trace_no DESC
                  LIMIT {$limit} OFFSET {$offset}
            ", $bindings);
        }

        $result = $this->mapSlocDescriptions($rows);
        if ($mode === 'LOG') {
            return ['data' => $result, 'total' => count($result), 'page' => $page, 'per_page' => $perPage];
        }
        return $result;
    }

    public function getActiveTanksForFeed(string $feedId, $plantId): array
    {
        $idPlant = $this->resolvePlantId($plantId);
        return $this->getActiveTanksBySlocType('FEED', $idPlant, $plantId);
    }

    public function getActiveTanksForRundown(string $rundownId, $plantId, ?string $subgroup = null): array
    {
        $idPlant = $this->resolvePlantId($plantId);

        return $this->getActiveTanksBySlocType('WIP', $idPlant, $plantId);
    }

    protected function getActiveTanksBySlocType(string $type, ?string $idPlant, mixed $rawPlantId = null): array
    {
        $rows = app(\Modules\Shared\Repositories\TankQueryRepository::class)
            ->getActiveTanksByKeywords([$type], $idPlant)
            ->toArray();

        return $this->mapSlocDescriptions($rows);
    }

    public function getActiveSpecificTanks(int $slocId): array
    {
        return app(\Modules\Shared\Repositories\TankQueryRepository::class)
            ->getActiveSpecificTanksRundown($slocId)
            ->toArray();
    }

    public function getQuantifierData(string $date, string $tagNumber): array
    {
        $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));

        try {
            $tsCol = $this->dbDateFormat('timestamp', '%Y-%m-%d');
            return DB::connection('dwsql')->select("
                SELECT FORMAT(value,3) AS value,
                       CONCAT(?, ' 07:00') AS timestamp
                  FROM {$tagNumber}
                 WHERE {$tsCol} = ?
                 UNION ALL
                SELECT 0 AS value, CONCAT(?, ' 07:00') AS timestamp
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

        // Handle "all plants" case
        $plantFilter = ($idPlant === '0' || $idPlant === null) ? '1=1' : 'id_plant = ?';
        $bindings = ($idPlant === '0' || $idPlant === null)
            ? []
            : [$idPlant, $idPlant, $idPlant, $idPlant, $idPlant, $idPlant, $idPlant];

        $sections = DB::connection('eudr_ts')->select("
            SELECT
                m.id_rundown AS section_id,
                m.code AS section_code,
                m.description AS section_name,
                (SELECT to_trace_no FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '3'
                   AND " . \Modules\Shared\Helpers\TraceHelper::only14Digit('to_trace_no') . "
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND {$plantFilter}
                 ORDER BY id_trace_head DESC LIMIT 1) AS latest_feed_trace,
                (SELECT entry_date FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '3'
                   AND " . \Modules\Shared\Helpers\TraceHelper::only14Digit('to_trace_no') . "
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND {$plantFilter}
                 ORDER BY id_trace_head DESC LIMIT 1) AS latest_feed_date,
                (SELECT curr_qtf FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '3'
                   AND " . \Modules\Shared\Helpers\TraceHelper::only14Digit('to_trace_no') . "
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND {$plantFilter}
                 ORDER BY id_trace_head DESC LIMIT 1) AS latest_feed_qty,
                (SELECT to_trace_no FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '2'
                   AND " . \Modules\Shared\Helpers\TraceHelper::only14Digit('to_trace_no') . "
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND {$plantFilter}
                 ORDER BY id_trace_head DESC LIMIT 1) AS latest_rundown_trace,
                (SELECT entry_date FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '2'
                   AND " . \Modules\Shared\Helpers\TraceHelper::only14Digit('to_trace_no') . "
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND {$plantFilter}
                 ORDER BY id_trace_head DESC LIMIT 1) AS latest_rundown_date,
                (SELECT curr_qtf FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,1,1) = '2'
                   AND " . \Modules\Shared\Helpers\TraceHelper::only14Digit('to_trace_no') . "
                   AND SUBSTRING(to_trace_no,8,3) = m.id_rundown
                   AND status = 1 AND {$plantFilter}
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

    protected function checkPeriodLock(string $date): bool
    {
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

    protected function mapFrontendSectionToDbFeedId(string $sectionId, ?string $subgroup = null, int $mode = 1): string
    {
        if ($sectionId === '105') {
            return $subgroup === 'short' ? '006-01' : '006-02';
        }
        if ($sectionId === '106' || $sectionId === '114') {
            return $mode === 2 ? '008-02' : '008-01';
        }
        $map = [
            '101' => '001',
            '102' => '001',
            '103' => '002',
            '104' => '003',
            '110' => '004',
            '111' => '007',
            '116' => '007',
            '112' => '009-01',
            '302' => '005',
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
                'cfa28' => '016',
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

    protected function mapRundownToFeedSectionId(string $rundownId, int $mode = 1): string
    {
        // Rundown '028' (LEFA) is ambiguous: Mode 1 → section 112 feed (009), Mode 2 → CFA80 feed (008-02)
        if ($mode === 2 && $rundownId === '028') {
            return '008';
        }
        /** @var array<string, string> $rundownToFeedMap */
        $rundownToFeedMap = config('wip_material_mapping.rundown_to_feed_map', []);
        $feedId = $rundownToFeedMap[$rundownId] ?? $rundownId;

        // Normalize to 3-digit trace position (positions 8-10 of trace number)
        return substr($feedId, 0, 3);
    }

    protected function getMaterialIdBySection(string $sectionId, string $type = 'feed', ?string $subgroup = null): ?int
    {
        $dbId = ($type === 'feed')
            ? $this->mapFrontendSectionToDbFeedId($sectionId)
            : $this->mapFrontendSectionToDbRundownId($sectionId, $subgroup);

        // Long feedIds (e.g. '006-01', '008-02') → look up via feed_material_map for precision.
        if ($type === 'feed' && strlen($dbId) >= 6) {
            $prefix = substr($dbId, 0, 3);
            $sign   = substr($dbId, 4, 2);
            $feedMaterialMap = config('wip_material_mapping.feed_material_map', []);
            if (isset($feedMaterialMap[$prefix][$sign])) {
                $entry = $feedMaterialMap[$prefix][$sign];
                // Prefer id_material; fall back to id_material1 for dual entries.
                $matId = $entry['id_material'] ?? $entry['id_material1'] ?? null;
                return $matId !== null ? (int) $matId : null;
            }
        }

        // DB stores numeric IDs without leading zeros ('8' not '008'); strip them for match.
        $dbIdStripped = ltrim(substr($dbId, 0, 3), '0') ?: $dbId;

        $column = ($type === 'feed') ? 'id_feed' : 'id_rundown';
        $rows = DB::connection('eudr_ts')->select(
            "SELECT id_material FROM m_material WHERE {$column} = ? AND status = 1 LIMIT 1",
            [$dbIdStripped]
        );

        return $rows[0]->id_material ?? null;
    }

    protected function resolvePlantId(mixed $plantId): ?string
    {
        if ($plantId === null || $plantId === '' || $plantId === 0 || $plantId === '0') {
            return '0';
        }
        $resolved = app(\Modules\Shared\Services\Contracts\PlantContextServiceInterface::class)
            ->resolvePlantId($plantId);
        return $resolved ?? (string) $plantId;
    }

    protected function executeSelect(string $sql, array $bindings, $idPlant)
    {
        return DB::connection('eudr_ts')->select($sql, $bindings);
    }
}
