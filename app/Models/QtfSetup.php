<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class QtfSetup extends Model
{
    protected $connection = 'eudr_ts';


    static function get_cmbActiveFlowmeter(){
        $db = DB:: select('SELECT qtf_feed AS qtf
                             FROM m_material
                            WHERE `status` = 1
                              AND qtf_feed LIKE "%FT%"
                            UNION ALL
                           SELECT qtf_rundown AS qtf
                             FROM m_material
                            WHERE `status` = 1
                              AND qtf_rundown LIKE "%FT%"
                            ORDER BY qtf ASC');
        return $db;
    }
    static function get_dtQuantifier(){
        $db = DB::select('SELECT a.id_reset, a.reset_date, a.flowmeter, a.remark, a.value,
                                 a.status, a.created_by, a.created_at
                            FROM t_reset_quantifier a
                           ORDER BY a.reset_date DESC
                         ');
        return $db;
    }
    static function post_destroyQtf($id, $user){
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'T_RESET_QTY', 'DE-ACTIVATE', 'Id: ' . $id . ' | Status: 1 >> 0', $user ]);

        $db = DB::update('UPDATE t_reset_quantifier
                             SET `status` = "0",
                                 `updated_by` = ?
                           WHERE id_reset = ?', [$user, $id]);
        $db = [ (object)['response' => $db ] ];

        return $db;
    }
    static function post_activateQtf($id, $user){
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'T_RESET_QTY', 'ACTIVATE', 'Id: ' . $id . ' | Status: 0 >> 1', $user ]);

        $db = DB::update('UPDATE t_reset_quantifier
                             SET `status` = "1",
                                 `updated_by` = ?
                           WHERE id_reset = ?', [$user, $id]);
        $db = [ (object)['response' => $db ] ];

        return $db;
    }
    static function post_storeQuantifier($user, $request){
        $id = $request->input('id');
        $mode = $request->input('mode');
        $flowmeter = $request->input('flowmeter');
        $reset_date = $request->input('reset_date');
        $value = $request->input('value');
        $remark = $request->input('remark');

        if ($mode == 'ADD'){
            /* CHECK RESET TYPE */
                if ($flowmeter == null){
                    $dat = DB::select('SELECT qtf_feed AS qtf
                                         FROM m_material
                                        WHERE `status` = 1
                                          AND qtf_feed LIKE "%FT%"
                                        UNION ALL
                                       SELECT qtf_rundown AS qtf
                                         FROM m_material
                                        WHERE `status` = 1
                                          AND qtf_rundown LIKE "%FT%"
                                        ORDER BY qtf ASC');
                    $len = count($dat);
                    for ($i = 0; $i < $len; $i++) {
                        $flowmeter = $dat[$i]->qtf;
                        /* INSERT TO DB */
                        $db = DB::insert('INSERT INTO t_reset_quantifier
                                                 (`reset_date`, `flowmeter`, `value`, `remark`, `created_by`)
                                          VALUES (?, ?, ?, ?, ?)', [$reset_date, $flowmeter, $value, $remark, $user]);
                        $db = [ (object)['response' => $db ? 1 : 0 ]];

                        /* LOGGING */
                        $id = DB::select('SELECT id_reset FROM t_reset_quantifier ORDER BY id_reset DESC LIMIT 1');
                        DB::insert('INSERT INTO log_transactions
                                           (log_module, log_type, log_description, created_by)
                                    VALUES (?, ?, ?, ?)', [ 'T_RESET_QTY', 'ADD', 'ID: ' . $id[0]->id_reset . ' | DATE: ' . $reset_date . ' / FLOWMETER: ' . $flowmeter .
                                                            ' / VALUE: ' . $value . ' / REMARK: ' . $remark .
                                                            ' | Status: 1', $user ]);
                    }

                } else {
                    /* INSERT TO DB */
                        $db = DB::insert('INSERT INTO t_reset_quantifier
                                                 (`reset_date`, `flowmeter`, `value`, `remark`, `created_by`)
                                          VALUES (?, ?, ?, ?, ?)', [$reset_date, $flowmeter, $value, $remark, $user]);
                        $db = [ (object)['response' => $db ? 1 : 0 ]];

                        /* LOGGING */
                        $id = DB::select('SELECT id_reset FROM t_reset_quantifier ORDER BY id_reset DESC LIMIT 1');
                        DB::insert('INSERT INTO log_transactions
                                           (log_module, log_type, log_description, created_by)
                                    VALUES (?, ?, ?, ?)', [ 'T_RESET_QTY', 'ADD', 'ID: ' . $id[0]->id_reset . ' | DATE: ' . $reset_date . ' / FLOWMETER: ' . $flowmeter .
                                                            ' / VALUE: ' . $value . ' / REMARK: ' . $remark .
                                                            ' | Status: 1', $user ]);
                }

        } elseif ($mode == 'UPDATE'){
            $old_db = DB::select('SELECT `reset_date`, `flowmeter`, `value`, `remark`
                                    FROM t_reset_quantifier
                                   WHERE id_reset = ?', [$id]);
            DB::insert('INSERT INTO log_transactions
                               (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)', [ 'T_RESET_QTY', 'UPDATE', 'ID: ' . $id . ' | DATE: ' . $old_db[0]->reset_date . ' >> ' . $reset_date .
                                                ' / FLOWMETER: ' . $old_db[0]->flowmeter . ' >> ' . $flowmeter .
                                                ' / VALUE: ' . $old_db[0]->value . ' >> ' . $value .
                                                ' / REMARK: ' . $old_db[0]->remark . ' >> ' . $remark .
                                                ' | Status: 1', $user ]);

            $db = DB::update('UPDATE t_reset_quantifier
                                 SET `reset_date` = ?,
                                     `flowmeter` = ?,
                                     `value` = ?,
                                     `remark` = ?,
                                     updated_by = ?
                               WHERE id_reset = ?', [$reset_date, $flowmeter, $value, $remark, $user, $id]);
            $db = [ (object)['response' => $db ]];

        }

        return $db;
    }
}
