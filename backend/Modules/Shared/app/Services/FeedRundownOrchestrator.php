<?php

declare(strict_types=1);

namespace Modules\Shared\Services;

use Modules\Shared\Helpers\Feed;
use Modules\Shared\Helpers\Rundown;

class FeedRundownOrchestrator
{
    /**
     * Executes the Feed and Rundown orchestration sequence.
     *
     * @param  array  $feedParams  Parameters for Feed::generalFeed
     * @param  array  $rundownParams  Parameters for Rundown::generalRundown, minus supplier_rows, in_qty, curr_qtf
     * @return array Response array with ['response' => code, 'id_trace_head' => id (optional)]
     */
    public function executeFeedRundownSequence(array $feedParams, array $rundownParams): array
    {
        // 1. Execute Feed
        $feedResult = Feed::generalFeed($feedParams);
        if ($feedResult['response'] != 1) {
            return ['response' => $feedResult['response']];
        }

        // 2. Extract Supplier Rows
        $supplierRows = $this->extractSupplierRows($feedResult);
        if ($supplierRows === null) {
            return ['response' => 6]; // No supplier traced
        }

        $actualQty = round($feedResult['total_out'], 4);

        // 2.5 Apply rounding correction to prevent float drift
        Rundown::adjustRundownToTotal($supplierRows, $actualQty);

        // 3. Inject dynamic values into Rundown params
        $rundownParams['in_qty'] = $actualQty;
        $rundownParams['curr_qtf'] = $actualQty;
        $rundownParams['supplier_rows'] = $supplierRows;

        // 4. Execute Rundown
        $rundownResult = Rundown::generalRundown($rundownParams);
        if ($rundownResult['response'] != 1) {
            return ['response' => 3]; // Insufficient balance or generic error during rundown
        }

        return $rundownResult;
    }

    private function extractSupplierRows(array $feedResult): ?array
    {
        if (empty($feedResult['feed_in_details'])) {
            return null;
        }

        return array_map(function ($d) {
            return [
                'id_supplier' => $d['id_supplier'],
                'batch_sap' => $d['batch_sap'],
                'rundownSupplier' => (float) $d['qty'],
            ];
        }, $feedResult['feed_in_details']);
    }
}
