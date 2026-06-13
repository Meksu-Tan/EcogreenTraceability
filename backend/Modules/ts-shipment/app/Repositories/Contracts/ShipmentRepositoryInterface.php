<?php declare(strict_types=1);

namespace Modules\TsShipment\Repositories\Contracts;

use Illuminate\Support\Collection;

interface ShipmentRepositoryInterface
{
    public function getDtShipEntry(): Collection;
    public function getActiveFgProduct(): Collection;
    public function getWipMaterialByFgProduct(array $data): Collection;
    public function getActiveBatchProduct(array $data): Collection;
    public function store(string $user, array $data): array;
    public function cancel(string $user, array $data): array;
    public function updateSo(string $user, array $data): array;
    public function generateTraceNo(int $plantId): string;
    public function getShipmentBatchPackaging(array $data): Collection;
    public function getPreparationRecord(array $data): Collection;
    public function getLabel(array $data): Collection;
    public function getSpecialLabel(array $data): Collection;
    public function getCustomerMark(array $data): Collection;
    public function getDatShipment(array $data): array;
    public function getDatSoAllocation(array $data): array;
}
