<?php declare(strict_types=1);

namespace Modules\Storage\Repositories;

use Modules\Storage\Models\Warehouse;
use Modules\Storage\Repositories\Contracts\StorageWarehouseRepositoryInterface;

class StorageWarehouseRepository implements StorageWarehouseRepositoryInterface
{
    public function getAllWarehouses(): array
    {
        return Warehouse::select([
            'id_warehouse', 'id_batch', 'code', 'description', 'status',
            'created_by', 'created_at', 'updated_by', 'updated_at',
        ])
        ->orderBy('id_batch')
        ->get()
        ->toArray();
    }

    public function findWarehouseById(int $id): ?object
    {
        $model = Warehouse::find($id);
        return $model ? (object) $model->toArray() : null;
    }

    public function createWarehouse(array $data): bool
    {
        $exists = Warehouse::where('id_batch', $data['id_batch'])
            ->where('code', $data['code'])
            ->where('description', $data['description'])
            ->where('status', '1')
            ->exists();

        if ($exists) {
            return false;
        }

        $model = Warehouse::create([
            'id_batch' => $data['id_batch'],
            'code' => $data['code'],
            'description' => $data['description'],
            'created_by' => $data['created_by'],
        ]);

        if ($model) {
            $this->logTransaction('M_WAREHOUSE', 'ADD', 'ID: ' . $model->id_warehouse . ' | CODE: ' . $data['code'] . ' / NAME: ' . $data['description'], $data['created_by']);
        }

        return (bool) $model;
    }

    public function updateWarehouse(int $id, array $data): bool
    {
        $model = Warehouse::find($id);
        if (!$model) {
            return false;
        }

        $this->logTransaction('M_WAREHOUSE', 'UPDATE', 'ID: ' . $id . ' | CODE: ' . $model->code . ' >> ' . $data['code'] . ' / NAME: ' . $model->description . ' >> ' . $data['description'], $data['updated_by']);

        return (bool) $model->update([
            'id_batch' => $data['id_batch'],
            'code' => $data['code'],
            'description' => $data['description'],
            'updated_by' => $data['updated_by'],
        ]);
    }

    public function deactivateWarehouse(int $id, string $user): bool
    {
        $this->logTransaction('M_WAREHOUSE', 'DE-ACTIVATE', 'ID: ' . $id . ' | Status: 1 >> 0', $user);

        $model = Warehouse::find($id);
        if (!$model) {
            return false;
        }

        return (bool) $model->update(['status' => '0', 'updated_by' => $user]);
    }

    public function activateWarehouse(int $id, string $user): bool
    {
        $this->logTransaction('M_WAREHOUSE', 'ACTIVATE', 'ID: ' . $id . ' | Status: 0 >> 1', $user);

        $model = Warehouse::find($id);
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