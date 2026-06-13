<?php declare(strict_types=1);

namespace Modules\Shared\Helpers;

/**
 * Proportionally distribute a remaining amount across an array of items.
 *
 * Consolidated from:
 *   - AdjustmentRepository::adjustAmtToTotal()
 *   - EloquentShipmentRepository::adjustQtyToTotal()
 *
 * Algorithm: scale each item's $qtyKey by factor = $totalTarget / currentSum,
 * round to 4 decimals, then adjust the last item by the rounding delta so
 * the sum exactly matches $totalTarget.  No negative values are produced
 * (caller is responsible for ensuring $totalTarget >= 0).
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
        $currentSum = array_sum(array_column($items, $qtyKey));
        if (abs($currentSum) < 1e-10) {
            return $items;
        }

        $factor = $totalTarget / $currentSum;
        $newSum = 0.0;
        $lastIdx = array_key_last($items);

        foreach ($items as $idx => $item) {
            $adjusted = round((float) ($item[$qtyKey] ?? 0) * $factor, 4);
            $items[$idx][$qtyKey] = $adjusted;
            $newSum += $adjusted;
        }

        // Absorb rounding drift into the last item
        if ($lastIdx !== null) {
            $delta = round($totalTarget - $newSum, 4);
            $items[$lastIdx][$qtyKey] += $delta;
        }

        return $items;
    }
}
