<?php

declare(strict_types=1);

namespace Modules\Shared\Traits;

use Illuminate\Support\Facades\DB;

trait TraceNumberGeneratorTrait
{
    use DbCompatTrait;

    /**
     * Generate a trace number for a material in a specific plant.
     * Format: {prefix}{yymmdd}{section_id}{plant}{sequence}
     * Example: 82606240110101
     *
     * @param  string  $prefix  E.g., '8' for blending, '9' for transfer, '5' for packaging/shipment
     * @param  int|null  $materialId  The material ID to fetch rundown/feed id. Can be null if warehouseId is provided.
     * @param  int  $plantId  Plant ID to suffix
     * @param  string  $tableName  The table to check sequence against (t_balance_header or t_trace_header)
     * @param  string  $traceCol  The column name (trace_no or to_trace_no)
     * @param  string  $idCol  The ID column to order by descending (id_balance_head or id_trace_head)
     * @param  string|null  $warehouseId  Optional explicit warehouse ID
     */
    protected function generateTraceNumberForMaterial(
        string $prefix,
        ?int $materialId,
        int $plantId,
        string $tableName = 't_balance_header',
        string $traceCol = 'trace_no',
        string $idCol = 'id_balance_head',
        ?string $warehouseId = null
    ): string {
        $dateFmt = $this->dbDateFormat($this->dbCurDate(), '%y%m%d');
        $plantStr = str_pad(substr((string) $plantId, -2), 2, '0', STR_PAD_LEFT);

        $sectionId = '000';
        if ($warehouseId !== null && $warehouseId !== '') {
            $sectionId = str_pad($warehouseId, 3, '0', STR_PAD_LEFT);
        } elseif ($materialId !== null) {
            if ($prefix === '5' || $prefix === '4') {
                $sectionId = str_pad((string) $materialId, 3, '0', STR_PAD_LEFT);
            } else {
                $material = DB::connection('eudr_ts')->table('m_material')
                    ->where('id_material', $materialId)
                    ->first();

                if ($material) {
                    if (! empty($material->id_rundown) && $material->id_rundown !== '0' && $material->id_rundown !== '-') {
                        $sectionId = str_pad((string) $material->id_rundown, 3, '0', STR_PAD_LEFT);
                    } elseif (! empty($material->id_feed) && $material->id_feed !== '0' && $material->id_feed !== '-') {
                        $sectionId = str_pad((string) $material->id_feed, 3, '0', STR_PAD_LEFT);
                    }
                }
            }
        }

        $prefixLen = strlen($prefix);
        $baseLen = $prefixLen + 6 + 3 + 2;
        $seqStart = $baseLen + 1;

        $lpadExpr = "LPAD(CAST(CAST(SUBSTRING(a.{$traceCol} FROM {$seqStart} FOR 2) AS INTEGER) + 1 AS TEXT), 2, '0')";

        $sql = "
            SELECT a.trace_no
              FROM (SELECT CONCAT(CAST(? AS TEXT), {$dateFmt}, CAST(? AS TEXT), CAST(? AS TEXT), {$lpadExpr}) AS trace_no
                      FROM {$tableName} a
                     WHERE SUBSTRING(a.{$traceCol} FROM 1 FOR {$baseLen}) = CONCAT(CAST(? AS TEXT), {$dateFmt}, CAST(? AS TEXT), CAST(? AS TEXT))
                       AND a.status = 1
                     ORDER BY a.{$idCol} DESC
                     LIMIT 1 ) a
             UNION ALL
             SELECT CONCAT(CAST(? AS TEXT), {$dateFmt}, CAST(? AS TEXT), CAST(? AS TEXT), '01') AS trace_no
              LIMIT 1
        ";

        $result = DB::connection('eudr_ts')->select($sql, [
            $prefix, $sectionId, $plantStr,
            $prefix, $sectionId, $plantStr,
            $prefix, $sectionId, $plantStr,
        ]);

        return $result[0]->trace_no ?? '';
    }
}
