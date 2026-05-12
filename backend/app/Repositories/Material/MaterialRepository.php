<?php

namespace App\Repositories\Material;

use App\Contracts\Material\MaterialRepositoryInterface;
use App\Models\Material;
use App\Models\MaterialPackaging;
use Illuminate\Support\Facades\DB;

class MaterialRepository implements MaterialRepositoryInterface
{
    public function getAll(): array
    {
        return DB::select('
            SELECT a.id_material, a.code, a.code_noneudr, a.description, a.status,
                   a.created_at, a.created_by, a.updated_at, a.updated_by,
                   a.type, FORMAT(a.yield,1) AS yield,
                   a.qtf_feed, a.qtf_rundown, a.id_feed, a.id_rundown,
                   a.status_packaging, a.code_matl_supplier
            FROM m_material a
            ORDER BY a.description ASC
        ');
    }

    public function findById(int $id): ?object
    {
        $result = DB::select('SELECT * FROM m_material WHERE id_material = ?', [$id]);
        return $result[0] ?? null;
    }

    public function create(array $data): bool
    {
        // Check duplicate code
        $exists = DB::select('SELECT COUNT(id_material) as cnt FROM m_material WHERE code = ? AND status = "1"', [$data['code']]);
        if ($exists[0]->cnt >= 1) {
            return false; // duplicate
        }
        $result = DB::insert('
            INSERT INTO m_material (code, code_noneudr, description, type, qtf_feed, qtf_rundown, yield, status_packaging, code_matl_supplier, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ', [
            $data['code'], $data['code_noneudr'] ?? null, $data['description'],
            $data['type'], $data['qtf_feed'] ?? null, $data['qtf_rundown'] ?? null,
            $data['yield'] ?? 100, $data['status_packaging'] ?? 0,
            $data['code_matl_supplier'] ?? null, $data['created_by'],
        ]);
        if ($result) {
            $last = DB::select('SELECT id_material FROM m_material ORDER BY id_material DESC LIMIT 1');
            DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
                'M_MATERIAL', 'ADD',
                'ID: ' . $last[0]->id_material . ' | CODE: ' . $data['code'] . ' / NAME: ' . $data['description'],
                $data['created_by'],
            ]);
        }
        return (bool) $result;
    }

    public function update(int $id, array $data): bool
    {
        $old = DB::select('SELECT code, description, type, yield, qtf_feed, qtf_rundown, code_noneudr FROM m_material WHERE id_material = ?', [$id]);
        if (empty($old)) return false;
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MATERIAL', 'UPDATE',
            'ID: ' . $id . ' | CODE: ' . $old[0]->code . ' >> ' . $data['code'],
            $data['updated_by'],
        ]);
        $result = DB::update('
            UPDATE m_material
               SET code = ?, code_noneudr = ?, description = ?, type = ?,
                   qtf_feed = ?, qtf_rundown = ?, yield = ?,
                   status_packaging = ?, code_matl_supplier = ?, updated_by = ?
             WHERE id_material = ?
        ', [
            $data['code'], $data['code_noneudr'] ?? null, $data['description'],
            $data['type'], $data['qtf_feed'] ?? null, $data['qtf_rundown'] ?? null,
            $data['yield'] ?? 100, $data['status_packaging'] ?? 0,
            $data['code_matl_supplier'] ?? null, $data['updated_by'], $id,
        ]);
        return (bool) $result;
    }

    public function deactivate(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MATERIAL', 'DE-ACTIVATE', 'Id: ' . $id . ' | Status: 1 >> 0', $user,
        ]);
        return (bool) DB::update('UPDATE m_material SET status = "0", updated_by = ? WHERE id_material = ?', [$user, $id]);
    }

    public function activate(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MATERIAL', 'ACTIVATE', 'Id: ' . $id . ' | Status: 0 >> 1', $user,
        ]);
        return (bool) DB::update('UPDATE m_material SET status = "1", updated_by = ? WHERE id_material = ?', [$user, $id]);
    }

    // -----------------------------------------------------------------------
    // Packaging
    // -----------------------------------------------------------------------

    public function getAllPackagings(): array
    {
        return DB::select('
            SELECT a.id_materialpck, a.code, a.code_noneudr, a.description, a.status,
                   a.created_at, a.created_by, a.updated_at, a.updated_by,
                   a.id_material, CONCAT(b.code, " :: ", b.description) AS source_product
            FROM m_material_pck a
            LEFT JOIN m_material b ON a.id_material = b.id_material
            ORDER BY a.description ASC
        ');
    }

    public function findPackagingById(int $id): ?object
    {
        $r = DB::select('SELECT * FROM m_material_pck WHERE id_materialpck = ?', [$id]);
        return $r[0] ?? null;
    }

    public function createPackaging(array $data): bool
    {
        $exists = DB::select('SELECT COUNT(id_materialpck) as cnt FROM m_material_pck WHERE code = ? AND status = "1"', [$data['code']]);
        if ($exists[0]->cnt >= 1) return false;
        $result = DB::insert('
            INSERT INTO m_material_pck (code, code_noneudr, description, id_material, created_by)
            VALUES (?, ?, ?, ?, ?)
        ', [$data['code'], $data['code_noneudr'] ?? null, $data['description'], $data['id_material'], $data['created_by']]);
        if ($result) {
            $last = DB::select('SELECT id_materialpck FROM m_material_pck ORDER BY id_materialpck DESC LIMIT 1');
            DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
                'M_MATERIAL_PCK', 'ADD', 'ID: ' . $last[0]->id_materialpck . ' | CODE: ' . $data['code'], $data['created_by'],
            ]);
        }
        return (bool) $result;
    }

    public function updatePackaging(int $id, array $data): bool
    {
        $old = DB::select('SELECT code, description, id_material FROM m_material_pck WHERE id_materialpck = ?', [$id]);
        if (empty($old)) return false;
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MATERIAL_PCK', 'UPDATE',
            'ID: ' . $id . ' | CODE: ' . $old[0]->code . ' >> ' . $data['code'],
            $data['updated_by'],
        ]);
        $result = DB::update('
            UPDATE m_material_pck SET code = ?, code_noneudr = ?, description = ?, id_material = ?, updated_by = ?
             WHERE id_materialpck = ?
        ', [$data['code'], $data['code_noneudr'] ?? null, $data['description'], $data['id_material'], $data['updated_by'], $id]);
        return (bool) $result;
    }

    public function deactivatePackaging(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MATERIAL_PCK', 'DE-ACTIVATE', 'Id: ' . $id . ' | Status: 1 >> 0', $user,
        ]);
        return (bool) DB::update('UPDATE m_material_pck SET status = "0", updated_by = ? WHERE id_materialpck = ?', [$user, $id]);
    }

    public function activatePackaging(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MATERIAL_PCK', 'ACTIVATE', 'Id: ' . $id . ' | Status: 0 >> 1', $user,
        ]);
        return (bool) DB::update('UPDATE m_material_pck SET status = "1", updated_by = ? WHERE id_materialpck = ?', [$user, $id]);
    }

    public function getActiveSourceProducts(): array
    {
        return DB::select('
            SELECT a.id_material, CONCAT(a.code, " / ", a.description) AS material
            FROM m_material a
            WHERE a.status = "1" AND a.status_packaging = "1"
            ORDER BY a.code ASC
        ');
    }
}
