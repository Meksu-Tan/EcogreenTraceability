<?php

declare(strict_types=1);

namespace Modules\Manufacturer\Repositories;

use Illuminate\Support\Facades\Cache;
use Modules\Manufacturer\Models\Manufacturer;
use Modules\Manufacturer\Repositories\Contracts\ManufacturerRepositoryInterface;
use Modules\Shared\Traits\TransactionLoggerTrait;

class ManufacturerRepository implements ManufacturerRepositoryInterface
{
    use TransactionLoggerTrait;

    private const CACHE_TTL = 3600;

    public function getAll(): array
    {
        return Cache::remember('manufacturer.all', self::CACHE_TTL, function () {
            return Manufacturer::selectRaw('
                a.id_manufacturer, a.description, a.category, a.material_type, a.status,
                a.created_at, a.created_by, a.updated_at, a.updated_by
            ')
                ->from('m_manufacturer AS a')
                ->orderBy('a.description')
                ->get()
                ->toArray();
        });
    }

    private function flushCache(): void
    {
        Cache::forget('manufacturer.all');
        Cache::forget('manufacturer.active');
    }

    public function findById(int $id): ?object
    {
        $model = Manufacturer::find($id);

        return $model ? (object) $model->toArray() : null;
    }

    public function create(array $data): bool
    {
        $exists = Manufacturer::where('description', $data['description'])->where('status', '1')->exists();
        if ($exists) {
            return false;
        }

        $model = Manufacturer::create([
            'description' => $data['description'],
            'created_by' => $data['created_by'],
        ]);

        if ($model) {
            $this->logTransaction('M_MANUFACTURER', 'ADD',
                'ID: '.$model->id_manufacturer.' | CODE: '.$data['code'].' / NAME: '.$data['description'],
                $data['created_by']);
            $this->flushCache();
        }

        return (bool) $model;
    }

    public function update(int $id, array $data): bool
    {
        $model = Manufacturer::find($id);
        if (! $model) {
            return false;
        }

        $this->logTransaction('M_MANUFACTURER', 'UPDATE',
            'ID: '.$id.' | NAME: '.$model->description.' >> '.$data['description'],
            $data['updated_by']);

        $result = (bool) $model->update([
            'description' => $data['description'],
            'updated_by' => $data['updated_by'],
        ]);

        if ($result) {
            $this->flushCache();
        }

        return $result;
    }

    public function deactivate(int $id, string $user): bool
    {
        $this->logTransaction('M_MANUFACTURER', 'DE-ACTIVATE', 'ID: '.$id.' | Status: 1 >> 0', $user);

        $model = Manufacturer::find($id);
        if (! $model) {
            return false;
        }

        $result = (bool) $model->update(['status' => '0', 'updated_by' => $user]);
        if ($result) {
            $this->flushCache();
        }

        return $result;
    }

    public function activate(int $id, string $user): bool
    {
        $this->logTransaction('M_MANUFACTURER', 'ACTIVATE', 'ID: '.$id.' | Status: 0 >> 1', $user);

        $model = Manufacturer::find($id);
        if (! $model) {
            return false;
        }

        $result = (bool) $model->update(['status' => '1', 'updated_by' => $user]);
        if ($result) {
            $this->flushCache();
        }

        return $result;
    }

    public function getActive(): array
    {
        return Cache::remember('manufacturer.active', self::CACHE_TTL, function () {
            return Manufacturer::selectRaw('a.id_manufacturer, a.description AS manufacturer, a.material_type')
                ->from('m_manufacturer as a')
                ->where('a.status', '1')
                ->orderBy('a.description')
                ->get()
                ->toArray();
        });
    }
}
