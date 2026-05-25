<?php

namespace Modules\Supplier\Repositories;

use Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SupplierRepository implements SupplierRepositoryInterface
{
    public function getAll(): array
    {
        return DB::select('
            SELECT a.id_supplier, a.code, a.description, a.status,
                   a.created_at, a.created_by, a.updated_at, a.updated_by,
                   a.type, a.batch_code,
                   a.type AS sloc
            FROM m_supplier a
            ORDER BY a.description ASC
        ');
    }

    public function findById(int $id): ?object
    {
        $r = DB::select('SELECT * FROM m_supplier WHERE id_supplier = ?', [$id]);
        return $r[0] ?? null;
    }

    public function create(array $data): bool
    {
        $exists = DB::select('SELECT COUNT(id_supplier) as cnt FROM m_supplier WHERE code = ? AND status = "1"', [$data['code']]);
        if ($exists[0]->cnt >= 1) return false;
        $result = DB::insert('
            INSERT INTO m_supplier (code, description, type, batch_code, created_by)
            VALUES (?, ?, ?, ?, ?)
        ', [$data['code'], $data['description'], $data['type'] ?? null, $data['batch_code'] ?? null, $data['created_by']]);
        if ($result) {
            $last = DB::select('SELECT id_supplier FROM m_supplier ORDER BY id_supplier DESC LIMIT 1');
            DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
                'M_SUPPLIER', 'ADD',
                'ID: ' . $last[0]->id_supplier . ' | CODE: ' . $data['code'] . ' / NAME: ' . $data['description'],
                $data['created_by'],
            ]);
        }
        return (bool) $result;
    }

    public function update(int $id, array $data): bool
    {
        $old = DB::select('SELECT code, description, type FROM m_supplier WHERE id_supplier = ?', [$id]);
        if (empty($old)) return false;
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_SUPPLIER', 'UPDATE',
            'ID: ' . $id . ' | CODE: ' . $old[0]->code . ' >> ' . $data['code'] . ' / NAME: ' . $old[0]->description . ' >> ' . $data['description'],
            $data['updated_by'],
        ]);
        $result = DB::update('
            UPDATE m_supplier SET code = ?, description = ?, type = ?, batch_code = ?, updated_by = ?
             WHERE id_supplier = ?
        ', [$data['code'], $data['description'], $data['type'] ?? null, $data['batch_code'] ?? null, $data['updated_by'], $id]);
        return (bool) $result;
    }

    public function deactivate(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_SUPPLIER', 'DE-ACTIVATE', 'ID: ' . $id . ' | Status: 1 >> 0', $user,
        ]);
        return (bool) DB::update('UPDATE m_supplier SET status = "0", updated_by = ? WHERE id_supplier = ?', [$user, $id]);
    }

    public function activate(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_SUPPLIER', 'ACTIVATE', 'ID: ' . $id . ' | Status: 0 >> 1', $user,
        ]);
        return (bool) DB::update('UPDATE m_supplier SET status = "1", updated_by = ? WHERE id_supplier = ?', [$user, $id]);
    }

    public function getActive(): array
    {
        return DB::select('
            SELECT a.id_supplier, CONCAT(a.code, " / ", a.description) AS supplier
            FROM m_supplier a WHERE a.status = "1" ORDER BY a.description ASC
        ');
    }
}
