<?php

declare(strict_types=1);

namespace Modules\Plant\Repositories;

use Illuminate\Support\Facades\Cache;
use Modules\Plant\Models\Plant;
use Modules\Plant\Repositories\Contracts\PlantRepositoryInterface;
use Modules\Shared\Traits\TransactionLoggerTrait;

class PlantRepository implements PlantRepositoryInterface
{
    use TransactionLoggerTrait;

    private const CACHE_KEY = 'plant.all';

    private const CACHE_TTL = 3600;

    public function getAll(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Plant::select([
                'id_plant', 'code', 'code_2', 'code_3', 'description', 'status',
                'created_at', 'created_by', 'updated_at', 'updated_by',
            ])
                ->orderBy('id_plant')
                ->get()
                ->toArray();
        });
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
            'id_sloc' => $data['id_sloc'] ?? 'T000',
            'description' => $data['description'],
            'status' => 1,
            'created_by' => $data['created_by'],
        ]);

        if ($model) {
            $this->logTransaction('M_PLANT', 'ADD',
                'ID: '.$model->id_plant.' | CODE: '.$data['code_2'].' / '.$data['code_3'].' | NAME: '.$data['description'],
                $data['created_by']);
            Cache::forget(self::CACHE_KEY);

            return (int) $model->id_plant;
        }

        return false;
    }

    public function update(int $id, array $data): bool
    {
        $model = Plant::find($id);
        if (! $model) {
            return false;
        }

        $this->logTransaction('M_PLANT', 'UPDATE',
            'ID: '.$id.' | CODE: '.$model->code_2.' >> '.$data['code_2'],
            $data['updated_by']);

        $result = (bool) $model->update([
            'code' => $data['code'] ?? null,
            'code_2' => $data['code_2'],
            'code_3' => $data['code_3'],
            'description' => $data['description'],
            'updated_by' => $data['updated_by'],
        ]);

        if ($result) {
            Cache::forget(self::CACHE_KEY);
        }

        return $result;
    }

    public function deactivate(int $id, string $user): bool
    {
        $this->logTransaction('M_PLANT', 'DE-ACTIVATE', 'Id: '.$id.' | Status: 1 >> 0', $user);

        $model = Plant::find($id);
        if (! $model) {
            return false;
        }

        $result = (bool) $model->update(['status' => '0', 'updated_by' => $user]);
        if ($result) {
            Cache::forget(self::CACHE_KEY);
        }

        return $result;
    }

    public function activate(int $id, string $user): bool
    {
        $this->logTransaction('M_PLANT', 'ACTIVATE', 'Id: '.$id.' | Status: 0 >> 1', $user);

        $model = Plant::find($id);
        if (! $model) {
            return false;
        }

        $result = (bool) $model->update(['status' => '1', 'updated_by' => $user]);
        if ($result) {
            Cache::forget(self::CACHE_KEY);
        }

        return $result;
    }
}
