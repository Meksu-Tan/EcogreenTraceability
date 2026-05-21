<?php

namespace Modules\Manufacturer\Repositories;

use Modules\Manufacturer\Repositories\Contracts\ManufacturerRepositoryInterface;
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
                       WHEN b.id_sloc IS NULL THEN "other"
                       ELSE CONCAT(COALESCE(b.id_plant,""), " - ", COALESCE(b.description,""), " (", COALESCE(b.code_4,""), ")")
                   END AS sloc
            FROM m_manufacturer a
            LEFT JOIN m_sloc b ON a.type = b.id_sloc
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
            INSERT INTO m_manufacturer (code, description, type, batch_code, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
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
            UPDATE m_manufacturer SET code = ?, description = ?, type = ?, batch_code = ?, updated_by = ?, updated_at = NOW()
             WHERE id_manufacturer = ?
        ', [$data['code'], $data['description'], $data['type'] ?? null, $data['batch_code'] ?? null, $data['updated_by'], $id]);
        return (bool) $result;
    }

    public function deactivate(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MANUFACTURER', 'DE-ACTIVATE', 'ID: ' . $id . ' | Status: 1 >> 0', $user,
        ]);
        return (bool) DB::update('UPDATE m_manufacturer SET status = "0", updated_by = ?, updated_at = NOW() WHERE id_manufacturer = ?', [$user, $id]);
    }

    public function activate(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MANUFACTURER', 'ACTIVATE', 'ID: ' . $id . ' | Status: 0 >> 1', $user,
        ]);
        return (bool) DB::update('UPDATE m_manufacturer SET status = "1", updated_by = ?, updated_at = NOW() WHERE id_manufacturer = ?', [$user, $id]);
    }

    public function getActive(): array
    {
        return DB::select('
            SELECT a.id_manufacturer, CONCAT(a.code, " / ", a.description) AS manufacturer
            FROM m_manufacturer a WHERE a.status = "1" ORDER BY a.description ASC
        ');
    }
}
