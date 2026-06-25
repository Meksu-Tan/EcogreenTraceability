<?php
declare(strict_types=1);
namespace Modules\Shared\Helpers;

/**
 * Proportionally distribute a remaining amount across an array of items.
 *
 * Consolidated from:
 *   - AdjustmentRepository::adjustAmtToTotal()
 *   - EloquentShipmentRepository::adjustQtyToTotal()
 *
 * Algorithm: scale each item's $qtyKey by factor = $totalTarget / currentSum,
 * using high-precision bcmath library to avoid float precision drift.
 */
class QuantityDistributionHelper
{
    /**
     * Proportionally distribute $totalTarget across $items.
     *
     * @param array<int, array<string, mixed>> $items  Flat list of items, each containing $qtyKey.
     * @param float                            $totalTarget  Desired sum after distribution.
     * @param string                           $qtyKey   Key holding the numeric quantity (default 'out_qty').
     * @return array<int, array<string, mixed>>  Items with adjusted $qtyKey values.
     */
    public static function adjustToTotal(array $items, float $totalTarget, string $qtyKey = 'out_qty'): array
    {
        $targetStr = sprintf('%.10F', $totalTarget);

        $currentSum = '0';
        foreach ($items as $item) {
            $val = sprintf('%.10F', (float) ($item[$qtyKey] ?? 0));
            $currentSum = bcadd($currentSum, $val, 10);
        }

        if (bccomp($currentSum, '0', 10) === 0) {
            return $items;
        }

        $factor = bcdiv($targetStr, $currentSum, 10);
        $newSum = '0';

        foreach ($items as $idx => $item) {
            $val = sprintf('%.10F', (float) ($item[$qtyKey] ?? 0));
            $adjusted = bcmul($val, $factor, 10);
            $adjustedFloat = round((float) $adjusted, 4);

            $items[$idx][$qtyKey] = $adjustedFloat;
            $newSum = bcadd($newSum, sprintf('%.10F', $adjustedFloat), 10);
        }

        $lastIdx = array_key_last($items);
        if ($lastIdx !== null) {
            $delta = bcsub($targetStr, $newSum, 10);
            $items[$lastIdx][$qtyKey] = round($items[$lastIdx][$qtyKey] + (float) $delta, 4);
        }

        return $items;
    }
}

