<?php

namespace Modules\Storage\Repositories;

use Modules\Storage\Repositories\Contracts\StorageRepositoryInterface;
use Illuminate\Support\Facades\DB;

class StorageRepository implements StorageRepositoryInterface
{
    // -----------------------------------------------------------------------
    // Storage Tank
    // -----------------------------------------------------------------------
    public function getAllTanks(): array
    {
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        return DB::select('
            SELECT a.id_sloc, CONCAT(a.code_2, " | ", a.code_3) AS code,
                   a.description, a.status, a.created_at, a.created_by,
                   a.updated_at, a.updated_by, a.code_2, a.code_3, a.code_4,
                   a.id_plant, IFNULL(b.total_tank, 0) AS total_tank
            FROM m_sloc a
            LEFT JOIN (
                SELECT COUNT(b.id_sloc_tail) AS total_tank, b.id_sloc
                FROM m_sloc_detail b WHERE b.status = 1 GROUP BY b.id_sloc
            ) b ON a.id_sloc = b.id_sloc
            ORDER BY a.description ASC
        ');
    }

    public function findTankById(int $id): ?object
    {
        $r = DB::select('SELECT * FROM m_sloc WHERE id_sloc = ?', [$id]);
        return $r[0] ?? null;
    }

    public function createTank(array $data): bool
    {
        $exists = DB::select('
            SELECT COUNT(id_sloc) as cnt FROM m_sloc
            WHERE id_plant = ? AND code_2 = ? AND code_3 = ? AND description = ? AND status = "1"
        ', [$data['id_plant'], $data['code_2'], $data['code_3'], $data['description']]);
        if ($exists[0]->cnt >= 1) return false;
        $result = DB::insert('
            INSERT INTO m_sloc (code_2, code_3, code_4, id_plant, description, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ', [$data['code_2'], $data['code_3'], $data['code_4'] ?? null, $data['id_plant'], $data['description'], $data['created_by']]);
        if ($result) {
            $last = DB::select('SELECT id_sloc FROM m_sloc ORDER BY id_sloc DESC LIMIT 1');
            DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
                'M_STORAGE_TANK', 'ADD', 'ID: ' . $last[0]->id_sloc . ' | CODE: ' . $data['code_3'] . ' / NAME: ' . $data['description'], $data['created_by'],
            ]);
        }
        return (bool) $result;
    }

    public function updateTank(int $id, array $data): bool
    {
        $old = DB::select('SELECT code_2, code_3, description, id_plant FROM m_sloc WHERE id_sloc = ?', [$id]);
        if (empty($old)) return false;
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_STORAGE_TANK', 'UPDATE',
            'ID: ' . $id . ' | CODE: ' . $old[0]->code_3 . ' >> ' . $data['code_3'] . ' / NAME: ' . $old[0]->description . ' >> ' . $data['description'],
            $data['updated_by'],
        ]);
        $result = DB::update('
            UPDATE m_sloc SET code_2 = ?, code_3 = ?, code_4 = ?, id_plant = ?, description = ?, updated_by = ?
             WHERE id_sloc = ?
        ', [$data['code_2'], $data['code_3'], $data['code_4'] ?? null, $data['id_plant'], $data['description'], $data['updated_by'], $id]);
        return (bool) $result;
    }

    public function deactivateTank(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_STORAGE_TANK', 'DE-ACTIVATE', 'ID: ' . $id . ' | Status: 1 >> 0', $user,
        ]);
        return (bool) DB::update('UPDATE m_sloc SET status = "0", updated_by = ? WHERE id_sloc = ?', [$user, $id]);
    }

    public function activateTank(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_STORAGE_TANK', 'ACTIVATE', 'ID: ' . $id . ' | Status: 0 >> 1', $user,
        ]);
        return (bool) DB::update('UPDATE m_sloc SET status = "1", updated_by = ? WHERE id_sloc = ?', [$user, $id]);
    }

    // -----------------------------------------------------------------------
    // Storage Detail
    // -----------------------------------------------------------------------
    public function getDetailsByTank(int $tankId): array
    {
        return DB::select('
            SELECT a.id_sloc_tail, a.tf_number, a.status, a.created_at, a.updated_at,
                   b.description AS storage, b.id_plant, b.id_sloc
            FROM m_sloc_detail a
            LEFT JOIN m_sloc b ON a.id_sloc = b.id_sloc
            WHERE b.id_sloc = ? AND a.status = 1
            ORDER BY a.tf_number ASC
        ', [$tankId]);
    }

    public function findDetailById(int $id): ?object
    {
        $r = DB::select('SELECT * FROM m_sloc_detail WHERE id_sloc_tail = ?', [$id]);
        return $r[0] ?? null;
    }

    public function createDetail(array $data): bool
    {
        $exists = DB::select('SELECT COUNT(id_sloc_tail) as cnt FROM m_sloc_detail WHERE tf_number = ? AND status = 1', [$data['tf_number']]);
        if ($exists[0]->cnt >= 1) return false;
        $result = DB::insert('INSERT INTO m_sloc_detail (id_sloc, tf_number, created_by) VALUES (?, ?, ?)', [
            $data['id_sloc'], $data['tf_number'], $data['created_by'],
        ]);
        if ($result) {
            $last = DB::select('SELECT id_sloc_tail FROM m_sloc_detail ORDER BY id_sloc_tail DESC LIMIT 1');
            DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
                'M_STORAGE_TANK', 'ADD', 'ID: ' . $last[0]->id_sloc_tail . ' | ID_TANK: ' . $data['id_sloc'] . ' / TF_NUMBER: ' . $data['tf_number'], $data['created_by'],
            ]);
        }
        return (bool) $result;
    }

    public function updateDetail(int $id, array $data): bool
    {
        $exists = DB::select('SELECT COUNT(id_sloc_tail) as cnt FROM m_sloc_detail WHERE tf_number = ? AND status = 1 AND id_sloc_tail != ?', [$data['tf_number'], $id]);
        if ($exists[0]->cnt >= 1) return false;
        $old = DB::select('SELECT tf_number FROM m_sloc_detail WHERE id_sloc_tail = ?', [$id]);
        if (empty($old)) return false;
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_STORAGE_TANK', 'UPDATE',
            'ID: ' . $id . ' | TF_NUMBER: ' . $old[0]->tf_number . ' >> ' . $data['tf_number'],
            $data['updated_by'],
        ]);
        $result = DB::update('UPDATE m_sloc_detail SET tf_number = ?, updated_by = ? WHERE id_sloc_tail = ?', [
            $data['tf_number'], $data['updated_by'], $id,
        ]);
        return (bool) $result;
    }

    public function deactivateDetail(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_STORAGE_DETAIL', 'DE-ACTIVATE', 'ID: ' . $id . ' | Status: 1 >> 0', $user,
        ]);
        return (bool) DB::update('UPDATE m_sloc_detail SET status = "0", updated_by = ? WHERE id_sloc_tail = ?', [$user, $id]);
    }

    public function activateDetail(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_STORAGE_DETAIL', 'ACTIVATE', 'ID: ' . $id . ' | Status: 0 >> 1', $user,
        ]);
        return (bool) DB::update('UPDATE m_sloc_detail SET status = "1", updated_by = ? WHERE id_sloc_tail = ?', [$user, $id]);
    }

    // -----------------------------------------------------------------------
    // Warehouse
    // -----------------------------------------------------------------------
    public function getAllWarehouses(): array
    {
        return DB::select('
            SELECT a.id_warehouse, a.id_batch, a.code, a.description, a.status,
                   a.created_by, a.created_at, a.updated_by, a.updated_at
            FROM m_warehouse a
            ORDER BY a.id_batch ASC
        ');
    }

    public function findWarehouseById(int $id): ?object
    {
        $r = DB::select('SELECT * FROM m_warehouse WHERE id_warehouse = ?', [$id]);
        return $r[0] ?? null;
    }

    public function createWarehouse(array $data): bool
    {
        $exists = DB::select('
            SELECT COUNT(id_warehouse) as cnt FROM m_warehouse
            WHERE id_batch = ? AND code = ? AND description = ? AND status = "1"
        ', [$data['id_batch'], $data['code'], $data['description']]);
        if ($exists[0]->cnt >= 1) return false;
        $result = DB::insert('INSERT INTO m_warehouse (id_batch, code, description, created_by) VALUES (?, ?, ?, ?)', [
            $data['id_batch'], $data['code'], $data['description'], $data['created_by'],
        ]);
        if ($result) {
            $last = DB::select('SELECT id_warehouse FROM m_warehouse ORDER BY id_warehouse DESC LIMIT 1');
            DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
                'M_WAREHOUSE', 'ADD', 'ID: ' . $last[0]->id_warehouse . ' | CODE: ' . $data['code'] . ' / NAME: ' . $data['description'], $data['created_by'],
            ]);
        }
        return (bool) $result;
    }

    public function updateWarehouse(int $id, array $data): bool
    {
        $old = DB::select('SELECT id_batch, code, description FROM m_warehouse WHERE id_warehouse = ?', [$id]);
        if (empty($old)) return false;
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_WAREHOUSE', 'UPDATE',
            'ID: ' . $id . ' | CODE: ' . $old[0]->code . ' >> ' . $data['code'] . ' / NAME: ' . $old[0]->description . ' >> ' . $data['description'],
            $data['updated_by'],
        ]);
        $result = DB::update('UPDATE m_warehouse SET id_batch = ?, code = ?, description = ?, updated_by = ? WHERE id_warehouse = ?', [
            $data['id_batch'], $data['code'], $data['description'], $data['updated_by'], $id,
        ]);
        return (bool) $result;
    }

    public function deactivateWarehouse(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_WAREHOUSE', 'DE-ACTIVATE', 'ID: ' . $id . ' | Status: 1 >> 0', $user,
        ]);
        return (bool) DB::update('UPDATE m_warehouse SET status = "0", updated_by = ? WHERE id_warehouse = ?', [$user, $id]);
    }

    public function activateWarehouse(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_WAREHOUSE', 'ACTIVATE', 'ID: ' . $id . ' | Status: 0 >> 1', $user,
        ]);
        return (bool) DB::update('UPDATE m_warehouse SET status = "1", updated_by = ? WHERE id_warehouse = ?', [$user, $id]);
    }
}
