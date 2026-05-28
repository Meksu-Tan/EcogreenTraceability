<?php declare(strict_types=1);

namespace Modules\Storage\Repositories;

use Modules\Storage\Models\StorageTank;
use Modules\Storage\Models\StorageDetail;
use Modules\Storage\Repositories\Contracts\StorageTankRepositoryInterface;

class StorageTankRepository implements StorageTankRepositoryInterface
{
    public function getAllTanks(): array
    {
        \DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))');

        return StorageTank::selectRaw('
            a.id_sloc, CONCAT(a.code_2, " | ", a.code_3) AS code,
            a.description, a.status, a.created_at, a.created_by,
            a.updated_at, a.updated_by, a.code_2, a.code_3, a.code_4,
            a.id_plant, IFNULL(b.total_tank, 0) AS total_tank
        ')
        ->from('m_sloc AS a')
        ->leftJoinSub(
            \DB::table('m_sloc_detail')
                ->selectRaw('COUNT(id_sloc_tail) AS total_tank, id_sloc')
                ->where('status', 1)
                ->groupBy('id_sloc'),
            'b',
            'a.id_sloc',
            '=',
            'b.id_sloc'
        )
        ->orderBy('a.description')
        ->get()
        ->toArray();
    }

    public function findTankById(int $id): ?object
    {
        $model = StorageTank::find($id);
        return $model ? (object) $model->toArray() : null;
    }

    public function createTank(array $data): bool
    {
        $exists = StorageTank::where('id_plant', $data['id_plant'])
            ->where('code_2', $data['code_2'])
            ->where('code_3', $data['code_3'])
            ->where('description', $data['description'])
            ->where('status', '1')
            ->exists();

        if ($exists) {
            return false;
        }

        $model = StorageTank::create([
            'code_2' => $data['code_2'],
            'code_3' => $data['code_3'],
            'code_4' => $data['code_4'] ?? null,
            'id_plant' => $data['id_plant'],
            'description' => $data['description'],
            'created_by' => $data['created_by'],
        ]);

        if ($model) {
            $this->logTransaction('M_STORAGE_TANK', 'ADD', 'ID: ' . $model->id_sloc . ' | CODE: ' . $data['code_3'] . ' / NAME: ' . $data['description'], $data['created_by']);
        }

        return (bool) $model;
    }

    public function updateTank(int $id, array $data): bool
    {
        $model = StorageTank::find($id);
        if (!$model) {
            return false;
        }

        $this->logTransaction('M_STORAGE_TANK', 'UPDATE', 'ID: ' . $id . ' | CODE: ' . $model->code_3 . ' >> ' . $data['code_3'] . ' / NAME: ' . $model->description . ' >> ' . $data['description'], $data['updated_by']);

        return (bool) $model->update([
            'code_2' => $data['code_2'],
            'code_3' => $data['code_3'],
            'code_4' => $data['code_4'] ?? null,
            'id_plant' => $data['id_plant'],
            'description' => $data['description'],
            'updated_by' => $data['updated_by'],
        ]);
    }

    public function deactivateTank(int $id, string $user): bool
    {
        $this->logTransaction('M_STORAGE_TANK', 'DE-ACTIVATE', 'ID: ' . $id . ' | Status: 1 >> 0', $user);

        $model = StorageTank::find($id);
        if (!$model) {
            return false;
        }

        return (bool) $model->update(['status' => '0', 'updated_by' => $user]);
    }

    public function activateTank(int $id, string $user): bool
    {
        $this->logTransaction('M_STORAGE_TANK', 'ACTIVATE', 'ID: ' . $id . ' | Status: 0 >> 1', $user);

        $model = StorageTank::find($id);
        if (!$model) {
            return false;
        }

        return (bool) $model->update(['status' => '1', 'updated_by' => $user]);
    }

    public function getDetailsByTank(int $tankId): array
    {
        return StorageDetail::selectRaw('
            a.id_sloc_tail, a.tf_number, a.status, a.created_at, a.updated_at,
            b.description AS storage, b.id_plant, b.id_sloc
        ')
        ->from('m_sloc_detail AS a')
        ->leftJoin('m_sloc AS b', 'a.id_sloc', '=', 'b.id_sloc')
        ->where('b.id_sloc', $tankId)
        ->where('a.status', 1)
        ->orderBy('a.tf_number')
        ->get()
        ->toArray();
    }

    public function findDetailById(int $id): ?object
    {
        $model = StorageDetail::find($id);
        return $model ? (object) $model->toArray() : null;
    }

    public function createDetail(array $data): bool
    {
        $exists = StorageDetail::where('tf_number', $data['tf_number'])->where('status', 1)->exists();
        if ($exists) {
            return false;
        }

        $model = StorageDetail::create([
            'id_sloc' => $data['id_sloc'],
            'tf_number' => $data['tf_number'],
            'created_by' => $data['created_by'],
        ]);

        if ($model) {
            $this->logTransaction('M_STORAGE_TANK', 'ADD', 'ID: ' . $model->id_sloc_tail . ' | ID_TANK: ' . $data['id_sloc'] . ' / TF_NUMBER: ' . $data['tf_number'], $data['created_by']);
        }

        return (bool) $model;
    }

    public function updateDetail(int $id, array $data): bool
    {
        $exists = StorageDetail::where('tf_number', $data['tf_number'])
            ->where('status', 1)
            ->where('id_sloc_tail', '!=', $id)
            ->exists();

        if ($exists) {
            return false;
        }

        $model = StorageDetail::find($id);
        if (!$model) {
            return false;
        }

        $this->logTransaction('M_STORAGE_TANK', 'UPDATE', 'ID: ' . $id . ' | TF_NUMBER: ' . $model->tf_number . ' >> ' . $data['tf_number'], $data['updated_by']);

        return (bool) $model->update([
            'tf_number' => $data['tf_number'],
            'updated_by' => $data['updated_by'],
        ]);
    }

    public function deactivateDetail(int $id, string $user): bool
    {
        $this->logTransaction('M_STORAGE_DETAIL', 'DE-ACTIVATE', 'ID: ' . $id . ' | Status: 1 >> 0', $user);

        $model = StorageDetail::find($id);
        if (!$model) {
            return false;
        }

        return (bool) $model->update(['status' => '0', 'updated_by' => $user]);
    }

    public function activateDetail(int $id, string $user): bool
    {
        $this->logTransaction('M_STORAGE_DETAIL', 'ACTIVATE', 'ID: ' . $id . ' | Status: 0 >> 1', $user);

        $model = StorageDetail::find($id);
        if (!$model) {
            return false;
        }

        return (bool) $model->update(['status' => '1', 'updated_by' => $user]);
    }

    private function logTransaction(string $module, string $type, string $description, string $user): void
    {
        \DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            $module, $type, $description, $user,
        ]);
    }
}