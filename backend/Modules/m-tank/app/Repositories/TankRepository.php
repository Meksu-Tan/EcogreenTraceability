<?php
declare(strict_types=1);
namespace Modules\Tank\Repositories;

use Modules\Tank\Models\Tank;
use Modules\Tank\Repositories\Contracts\TankRepositoryInterface;
use Modules\Shared\Traits\TransactionLoggerTrait;

class TankRepository implements TankRepositoryInterface
{
    use TransactionLoggerTrait;
    public function getAll(): array
    {
        return Tank::selectRaw('id_sloc as id, id_plant AS plant_code, plant_name, tf_number, tf_number AS tank_number, tank_height, description, status, created_at, created_by, updated_at, updated_by')
            ->orderBy('id_sloc')
            ->get()
            ->toArray();
    }

    public function findById(int $id): ?object
    {
        $model = Tank::find($id);
        if (!$model) {
            return null;
        }

        return (object) [
            'id' => $model->id_sloc,
            'plant_code' => $model->id_plant,
            'plant_name' => $model->plant_name,
            'tank_number' => $model->tf_number,
            'tank_height' => $model->tank_height,
            'description' => $model->description,
            'status' => $model->status,
            'created_at' => $model->created_at,
            'created_by' => $model->created_by,
            'updated_at' => $model->updated_at,
            'updated_by' => $model->updated_by,
        ];
    }

    public function create(array $data): int|bool
    {
        $exists = Tank::where('tf_number', $data['tank_number'])
            ->where('id_plant', $data['plant_code'])
            ->where('status', '1')
            ->exists();

        if ($exists) {
            return false;
        }

        $id = $data['id'] ?? null;
        if (!$id) {
            $maxId = Tank::max('id_sloc') ?? 0;
            $id = $maxId + 1;
        }

        $model = Tank::create([
            'id_sloc' => $id,
            'id_plant' => $data['plant_code'],
            'plant_name' => $data['plant_name'],
            'tf_number' => $data['tank_number'],
            'tank_height' => $data['tank_height'],
            'description' => $data['description'] ?? null,
            'status' => 1,
            'created_by' => $data['created_by'] ?? 'System',
        ]);

        if ($model) {
            $this->logTransaction('M_SLOC', 'ADD',
                'ID: ' . $id . ' | TANK: ' . $data['tank_number'] . ' | PLANT: ' . $data['plant_code'] . ' | HEIGHT: ' . $data['tank_height'],
                $data['created_by'] ?? 'System');
            return (int) $id;
        }

        return false;
    }

    public function update(int $id, array $data): bool
    {
        $model = Tank::find($id);
        if (!$model) {
            return false;
        }

        $this->logTransaction('M_SLOC', 'UPDATE',
            'ID: ' . $id . ' | TANK: ' . $model->tf_number . ' >> ' . $data['tank_number'],
            $data['updated_by'] ?? 'System');

        return (bool) $model->update([
            'id_plant' => $data['plant_code'],
            'plant_name' => $data['plant_name'],
            'tf_number' => $data['tank_number'],
            'tank_height' => $data['tank_height'],
            'description' => $data['description'] ?? $model->description,
            'updated_by' => $data['updated_by'] ?? 'System',
        ]);
    }

    public function deactivate(int $id, string $user): bool
    {
        $this->logTransaction('M_SLOC', 'DE-ACTIVATE', 'Id: ' . $id . ' | Status: 1 >> 0', $user);

        $model = Tank::find($id);
        if (!$model) {
            return false;
        }

        return (bool) $model->update(['status' => '0', 'updated_by' => $user]);
    }

    public function activate(int $id, string $user): bool
    {
        $this->logTransaction('M_SLOC', 'ACTIVATE', 'Id: ' . $id . ' | Status: 0 >> 1', $user);

        $model = Tank::find($id);
        if (!$model) {
            return false;
        }

        return (bool) $model->update(['status' => '1', 'updated_by' => $user]);
    }

    public function syncUpdateOrCreate(array $data, string $user): bool
    {
        $existing = Tank::where('tf_number', $data['tank_number'])
            ->where('id_plant', $data['plant_code'])
            ->first();

        if ($existing) {
            if ($existing->plant_name != $data['plant_name'] || $existing->tank_height != $data['tank_height']) {
                $existing->update([
                    'plant_name' => $data['plant_name'],
                    'tank_height' => $data['tank_height'],
                    'description' => $data['description'] ?? $existing->description,
                    'updated_by' => $user,
                ]);
                return true;
            }
            return false;
        } else {
            $maxId = Tank::max('id_sloc') ?? 0;
            Tank::create([
                'id_sloc' => $maxId + 1,
                'id_plant' => $data['plant_code'],
                'plant_name' => $data['plant_name'],
                'tf_number' => $data['tank_number'],
                'tank_height' => $data['tank_height'],
                'description' => $data['description'] ?? null,
                'status' => '1',
                'created_by' => $user,
            ]);
            return true;
        }
    }
}
