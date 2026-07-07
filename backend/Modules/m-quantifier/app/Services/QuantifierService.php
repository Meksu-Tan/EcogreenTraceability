<?php

declare(strict_types=1);

namespace Modules\Quantifier\Services;

use Illuminate\Support\Facades\DB;
use Modules\Quantifier\Repositories\Contracts\QuantifierRepositoryInterface;
use Modules\Quantifier\Services\Contracts\QuantifierServiceInterface;

class QuantifierService implements QuantifierServiceInterface
{
    public function __construct(
        protected QuantifierRepositoryInterface $repository
    ) {}

    public function getQuantifierList(array $filters = []): array
    {
        $result = $this->repository->getQuantifierList($filters);

        return [
            'status' => 1,
            'data' => $result['data'] ?? [],
            'total' => $result['total'] ?? 0,
            'message' => 'Quantifier list retrieved',
        ];
    }

    public function getActiveFlowmeters(): array
    {
        return [
            'status' => 1,
            'data' => $this->repository->getActiveFlowmeters(),
            'message' => 'Active flowmeters retrieved',
        ];
    }

    public function getQuantifierDetail(int $id): ?array
    {
        return $this->repository->getQuantifierDetail($id);
    }

    public function storeQuantifier(string $user, array $data): array
    {
        $mode = $data['mode'] ?? 'ADD';
        $flowmeter = $data['flowmeter'] ?? null;
        $resetDate = $data['reset_date'] ?? now()->toDateString();
        $value = (float) ($data['value'] ?? 0);
        $remark = $data['remark'] ?? '';

        return DB::connection('eudr_ts')->transaction(function () use ($user, $data, $mode, $flowmeter, $resetDate, $value, $remark) {
            // Bulk create for all flowmeters
            if ($mode === 'ADD' && empty($flowmeter)) {
                $flowmeters = $this->repository->getActiveFlowmeters();
                $insertedIds = [];
                foreach ($flowmeters as $fm) {
                    $id = $this->repository->createQuantifier(
                        $resetDate, $fm['flowmeter'], $value, $remark, $user
                    );
                    $insertedIds[] = $id;
                }

                return ['response' => 1, 'message' => 'Bulk quantifier created', 'ids' => $insertedIds];
            }

            // Single create
            if ($mode === 'ADD') {
                $id = $this->repository->createQuantifier(
                    $resetDate, $flowmeter, $value, $remark, $user
                );

                return ['response' => 1, 'message' => 'Quantifier created', 'id' => $id];
            }

            // Update
            if ($mode === 'UPDATE') {
                $id = (int) ($data['id'] ?? 0);
                $result = $this->repository->updateQuantifier(
                    $id, $resetDate, $flowmeter, $value, $remark, $user
                );

                return $result;
            }

            return ['response' => 0, 'message' => 'Invalid mode'];
        });
    }

    public function deactivateQuantifier(string $user, int $id): array
    {
        return $this->repository->deactivateQuantifier($id, $user);
    }

    public function activateQuantifier(string $user, int $id): array
    {
        return $this->repository->activateQuantifier($id, $user);
    }
}
