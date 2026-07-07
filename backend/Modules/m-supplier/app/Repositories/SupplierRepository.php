<?php

declare(strict_types=1);

namespace Modules\Supplier\Repositories;

use Illuminate\Support\Facades\Cache;
use Modules\Shared\Traits\TransactionLoggerTrait;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;

class SupplierRepository implements SupplierRepositoryInterface
{
    use TransactionLoggerTrait;

    private const CACHE_TTL = 3600;

    public function getAll(): array
    {
        return Cache::remember('supplier.all', self::CACHE_TTL, function () {
            return Supplier::select([
                'id_supplier', 'code', 'description', 'status',
                'created_at', 'created_by', 'updated_at', 'updated_by',
                'type', 'batch_code',
            ])
                ->selectRaw('type AS sloc')
                ->orderBy('description')
                ->get()
                ->toArray();
        });
    }

    private function flushCache(): void
    {
        Cache::forget('supplier.all');
        Cache::forget('supplier.active');
    }

    public function findById(int $id): ?object
    {
        $model = Supplier::find($id);

        return $model ? (object) $model->toArray() : null;
    }

    public function create(array $data): bool
    {
        $exists = Supplier::where('code', $data['code'])->where('status', '1')->exists();
        if ($exists) {
            return false;
        }

        $model = Supplier::create([
            'code' => $data['code'],
            'description' => $data['description'],
            'type' => $data['type'] ?? null,
            'batch_code' => $data['batch_code'] ?? null,
            'created_by' => $data['created_by'],
        ]);

        if ($model) {
            $this->logTransaction('M_SUPPLIER', 'ADD',
                'ID: '.$model->id_supplier.' | CODE: '.$data['code'].' / NAME: '.$data['description'],
                $data['created_by']);
            $this->flushCache();
        }

        return (bool) $model;
    }

    public function update(int $id, array $data): bool
    {
        $model = Supplier::find($id);
        if (! $model) {
            return false;
        }

        $this->logTransaction('M_SUPPLIER', 'UPDATE',
            'ID: '.$id.' | CODE: '.$model->code.' >> '.$data['code'].' / NAME: '.$model->description.' >> '.$data['description'],
            $data['updated_by']);

        $result = (bool) $model->update([
            'code' => $data['code'],
            'description' => $data['description'],
            'type' => $data['type'] ?? null,
            'batch_code' => $data['batch_code'] ?? null,
            'updated_by' => $data['updated_by'],
        ]);

        if ($result) {
            $this->flushCache();
        }

        return $result;
    }

    public function deactivate(int $id, string $user): bool
    {
        $this->logTransaction('M_SUPPLIER', 'DE-ACTIVATE', 'ID: '.$id.' | Status: 1 >> 0', $user);

        $model = Supplier::find($id);
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
        $this->logTransaction('M_SUPPLIER', 'ACTIVATE', 'ID: '.$id.' | Status: 0 >> 1', $user);

        $model = Supplier::find($id);
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
        return Cache::remember('supplier.active', self::CACHE_TTL, function () {
            return Supplier::selectRaw("id_supplier, CONCAT(code, ' / ', description) AS supplier")
                ->where('status', '1')
                ->orderBy('description')
                ->get()
                ->toArray();
        });
    }
}
