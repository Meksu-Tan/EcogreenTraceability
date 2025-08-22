<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MaterialSetup extends Model
{
    protected $connection = 'eudr_ts';

    static function get_dtMaterial(){
        $db = DB::select('SELECT a.id_material, a.code, a.description, a.status,
                                 a.created_at, a.created_by, a.updated_at,
                                 a.updated_by, a.type, FORMAT(a.yield,1) AS yield,
                                 a.qtf_feed, a.qtf_rundown, a.id_feed, a.id_rundown,
                                 a.code_noneudr, a.status_packaging, a.code_matl_supplier
                            FROM m_material a
                           ORDER BY a.description ASC');
        return $db;
    }
    static function get_activeMaterial(){
        $db = DB::select('SELECT a.id_material, CONCAT(a.code, " / ", a.description) AS material
                            FROM m_material a
                           WHERE a.status = "1"
                           ORDER BY a.code ASC');
        return $db;
    }
    static function get_cmbActiveSourceProduct(){
        $db = DB::select('SELECT a.id_material, CONCAT(a.code, " / ", a.description) AS material
                            FROM m_material a
                           WHERE a.status = "1"
                             AND a.status_packaging = "1"
                           ORDER BY a.code ASC');
        return $db;
    }
    static function post_destroyMaterial($id, $user){
        $old_db = DB::select('SELECT a.id_material, a.code, a.description, a.type, a.yield, a.status
                                FROM m_material a
                               WHERE a.id_material = ?', [$id]);
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'M_MATERIAL', 'DE-ACTIVATE', 'Id: ' . $id . ' | Status: 1 >> 0', $user ]);

        $db = DB::update('UPDATE m_material
                             SET `status` = "0",
                                 `updated_by` = ?
                           WHERE id_material = ?', [$user, $id]);
        $db = [ (object)['response' => $db ] ];

        return $db;
    }
    static function post_activateMaterial($id, $user){
        $old_db = DB::select('SELECT a.id_material, a.code, a.description, a.type, a.yield, a.status
                                FROM m_material a
                               WHERE a.id_material = ?', [$id]);
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'M_MATERIAL', 'ACTIVATE', 'Id: ' . $id . ' | Status: 0 >> 1', $user ]);

        $db = DB::update('UPDATE m_material
                             SET `status` = "1",
                                 `updated_by` = ?
                           WHERE id_material = ?', [$user, $id]);
        $db = [ (object)['response' => $db ] ];

        return $db;
    }
    static function post_storeMaterial($user, $request){
        $id = $request->input('id');
        $mode = $request->input('mode');
        $code = $request->input('code');
        $code_noneudr = $request->input('code_noneudr');
        $description = $request->input('description');
        $type = $request->input('type');
        $yield = $request->input('yield');
        $qtf_feed = $request->input('qtf_feed');
        $qtf_rundown = $request->input('qtf_rundown');
        $status_packaging = $request->input('statusPackaging');
        $code_supplier = $request->input('code_matl_supplier');

        if ($mode == 'ADD'){
            /* CHECK FOR DOUBLE MATERIAL CODE */
            $db = DB::select('SELECT IF(COUNT(id_material)>=1,2,1) as response
                                FROM m_material
                               WHERE code = ?
                                 AND `status` = "1"', [$code]);

            if ($db[0]->response == '1'){
                /* INSERT TO DB */
                $db = DB::insert('INSERT INTO m_material
                                         (code, code_noneudr, `description`, `type`, qtf_feed, qtf_rundown, yield, status_packaging, code_matl_supplier, created_by)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$code, $code_noneudr, $description, $type, $qtf_feed, $qtf_rundown, $yield, $status_packaging, $code_supplier, $user]);
                $db = [ (object)['response' => $db ? 1 : 0 ]];

                /* LOGGING */
                $id = DB::select('SELECT id_material FROM m_material ORDER BY id_material DESC LIMIT 1');
                DB::insert('INSERT INTO log_transactions
                                   (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'M_MATERIAL', 'ADD', 'ID: ' . $id[0]->id_material . ' | CODE: ' . $code . ' / NAME: ' . $description .
                                                    ' / TYPE: ' . $type . ' / YIELD: ' . $yield . ' / FEED: ' . $qtf_feed . ' / RUNDOWN: ' . $qtf_rundown .
                                                    ' | Status: 1', $user ]);
            }

        } elseif ($mode == 'UPDATE'){
            $old_db = DB::select('SELECT code, `description`, `type`, yield, qtf_feed, qtf_rundown, code_noneudr
                                    FROM m_material
                                   WHERE id_material = ?', [$id]);
            DB::insert('INSERT INTO log_transactions
                               (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)', [ 'M_MATERIAL', 'UPDATE', 'ID: ' . $id . ' | CODE: ' . $old_db[0]->code . ' >> ' .$code .
                                                ' / NAME: ' . $old_db[0]->description . ' >> ' . $description .
                                                ' / TYPE: ' . $old_db[0]->type . ' >> ' . $type .
                                                ' / YIELD: ' . $old_db[0]->yield . ' >> ' . $yield .
                                                ' / FEED: ' . $old_db[0]->qtf_feed . ' >> ' . $qtf_feed .
                                                ' / RUNDOWN: ' . $old_db[0]->qtf_rundown . ' >> ' . $qtf_rundown .
                                                ' | Status: 1', $user ]);

            $db = DB::update('UPDATE m_material
                                 SET code = ?,
                                     code_noneudr = ?,
                                     `description` = ?,
                                     `type` = ?,
                                     qtf_feed = ?,
                                     qtf_rundown = ?,
                                     yield = ?,
                                     status_packaging = ?,
                                     code_matl_supplier = ?,
                                     updated_by = ?
                               WHERE id_material = ?', [$code, $code_noneudr, $description, $type, $qtf_feed, $qtf_rundown, $yield, $status_packaging, $code_supplier, $user, $id]);
            $db = [ (object)['response' => $db ]];

        }

        return $db;
    }
    static function get_dtMatlPck(){
        $db = DB::select('SELECT a.id_materialpck, a.code, a.description, a.status,
                                 a.created_at, a.created_by, a.updated_at, a.id_material,
                                 a.updated_by, CONCAT(b.code, " :: ", b.description) AS source_product,
                                 a.code_noneudr
                            FROM m_material_pck a
                            LEFT JOIN m_material b
                              ON a.id_material = b.id_material
                           ORDER BY a.description ASC');

        return $db;
    }
    static function post_destroyMaterialPck($id, $user){
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'M_MATERIAL_PCK', 'DE-ACTIVATE', 'Id: ' . $id . ' | Status: 1 >> 0', $user ]);

        $db = DB::update('UPDATE m_material_pck
                             SET `status` = "0",
                                 `updated_by` = ?
                           WHERE id_materialpck = ?', [$user, $id]);
        $db = [ (object)['response' => $db ] ];

        return $db;
    }
    static function post_activateMaterialPck($id, $user){
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'M_MATERIAL_PCK', 'ACTIVATE', 'Id: ' . $id . ' | Status: 0 >> 1', $user ]);

        $db = DB::update('UPDATE m_material_pck
                             SET `status` = "1",
                                 `updated_by` = ?
                           WHERE id_materialpck = ?', [$user, $id]);
        $db = [ (object)['response' => $db ] ];

        return $db;
    }
    static function post_storeMatlPck($user, $request){
        $id = $request->input('id');
        $mode = $request->input('mode');
        $code = $request->input('code');
        $code_noneudr = $request->input('code_noneudr');
        $description = $request->input('description');
        $id_material = $request->input('source');

        if ($mode == 'ADD'){
            /* CHECK FOR DOUBLE MATERIAL CODE */
            $db = DB::select('SELECT IF(COUNT(id_materialpck)>=1,2,1) as response
                                FROM m_material_pck
                               WHERE code = ?
                                 AND `status` = "1"', [$code]);

            if ($db[0]->response == '1'){
                /* INSERT TO DB */
                $db = DB::insert('INSERT INTO m_material_pck
                                         (code, code_noneudr, `description`, id_material, created_by)
                                  VALUES (?, ?, ?, ?, ?)', [$code, $code_noneudr, $description, $id_material, $user]);
                $db = [ (object)['response' => $db ? 1 : 0 ]];

                /* LOGGING */
                $id = DB::select('SELECT id_materialpck FROM m_material_pck ORDER BY id_materialpck DESC LIMIT 1');
                DB::insert('INSERT INTO log_transactions
                                   (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'M_MATERIAL_PCK', 'ADD', 'ID: ' . $id[0]->id_materialpck . ' | CODE: ' . $code . ' / NAME: ' . $description .
                                                    ' / ID_MATERIAL: ' . $id_material .
                                                    ' | Status: 1', $user ]);
            }

        } elseif ($mode == 'UPDATE'){

            $old_db = DB::select('SELECT code, `description`, id_material
                                    FROM m_material_pck
                                   WHERE id_materialpck = ?', [$id]);
            DB::insert('INSERT INTO log_transactions
                               (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)', [ 'M_MATERIAL_PCK', 'UPDATE', 'ID: ' . $id . ' | CODE: ' . $old_db[0]->code . ' >> ' .$code .
                                                ' / NAME: ' . $old_db[0]->description . ' >> ' . $description .
                                                ' / ID_MATERIAL: ' . $old_db[0]->id_material . ' >> ' . $id_material .
                                                ' | Status: 1', $user ]);

            $db = DB::update('UPDATE m_material_pck
                                 SET code = ?,
                                     code_noneudr = ?,
                                     `description` = ?,
                                     `id_material` = ?,
                                     updated_by = ?
                               WHERE id_materialpck = ?', [$code, $code_noneudr, $description, $id_material, $user, $id]);
            $db = [ (object)['response' => $db ]];

        }

        return $db;
    }
}
