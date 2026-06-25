<?php
declare(strict_types=1);
namespace Modules\Material\Services;

use Illuminate\Support\Facades\DB;
use Modules\Material\Repositories\Contracts\MaterialRepositoryInterface;
use Modules\Material\Services\Contracts\MaterialServiceInterface;

class MaterialService implements MaterialServiceInterface
{
    public function __construct(
        protected MaterialRepositoryInterface $materialRepo
    ) {}

    public function listMaterials(?string $type = null): array
    {
        return $this->materialRepo->getAll($type);
    }

    public function storeMaterial(array $data): array
    {
        $created = $this->materialRepo->create($data);
        if ($created === false) {
            return ['status' => 0, 'message' => 'Material code already exists'];
        }
        return ['status' => 1, 'message' => 'Material created successfully'];
    }

    public function updateMaterial(int $id, array $data): array
    {
        $updated = $this->materialRepo->update($id, $data);
        if (!$updated) {
            return ['status' => 0, 'message' => 'Failed to update material'];
        }
        return ['status' => 1, 'message' => 'Material updated successfully'];
    }

    public function deactivateMaterial(int $id, string $user): array
    {
        $result = $this->materialRepo->deactivate($id, $user);
        return $result
            ? ['status' => 1, 'message' => 'Material deactivated']
            : ['status' => 0, 'message' => 'Failed to deactivate material'];
    }

    public function activateMaterial(int $id, string $user): array
    {
        $result = $this->materialRepo->activate($id, $user);
        return $result
            ? ['status' => 1, 'message' => 'Material activated']
            : ['status' => 0, 'message' => 'Failed to activate material'];
    }

    // Packaging
    public function listPackagings(): array
    {
        return $this->materialRepo->getAllPackagings();
    }

    public function storePackaging(array $data): array
    {
        $created = $this->materialRepo->createPackaging($data);
        if ($created === false) {
            return ['status' => 0, 'message' => 'Packaging code already exists'];
        }
        return ['status' => 1, 'message' => 'Material packaging created successfully'];
    }

    public function updatePackaging(int $id, array $data): array
    {
        $updated = $this->materialRepo->updatePackaging($id, $data);
        return $updated
            ? ['status' => 1, 'message' => 'Packaging updated successfully']
            : ['status' => 0, 'message' => 'Failed to update packaging'];
    }

    public function deactivatePackaging(int $id, string $user): array
    {
        $result = $this->materialRepo->deactivatePackaging($id, $user);
        return $result
            ? ['status' => 1, 'message' => 'Packaging deactivated']
            : ['status' => 0, 'message' => 'Failed to deactivate packaging'];
    }

    public function activatePackaging(int $id, string $user): array
    {
        $result = $this->materialRepo->activatePackaging($id, $user);
        return $result
            ? ['status' => 1, 'message' => 'Packaging activated']
            : ['status' => 0, 'message' => 'Failed to activate packaging'];
    }

    public function getActiveSourceProducts(): array
    {
        return $this->materialRepo->getActiveSourceProducts();
    }

    public function fetchBalance(int $idPlant, int $idMaterial): array
    {
        $plantCode = DB::connection()
            ->table('m_plant')
            ->where('id_plant', $idPlant)
            ->value('code_3');

        if (!$plantCode) {
            return ['status' => 0, 'message' => 'Plant not found', 'data' => ['qty' => 0]];
        }

        $qty = DB::connection('eudr_ts')
            ->table('t_balance_header')
            ->where('id_material', $idMaterial)
            ->where('id_plant', $plantCode)
            ->where('status', 1)
            ->sum('qty');

        return ['status' => 1, 'data' => ['qty' => round((float) $qty, 3)]];
    }
}
