<?php

namespace Modules\Plant\Repositories;

use Modules\Plant\Repositories\Contracts\PlantRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PlantRepository implements PlantRepositoryInterface
{
    public function getAll(): array
    {
        return DB::select('
            SELECT id_plant, code, code_2, code_3, description, status,
                   created_at, created_by, updated_at, updated_by
            FROM m_plant
            ORDER BY id_plant ASC
        ');
    }

    public function findById(int $id): ?object
    {
        $result = DB::select('SELECT * FROM m_plant WHERE id_plant = ?', [$id]);
        return $result[0] ?? null;
    }

    public function create(array $data): int|bool
    {
        $exists = DB::select('SELECT COUNT(id_plant) as cnt FROM m_plant WHERE (code_2 = ? OR code_3 = ?) AND status = "1"', [
            $data['code_2'], $data['code_3']
        ]);

        if ($exists[0]->cnt >= 1) {
            return false;
        }

        $result = DB::insert('
            INSERT INTO m_plant (code, code_2, code_3, id_tank, description, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ', [
            $data['code'] ?? null,
            $data['code_2'],
            $data['code_3'],
            $data['id_sloc'] ?? 'T000',
            $data['description'],
            1,
            $data['created_by']
        ]);

        if ($result) {
            $last = DB::select('SELECT id_plant FROM m_plant ORDER BY id_plant DESC LIMIT 1');
            $id = $last[0]->id_plant;
            DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
                'M_PLANT', 'ADD',
                'ID: ' . $id . ' | CODE: ' . $data['code_2'] . ' / ' . $data['code_3'] . ' | NAME: ' . $data['description'],
                $data['created_by'],
            ]);
            return (int) $id;
        }

        return false;
    }

    public function update(int $id, array $data): bool
    {
        $old = DB::select('SELECT code_2, code_3, description FROM m_plant WHERE id_plant = ?', [$id]);
        if (empty($old)) return false;

        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_PLANT', 'UPDATE',
            'ID: ' . $id . ' | CODE: ' . $old[0]->code_2 . ' >> ' . $data['code_2'],
            $data['updated_by'],
        ]);

        $result = DB::update('
            UPDATE m_plant
               SET code = ?, code_2 = ?, code_3 = ?, description = ?, updated_by = ?
             WHERE id_plant = ?
        ', [
            $data['code'] ?? null,
            $data['code_2'],
            $data['code_3'],
            $data['description'],
            $data['updated_by'],
            $id,
        ]);

        return (bool) $result;
    }

    public function deactivate(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_PLANT', 'DE-ACTIVATE', 'Id: ' . $id . ' | Status: 1 >> 0', $user,
        ]);
        return (bool) DB::update('UPDATE m_plant SET status = "0", updated_by = ? WHERE id_plant = ?', [$user, $id]);
    }

    public function activate(int $id, string $user): bool
    {
        DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_PLANT', 'ACTIVATE', 'Id: ' . $id . ' | Status: 0 >> 1', $user,
        ]);
        return (bool) DB::update('UPDATE m_plant SET status = "1", updated_by = ? WHERE id_plant = ?', [$user, $id]);
    }
}
