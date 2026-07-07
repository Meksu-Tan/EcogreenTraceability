<?php

declare(strict_types=1);

namespace Modules\TraceBackward\Services\Contracts;

interface ShipmentTraceVerificationServiceInterface
{
    public function verifyBySoNo(string $soNo): array;

    public function verifyByTraceNo(string $traceNo): array;
}
