<?php declare(strict_types=1);
namespace Modules\TsWip\Repositories\Traits;

use Illuminate\Support\Facades\DB;

trait WipEntryBatchTrait
{
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

    public function generateNewFeedNumber(string $feedId, $plantId): ?string
    {
        $idPlant = $this->resolvePlantId($plantId);
        $datePrefix = date('ymd');
        $sectionId = $this->mapFrontendSectionToDbFeedId($feedId);
        $traceSectionId = substr($sectionId, 0, 3);
        $plantCode = ($idPlant == 0 || $idPlant == '0') ? '00' : str_pad(substr((string)$idPlant, -2), 2, '0', STR_PAD_LEFT);

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

    public function generateNewRundownNumber(string $rundownId, $plantId, ?string $subgroup = null): ?string
    {
        $idPlant = $this->resolvePlantId($plantId);
        $datePrefix = date('ymd');
        $sectionId = $this->mapFrontendSectionToDbRundownId($rundownId, $subgroup);
        $traceSectionId = substr($sectionId, 0, 3);
        $plantCode = ($idPlant == 0 || $idPlant == '0') ? '00' : str_pad(substr((string)$idPlant, -2), 2, '0', STR_PAD_LEFT);

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
}
