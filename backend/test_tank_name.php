<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::connection('eudr_ts')->statement('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))');

$query = "SELECT a.id_tank_tail,
          CONCAT(d.description,
              IF(
                  a.id_tank_tail IS NOT NULL
                  AND a.id_tank_tail != ''
                  AND a.id_tank_tail != '[]',
                  CONCAT(' | ',
                      COALESCE(
                          (
                              SELECT GROUP_CONCAT(h.tf_number ORDER BY h.tf_number ASC SEPARATOR ' & ')
                              FROM JSON_TABLE(
                                  IF(JSON_VALID(a.id_tank_tail), a.id_tank_tail, '[]'),
                                  '$[*]' COLUMNS (sloc_id INT PATH '$')
                              ) AS jt
                              JOIN m_sloc_detail h ON h.id_sloc_tail = jt.sloc_id AND h.status = 1
                          ),
                          REPLACE(REPLACE(REPLACE(a.id_tank_tail, '[', ''), ']', ''), '\"', '')
                      )
                  ),
                  ''
              )
          ) AS tank_name
          FROM t_trace_header a
          JOIN m_sloc d ON a.id_sloc = d.id_sloc
          WHERE a.id_tank_tail IS NOT NULL AND a.id_tank_tail != '[]'
          ORDER BY a.id_trace_head DESC LIMIT 5";

echo json_encode(DB::connection('eudr_ts')->select($query));
