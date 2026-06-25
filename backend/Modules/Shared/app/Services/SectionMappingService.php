<?php
declare(strict_types=1);
namespace Modules\Shared\Services;

/**
 * Maps frontend section IDs to database FeedId/RundownId.
 *
 * Consolidates mapFrontendSectionToDbFeedId and mapFrontendSectionToDbRundownId
 * from WipEntryQueryTrait into an injectable, testable service.
 *
 * ponytail: Add caching layer if called many times per request.
 *           Consider moving maps to config/wip_material_mapping.php.
 */
class SectionMappingService
{
    /**
     * Map frontend section ID → database feed ID.
     *
     * | Frontend | DB FeedId | Seksi |
     * |----------|-----------|-------|
     * | 101/102  | 001       | Degumming |
     * | 103      | 002       | Esterifikasi |
     * | 104      | 003       | Fractionation |
     * | 110      | 004       | Glycerin Purif |
     * | 302      | 005       | Cracking |
     * | 105      | 006-01/02 | Hidrogenasi |
     * | 111/116  | 007       | Glycerin Ref |
     * | 106/114  | 008-01/02 | FA Distilasi |
     * | 112      | 009-01    | FA Re-distilasi |
     */
    public function toFeedId(string $sectionId, ?string $subgroup = null, int $mode = 1): string
    {
        if ($sectionId === '105') {
            return $subgroup === 'short' ? '006-01' : '006-02';
        }
        if ($sectionId === '106' || $sectionId === '114') {
            return $mode === 2 ? '008-02' : '008-01';
        }

        return match ($sectionId) {
            '101', '102' => '001',
            '103'        => '002',
            '104'        => '003',
            '110'        => '004',
            '111', '116' => '007',
            '112'        => '009-01',
            '302'        => '005',
            default      => $sectionId,
        };
    }

    /**
     * Map frontend section ID + product → database rundown ID.
     *
     * @return string 3-digit DB rundown ID
     */
    public function toRundownId(string $sectionId, ?string $subgroup = null): string
    {
        $map = [
            '102' => ['daoil' => '011', 'pkfad' => '021'],
            '103' => ['crudeme' => '012', 'treatedgly' => '022'],
            '104' => ['ume' => '033', 'bdme' => '023', 'me28' => '043', 'econoate665' => '053', 'me80' => '063'],
            '105' => ['cfa28' => '016', 'cfa80' => '026'],
            '106' => ['fa1299' => '078', 'fa1499' => '088'],
            '110' => ['crudegly' => '014'],
            '111' => ['glycerine' => '017'],
            '112' => ['cfa28' => '069', 'fa12' => '039', 'fa14lrr' => '079', 'fa14' => '059', 'fa18' => '029', 'fa18lrr' => '049', 'ecowax' => '019'],
            '114' => ['ecowax' => '018', 'lefa' => '028', 'fa24' => '038', 'fa16' => '048', 'fa18lrr' => '058', 'fa26' => '068'],
            '302' => ['wme' => '015', 'me28' => '025'],
        ];

        if (!isset($map[$sectionId])) {
            return $sectionId;
        }

        if ($subgroup && isset($map[$sectionId][$subgroup])) {
            return $map[$sectionId][$subgroup];
        }

        return reset($map[$sectionId]);
    }

    /**
     * Check if a trace prefix is a WIP feed prefix.
     * Feed prefixes consume balance.
     */
    public function isFeedPrefix(string $prefix): bool
    {
        return in_array($prefix, ['3'], true);
    }

    /**
     * Check if a trace prefix is a WIP rundown prefix.
     * Rundown prefixes produce output.
     */
    public function isRundownPrefix(string $prefix): bool
    {
        return in_array($prefix, ['2'], true);
    }

    /**
     * Balance types consumed by feed/blending/transfer operations.
     */
    public static function consumablePrefixes(): array
    {
        return ['1', '2', '7', '8', '9'];
    }

    /**
     * All valid movement type prefixes.
     */
    public static function allPrefixes(): array
    {
        return ['1', '2', '3', '4', '5', '6', '7', '8', '9'];
    }
}
