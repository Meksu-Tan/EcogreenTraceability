<?php declare(strict_types=1);

namespace Modules\Shared\Services;

class TraceNumberGeneratorService
{
    /**
     * Parse a trace number into components.
     * Format: [Prefix][YYMMDD][Section/Warehouse][Plant][Sequence]
     */
    public static function parse(string $traceNo): array
    {
        return [
            'prefix'   => substr($traceNo, 0, 1),
            'date'     => substr($traceNo, 1, 6),
            'section'  => substr($traceNo, 7, 3),
            'plant'    => substr($traceNo, 10, 2),
            'sequence' => substr($traceNo, 12, 2),
        ];
    }

    /**
     * Format components into a standardized trace/batch number.
     */
    public static function format(
        string $prefix,
        string $date,
        string $section,
        string $plantCode,
        int $sequence
    ): string {
        $cleanPrefix = preg_replace('/\D/', '', $prefix);
        
        $cleanDate = str_pad(substr(preg_replace('/\D/', '', $date), 0, 6), 6, '0', STR_PAD_LEFT);
        
        $cleanSection = str_pad(substr(preg_replace('/\D/', '', $section) ?: '000', 0, 3), 3, '0', STR_PAD_LEFT);
        
        $cleanPlant = str_pad(substr(preg_replace('/\D/', '', $plantCode) ?: '0', -2, 2), 2, '0', STR_PAD_LEFT);
        
        $cleanSeq = str_pad((string) max(1, min(99, $sequence)), 2, '0', STR_PAD_LEFT);
        
        return $cleanPrefix . $cleanDate . $cleanSection . $cleanPlant . $cleanSeq;
    }
}
