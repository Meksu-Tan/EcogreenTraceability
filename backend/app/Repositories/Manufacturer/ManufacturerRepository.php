<?php

namespace App\Repositories\Manufacturer;

use App\Contracts\Manufacturer\ManufacturerRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ManufacturerRepository implements ManufacturerRepositoryInterface
{
    public function getAll(): array
    {
        return DB::select('
            SELECT a.id_manufacturer, a.code, a.description, a.status,
                   a.created_at, a.created_by, a.updated_at, a.updated_by,
                   a.type, a.batch_code,
                   CASE
                       WHEN td.tf_number IS NOT NULL AND b.id_plant IS NOT NULL 
                           THEN CONCAT(b.id_plant, " - ", b.description, " (", td.tf_number, ")")
                       WHEN b.id_plant IS NOT NULL 
                           THEN CONCAT(b.id_plant, " - ", b.description, " (", b.code_4, ")")
                       ELSE "other"
                   END AS sloc
            FROM m_manufacturer a
            LEFT JOIN m_tank_detail td ON a.type = CAST(td.id_tank_tail AS CHAR(50)) OR a.type = td.tf_number
            LEFT JOIN m_tank b ON (td.id_tank IS NOT NULL AND td.id_tank = b.id_tank) 
                              OR (td.id_tank IS NULL AND a.type REGEXP "^[0-9]+$" AND CAST(a.type AS UNSIGNED) = b.id_tank)
            ORDER BY a.description ASC
        ');
    }

    public function findById(int $id): ?object
    {
        $r = DB::select('SELECT * FROM m_manufacturer WHERE id_manufacturer = ?', [$id]);
        return $r[0] ?? null;
    }

    public function create(array $data): bool
    {
        $exists = DB::select('SELECT COUNT(id_manufacturer) as cnt FROM m_manufacturer WHERE code = ? AND status = "1"', [$data['code']]);
        if ($exists[0]->cnt >= 1) return false;
        $result = DB::insert('
            INSERT INTO m_manufacturer (code, description, type, batch_code, created_by)
            VALUES (?, ?, ?, ?, ?)
        ', [$data['code'], $data['description'], $data['type'] ?? null, $data['batch_code'] ?? null, $data['created_by']]);
        if ($result) {
            $last = DB::select('SELECT id_manufacturer FROM m_manufacturer ORDER BY id_manufacturer DESC LIMIT 1');
            DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
                'M_MANUFACTURER', 'ADD',
                'ID: ' . $last[0]->id_manufacturer . ' | CODE: ' . $data['code'] . ' / NAME: ' . $data['description'],
                $data['created_by'],
            ]);
        }
        return (bool) $result;
    }

    public function update(int $id, array $data): bool
    {
        $old = DB::select('SELECT code, description, type FROM m_manufacturer WHERE id_manufacturer = ?', [$id]);
        if (empty($old)) return false;
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MANUFACTURER', 'UPDATE',
            'ID: ' . $id . ' | CODE: ' . $old[0]->code . ' >> ' . $data['code'] . ' / NAME: ' . $old[0]->description . ' >> ' . $data['description'],
            $data['updated_by'],
        ]);
        $result = DB::update('
            UPDATE m_manufacturer SET code = ?, description = ?, type = ?, batch_code = ?, updated_by = ?
             WHERE id_manufacturer = ?
        ', [$data['code'], $data['description'], $data['type'] ?? null, $data['batch_code'] ?? null, $data['updated_by'], $id]);
        return (bool) $result;
    }

    public function deactivate(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MANUFACTURER', 'DE-ACTIVATE', 'ID: ' . $id . ' | Status: 1 >> 0', $user,
        ]);
        return (bool) DB::update('UPDATE m_manufacturer SET status = "0", updated_by = ? WHERE id_manufacturer = ?', [$user, $id]);
    }

    public function activate(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MANUFACTURER', 'ACTIVATE', 'ID: ' . $id . ' | Status: 0 >> 1', $user,
        ]);
        return (bool) DB::update('UPDATE m_manufacturer SET status = "1", updated_by = ? WHERE id_manufacturer = ?', [$user, $id]);
    }

    public function getActive(): array
    {
        return DB::select('
            SELECT a.id_manufacturer, CONCAT(a.code, " / ", a.description) AS manufacturer
            FROM m_manufacturer a WHERE a.status = "1" ORDER BY a.description ASC
        ');
    }
}
