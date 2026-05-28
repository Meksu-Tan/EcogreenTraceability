<?php declare(strict_types=1);

namespace Modules\Manufacturer\Repositories;

use Modules\Manufacturer\Models\Manufacturer;
use Modules\Manufacturer\Repositories\Contracts\ManufacturerRepositoryInterface;

class ManufacturerRepository implements ManufacturerRepositoryInterface
{
    public function getAll(): array
    {
        return Manufacturer::selectRaw('
            a.id_manufacturer, a.code, a.description, a.status,
            a.created_at, a.created_by, a.updated_at, a.updated_by,
            a.type, a.batch_code,
            CASE
                WHEN b.id_sloc IS NULL THEN "other"
                ELSE CONCAT(COALESCE(b.id_plant,""), " - ", COALESCE(b.description,""), " (", COALESCE(b.code_4,""), ")")
            END AS sloc
        ')
        ->from('m_manufacturer AS a')
        ->leftJoin('m_sloc AS b', 'a.type', '=', 'b.id_sloc')
        ->orderBy('a.description')
        ->get()
        ->toArray();
    }

    public function findById(int $id): ?object
    {
        $model = Manufacturer::find($id);
        return $model ? (object) $model->toArray() : null;
    }

    public function create(array $data): bool
    {
        $exists = Manufacturer::where('code', $data['code'])->where('status', '1')->exists();
        if ($exists) {
            return false;
        }

        $model = Manufacturer::create([
            'code' => $data['code'],
            'description' => $data['description'],
            'type' => $data['type'] ?? null,
            'batch_code' => $data['batch_code'] ?? null,
            'created_by' => $data['created_by'],
        ]);

        if ($model) {
            \DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
                'M_MANUFACTURER', 'ADD',
                'ID: ' . $model->id_manufacturer . ' | CODE: ' . $data['code'] . ' / NAME: ' . $data['description'],
                $data['created_by'],
            ]);
        }

        return (bool) $model;
    }

    public function update(int $id, array $data): bool
    {
        $model = Manufacturer::find($id);
        if (!$model) {
            return false;
        }

        \DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MANUFACTURER', 'UPDATE',
            'ID: ' . $id . ' | CODE: ' . $model->code . ' >> ' . $data['code'] . ' / NAME: ' . $model->description . ' >> ' . $data['description'],
            $data['updated_by'],
        ]);

        return (bool) $model->update([
            'code' => $data['code'],
            'description' => $data['description'],
            'type' => $data['type'] ?? null,
            'batch_code' => $data['batch_code'] ?? null,
            'updated_by' => $data['updated_by'],
        ]);
    }

    public function deactivate(int $id, string $user): bool
    {
        \DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MANUFACTURER', 'DE-ACTIVATE', 'ID: ' . $id . ' | Status: 1 >> 0', $user,
        ]);

        $model = Manufacturer::find($id);
        if (!$model) {
            return false;
        }

        return (bool) $model->update(['status' => '0', 'updated_by' => $user]);
    }

    public function activate(int $id, string $user): bool
    {
        \DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MANUFACTURER', 'ACTIVATE', 'ID: ' . $id . ' | Status: 0 >> 1', $user,
        ]);

        $model = Manufacturer::find($id);
        if (!$model) {
            return false;
        }

        return (bool) $model->update(['status' => '1', 'updated_by' => $user]);
    }

    public function getActive(): array
    {
        return Manufacturer::selectRaw('a.id_manufacturer, CONCAT(a.code, " / ", a.description) AS manufacturer')
            ->where('a.status', '1')
            ->orderBy('a.description')
            ->get()
            ->toArray();
    }
}
