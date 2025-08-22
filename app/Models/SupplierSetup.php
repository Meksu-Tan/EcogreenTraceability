<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SupplierSetup extends Model
{
    protected $connection = 'eudr_ts';

    static function get_dtSupplier(){
        $db = DB::select('SELECT a.id_supplier, a.code, a.description, a.status,
                                 a.created_at, a.created_by, a.updated_at,
                                 a.updated_by, a.type, a.batch_code,
                                 CASE
                                    WHEN b.id_plant IS NULL THEN "other"
                                    WHEN b.id_plant IS NOT NULL THEN CONCAT(b.id_plant, " - ", b.description, " (", b.code_4, ")")
                                 END AS sloc
                            FROM m_supplier a
                            LEFT JOIN m_tank b
                              ON a.type = b.id_tank
                           ORDER BY a.description ASC');
        return $db;
    }
    static function get_activeSupplier(){
        $db = DB::select('SELECT a.id_supplier, CONCAT(a.code, " / ", a.description) AS supplier
                            FROM m_supplier a
                           WHERE a.status = "1"
                           ORDER BY a.description ASC');
        return $db;
    }
    static function post_destroySupplier($id, $user){
        $old_db = DB::select('SELECT a.id_supplier, a.code, a.description, a.type, a.status
                                FROM m_supplier a
                               WHERE a.id_supplier = ?', [$id]);
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'M_SUPPLIER', 'DE-ACTIVATE', 'ID: ' . $id . ' | Status: 1 >> 0', $user ]);

        $db = DB::update('UPDATE m_supplier
                             SET `status` = "0",
                                 `updated_by` = ?
                           WHERE id_supplier = ?', [$user, $id]);
        $db = [ (object)['response' => $db ] ];

        return $db;
    }
    static function post_activateSupplier($id, $user){
        $old_db = DB::select('SELECT a.id_supplier, a.code, a.description, a.type, a.status
                                FROM m_supplier a
                               WHERE a.id_supplier = ?', [$id]);
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'M_SUPPLIER', 'ACTIVATE', 'ID: ' . $id . ' | Status: 0 >> 1', $user ]);

        $db = DB::update('UPDATE m_supplier
                             SET `status` = "1",
                                 `updated_by` = ?
                           WHERE id_supplier = ?', [$user, $id]);
        $db = [ (object)['response' => $db ] ];

        return $db;
    }
    static function post_storeSupplier($user, $request){
        $id = $request->input('id');
        $mode = $request->input('mode');
        $code = $request->input('code');
        $description = $request->input('description');
        $type = $request->input('type');
        $batchCode = $request->input('batch_code');

        if ($mode == 'ADD'){
            /* CHECK FOR DOUBLE MATERIAL CODE */
            $db = DB::select('SELECT IF(COUNT(id_supplier)>=1,2,1) as response
                                FROM m_supplier
                               WHERE code = ?
                                 AND `status` = "1"', [$code]);

            if ($db[0]->response == '1'){
                /* INSERT TO DB */
                $db = DB::insert('INSERT INTO m_supplier
                                         (code, `description`, `type`, `batch_code`, created_by)
                                  VALUES (?, ?, ?, ?, ?)', [$code, $description, $type, $batchCode, $user]);
                $db = [ (object)['response' => $db ? 1 : 0 ]];

                /* LOGGING */
                $id = DB::select('SELECT id_supplier FROM m_supplier ORDER BY id_supplier DESC LIMIT 1');
                DB::insert('INSERT INTO log_transactions
                                   (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'M_SUPPLIER', 'ADD', 'ID: ' . $id[0]->id_supplier . ' | CODE: ' . $code .
                                                    ' / NAME: ' . $description . ' / TYPE: ' . $type . ' / BATCH-CODE: ' . $batchCode .
                                                    ' | Status: 1', $user ]);
            }

        } elseif ($mode == 'UPDATE'){
            $old_db = DB::select('SELECT code, `description`, `type`
                                    FROM m_supplier
                                   WHERE id_supplier = ?', [$id]);
            DB::insert('INSERT INTO log_transactions
                               (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)', [ 'M_SUPPLIER', 'UPDATE', 'ID: ' . $id . ' | CODE: ' . $old_db[0]->code . ' >> ' .$code .
                                                ' / NAME: ' . $old_db[0]->description . ' >> ' . $description .
                                                ' / TYPE: ' . $old_db[0]->type . ' >> ' . $type .
                                                ' | Status: 1', $user ]);

            $db = DB::update('UPDATE m_supplier
                                 SET code = ?,
                                     `description` = ?,
                                     `type` = ?,
                                     `batch_code` = ?,
                                     updated_by = ?
                               WHERE id_supplier = ?', [$code, $description, $type, $batchCode, $user, $id]);
            $db = [ (object)['response' => $db ]];

        }

        return $db;
    }

}
