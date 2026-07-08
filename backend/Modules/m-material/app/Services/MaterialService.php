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
        if (! $updated) {
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

    public function fetchBalance(int $idPlant, int $idMaterial, ?int $idSloc = null): array
    {
        $plantCode = DB::connection()
            ->table('m_plant')
            ->where('id_plant', $idPlant)
            ->value('code_3');

        if (! $plantCode) {
            return ['status' => 0, 'message' => 'Plant not found', 'data' => ['qty' => 0]];
        }

        $materialType = DB::connection()
            ->table('m_material')
            ->where('id_material', $idMaterial)
            ->value('type');

        // ponytail: use t_trace_header in/out like legacy + stock on hand
        // t_balance_header.qty is remaining balance (decremented by outflows), not total stock
        // filter via m_sloc.id_plant since t_balance_header.id_plant is unreliable
        $query = "SELECT ROUND(CAST(COALESCE(SUM(th.in_qty) - SUM(th.out_qty), 0) AS numeric), 3) AS total
               FROM t_trace_header th
               JOIN t_balance_header bh ON th.id_balance_head = bh.id_balance_head
               JOIN m_sloc ms ON ms.id_sloc = bh.id_sloc
              WHERE th.status = 1
                AND th.id_material = ?
                AND ms.id_plant = ?
                AND (
                    (? = 'WIP' AND ms.code_3 = 'WIP') OR
                    (? = 'PRD' AND ms.code_3 = 'PRD') OR
                    (? = 'RM' AND ms.code_3 IN ('STORAGE', 'FEED'))
                )
                AND SUBSTRING(bh.trace_no, 1, 1) IN ('1','2','3','7','8','9')";

        $bindings = [$idMaterial, $plantCode, $materialType, $materialType, $materialType];

        if ($idSloc !== null) {
            $slocCode3 = DB::connection('eudr_ts')
                ->table('m_sloc')
                ->where('id_sloc', $idSloc)
                ->value('code_3');
            if ($slocCode3 !== null && $slocCode3 !== '') {
                $query .= " AND ms.code_3 = ?";
                $bindings[] = $slocCode3;
            } else {
                $query .= " AND ms.id_sloc = ?";
                $bindings[] = $idSloc;
            }
        }

        $result = DB::connection('eudr_ts')->select($query, $bindings);

        $qty = (float) ($result[0]->total ?? 0);

        return ['status' => 1, 'data' => ['qty' => $qty]];
    }
}
