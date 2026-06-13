<?php declare(strict_types=1);

namespace Modules\TsShipment\Services;

use Modules\TsShipment\Services\Contracts\ShipmentServiceInterface;
use Modules\TsShipment\Repositories\Contracts\ShipmentRepositoryInterface;
use Illuminate\Support\Collection;

class ShipmentService implements ShipmentServiceInterface
{
    public function __construct(
        protected ShipmentRepositoryInterface $shipmentRepo
    ) {}

    public function getDtShipEntry(): Collection
    {
        return $this->shipmentRepo->getDtShipEntry();
    }

    public function getActiveFgProduct(): Collection
    {
        return $this->shipmentRepo->getActiveFgProduct();
    }

    public function getWipMaterialByFgProduct(array $data): Collection
    {
        return $this->shipmentRepo->getWipMaterialByFgProduct($data);
    }

    public function getActiveBatchProduct(array $data): Collection
    {
        return $this->shipmentRepo->getActiveBatchProduct($data);
    }

    public function store(string $user, array $data): array
    {
        return $this->shipmentRepo->store($user, $data);
    }

    public function cancel(string $user, array $data): array
    {
        return $this->shipmentRepo->cancel($user, $data);
    }

    public function updateSo(string $user, array $data): array
    {
        return $this->shipmentRepo->updateSo($user, $data);
    }

    public function generateTraceNo(int $plantId): string
    {
        return $this->shipmentRepo->generateTraceNo($plantId);
    }

    public function getShipmentBatchPackaging(array $data): Collection
    {
        return $this->shipmentRepo->getShipmentBatchPackaging($data);
    }

    public function getPreparationRecord(array $data): Collection
    {
        return $this->shipmentRepo->getPreparationRecord($data);
    }

    public function getLabel(array $data): Collection
    {
        return $this->shipmentRepo->getLabel($data);
    }

    public function getSpecialLabel(array $data): Collection
    {
        return $this->shipmentRepo->getSpecialLabel($data);
    }

    public function getCustomerMark(array $data): Collection
    {
        return $this->shipmentRepo->getCustomerMark($data);
    }

    public function getDatShipment(array $data): array
    {
        return $this->shipmentRepo->getDatShipment($data);
    }

    public function getDatSoAllocation(array $data): array
    {
        return $this->shipmentRepo->getDatSoAllocation($data);
    }
}
