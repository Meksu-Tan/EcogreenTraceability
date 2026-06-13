<?php declare(strict_types=1);

namespace Modules\TsRaw\Repositories\Traits;

use Illuminate\Support\Facades\DB;

trait RmEntrySupplierTrait
{
    public function searchSuppliers(string $query): array
    {
        return DB::connection('eudr_ts')->select(
            "SELECT id_supplier, code, description
               FROM m_supplier
              WHERE status = '1'
                AND (code LIKE ? OR description LIKE ?)
              ORDER BY code
              LIMIT 20",
            ['%' . $query . '%', '%' . $query . '%']
        );
    }

    public function addSupplierTemp(array $data, string $user): object
    {
        $requiredKeys = ['entry_no', 'id_material', 'qty'];
        foreach ($requiredKeys as $key) {
            if (!isset($data[$key])) {
                throw new \Exception("Required field '{$key}' is missing");
            }
        }

        $idManufacturer = null;
        if (!empty($data['id_manufacturer'])) {
            $val = $data['id_manufacturer'];
            if (is_numeric($val)) {
                $idManufacturer = (int) $val;
            } else {
                $existing = DB::connection('eudr_ts')->table('m_manufacturer')
                    ->where('description', $val)
                    ->orWhere('code', $val)
                    ->first();
                if ($existing) {
                    $idManufacturer = $existing->id_manufacturer;
                } else {
                    $baseCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $val));
                    $baseCode = substr($baseCode, 0, 40);
                    if (empty($baseCode)) {
                        $baseCode = 'MFG';
                    }
                    $code = $baseCode;
                    $counter = 1;
                    while (DB::connection('eudr_ts')->table('m_manufacturer')->where('code', $code)->exists()) {
                        $code = substr($baseCode, 0, 35) . '-' . $counter;
                        $counter++;
                    }

                    $idManufacturer = DB::connection('eudr_ts')->table('m_manufacturer')->insertGetId([
                        'code' => $code,
                        'description' => $val,
                        'status' => 1,
                        'created_by' => $user,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $insertData = [
            'entry_no' => $data['entry_no'],
            'id_supplier' => $data['id_supplier'] ?? null,
            'id_material' => $data['id_material'],
            'id_manufacturer' => $idManufacturer,
            'id_plant' => $data['id_plant'],
            'qty' => $data['qty'],
            'batch_sap' => $data['batch_sap'] ?? null,
            'status' => 1,
            'created_by' => $user,
        ];

        if (isset($data['id_tank'])) {
            $insertData['id_tank'] = $data['id_tank'];
        }

        $result = DB::connection('eudr_ts')->table('t_balance_temporary')->insertGetId($insertData);

        return (object) ['id_balance_temp' => $result];
    }

    public function getSupplierList(string $entryNo): array
    {
        $results = DB::connection('eudr_ts')->select(
            "SELECT a.id_balance_temp, a.id_supplier, a.id_material, a.id_manufacturer, a.batch_sap, a.qty,
                    b.code AS supplier_code, b.description AS supplier_name,
                    c.code AS material_code,
                    d.code AS manufacturer_code, d.description AS manufacturer_name
               FROM t_balance_temporary a
               LEFT JOIN m_supplier b ON a.id_supplier = b.id_supplier
               LEFT JOIN m_material c ON a.id_material = c.id_material
               LEFT JOIN m_manufacturer d ON a.id_manufacturer = d.id_manufacturer
              WHERE a.entry_no = ? AND a.status = 1
              ORDER BY a.id_balance_temp",
            [$entryNo]
        );

        return array_map(function ($item) {
            return [
                'id' => $item->id_balance_temp,
                'supplier' => $item->supplier_code ? ($item->supplier_code . ' :: ' . $item->supplier_name) : 'N/A',
                'material' => $item->material_code ?? 'N/A',
                'manufacturer' => $item->manufacturer_code ? ($item->manufacturer_code . ' :: ' . $item->manufacturer_name) : 'N/A',
                'batch_sap' => $item->batch_sap ?? 'N/A',
                'qty' => number_format($item->qty, 3),
            ];
        }, $results);
    }

    public function deleteSupplierTemp(int $id, string $user): bool
    {
        return (bool) DB::connection('eudr_ts')->table('t_balance_temporary')
            ->where('id_balance_temp', $id)
            ->update(['status' => 0, 'updated_by' => $user]);
    }

    public function getTotalQtyTemp(string $entryNo): float
    {
        $result = DB::connection('eudr_ts')->select(
            'SELECT COALESCE(SUM(qty), 0) AS total FROM t_balance_temporary WHERE entry_no = ? AND status = 1',
            [$entryNo]
        );
        return floatval($result[0]->total ?? 0);
    }

    public function getTempData(string $entryNo): array
    {
        return DB::connection('eudr_ts')->select(
            'SELECT id_supplier, qty AS qty_tail, batch_sap, id_material, id_manufacturer
               FROM t_balance_temporary
              WHERE entry_no = ? AND status = 1',
            [$entryNo]
        );
    }

    public function clearTempData(string $entryNo, string $user): bool
    {
        return (bool) DB::connection('eudr_ts')->table('t_balance_temporary')
            ->where('entry_no', $entryNo)
            ->update(['status' => 0, 'updated_by' => $user]);
    }

    public function generateBatchCode($supplierId): ?string
    {
        if (is_array($supplierId)) {
            $supplierId = isset($supplierId['id']) ? (int) $supplierId['id'] : 0;
        } else {
            $supplierId = (int) $supplierId;
        }

        if (empty($supplierId)) {
            return null;
        }

        $datSeq = DB::connection('eudr_ts')->select(
            'SELECT a.seq_no
               FROM (SELECT LPAD(SUBSTRING(a.batch_sap,7,2) + 1, 2,0) AS seq_no
                       FROM t_balance_detail a
                       LEFT JOIN t_balance_header b ON a.id_balance_head = b.id_balance_head
                      WHERE a.status = 1
                        AND SUBSTRING(a.batch_sap,1,6) = DATE_FORMAT(NOW(), "%y%m%d")
                        AND SUBSTRING(b.trace_no,1,1) = 1
                      ORDER BY SUBSTRING(a.batch_sap,1,8) DESC
                      LIMIT 1) a
               UNION ALL SELECT "01" AS seq_no LIMIT 1',
            []
        );
        $seqNo = $datSeq[0]->seq_no ?? '01';

        $result = DB::connection('eudr_ts')->select(
            'SELECT CONCAT(DATE_FORMAT(NOW(), "%y%m%d"), ?, "-", UCASE(a.batch_code)) AS batchCode
               FROM m_supplier a
              WHERE a.status = 1 AND a.id_supplier = ?',
            [$seqNo, $supplierId]
        );

        return $result[0]->batchCode ?? null;
    }
}
