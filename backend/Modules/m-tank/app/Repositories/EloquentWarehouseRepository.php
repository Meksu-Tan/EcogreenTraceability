<?php

declare(strict_types=1);

namespace Modules\Tank\Repositories;

use Modules\Shared\Traits\TransactionLoggerTrait;
use Modules\Tank\Models\Warehouse;
use Modules\Tank\Repositories\Contracts\WarehouseRepositoryInterface;

class EloquentWarehouseRepository implements WarehouseRepositoryInterface
{
    use TransactionLoggerTrait;

    public function getAll(): array
    {
        return Warehouse::selectRaw('id_warehouse as id, id_batch, code, description, status, created_at, created_by, updated_at, updated_by')
            ->orderBy('id_batch', 'asc')
            ->get()
            ->toArray();
    }

    public function findById(int $id): ?object
    {
        $model = Warehouse::find($id);
        if (! $model) {
            return null;
        }

        return (object) $model->toArray();
    }

    public function create(string $user, array $data): int|bool
    {
        $idBatch = $data['id_batch'];
        $code = $data['code'];
        $description = $data['description'];

        // Check duplicate
        $exists = Warehouse::where('id_batch', $idBatch)
            ->where('code', $code)
            ->where('description', $description)
            ->where('status', 1)
            ->exists();

        if ($exists) {
            return false;
        }

        // Custom auto-increment if needed (as PK might not be serial in postgres if populated manually in seeds)
        // PostgreSQL bigIncrements should handle it but maximum is safe
        $model = Warehouse::create([
            'id_batch' => $idBatch,
            'code' => $code,
            'description' => $description,
            'status' => 1,
            'created_by' => $user,
        ]);

        if ($model) {
            $this->logTransaction('M_WAREHOUSE', 'ADD',
                'ID: '.$model->id_warehouse.' | CODE: '.$code.' / NAME: '.$description.' / ID_BATCH: '.$idBatch.' | Status: 1',
                $user);

            return (int) $model->id_warehouse;
        }

        return false;
    }

    public function update(int $id, string $user, array $data): bool
    {
        $model = Warehouse::find($id);
        if (! $model) {
            return false;
        }

        $idBatch = $data['id_batch'];
        $code = $data['code'];
        $description = $data['description'];

        // Check duplicate excluding self
        $exists = Warehouse::where('id_batch', $idBatch)
            ->where('code', $code)
            ->where('description', $description)
            ->where('status', 1)
            ->where('id_warehouse', '!=', $id)
            ->exists();

        if ($exists) {
            return false;
        }

        $this->logTransaction('M_WAREHOUSE', 'UPDATE',
            'ID: '.$id.' | CODE: '.$model->code.' >> '.$code.' / NAME: '.$model->description.' >> '.$description.' / ID_BATCH: '.$model->id_batch.' >> '.$idBatch.' | Status: 1',
            $user);

        return $model->update([
            'id_batch' => $idBatch,
            'code' => $code,
            'description' => $description,
            'updated_by' => $user,
        ]);
    }

    public function deactivate(int $id, string $user): bool
    {
        $model = Warehouse::find($id);
        if (! $model) {
            return false;
        }

        $this->logTransaction('M_WAREHOUSE', 'DE-ACTIVATE', 'ID: '.$id.' | Status: 1 >> 0', $user);

        return $model->update([
            'status' => 0,
            'updated_by' => $user,
        ]);
    }

    public function activate(int $id, string $user): bool
    {
        $model = Warehouse::find($id);
        if (! $model) {
            return false;
        }

        $this->logTransaction('M_WAREHOUSE', 'ACTIVATE', 'ID: '.$id.' | Status: 0 >> 1', $user);

        return $model->update([
            'status' => 1,
            'updated_by' => $user,
        ]);
    }
}
