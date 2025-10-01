<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserMgmt extends Model
{
    protected $connection = 'oee_756';

    static function getUserData(){
        $db = DB::select('SELECT a.id, a.`name`, a.email, c.`name` AS `role`,
                            CASE 
                              WHEN c.`name` IN ("admin", "super-admin") 
                              THEN (SELECT GROUP_CONCAT(description SEPARATOR ", ") FROM m_plant)
                              ELSE GROUP_CONCAT(p.description SEPARATOR ", ")
                            END AS plant
                            FROM users a
                            LEFT JOIN role_user b
                              ON a.id = b.user_id
                            LEFT JOIN roles c
                              ON b.role_id = c.id
                            LEFT JOIN m_plant_user pu
                              ON a.id = pu.user_id
                            LEFT JOIN m_plant p
                              ON pu.id_plant = p.id_plant
                           WHERE a.isActive = 1
                           GROUP BY a.id, a.name, a.email, c.name
                           ORDER BY a.`name` ASC');
        return $db;
    }

    static function updateUser($id, $name, $email, $role, $user){
        $data = [$name, $email, $user, $id];

        $old_db = DB::select('SELECT a.`name`, a.email, c.`name` AS `role`
                                FROM users a
                                LEFT JOIN role_user b
                                  ON a.id = b.user_id
                                LEFT JOIN roles c
                                  ON b.role_id = c.id
                               WHERE a.id = ?', [$id]);
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'TF-USER', 'UPDATE', $id . ' | ' . $old_db[0]->name . '>>>' . $name . ' | ' . $old_db[0]->email . '>>>' . $email . ' | ' . $old_db[0]->role . '>>>' . $role . ' | ' . $old_db[0]->role, $user ]);

        $db = DB::update('UPDATE users
                             SET `name` = ?,
                                 email = ?,
                                 updated_by = ?
                           WHERE id = ?', $data);
        $db = [ (object)['response' => $db ]];

        return $db;
    }

    static function updatePassword($email, $password, $user){
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'TF-USER', 'RESET', $email . ' | ' . $password, $user ]);

        $hashed_password = Hash::make($password);
        $db = DB::update('UPDATE users
                             SET `password` = ?
                           WHERE email = ?', [$hashed_password, $email]);
        $db = [ (object)['response' => $db ]];

        return $db;
    }

    static function destroyUser($id, $user){
        $old_db = DB::select('SELECT a.`name`, a.email, c.`name` AS `role`
                                FROM users a
                                LEFT JOIN role_user b
                                  ON a.id = b.user_id
                                LEFT JOIN roles c
                                  ON b.role_id = c.id
                               WHERE a.id = ?', [$id]);
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'TF-USER', 'DESTROY', $id . ' | ' . $old_db[0]->name . ' | ' . $old_db[0]->email . ' | ' . $old_db[0]->role, $user ]);

        $password = 'Default_resetByAdmin123++';
        $hashed_password = Hash::make($password);
        $db = DB::update('UPDATE users
                            SET `password` = ?,
                                `isActive` = "0"
                        WHERE id = ?', [$hashed_password, $id]);
        $db = [ (object)['response' => $db ]];
        return $db;
    }
}
