<?php
declare(strict_types=1);
namespace Modules\TsWip\Repositories\Traits;

use Illuminate\Support\Facades\DB;
use Modules\Shared\Traits\DbCompatTrait;

trait WipEntryBatchTrait
{
    use DbCompatTrait;

    public function getFeedNewBatchNumber(string $feedId, $plantId): ?string
    {
        $idPlant = $this->resolvePlantId($plantId);
        $svc = app(\Modules\Shared\Services\TraceNumberService::class);
        $mapping = app(\Modules\Shared\Services\SectionMappingService::class);
        $section = substr($mapping->toFeedId($feedId), 0, 3);
        $plantCode = $svc->resolvePlantCode($idPlant);
        return $svc->generate('3', date('ymd'), $section, $plantCode);
    }

    public function getRundownNewBatchNumber(string $rundownId, $plantId): ?string
    {
        $idPlant = $this->resolvePlantId($plantId);
        $svc = app(\Modules\Shared\Services\TraceNumberService::class);
        $mapping = app(\Modules\Shared\Services\SectionMappingService::class);
        $section = substr($mapping->toRundownId($rundownId), 0, 3);
        $plantCode = $svc->resolvePlantCode($idPlant);
        return $svc->generate('2', date('ymd'), $section, $plantCode);
    }

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
                SELECT a.curr_qtf, a.entry_date, \'-NORMAL-\' AS status
                  FROM (SELECT a.curr_qtf, a.entry_date
                          FROM t_trace_header a
             WHERE SUBSTRING(a.to_trace_no,1,1) = 2
               AND ' . \Modules\Shared\Helpers\TraceHelper::warehouseCondition('a.to_trace_no', '=', substr($feedId, 0, 3)) . '
               AND a.status = 1 AND a.id_plant = ?
             ORDER BY a.id_trace_head DESC LIMIT 1) a
            UNION ALL
            SELECT 0 AS curr_qtf, ' . $this->dbDateFormat($this->dbCurDate(), '%Y-%m-%d') . ' AS entry_date, \'-INIT-\' AS status
             LIMIT 1
        ', [$idPlant], $idPlant);

            if (!empty($db) && (float)($db[0]->curr_qtf ?? 0) !== 0.0) {
                $db1 = DB::connection('eudr_ts')->select('
                    SELECT COALESCE(b.curr_qtf, 0) AS curr_qtf,
                           COALESCE(b.entry_date, ' . $this->dbDateFormat($this->dbCurDate(), '%Y-%m-%d') . ') AS entry_date,
                           \'-RESET-\' AS status
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

        return DB::connection('eudr_ts')->select('
            SELECT 0 AS curr_qtf, ' . $this->dbDateFormat($this->dbCurDate(), '%Y-%m-%d') . ' AS entry_date, \'-QTF-\' AS status
        ');
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
                SELECT a.curr_qtf, a.entry_date, \'-NORMAL-\' AS status, a.created_at
                  FROM (SELECT a.curr_qtf, a.entry_date, a.created_at
                          FROM t_trace_header a
             WHERE SUBSTRING(a.to_trace_no,1,1) = 1
               AND ' . \Modules\Shared\Helpers\TraceHelper::warehouseCondition('a.to_trace_no', '=', substr($rundownId, 0, 3)) . '
               AND a.status = 1 AND a.id_plant = ?
             ORDER BY a.id_trace_head DESC LIMIT 1) a
            UNION ALL
            SELECT 0 AS curr_qtf, ' . $this->dbDateFormat($this->dbCurDate(), '%Y-%m-%d') . ' AS entry_date, \'-INIT-\' AS status, \'\' AS created_at
             LIMIT 1
        ', [$idPlant], $idPlant);

            if (!empty($db) && (float)($db[0]->curr_qtf ?? 0) !== 0.0) {
                $db1 = DB::connection('eudr_ts')->select('
                    SELECT COALESCE(b.curr_qtf, 0) AS curr_qtf, b.created_at,
                           COALESCE(b.entry_date, ' . $this->dbDateFormat($this->dbCurDate(), '%Y-%m-%d') . ') AS entry_date,
                           \'-RESET-\' AS status
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

        return DB::connection('eudr_ts')->select('
            SELECT 0 AS curr_qtf, ' . $this->dbDateFormat($this->dbCurDate(), '%Y-%m-%d') . ' AS entry_date, \'-QTF-\' AS status
        ');
    }

    public function generateNewFeedNumber(string $feedId, $plantId): ?string
    {
        $idPlant = $this->resolvePlantId($plantId);
        $svc = app(\Modules\Shared\Services\TraceNumberService::class);
        $mapping = app(\Modules\Shared\Services\SectionMappingService::class);
        $section = substr($mapping->toFeedId($feedId), 0, 3);
        $plantCode = $svc->resolvePlantCode($idPlant);
        return $svc->generate('3', date('ymd'), $section, $plantCode);
    }

    public function generateNewRundownNumber(string $rundownId, $plantId, ?string $subgroup = null): ?string
    {
        $idPlant = $this->resolvePlantId($plantId);
        $svc = app(\Modules\Shared\Services\TraceNumberService::class);
        $mapping = app(\Modules\Shared\Services\SectionMappingService::class);
        $section = substr($mapping->toRundownId($rundownId, $subgroup), 0, 3);
        $plantCode = $svc->resolvePlantCode($idPlant);
        return $svc->generate('2', date('ymd'), $section, $plantCode);
    }
}
