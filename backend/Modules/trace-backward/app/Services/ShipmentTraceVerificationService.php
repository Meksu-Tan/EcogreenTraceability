<?php
declare(strict_types=1);
namespace Modules\TraceBackward\Services;

use Modules\TraceBackward\Repositories\Contracts\TraceBackwardRepositoryInterface;
use Modules\TraceBackward\Services\Contracts\ShipmentTraceVerificationServiceInterface;
use Modules\TsShipment\Services\Contracts\ShipmentServiceInterface;

class ShipmentTraceVerificationService implements ShipmentTraceVerificationServiceInterface
{
    public function __construct(
        protected TraceBackwardRepositoryInterface $traceBackwardRepository,
        protected ShipmentServiceInterface $shipmentService,
    ) {}

    public function verifyBySoNo(string $soNo): array
    {
        $shipment = $this->traceBackwardRepository->findShipmentBySo($soNo);
        return $shipment ? $this->buildVerification($shipment) : ['found' => false];
    }

    public function verifyByTraceNo(string $traceNo): array
    {
        $shipment = $this->traceBackwardRepository->findShipmentByTraceNo($traceNo);
        return $shipment ? $this->buildVerification($shipment) : ['found' => false];
    }

    private function buildVerification(object $shipment): array
    {
        $chain = $this->traceBackwardRepository->backwardTrace((string) $shipment->trace_no);
        $packaging = $this->shipmentService->getShipmentBatchPackaging(['batchNo' => $shipment->batch_no]);
        $sapOverview = $this->shipmentService->getDatShipment([
            'soNo' => $shipment->so_no,
            'batchNo' => $shipment->batch_no,
        ]);

        return [
            'found' => true,
            'so_no' => $shipment->so_no,
            'trace_no' => $shipment->trace_no,
            'batch_no' => $shipment->batch_no,
            'backward_trace' => $chain,
            'batch_packaging_detail' => $packaging,
            'shipment_overview' => $sapOverview,
        ];
    }
}
