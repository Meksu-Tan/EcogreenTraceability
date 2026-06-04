<?php declare(strict_types=1);
namespace Modules\Material\Services\Contracts;

interface MaterialServiceInterface
{
    public function listMaterials(?string $type = null): array;
    public function storeMaterial(array $data): array;
    public function updateMaterial(int $id, array $data): array;
    public function deactivateMaterial(int $id, string $user): array;
    public function activateMaterial(int $id, string $user): array;
    public function listPackagings(): array;
    public function storePackaging(array $data): array;
    public function updatePackaging(int $id, array $data): array;
    public function deactivatePackaging(int $id, string $user): array;
    public function activatePackaging(int $id, string $user): array;
    public function getActiveSourceProducts(): array;
}