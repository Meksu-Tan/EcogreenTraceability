<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PlantSetup extends Model
{
    use HasFactory;

    protected $connection = 'eudr_ts';

    static function get_activePlant(){
        $db = DB::select('SELECT a.id_plant, a.description,
                            FROM m_plant a
                           WHERE a.status = 1
                           ORDER BY a.description ASC');

        return $db;
    }
    static function get_plant_dt(){
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        $db = DB::select('SELECT a.id_plant, CONCAT(a.code_2, " | ", a.code_3) AS code, a.description, a.status,
                                 a.created_at, a.created_by, a.updated_at, a.code_2, a.code_3,
                                 a.updated_by, a.id_tank,
                                 a.description AS plant
                            FROM m_plant a
                           ORDER BY a.description ASC');
        return $db;
    }
    // static function get_plantDetail_dt($request){
    //     $idPlant = $request->input('id_plant');

    //     $db = DB::select('SELECT a.id_plant_tail, a.tf_number, a.status, a.created_at, a.updated_at,
    //                              b.description AS plant, b.id_tank, b.id_plant
    //                         FROM m_plant_detail a
    //                         LEFT JOIN m_plant b
    //                           ON a.id_plant = b.id_plant
    //                        WHERE b.id_plant = ?
    //                          AND a.status = 1
    //                        ORDER BY a.tf_number ASC', [$idPlant]);
    //     return $db;
    // }

    static function post_plant_destroy($id, $user){
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'M_PLANT', 'DE-ACTIVATE', 'ID: ' . $id . ' | Status: 1 >> 0', $user ]);

        $db = DB::update('UPDATE m_plant
                             SET `status` = "0",
                                 `updated_by` = ?
                           WHERE id_plant = ?', [$user, $id]);
        $db = [ (object)['response' => $db ] ];

        return $db;
    }
    static function post_plant_activate($id, $user){
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'M_PLANT', 'ACTIVATE', 'ID: ' . $id . ' | Status: 0 >> 1', $user ]);

        $db = DB::update('UPDATE m_plant
                             SET `status` = "1",
                                 `updated_by` = ?
                           WHERE id_plant = ?', [$user, $id]);
        $db = [ (object)['response' => $db ] ];

        return $db;
    }
    static function post_plant_store($user, $request){
        $id = $request->input('id');
        $mode = $request->input('mode');
        $code_3 = $request->input('code');
        $code_2 = $request->input('type');
        $idTank = $request->input('id_tank');
        $description = $request->input('description');

        if ($mode == 'ADD'){
            /* CHECK FOR DOUBLE STORAGE TANK */
            $db = DB::select('SELECT IF(COUNT(id_plant)>=1,2,1) as response
                                FROM m_tank
                               WHERE id_tank = ?
                                 AND code_2 = ?
                                 AND code_3 = ?
                                 AND `description` = ?
                                 AND `status` = "1"', [$idTank, $code_2, $code_3, $description]);

            if ($db[0]->response == '1'){
                /* INSERT TO DB */
                $db = DB::insert('INSERT INTO m_plant
                                         (code_2, code_3, id_tank, `description`, created_by)
                                  VALUES (?, ?, ?, ?, ?, ?)', [$code_2, $code_3, $idTank, $description, $user]);
                $db = [ (object)['response' => $db ? 1 : 0 ]];

                /* LOGGING */
                $id = DB::select('SELECT id_plant FROM m_plant ORDER BY id_plant DESC LIMIT 1');
                DB::insert('INSERT INTO log_transactions
                                   (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'M_PLANT', 'ADD', 'ID: ' . $id[0]->id_tank . ' | CODE: ' . $code_3 .
                                                    ' / NAME: ' . $description . ' / TYPE: ' . $code_2 . ' / ID_TANK: ' . $idTank .
                                                    ' | Status: 1', $user ]);
            }

        } elseif ($mode == 'UPDATE'){
            $old_db = DB::select('SELECT code_2, code_3, `description`, `id_tank`
                                    FROM m_plant
                                   WHERE id_plant = ?', [$id]);
            DB::insert('INSERT INTO log_transactions
                               (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)', [ 'M_PLANT', 'UPDATE', 'ID: ' . $id . ' | CODE: ' . $old_db[0]->code_3 . ' >> ' .$code_3 .
                                                ' / NAME: ' . $old_db[0]->description . ' >> ' . $description .
                                                ' / TYPE: ' . $old_db[0]->code_2 . ' >> ' . $code_2 .
                                                ' / ID_TANK: ' . $old_db[0]->id_tank . ' >> ' . $idTank .
                                                ' | Status: 1', $user ]);

            $db = DB::update('UPDATE m_plant
                                 SET code_2 = ?,
                                     code_3 = ?,
                                     id_tank = ?,
                                     `description` = ?,
                                     updated_by = ?
                               WHERE id_plant = ?', [$code_2, $code_3, $idTank, $description, $user, $id]);
            $db = [ (object)['response' => $db ]];

        }

        return $db;
    }

    // static function post_plantDetail_destroy($id, $user){
    //     DB::insert('INSERT INTO log_transactions
    //                        (log_module, log_type, log_description, created_by)
    //                 VALUES (?, ?, ?, ?)', [ 'M_PLANT_DETAIL', 'DE-ACTIVATE', 'ID: ' . $id . ' | Status: 1 >> 0', $user ]);

    //     $db = DB::update('UPDATE m_plant_detail
    //                          SET `status` = "0",
    //                              `updated_by` = ?
    //                        WHERE id_plant_tail = ?', [$user, $id]);
    //     $db = [ (object)['response' => $db ] ];

    //     return $db;
    // }
    // static function post_plantDetail_activate($id, $user){
    //     DB::insert('INSERT INTO log_transactions
    //                        (log_module, log_type, log_description, created_by)
    //                 VALUES (?, ?, ?, ?)', [ 'M_PLANT_DETAIL', 'ACTIVATE', 'ID: ' . $id . ' | Status: 0 >> 1', $user ]);

    //     $db = DB::update('UPDATE m_plant_detail
    //                          SET `status` = "1",
    //                              `updated_by` = ?
    //                        WHERE id_plant_tail = ?', [$user, $id]);
    //     $db = [ (object)['response' => $db ] ];

    //     return $db;
    // }
    // static function post_plantDetail_store($user, $request){
    //     $id = $request->input('id');
    //     $idPlant = $request->input('id_plant');
    //     $mode = $request->input('mode');
    //     $tfNumber = $request->input('tf_number');

    //     if ($mode == 'ADD'){
    //         /* CHECK FOR DOUBLE STORAGE TANK */
    //         $db = DB::select('SELECT IF(COUNT(id_plant_tail)>=1,2,1) as response
    //                             FROM m_plant_detail
    //                            WHERE tf_number = ?
    //                              AND `status` = 1
    //                         ', [$tfNumber]);

    //         if ($db[0]->response == '1'){
    //             /* INSERT TO DB */
    //             $db = DB::insert('INSERT INTO m_plant_detail
    //                                      (id_plant, tf_number, created_by)
    //                               VALUES (?, ?, ?)', [$idPlant, $tfNumber, $user]);
    //             $db = [ (object)['response' => $db ? 1 : 0 ]];

    //             /* LOGGING */
    //             $id = DB::select('SELECT id_plant_tail FROM m_plant_detail ORDER BY id_plant_tail DESC LIMIT 1');
    //             DB::insert('INSERT INTO log_transactions
    //                                (log_module, log_type, log_description, created_by)
    //                         VALUES (?, ?, ?, ?)', [ 'M_PLANT', 'ADD', 'ID: ' . $id[0]->id_plant_tail . ' | ID_PLANT: ' . $idPlant .
    //                                                 ' / TF_NUMBER: ' . $tfNumber .
    //                                                 ' | Status: 1', $user ]);
    //         }

    //     } elseif ($mode == 'UPDATE'){
    //         $db = DB::select('SELECT IF(COUNT(id_plant_tail)>=1,2,1) as response
    //                             FROM m_plant_detail
    //                            WHERE tf_number = ?
    //                              AND `status` = 1
    //                         ', [$tfNumber]);

    //         if ($db[0]->response == '1'){
    //             $old_db = DB::select('SELECT tf_number
    //                                     FROM m_plant_detail
    //                                 WHERE id_plant_tail = ?', [$id]);
    //             DB::insert('INSERT INTO log_transactions
    //                             (log_module, log_type, log_description, created_by)
    //                         VALUES (?, ?, ?, ?)', [ 'M_PLANT', 'UPDATE', 'ID: ' . $id . ' | TF_NUMBER: ' . $old_db[0]->tf_number . ' >> ' .$tfNumber .
    //                                                 ' | Status: 1', $user ]);

    //             $db = DB::update('UPDATE m_plant_detail
    //                                 SET tf_number = ?,
    //                                     updated_by = ?
    //                             WHERE id_plant_tail = ?', [$tfNumber, $user, $id]);
    //             $db = [ (object)['response' => $db ]];
    //         }

    //     }

    //     return $db;
    // }
}
