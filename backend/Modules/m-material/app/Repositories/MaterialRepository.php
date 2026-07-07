<?php

declare(strict_types=1);

namespace Modules\Material\Repositories;

use Illuminate\Support\Facades\Cache;
use Modules\Material\Models\Material;
use Modules\Material\Models\MaterialPackaging;
use Modules\Material\Repositories\Contracts\MaterialRepositoryInterface;
use Modules\Shared\Traits\DbCompatTrait;
use Modules\Shared\Traits\TransactionLoggerTrait;

class MaterialRepository implements MaterialRepositoryInterface
{
    use DbCompatTrait;
    use TransactionLoggerTrait;

    protected $connection = 'eudr_ts';

    private const CACHE_TTL = 3600;

    public function getAll(?string $type = null): array
    {
        $cacheKey = 'material.all.'.($type ?? '_');
        $this->rememberCacheKey($cacheKey);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($type) {
            $yieldFmt = $this->dbNumberFormat('yield', 1);
            $query = Material::selectRaw("
                id_material, code, code_noneudr, description, status,
                created_at, created_by, updated_at, updated_by,
                type, {$yieldFmt} AS yield,
                qtf_feed, qtf_rundown, id_feed, id_rundown,
                status_packaging, code_matl_supplier
            ");

            if (! empty($type)) {
                $query->where('type', $type);
            }

            return $query->orderBy('description')->get()->toArray();
        });
    }

    // ponytail: file cache driver has no tags — track keys manually to flush on write
    private function rememberCacheKey(string $key): void
    {
        $keys = Cache::get('material.cache_keys', []);
        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::forever('material.cache_keys', $keys);
        }
    }

    private function flushMaterialCache(): void
    {
        foreach (Cache::get('material.cache_keys', []) as $key) {
            Cache::forget($key);
        }
        Cache::forget('material.cache_keys');
        Cache::forget('material.packagings.all');
        Cache::forget('material.active_source_products');
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
            $this->logTransaction('M_MATERIAL', 'ADD',
                'ID: '.$model->id_material.' | CODE: '.$data['code'].' / NAME: '.$data['description'],
                $data['created_by']);
            $this->flushMaterialCache();
        }

        return (bool) $model;
    }

    public function update(int $id, array $data): bool
    {
        $model = Material::find($id);
        if (! $model) {
            return false;
        }

        $this->logTransaction('M_MATERIAL', 'UPDATE',
            'ID: '.$id.' | CODE: '.$model->code.' >> '.$data['code'],
            $data['updated_by']);

        $result = (bool) $model->update([
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

        if ($result) {
            $this->flushMaterialCache();
        }

        return $result;
    }

    public function deactivate(int $id, string $user): bool
    {
        $this->logTransaction('M_MATERIAL', 'DE-ACTIVATE', 'Id: '.$id.' | Status: 1 >> 0', $user);

        $model = Material::find($id);
        if (! $model) {
            return false;
        }

        $result = (bool) $model->update(['status' => '0', 'updated_by' => $user]);
        if ($result) {
            $this->flushMaterialCache();
        }

        return $result;
    }

    public function activate(int $id, string $user): bool
    {
        $this->logTransaction('M_MATERIAL', 'ACTIVATE', 'Id: '.$id.' | Status: 0 >> 1', $user);

        $model = Material::find($id);
        if (! $model) {
            return false;
        }

        $result = (bool) $model->update(['status' => '1', 'updated_by' => $user]);
        if ($result) {
            $this->flushMaterialCache();
        }

        return $result;
    }

    // -----------------------------------------------------------------------
    // Packaging
    // -----------------------------------------------------------------------

    public function getAllPackagings(): array
    {
        return Cache::remember('material.packagings.all', self::CACHE_TTL, function () {
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
        });
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
            $this->logTransaction('M_MATERIAL_PCK', 'ADD', 'ID: '.$model->id_materialpck.' | CODE: '.$data['code'], $data['created_by']);
            $this->flushMaterialCache();
        }

        return (bool) $model;
    }

    public function updatePackaging(int $id, array $data): bool
    {
        $model = MaterialPackaging::find($id);
        if (! $model) {
            return false;
        }

        $this->logTransaction('M_MATERIAL_PCK', 'UPDATE',
            'ID: '.$id.' | CODE: '.$model->code.' >> '.$data['code'],
            $data['updated_by']);

        $result = (bool) $model->update([
            'code' => $data['code'],
            'code_noneudr' => $data['code_noneudr'] ?? null,
            'description' => $data['description'],
            'id_material' => $data['id_material'],
            'updated_by' => $data['updated_by'],
        ]);

        if ($result) {
            $this->flushMaterialCache();
        }

        return $result;
    }

    public function deactivatePackaging(int $id, string $user): bool
    {
        $this->logTransaction('M_MATERIAL_PCK', 'DE-ACTIVATE', 'Id: '.$id.' | Status: 1 >> 0', $user);

        $model = MaterialPackaging::find($id);
        if (! $model) {
            return false;
        }

        $result = (bool) $model->update(['status' => '0', 'updated_by' => $user]);
        if ($result) {
            $this->flushMaterialCache();
        }

        return $result;
    }

    public function activatePackaging(int $id, string $user): bool
    {
        $this->logTransaction('M_MATERIAL_PCK', 'ACTIVATE', 'Id: '.$id.' | Status: 0 >> 1', $user);

        $model = MaterialPackaging::find($id);
        if (! $model) {
            return false;
        }

        $result = (bool) $model->update(['status' => '1', 'updated_by' => $user]);
        if ($result) {
            $this->flushMaterialCache();
        }

        return $result;
    }

    public function getActiveSourceProducts(): array
    {
        return Cache::remember('material.active_source_products', self::CACHE_TTL, function () {
            return Material::selectRaw("id_material, CONCAT(code, ' / ', description) AS material")
                ->where('status', '1')
                ->where('status_packaging', '1')
                ->orderBy('code')
                ->get()
                ->toArray();
        });
    }
}
