<?php

declare(strict_types=1);

namespace Modules\TsWip\Services;

/**
 * Handles generation and parsing of batch numbers for WIP entries.
 *
 * Format: [P][YYMMDD][TTT][PP][SS]
 *  P      = prefix (3=Feed, 2=Rundown)
 *  YYMMDD = date
 *  TTT    = section/feed ID (3-digit, zero-padded)
 *  PP     = last 2 chars of plant code_3
 *  SS     = daily sequence (01–99)
 */
class BatchNumberGenerator
{
    public static function parse(string $batchNo): array
    {
        return [
            'prefix'   => substr($batchNo, 0, 1),
            'date'     => substr($batchNo, 1, 6),
            'section'  => substr($batchNo, 7, 3),
            'plant'    => substr($batchNo, 10, 2),
            'sequence' => substr($batchNo, 12, 2),
        ];
    }

    public static function format(
        string $prefix,
        string $date,
        string $section,
        string $plant,
        int $sequence
    ): string {
        return $prefix . $date . $section . $plant . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Derive the next sequence number from a collection of existing batch numbers.
     * Returns 1 if no existing numbers are found (first of day).
     *
     * @param  iterable<string>  $existingNumbers
     */
    public static function nextSequence(iterable $existingNumbers): int
    {
        $max = 0;

        foreach ($existingNumbers as $batchNo) {
            $parsed = self::parse((string) $batchNo);
            $seq    = (int) $parsed['sequence'];
            if ($seq > $max) {
                $max = $seq;
            }
        }

        return $max + 1;
    }
}
