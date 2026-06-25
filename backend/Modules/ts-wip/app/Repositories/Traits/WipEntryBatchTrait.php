<?php
declare(strict_types=1);
namespace Modules\TsWip\Repositories\Traits;

use Illuminate\Support\Facades\DB;
use Modules\Shared\Traits\DbCompatTrait;
use Modules\TsWip\Services\BatchNumberGenerator;

trait WipEntryBatchTrait
{
    use DbCompatTrait;

    public function getFeedNewBatchNumber(string $feedId, $plantId): ?string
    {
        $idPlant = $this->resolvePlantId($plantId);
        $feedPrefix = substr($feedId, 0, 3);
        $datePrefix = date('ymd');

        // Legacy fallback — only 14-digit feed traces (type 3) exist by definition.
        $only14 = \Modules\Shared\Helpers\TraceHelper::only14Digit('a.to_trace_no');
        $rows = $this->executeSelect('
            SELECT a.feed_number
              FROM (SELECT a.to_trace_no+1 AS feed_number
                      FROM t_trace_header a
                     WHERE ' . $only14 . '
                       AND SUBSTRING(a.to_trace_no,1,10) = CONCAT(3, ?, ?)
                       AND a.status = 1 AND a.id_plant = ?
                     ORDER BY a.id_trace_head DESC LIMIT 1) a
             UNION ALL
            SELECT CONCAT(3, ?, ? , LPAD(RIGHT(?, 2), 2, \'0\'), \'01\') AS feed_number
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
            // Legacy fallback — only 14-digit rundown traces (type 2) exist.
            $only14 = \Modules\Shared\Helpers\TraceHelper::only14Digit('a.to_trace_no');
            $rows = $this->executeSelect('
                SELECT CONCAT(2, SUBSTRING(a.to_trace_no,2,6), ?, LPAD(RIGHT(?, 2), 2, \'0\'), SUBSTRING(a.to_trace_no,13,2)) AS rundown_number
                  FROM (SELECT to_trace_no + 1 AS to_trace_no
                          FROM t_trace_header a
                         WHERE a.status = 1 AND a.id_plant = ?
                           AND ' . $only14 . '
                           AND SUBSTRING(a.to_trace_no,1,10) = CONCAT(2, ?, ?)
                         ORDER BY to_trace_no DESC LIMIT 1) a
                 UNION ALL
                SELECT CONCAT(2, ?, ? , LPAD(RIGHT(?, 2), 2, \'0\'), \'01\') AS rundown_number
                 LIMIT 1
            ', [$rundownId, $idPlant, $idPlant, $datePrefix, $rundownId, $datePrefix, $rundownId, $idPlant], $idPlant);
        } else {
            // Legacy fallback — only 14-digit rundown traces (type 2) exist.
            $only14 = \Modules\Shared\Helpers\TraceHelper::only14Digit('a.to_trace_no');
            $rows = $this->executeSelect('
                SELECT a.rundown_number
                  FROM (SELECT a.to_trace_no+1 AS rundown_number
                          FROM t_trace_header a
                         WHERE ' . $only14 . '
                           AND SUBSTRING(a.to_trace_no,1,10) = CONCAT(2, ?, ?)
                           AND a.status = 1 AND a.id_plant = ?
                         ORDER BY a.id_trace_head DESC LIMIT 1) a
                 UNION ALL
                SELECT CONCAT(2, ?, ? , LPAD(RIGHT(?, 2), 2, \'0\'), \'01\') AS rundown_number
                 LIMIT 1
            ', [$datePrefix, $rundownId, $idPlant, $datePrefix, $rundownId, $idPlant], $idPlant);
        }

        return $rows[0]->rundown_number ?? null;
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
        $idPlant       = $this->resolvePlantId($plantId);
        $datePrefix    = date('ymd');
        $traceSectionId = substr($this->mapFrontendSectionToDbFeedId($feedId), 0, 3);
        $plantCode     = $this->resolvePlantCode($idPlant);

        return DB::connection('eudr_ts')->transaction(
            fn () => $this->computeNextBatchNumber('3', $datePrefix, $traceSectionId, $plantCode)
        );
    }

    /**
     * Fetch existing today's batch numbers for the given prefix/date/section/plant
     * under a row-level lock, then derive the next sequence in PHP.
     */
    private function computeNextBatchNumber(
        string $prefix,
        string $date,
        string $section,
        string $plantCode
    ): string {
        $existing = DB::connection('eudr_ts')
            ->table('t_trace_header')
            ->where('status', 1)
            ->whereRaw('SUBSTRING(to_trace_no, 1, 1) = ?', [$prefix])
            ->whereRaw('SUBSTRING(to_trace_no, 2, 6) = ?', [$date])
            ->whereRaw(\Modules\Shared\Helpers\TraceHelper::warehouseCondition('to_trace_no', '=', $section))
            ->whereRaw(\Modules\Shared\Helpers\TraceHelper::plantCondition('to_trace_no', [$plantCode]))
            ->lockForUpdate()
            ->pluck('to_trace_no');

        $nextSeq = BatchNumberGenerator::nextSequence($existing);

        return BatchNumberGenerator::format($prefix, $date, $section, $plantCode, $nextSeq);
    }

    public function generateNewRundownNumber(string $rundownId, $plantId, ?string $subgroup = null): ?string
    {
        $idPlant        = $this->resolvePlantId($plantId);
        $datePrefix     = date('ymd');
        $traceSectionId = substr($this->mapFrontendSectionToDbRundownId($rundownId, $subgroup), 0, 3);
        $plantCode      = $this->resolvePlantCode($idPlant);

        return DB::connection('eudr_ts')->transaction(
            fn () => $this->computeNextBatchNumber('2', $datePrefix, $traceSectionId, $plantCode)
        );
    }

    /**
     * Resolve the 2-character plant suffix used in batch numbers.
     * Returns '00' when no plant is selected (all-plants context).
     */
    private function resolvePlantCode(?string $idPlant): string
    {
        if ($idPlant === null || $idPlant === '0' || $idPlant === '') {
            return '00';
        }

        $plant = DB::connection('eudr_ts')
            ->table('m_plant')
            ->where('code_3', $idPlant)
            ->value('code_3');

        return $plant ? substr($plant, -2) : substr($idPlant, -2);
    }
}
