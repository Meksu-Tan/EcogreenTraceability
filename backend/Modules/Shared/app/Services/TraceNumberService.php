<?php
declare(strict_types=1);
namespace Modules\Shared\Services;

use Illuminate\Support\Facades\DB;
use Modules\Shared\Helpers\TraceHelper;

/**
 * Unified trace number generator — FOR UPDATE + PHP sequencing.
 *
 * Consolidates ALL trace number generation across ts-* and m-adjustment.
 * Format: {prefix}{YYMMDD}{section}{plant}{seq} (14-digit)
 *
 * ponytail: Replaces TraceNumberGeneratorTrait, inline UNION ALL CONCAT in
 *           repositories, getNewNumber/getFeedNewBatchNumber/getRundownNewBatchNumber.
 *           Add material_id → section caching if perf becomes an issue.
 */
class TraceNumberService
{
    protected string $connection = 'eudr_ts';

    /**
     * Generate next trace number with pessimistic locking.
     *
     * @param string $prefix    Movement type (1-9)
     * @param string $date      YYMMDD
     * @param string $section   3-digit section/warehouse/rundown code
     * @param string $plantCode 2-digit plant suffix
     * @param string $table     Sequence-check table (t_trace_header|t_balance_header)
     * @param string $column    Column (to_trace_no|trace_no)
     */
    public function generate(
        string $prefix,
        string $date,
        string $section,
        string $plantCode,
        string $table = 't_trace_header',
        string $column = 'to_trace_no',
    ): string {
        $existing = DB::connection($this->connection)
            ->table($table)
            ->where('status', 1)
            ->whereRaw('SUBSTRING(' . $column . ', 1, 1) = ?', [$prefix])
            ->whereRaw('SUBSTRING(' . $column . ', 2, 6) = ?', [$date])
            ->whereRaw(TraceHelper::warehouseCondition($column, '=', $section))
            ->whereRaw(TraceHelper::plantCondition($column, [$plantCode]))
            ->lockForUpdate()
            ->pluck($column);

        $nextSeq = $this->nextSequence($existing);

        return TraceNumberGeneratorService::format($prefix, $date, $section, $plantCode, $nextSeq);
    }

    /**
     * Resolve 3-digit section/warehouse from material or warehouse context.
     * Priority: warehouseId → material.id_rundown → material.id_feed → '000'
     */
    public function resolveSection(
        string $prefix,
        ?int $materialId = null,
        ?string $warehouseId = null,
    ): string {
        if ($warehouseId !== null && $warehouseId !== '') {
            return str_pad($warehouseId, 3, '0', STR_PAD_LEFT);
        }

        if (in_array($prefix, ['5', '4'], true)) {
            return $materialId
                ? str_pad((string)$materialId, 3, '0', STR_PAD_LEFT)
                : '000';
        }

        if ($materialId === null) {
            return '000';
        }

        $material = DB::connection($this->connection)->table('m_material')
            ->where('id_material', $materialId)
            ->first();

        if (!$material) {
            return '000';
        }

        if (!empty($material->id_rundown) && !in_array($material->id_rundown, ['0', '-', ''], true)) {
            return str_pad((string)$material->id_rundown, 3, '0', STR_PAD_LEFT);
        }

        if (!empty($material->id_feed) && !in_array($material->id_feed, ['0', '-', ''], true)) {
            return str_pad((string)$material->id_feed, 3, '0', STR_PAD_LEFT);
        }

        return '000';
    }

    /**
     * Resolve 2-digit plant suffix from code_3 or id_plant.
     */
    public function resolvePlantCode(?string $idPlant): string
    {
        if ($idPlant === null || $idPlant === '0' || $idPlant === '') {
            return '00';
        }

        $plant = DB::connection($this->connection)
            ->table('m_plant')
            ->where('code_3', $idPlant)
            ->value('code_3');

        return $plant ? substr($plant, -2) : substr($idPlant, -2);
    }

    /**
     * Generate companion trace by replacing a position in the source.
     * Blending: companion(entryNo, 8, 1, '0')
     * Package:  companion(entryNo, 7, 3, '000')
     */
    public function companion(string $entryNo, int $position, int $length, string $replacement): string
    {
        return substr_replace($entryNo, $replacement, $position, $length);
    }

    /**
     * Derive next sequence (1-99) from existing batch numbers.
     */
    public function nextSequence(iterable $existingNumbers): int
    {
        $max = 0;
        foreach ($existingNumbers as $batchNo) {
            $parsed = TraceNumberGeneratorService::parse((string) $batchNo);
            $seq = (int) ($parsed['sequence'] ?? 0);
            if ($seq > $max) {
                $max = $seq;
            }
        }
        return $max + 1;
    }
}
