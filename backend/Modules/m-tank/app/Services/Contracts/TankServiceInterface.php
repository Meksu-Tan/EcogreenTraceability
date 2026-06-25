<?php
declare(strict_types=1);
namespace Modules\Tank\Services\Contracts;

interface TankServiceInterface
{
    public function listTanks(): array;
    public function storeTank(array $data): array;
    public function updateTank(int $id, array $data): array;
    public function deactivateTank(int $id, string $user): array;
    public function activateTank(int $id, string $user): array;
    public function syncFromExternal(string $user): array;
    public function getLastSyncInfo(): array;
}