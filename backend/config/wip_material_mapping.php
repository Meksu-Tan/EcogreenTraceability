<?php

declare(strict_types=1);

return [
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

];
