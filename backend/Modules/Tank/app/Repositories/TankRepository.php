<?php

namespace Modules\Tank\Repositories;

use Modules\Tank\Repositories\Contracts\TankRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TankRepository implements TankRepositoryInterface
{
    public function getAll(): array
    {
        return DB::connection('eudr_ts')->select('
            SELECT id_sloc as id, id_plant AS plant_code, plant_name, id_tank AS tank_number, tank_height, status,
                   created_at, created_by, updated_at, updated_by
            FROM m_sloc
            ORDER BY id_sloc ASC
        ');
    }

    public function findById(int $id): ?object
    {
        $result = DB::connection('eudr_ts')->select('SELECT id_sloc as id, id_plant AS plant_code, plant_name, id_tank AS tank_number, tank_height, status FROM m_sloc WHERE id_sloc = ?', [$id]);
        return $result[0] ?? null;
    }

    public function create(array $data): int|bool
    {
        $exists = DB::connection('eudr_ts')->select('SELECT COUNT(id_sloc) as cnt FROM m_sloc WHERE id_tank = ? AND id_plant = ? AND status = "1"', [
            $data['tank_number'], $data['plant_code']
        ]);

        if ($exists[0]->cnt >= 1) {
            return false;
        }

        $id = $data['id'] ?? null;
        if (!$id) {
            $maxResult = DB::connection('eudr_ts')->select('SELECT MAX(id_sloc) as max_id FROM m_sloc');
            $id = ($maxResult[0]->max_id ?? 0) + 1;
        }

        $result = DB::connection('eudr_ts')->insert('
            INSERT INTO m_sloc (id_sloc, id_plant, plant_name, id_tank, tank_height, status, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ', [
            $id,
            $data['plant_code'],
            $data['plant_name'],
            $data['tank_number'],
            $data['tank_height'],
            1,
            $data['created_by'] ?? 'System'
        ]);

        if ($result) {
            DB::connection('eudr_ts')->insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
                'M_SLOC', 'ADD',
                'ID: ' . $id . ' | TANK: ' . $data['tank_number'] . ' | PLANT: ' . $data['plant_code'] . ' | HEIGHT: ' . $data['tank_height'],
                $data['created_by'] ?? 'System',
            ]);
            return (int) $id;
        }

        return false;
    }

    public function update(int $id, array $data): bool
    {
        $old = DB::connection('eudr_ts')->select('SELECT id_plant AS plant_code, id_tank AS tank_number, tank_height FROM m_sloc WHERE id_sloc = ?', [$id]);
        if (empty($old)) return false;

        DB::connection('eudr_ts')->insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_SLOC', 'UPDATE',
            'ID: ' . $id . ' | TANK: ' . $old[0]->tank_number . ' >> ' . $data['tank_number'],
            $data['updated_by'] ?? 'System',
        ]);

        $result = DB::connection('eudr_ts')->update('
            UPDATE m_sloc
               SET id_plant = ?, plant_name = ?, id_tank = ?, tank_height = ?, updated_by = ?, updated_at = NOW()
             WHERE id_sloc = ?
        ', [
            $data['plant_code'],
            $data['plant_name'],
            $data['tank_number'],
            $data['tank_height'],
            $data['updated_by'] ?? 'System',
            $id,
        ]);

        return (bool) $result;
    }

    public function deactivate(int $id, string $user): bool
    {
        DB::connection('eudr_ts')->insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_SLOC', 'DE-ACTIVATE', 'Id: ' . $id . ' | Status: 1 >> 0', $user,
        ]);
        return (bool) DB::connection('eudr_ts')->update('UPDATE m_sloc SET status = "0", updated_by = ?, updated_at = NOW() WHERE id_sloc = ?', [$user, $id]);
    }

    public function activate(int $id, string $user): bool
    {
        DB::connection('eudr_ts')->insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_SLOC', 'ACTIVATE', 'Id: ' . $id . ' | Status: 0 >> 1', $user,
        ]);
        return (bool) DB::connection('eudr_ts')->update('UPDATE m_sloc SET status = "1", updated_by = ?, updated_at = NOW() WHERE id_sloc = ?', [$user, $id]);
    }
}
