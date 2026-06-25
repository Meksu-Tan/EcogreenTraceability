<?php
declare(strict_types=1);
namespace Modules\Adjustment\Services\Contracts;

interface AdjustmentPeriodServiceInterface
{
    public function getPeriodHeaders(array $filters = []): array;
    public function getPeriodViewData(int $idHead): array;
    public function periodHeadersUpload(string $user, array $data, mixed $file): array;
    public function periodViewOnHand(string $user, int $idHead): array;
    public function periodViewAdjustment(string $user, int $idHead): array;
    public function periodHeaderLock(string $user, int $idHead): array;
    public function destroyAdjustmentPeriod(int $id, string $user): array;
    public function getLastAdjustmentRecord(mixed $plantId): array;
    public function storeAdjustmentWhx(string $user, array $data, mixed $plantId): array;
    public function adjustmentInitWhx(string $user, array $data, mixed $plantId): array;
    public function getAdjustStatus(?string $adjustNo, ?int $idAdjustHead): array;
}
