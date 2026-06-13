<?php declare(strict_types=1);

namespace Modules\Inquiry\Repositories;

use Modules\Inquiry\Repositories\Contracts\PsPaReportRepositoryInterface;
use Modules\Shared\Repositories\Traits\PlantFilterTrait;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PsPaReportRepository implements PsPaReportRepositoryInterface
{
    use PlantFilterTrait;

    protected string $connection = 'eudr_ts';

    /**
     * Get PSPA report head list with plant filter.
     */
    public function getReportHeadList(mixed $plantId, ?int $userId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $plantFilter = $this->buildTablePlantFilter('r', $plantId, $userId);

        $where = [$plantFilter['sql']];
        $bindings = $plantFilter['bindings'];

        if ($dateFrom) {
            $where[] = 'r.period >= ?';
            $bindings[] = $dateFrom;
        }

        if ($dateTo) {
            $where[] = 'r.period <= ?';
            $bindings[] = $dateTo;
        }

        $sql = "
            SELECT r.id_report_head, r.period, r.batch_sap, r.adjust_status, r.status,
                   CASE r.status
                     WHEN 1 THEN 'DRAFT'
                     WHEN 2 THEN 'CALCULATED'
                     WHEN 3 THEN 'APPROVED'
                     ELSE 'UNKNOWN'
                   END AS status_label,
                   r.created_by, r.created_at,
                   COUNT(t.id_report_tail) AS material_count,
                   SUM(t.opening_stock) AS total_opening,
                   SUM(t.receive) AS total_receive,
                   SUM(t.production) AS total_production,
                   SUM(t.adjustment) AS total_adjustment,
                   SUM(t.closing_stock) AS total_closing
              FROM t_report_pspa_head r
              LEFT JOIN t_report_pspa_tail t ON r.id_report_head = t.id_report_head
             WHERE ({$this->sqlWhere($where)})
            GROUP BY r.id_report_head
            ORDER BY r.period DESC, r.id_report_head DESC
        ";

        return DB::connection($this->connection)->select($sql, $bindings);
    }

    /**
     * Get PSPA report head by ID.
     */
    public function getReportHead(int $reportId): ?object
    {
        return DB::connection($this->connection)->selectOne(
            'SELECT * FROM t_report_pspa_head WHERE id_report_head = ?',
            [$reportId]
        );
    }

    /**
     * Get PSPA report detail with tail data.
     */
    public function getReportDetail(int $reportId): ?array
    {
        $head = $this->getReportHead($reportId);
        if (!$head) {
            return null;
        }

        $tails = DB::connection($this->connection)->select(
            'SELECT t.*, CONCAT(m.code, " :: ", m.description) AS material
               FROM t_report_pspa_tail t
               LEFT JOIN m_material m ON t.id_material = m.id_material
              WHERE t.id_report_head = ?
              ORDER BY m.description ASC',
            [$reportId]
        );

        return [
            'head' => (array) $head,
            'tails' => $tails,
        ];
    }

    /**
     * Check if report exists for period.
     */
    public function reportExists(string $period, mixed $plantId): bool
    {
        $resolvedCode = \Modules\Shared\Services\PlantContextService::resolvePlantId($plantId);

        $result = DB::connection($this->connection)->selectOne(
            'SELECT COUNT(*) as cnt FROM t_report_pspa_head
              WHERE period = ? AND id_plant = ? AND status IN (1, 2, 3)',
            [$period, $resolvedCode]
        );

        return ($result->cnt ?? 0) > 0;
    }

    /**
     * Generate new PSPA report.
     */
    public function generateReport(string $user, mixed $plantId, string $period, array $data): array
    {
        $resolvedCode = \Modules\Shared\Services\PlantContextService::resolvePlantId($plantId);

        $id = DB::connection($this->connection)->table('t_report_pspa_head')->insertGetId([
            'period' => $period,
            'batch_sap' => $data['batch_sap'] ?? '',
            'adjust_status' => $data['adjust_status'] ?? 0,
            'status' => 1, // DRAFT
            'id_plant' => $resolvedCode,
            'created_by' => $user,
        ]);

        // Auto-generate detail rows from materials
        $materials = DB::connection($this->connection)->select(
            'SELECT id_material, code, description FROM m_material WHERE status = 1 AND type IN ("RM", "WIP", "PM")'
        );

        foreach ($materials as $mat) {
            DB::connection($this->connection)->table('t_report_pspa_tail')->insert([
                'id_report_head' => $id,
                'id_material' => $mat->id_material,
                'opening_stock' => 0,
                'receive' => 0,
                'production' => 0,
                'adjustment' => 0,
                'closing_stock' => 0,
                'status' => 1,
                'created_by' => $user,
            ]);
        }

        return ['response' => 1, 'id' => $id];
    }

    /**
     * Calculate PSPA report with actual stock data.
     *
     * Formula:
     * Opening Stock = Stock at start of period
     * Receive = Sum of all incoming (in_qty from trace_header where entry_date in period)
     * Production = Sum of all production outputs (WIP entries)
     * Adjustment = Sum of all adjustments in period
     * Closing Stock = Opening + Receive - Production - Adjustment
     */
    public function calculateReport(int $reportId): array
    {
        $report = $this->getReportHead($reportId);
        if (!$report) {
            return ['response' => 0, 'message' => 'Report not found'];
        }

        $period = $report->period;
        $plantCode = $report->id_plant;

        // Get all tail rows for this report
        $tails = DB::connection($this->connection)->select(
            'SELECT * FROM t_report_pspa_tail WHERE id_report_head = ? AND status = 1',
            [$reportId]
        );

        foreach ($tails as $tail) {
            $materialId = $tail->id_material;

            // Calculate Opening Stock (stock at first day of period)
            $periodStart = Carbon::parse($period)->startOfMonth()->toDateString();
            $opening = $this->calculateOpeningStock($materialId, $periodStart, $plantCode);

            // Calculate Receive (incoming in period)
            $receive = $this->calculateReceive($materialId, $period, $plantCode);

            // Calculate Production (WIP output in period)
            $production = $this->calculateProduction($materialId, $period, $plantCode);

            // Calculate Adjustment (adjustments in period)
            $adjustment = $this->calculateAdjustment($materialId, $period, $plantCode);

            // Calculate Closing Stock
            $closing = $opening + $receive - $production + $adjustment;

            // Update tail row
            DB::connection($this->connection)->table('t_report_pspa_tail')
                ->where('id_report_tail', $tail->id_report_tail)
                ->update([
                    'opening_stock' => $opening,
                    'receive' => $receive,
                    'production' => $production,
                    'adjustment' => $adjustment,
                    'closing_stock' => $closing,
                ]);
        }

        // Update report status to CALCULATED (2)
        DB::connection($this->connection)->table('t_report_pspa_head')
            ->where('id_report_head', $reportId)
            ->update(['status' => 2]);

        return ['response' => 1];
    }

    /**
     * Approve PSPA report.
     */
    public function approveReport(int $reportId, string $user): array
    {
        DB::connection($this->connection)->table('t_report_pspa_head')
            ->where('id_report_head', $reportId)
            ->update([
                'status' => 3, // APPROVED
                'approved_by' => $user,
                'approved_at' => now(),
            ]);

        return ['response' => 1];
    }

    /**
     * Get material stock summary.
     */
    public function getMaterialStock(array $filters): array
    {
        $plantId = $filters['plant_id'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $materialId = $filters['material_id'] ?? null;

        $plantFilter = $this->buildTablePlantFilter('bh', $plantId, $userId);

        $where = [$plantFilter['sql']];
        $bindings = $plantFilter['bindings'];

        if ($materialId) {
            $where[] = 'bh.id_material = ?';
            $bindings[] = $materialId;
        }

        $sql = "
            SELECT m.id_material, m.code, m.description AS material,
                   SUM(bh.qty) AS current_stock,
                   COUNT(DISTINCT bh.id_balance_head) AS batch_count,
                   MIN(bh.entry_date) AS first_entry,
                   MAX(bh.entry_date) AS last_entry
              FROM t_balance_header bh
              LEFT JOIN m_material m ON bh.id_material = m.id_material
             WHERE bh.status = 1
               AND bh.qty > 0.0001
               AND ({$this->sqlWhere($where)})
            GROUP BY m.id_material
            ORDER BY current_stock DESC
        ";

        return DB::connection($this->connection)->select($sql, $bindings);
    }

    /**
     * Calculate opening stock for a material.
     */
    protected function calculateOpeningStock(string $materialId, string $periodStart, string $plantCode): float
    {
        $result = DB::connection($this->connection)->selectOne(
            'SELECT SUM(in_qty) - SUM(out_qty) AS opening
               FROM t_trace_header
              WHERE id_material = ?
                AND id_plant = ?
                AND entry_date < ?
                AND status = 1',
            [$materialId, $plantCode, $periodStart]
        );

        return (float) ($result->opening ?? 0);
    }

    /**
     * Calculate receive for a material in period.
     */
    protected function calculateReceive(string $materialId, string $period, string $plantCode): float
    {
        $periodStart = Carbon::parse($period)->startOfMonth()->toDateString();
        $periodEnd = Carbon::parse($period)->endOfMonth()->toDateString();

        $result = DB::connection($this->connection)->selectOne(
            'SELECT SUM(in_qty) AS receive
               FROM t_trace_header
              WHERE id_material = ?
                AND id_plant = ?
                AND entry_date BETWEEN ? AND ?
                AND in_qty > 0
                AND status = 1',
            [$materialId, $plantCode, $periodStart, $periodEnd]
        );

        return (float) ($result->receive ?? 0);
    }

    /**
     * Calculate production for a material in period.
     */
    protected function calculateProduction(string $materialId, string $period, string $plantCode): float
    {
        $periodStart = Carbon::parse($period)->startOfMonth()->toDateString();
        $periodEnd = Carbon::parse($period)->endOfMonth()->toDateString();

        // Production = output from WIP (trace_prefix = 3)
        $result = DB::connection($this->connection)->selectOne(
            'SELECT SUM(in_qty) AS production
               FROM t_trace_header
              WHERE id_material = ?
                AND id_plant = ?
                AND entry_date BETWEEN ? AND ?
                AND SUBSTRING(to_trace_no, 1, 1) = "3"
                AND in_qty > 0
                AND status = 1',
            [$materialId, $plantCode, $periodStart, $periodEnd]
        );

        return (float) ($result->production ?? 0);
    }

    /**
     * Calculate adjustment for a material in period.
     */
    protected function calculateAdjustment(string $materialId, string $period, string $plantCode): float
    {
        $periodStart = Carbon::parse($period)->startOfMonth()->toDateString();
        $periodEnd = Carbon::parse($period)->endOfMonth()->toDateString();

        // Adjustment = from t_adjustment_header where status = 4 (executed)
        $result = DB::connection($this->connection)->selectOne(
            'SELECT SUM(ah.after_adjust - ah.before_adjust) AS adjustment
               FROM t_adjustment_header ah
               LEFT JOIN t_balance_header bh ON ah.id_balance_head = bh.id_balance_head
              WHERE ah.id_material = ?
                AND ah.entry_date BETWEEN ? AND ?
                AND ah.status = 4',
            [$materialId, $periodStart, $periodEnd]
        );

        return (float) ($result->adjustment ?? 0);
    }

    /**
     * Build SQL WHERE clause.
     */
    protected function sqlWhere(array $conditions): string
    {
        return implode(' AND ', $conditions);
    }
}