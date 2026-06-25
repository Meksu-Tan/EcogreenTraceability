<?php declare(strict_types=1);

/*
 * WIP Material Mapping Configuration
 *
 * Contains all hardcoded domain mappings used in WipEntryQueryTrait.
 *
 * feed_prefix_009  — Feed ID prefix for PKO/Palm Kernel Oil section (fractionation)
 * feed_prefix_006  — Feed ID prefix for PFAD/Palm Fatty Acid Distillate section
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Feed Prefix Constants
    |--------------------------------------------------------------------------
    | Named keys for the two feed prefixes that require material-sign routing.
    | '009' = PKO/fractionation feed section
    | '006' = PFAD feed section
    */
    'feed_prefix_009' => '009',
    'feed_prefix_006' => '006',

    /*
    |--------------------------------------------------------------------------
    | Feed Material Map
    |--------------------------------------------------------------------------
    | Maps (feed_prefix + material_sign) to one or two material IDs.
    |
    | Structure per entry:
    |   'id_material'  — single target material ID (string), or null when dual
    |   'id_material1' — first material ID for dual-material queries, or null
    |   'id_material2' — second material ID for dual-material queries, or null
    */
    'feed_material_map' => [
        '009' => [
            '01' => ['id_material' => '12',  'id_material1' => null, 'id_material2' => null],
            '02' => ['id_material' => '25',  'id_material1' => null, 'id_material2' => null],
            '03' => ['id_material' => null,  'id_material1' => '18', 'id_material2' => '22'],
            '04' => ['id_material' => '14',  'id_material1' => null, 'id_material2' => null],
        ],
        '006' => [
            '01' => ['id_material' => null,  'id_material1' => '6',  'id_material2' => '31'],
            '02' => ['id_material' => '66',  'id_material1' => null, 'id_material2' => null],
        ],
        '008' => [
            '01' => ['id_material' => null,  'id_material1' => '7',  'id_material2' => '24'],
            '02' => ['id_material' => '65',  'id_material1' => null, 'id_material2' => null],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rundown-to-Feed Map
    |--------------------------------------------------------------------------
    | Maps a DB rundown ID to the corresponding frontend feed section ID.
    | Used by mapRundownToFeedSectionId() in WipEntryQueryTrait.
    */
    'rundown_to_feed_map' => [
        '101' => '101',
        '102' => '103',
        '103' => '104',
        '104' => '105',
        '110' => '111',
        '111' => '112',
        '114' => '114',
        '011' => '001',
        '021' => '001',
        '012' => '002',
        '022' => '002',
        '033' => '003',
        '023' => '003',
        '043' => '006-01',
        '053' => '003',
        '063' => '003',
        '026' => '006-02',
        '078' => '009-02',
        '088' => '009-02',
        '014' => '004',
        '017' => '007',
        '013' => '003',
        '016' => '006-01',
        '018' => '009',
        '028' => '009',
        '098' => '008-02',
        '108' => '008-02',
        '118' => '008-02',
        '038' => '009',
        '048' => '009',
        '058' => '009',
        '068' => '009',
        '069' => '009-01',
        '039' => '009-01',
        '079' => '009-02',
        '059' => '009-02',
        '029' => '009-03',
        '049' => '009-03',
        '019' => '009-04',
        '015' => '006-01',
        '025' => '006-01',
    ],

];
