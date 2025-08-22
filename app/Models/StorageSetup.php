<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StorageSetup extends Model
{
    protected $connection = 'eudr_ts';

    static function get_activeStorage(){
        $db = DB::select('SELECT a.id_tank, a.description,
                            FROM m_tank a
                           WHERE a.status = 1
                           ORDER BY a.description ASC');

        return $db;
    }
    static function get_warehouse_dt(){
        $db = DB::select('  SELECT
                                a.id_warehouse, a.id_batch, a.code, a.description, a.status, a.created_by,
                                a.created_at, a.updated_by, a.updated_at
                            FROM
                                m_warehouse a
                            ORDER BY
                                a.id_batch ASC');

        return $db;
    }
    static function get_storageTank_dt(){
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        $db = DB::select('SELECT a.id_tank, CONCAT(a.code_2, " | ", a.code_3) AS code, a.description, a.status,
                                 a.created_at, a.created_by, a.updated_at, a.code_2, a.code_3,
                                 a.updated_by, a.id_plant, IFNULL(b.total_tank, 0) AS total_tank,
                                 a.description AS storage, a.code_4
                            FROM m_tank a
                            LEFT JOIN (SELECT COUNT(b.id_tank_tail) AS total_tank, b.id_tank
                                         FROM m_tank_detail b
                                        WHERE b.status = 1
                                        GROUP BY b.id_tank) b
                              ON a.id_tank = b.id_tank
                           ORDER BY a.description ASC');
        return $db;
    }
    static function get_storageDetail_dt($request){
        $idTank = $request->input('id_tank');

        $db = DB::select('SELECT a.id_tank_tail, a.tf_number, a.status, a.created_at, a.updated_at,
                                 b.description AS storage, b.id_plant, b.id_tank
                            FROM m_tank_detail a
                            LEFT JOIN m_tank b
                              ON a.id_tank = b.id_tank
                           WHERE b.id_tank = ?
                             AND a.status = 1
                           ORDER BY a.tf_number ASC', [$idTank]);
        return $db;
    }

    static function post_storageTank_destroy($id, $user){
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'M_STORAGE_TANK', 'DE-ACTIVATE', 'ID: ' . $id . ' | Status: 1 >> 0', $user ]);

        $db = DB::update('UPDATE m_tank
                             SET `status` = "0",
                                 `updated_by` = ?
                           WHERE id_tank = ?', [$user, $id]);
        $db = [ (object)['response' => $db ] ];

        return $db;
    }
    static function post_storageTank_activate($id, $user){
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'M_STORAGE_TANK', 'ACTIVATE', 'ID: ' . $id . ' | Status: 0 >> 1', $user ]);

        $db = DB::update('UPDATE m_tank
                             SET `status` = "1",
                                 `updated_by` = ?
                           WHERE id_tank = ?', [$user, $id]);
        $db = [ (object)['response' => $db ] ];

        return $db;
    }
    static function post_storageTank_store($user, $request){
        $id = $request->input('id');
        $mode = $request->input('mode');
        $code_3 = $request->input('code');
        $code_2 = $request->input('type');
        $idPlant = $request->input('id_plant');
        $description = $request->input('description');
        $code_4 = $request->input('codeSupplier');

        if ($mode == 'ADD'){
            /* CHECK FOR DOUBLE STORAGE TANK */
            $db = DB::select('SELECT IF(COUNT(id_tank)>=1,2,1) as response
                                FROM m_tank
                               WHERE id_plant = ?
                                 AND code_2 = ?
                                 AND code_3 = ?
                                 AND `description` = ?
                                 AND `status` = "1"', [$idPlant, $code_2, $code_3, $description]);

            if ($db[0]->response == '1'){
                /* INSERT TO DB */
                $db = DB::insert('INSERT INTO m_tank
                                         (code_2, code_3, code_4, id_plant, `description`, created_by)
                                  VALUES (?, ?, ?, ?, ?, ?)', [$code_2, $code_3, $code_4, $idPlant, $description, $user]);
                $db = [ (object)['response' => $db ? 1 : 0 ]];

                /* LOGGING */
                $id = DB::select('SELECT id_tank FROM m_tank ORDER BY id_tank DESC LIMIT 1');
                DB::insert('INSERT INTO log_transactions
                                   (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'M_STORAGE_TANK', 'ADD', 'ID: ' . $id[0]->id_tank . ' | CODE: ' . $code_3 .
                                                    ' / NAME: ' . $description . ' / TYPE: ' . $code_2 . ' / ID_PLANT: ' . $idPlant .
                                                    ' | Status: 1', $user ]);
            }

        } elseif ($mode == 'UPDATE'){
            $old_db = DB::select('SELECT code_2, code_3, `description`, `id_plant`
                                    FROM m_tank
                                   WHERE id_tank = ?', [$id]);
            DB::insert('INSERT INTO log_transactions
                               (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)', [ 'M_STORAGE_TANK', 'UPDATE', 'ID: ' . $id . ' | CODE: ' . $old_db[0]->code_3 . ' >> ' .$code_3 .
                                                ' / NAME: ' . $old_db[0]->description . ' >> ' . $description .
                                                ' / TYPE: ' . $old_db[0]->code_2 . ' >> ' . $code_2 .
                                                ' / ID_PLANT: ' . $old_db[0]->id_plant . ' >> ' . $idPlant .
                                                ' | Status: 1', $user ]);

            $db = DB::update('UPDATE m_tank
                                 SET code_2 = ?,
                                     code_3 = ?,
                                     code_4 = ?,
                                     id_plant = ?,
                                     `description` = ?,
                                     updated_by = ?
                               WHERE id_tank = ?', [$code_2, $code_3, $code_4, $idPlant, $description, $user, $id]);
            $db = [ (object)['response' => $db ]];

        }

        return $db;
    }

    static function post_storageDetail_destroy($id, $user){
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'M_STORAGE_DETAIL', 'DE-ACTIVATE', 'ID: ' . $id . ' | Status: 1 >> 0', $user ]);

        $db = DB::update('UPDATE m_tank_detail
                             SET `status` = "0",
                                 `updated_by` = ?
                           WHERE id_tank_tail = ?', [$user, $id]);
        $db = [ (object)['response' => $db ] ];

        return $db;
    }
    static function post_storageDetail_activate($id, $user){
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'M_STORAGE_DETAIL', 'ACTIVATE', 'ID: ' . $id . ' | Status: 0 >> 1', $user ]);

        $db = DB::update('UPDATE m_tank_detail
                             SET `status` = "1",
                                 `updated_by` = ?
                           WHERE id_tank_tail = ?', [$user, $id]);
        $db = [ (object)['response' => $db ] ];

        return $db;
    }
    static function post_storageDetail_store($user, $request){
        $id = $request->input('id');
        $idTank = $request->input('id_tank');
        $mode = $request->input('mode');
        $tfNumber = $request->input('tf_number');

        if ($mode == 'ADD'){
            /* CHECK FOR DOUBLE STORAGE TANK */
            $db = DB::select('SELECT IF(COUNT(id_tank_tail)>=1,2,1) as response
                                FROM m_tank_detail
                               WHERE tf_number = ?
                                 AND `status` = 1
                            ', [$tfNumber]);

            if ($db[0]->response == '1'){
                /* INSERT TO DB */
                $db = DB::insert('INSERT INTO m_tank_detail
                                         (id_tank, tf_number, created_by)
                                  VALUES (?, ?, ?)', [$idTank, $tfNumber, $user]);
                $db = [ (object)['response' => $db ? 1 : 0 ]];

                /* LOGGING */
                $id = DB::select('SELECT id_tank_tail FROM m_tank_detail ORDER BY id_tank_tail DESC LIMIT 1');
                DB::insert('INSERT INTO log_transactions
                                   (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'M_STORAGE_TANK', 'ADD', 'ID: ' . $id[0]->id_tank_tail . ' | ID_TANK: ' . $idTank .
                                                    ' / TF_NUMBER: ' . $tfNumber .
                                                    ' | Status: 1', $user ]);
            }

        } elseif ($mode == 'UPDATE'){
            $db = DB::select('SELECT IF(COUNT(id_tank_tail)>=1,2,1) as response
                                FROM m_tank_detail
                               WHERE tf_number = ?
                                 AND `status` = 1
                            ', [$tfNumber]);

            if ($db[0]->response == '1'){
                $old_db = DB::select('SELECT tf_number
                                        FROM m_tank_detail
                                    WHERE id_tank_tail = ?', [$id]);
                DB::insert('INSERT INTO log_transactions
                                (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'M_STORAGE_TANK', 'UPDATE', 'ID: ' . $id . ' | TF_NUMBER: ' . $old_db[0]->tf_number . ' >> ' .$tfNumber .
                                                    ' | Status: 1', $user ]);

                $db = DB::update('UPDATE m_tank_detail
                                    SET tf_number = ?,
                                        updated_by = ?
                                WHERE id_tank_tail = ?', [$tfNumber, $user, $id]);
                $db = [ (object)['response' => $db ]];
            }

        }

        return $db;
    }

    static function post_warehouse_destroy($id, $user){
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'M_WAREHOUSE', 'DE-ACTIVATE', 'ID: ' . $id . ' | Status: 1 >> 0', $user ]);

        $db = DB::update('UPDATE m_warehouse
                             SET `status` = "0",
                                 `updated_by` = ?
                           WHERE id_warehouse = ?', [$user, $id]);
        $db = [ (object)['response' => $db ] ];

        return $db;
    }
    static function post_warehouse_activate($id, $user){
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'M_WAREHOUSE', 'ACTIVATE', 'ID: ' . $id . ' | Status: 0 >> 1', $user ]);

        $db = DB::update('UPDATE m_warehouse
                             SET `status` = "1",
                                 `updated_by` = ?
                           WHERE id_warehouse = ?', [$user, $id]);
        $db = [ (object)['response' => $db ] ];

        return $db;
    }
    static function post_warehouse_store($user, $request){
        $id = $request->input('id');
        $mode = $request->input('mode');
        $code = $request->input('code');
        $idBatch = $request->input('id_batch');
        $description = $request->input('description');

        if ($mode == 'ADD'){
            /* CHECK FOR DOUBLE STORAGE TANK */
            $db = DB::select('SELECT IF(COUNT(id_warehouse)>=1,2,1) as response
                                FROM m_warehouse
                               WHERE id_batch = ?
                                 AND code = ?
                                 AND `description` = ?
                                 AND `status` = "1"', [$idBatch, $code, $description]);

            if ($db[0]->response == '1'){
                /* INSERT TO DB */
                $db = DB::insert('INSERT INTO m_warehouse
                                         (id_warehouse, code, `description`, created_by)
                                  VALUES (?, ?, ?, ?)', [$idBatch, $code, $description, $user]);
                $db = [ (object)['response' => $db ? 1 : 0 ]];

                /* LOGGING */
                $id = DB::select('SELECT id_warehouse FROM m_warehouse ORDER BY id_tank DESC LIMIT 1');
                DB::insert('INSERT INTO log_transactions
                                   (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'M_WAREHOUSE', 'ADD', 'ID: ' . $id[0]->id_warehouse . ' | CODE: ' . $code .
                                                    ' / NAME: ' . $description . ' / ID_BATCH: ' . $idBatch .
                                                    ' | Status: 1', $user ]);
            }

        } elseif ($mode == 'UPDATE'){
            /* CHECK FOR DOUBLE STORAGE TANK */
            $db = DB::select('SELECT IF(COUNT(id_warehouse)>=1,2,1) as response
                                FROM m_warehouse
                               WHERE id_batch = ?
                                 AND code = ?
                                 AND `description` = ?
                                 AND `status` = "1"', [$idBatch, $code, $description]);

            if ($db[0]->response == '1'){
                $old_db = DB::select('SELECT id_batch, code, `description`
                                        FROM m_warehouse
                                    WHERE id_warehouse = ?', [$id]);
                DB::insert('INSERT INTO log_transactions
                                (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'M_WAREHOUSE', 'UPDATE', 'ID: ' . $id . ' | CODE: ' . $old_db[0]->code . ' >> ' .$code .
                                                    ' / NAME: ' . $old_db[0]->description . ' >> ' . $description .
                                                    ' / ID_BATCH: ' . $old_db[0]->id_batch . ' >> ' . $idBatch .
                                                    ' | Status: 1', $user ]);

                $db = DB::update('UPDATE m_warehouse
                                    SET id_batch = ?,
                                        code = ?,
                                        `description` = ?,
                                        updated_by = ?
                                WHERE id_warehouse = ?', [$idBatch, $code, $description, $user, $id]);
                $db = [ (object)['response' => $db ]];
            }
        }

        return $db;
    }

}
