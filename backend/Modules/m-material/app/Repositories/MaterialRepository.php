<?php
declare(strict_types=1);
namespace Modules\Material\Repositories;

use Modules\Material\Models\Material;
use Modules\Material\Models\MaterialPackaging;
use Modules\Material\Repositories\Contracts\MaterialRepositoryInterface;
use Modules\Shared\Traits\DbCompatTrait;

class MaterialRepository implements MaterialRepositoryInterface
{
    use DbCompatTrait;

    protected $connection = 'eudr_ts';

    public function getAll(?string $type = null): array
    {
        $yieldFmt = $this->dbNumberFormat('yield', 1);
        $query = Material::selectRaw("
            id_material, code, code_noneudr, description, status,
            created_at, created_by, updated_at, updated_by,
            type, {$yieldFmt} AS yield,
            qtf_feed, qtf_rundown, id_feed, id_rundown,
            status_packaging, code_matl_supplier
        ");

        if (!empty($type)) {
            $query->where('type', $type);
        }

        return $query->orderBy('description')->get()->toArray();
    }

    public function findById(int $id): ?object
    {
        $model = Material::find($id);
        return $model ? (object) $model->toArray() : null;
    }

    public function create(array $data): bool
    {
        $exists = Material::where('code', $data['code'])->where('status', '1')->exists();
        if ($exists) {
            return false;
        }

        $model = Material::create([
            'code' => $data['code'],
            'code_noneudr' => $data['code_noneudr'] ?? null,
            'description' => $data['description'],
            'type' => $data['type'],
            'qtf_feed' => $data['qtf_feed'] ?? null,
            'qtf_rundown' => $data['qtf_rundown'] ?? null,
            'yield' => $data['yield'] ?? 100,
            'status_packaging' => $data['status_packaging'] ?? 0,
            'code_matl_supplier' => $data['code_matl_supplier'] ?? null,
            'created_by' => $data['created_by'],
        ]);

        if ($model) {
            \DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
                'M_MATERIAL', 'ADD',
                'ID: ' . $model->id_material . ' | CODE: ' . $data['code'] . ' / NAME: ' . $data['description'],
                $data['created_by'],
            ]);
        }

        return (bool) $model;
    }

    public function update(int $id, array $data): bool
    {
        $model = Material::find($id);
        if (!$model) {
            return false;
        }

        \DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MATERIAL', 'UPDATE',
            'ID: ' . $id . ' | CODE: ' . $model->code . ' >> ' . $data['code'],
            $data['updated_by'],
        ]);

        return (bool) $model->update([
            'code' => $data['code'],
            'code_noneudr' => $data['code_noneudr'] ?? null,
            'description' => $data['description'],
            'type' => $data['type'],
            'qtf_feed' => $data['qtf_feed'] ?? null,
            'qtf_rundown' => $data['qtf_rundown'] ?? null,
            'yield' => $data['yield'] ?? 100,
            'status_packaging' => $data['status_packaging'] ?? 0,
            'code_matl_supplier' => $data['code_matl_supplier'] ?? null,
            'updated_by' => $data['updated_by'],
        ]);
    }

    public function deactivate(int $id, string $user): bool
    {
        \DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MATERIAL', 'DE-ACTIVATE', 'Id: ' . $id . ' | Status: 1 >> 0', $user,
        ]);

        $model = Material::find($id);
        if (!$model) {
            return false;
        }

        return (bool) $model->update(['status' => '0', 'updated_by' => $user]);
    }

    public function activate(int $id, string $user): bool
    {
        \DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MATERIAL', 'ACTIVATE', 'Id: ' . $id . ' | Status: 0 >> 1', $user,
        ]);

        $model = Material::find($id);
        if (!$model) {
            return false;
        }

        return (bool) $model->update(['status' => '1', 'updated_by' => $user]);
    }

    // -----------------------------------------------------------------------
    // Packaging
    // -----------------------------------------------------------------------

    public function getAllPackagings(): array
    {
        return MaterialPackaging::selectRaw("
            a.id_materialpck, a.code, a.code_noneudr, a.description, a.status,
            a.created_at, a.created_by, a.updated_at, a.updated_by,
            a.id_material, CONCAT(b.code, ' :: ', b.description) AS source_product
        ")
        ->from('m_material_pck AS a')
        ->leftJoin('m_material AS b', 'a.id_material', '=', 'b.id_material')
        ->orderBy('a.description')
        ->get()
        ->toArray();
    }

    public function findPackagingById(int $id): ?object
    {
        $model = MaterialPackaging::find($id);
        return $model ? (object) $model->toArray() : null;
    }

    public function createPackaging(array $data): bool
    {
        $exists = MaterialPackaging::where('code', $data['code'])->where('status', '1')->exists();
        if ($exists) {
            return false;
        }

        $model = MaterialPackaging::create([
            'code' => $data['code'],
            'code_noneudr' => $data['code_noneudr'] ?? null,
            'description' => $data['description'],
            'id_material' => $data['id_material'],
            'created_by' => $data['created_by'],
        ]);

        if ($model) {
            \DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
                'M_MATERIAL_PCK', 'ADD', 'ID: ' . $model->id_materialpck . ' | CODE: ' . $data['code'], $data['created_by'],
            ]);
        }

        return (bool) $model;
    }

    public function updatePackaging(int $id, array $data): bool
    {
        $model = MaterialPackaging::find($id);
        if (!$model) {
            return false;
        }

        \DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MATERIAL_PCK', 'UPDATE',
            'ID: ' . $id . ' | CODE: ' . $model->code . ' >> ' . $data['code'],
            $data['updated_by'],
        ]);

        return (bool) $model->update([
            'code' => $data['code'],
            'code_noneudr' => $data['code_noneudr'] ?? null,
            'description' => $data['description'],
            'id_material' => $data['id_material'],
            'updated_by' => $data['updated_by'],
        ]);
    }

    public function deactivatePackaging(int $id, string $user): bool
    {
        \DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MATERIAL_PCK', 'DE-ACTIVATE', 'Id: ' . $id . ' | Status: 1 >> 0', $user,
        ]);

        $model = MaterialPackaging::find($id);
        if (!$model) {
            return false;
        }

        return (bool) $model->update(['status' => '0', 'updated_by' => $user]);
    }

    public function activatePackaging(int $id, string $user): bool
    {
        \DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)', [
            'M_MATERIAL_PCK', 'ACTIVATE', 'Id: ' . $id . ' | Status: 0 >> 1', $user,
        ]);

        $model = MaterialPackaging::find($id);
        if (!$model) {
            return false;
        }

        return (bool) $model->update(['status' => '1', 'updated_by' => $user]);
    }

    public function getActiveSourceProducts(): array
    {
        return Material::selectRaw("id_material, CONCAT(code, ' / ', description) AS material")
            ->where('status', '1')
            ->where('status_packaging', '1')
            ->orderBy('code')
            ->get()
            ->toArray();
    }
}
