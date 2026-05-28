<?php declare(strict_types=1);

namespace Modules\Plant\Repositories;

use Modules\Plant\Models\Plant;
use Modules\Plant\Repositories\Contracts\PlantRepositoryInterface;

class PlantRepository implements PlantRepositoryInterface
{
    public function getAll(): array
    {
        return Plant::select([
            'id_plant', 'code', 'code_2', 'code_3', 'description', 'status',
            'created_at', 'created_by', 'updated_at', 'updated_by',
        ])
        ->orderBy('id_plant')
        ->get()
        ->toArray();
    }

    public function findById(int $id): ?object
    {
        $model = Plant::find($id);
        return $model ? (object) $model->toArray() : null;
    }

    public function create(array $data): int|bool
    {
        $exists = Plant::where(function ($q) use ($data) {
            $q->where('code_2', $data['code_2'])
              ->orWhere('code_3', $data['code_3']);
        })->where('status', '1')->exists();

        if ($exists) {
            return false;
        }

        $model = Plant::create([
            'code' => $data['code'] ?? null,
            'code_2' => $data['code_2'],
            'code_3' => $data['code_3'],
            'id_tank' => $data['id_sloc'] ?? 'T000',
            'description' => $data['description'],
            'status' => 1,
            'created_by' => $data['created_by'],
        ]);

        if ($model) {
            \DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
                'M_PLANT', 'ADD',
                'ID: ' . $model->id_plant . ' | CODE: ' . $data['code_2'] . ' / ' . $data['code_3'] . ' | NAME: ' . $data['description'],
                $data['created_by'],
            ]);
            return (int) $model->id_plant;
        }

        return false;
    }

    public function update(int $id, array $data): bool
    {
        $model = Plant::find($id);
        if (!$model) {
            return false;
        }

        \DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_PLANT', 'UPDATE',
            'ID: ' . $id . ' | CODE: ' . $model->code_2 . ' >> ' . $data['code_2'],
            $data['updated_by'],
        ]);

        return (bool) $model->update([
            'code' => $data['code'] ?? null,
            'code_2' => $data['code_2'],
            'code_3' => $data['code_3'],
            'description' => $data['description'],
            'updated_by' => $data['updated_by'],
        ]);
    }

    public function deactivate(int $id, string $user): bool
    {
        \DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_PLANT', 'DE-ACTIVATE', 'Id: ' . $id . ' | Status: 1 >> 0', $user,
        ]);

        $model = Plant::find($id);
        if (!$model) {
            return false;
        }

        return (bool) $model->update(['status' => '0', 'updated_by' => $user]);
    }

    public function activate(int $id, string $user): bool
    {
        \DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_PLANT', 'ACTIVATE', 'Id: ' . $id . ' | Status: 0 >> 1', $user,
        ]);

        $model = Plant::find($id);
        if (!$model) {
            return false;
        }

        return (bool) $model->update(['status' => '1', 'updated_by' => $user]);
    }
}
