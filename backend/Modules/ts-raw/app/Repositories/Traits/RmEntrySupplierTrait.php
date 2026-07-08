<?php

declare(strict_types=1);

namespace Modules\TsRaw\Repositories\Traits;

use Illuminate\Support\Facades\DB;
use Modules\Shared\Traits\DbCompatTrait;

trait RmEntrySupplierTrait
{
    use DbCompatTrait;

    public function searchSuppliers(string $query): array
    {
        return DB::connection('eudr_ts')->select(
            "SELECT id_supplier, code, description
               FROM m_supplier
              WHERE status = '1'
                AND (code LIKE ? OR description LIKE ?)
              ORDER BY code
              LIMIT 20",
            ['%'.$query.'%', '%'.$query.'%']
        );
    }

    public function addSupplierTemp(array $data, string $user): array
    {
        $mode = $data['mode'] ?? 'ADD';
        $entryNo = $data['entry_no'] ?? '';
        $idMaterial = (int) ($data['id_material'] ?? 0);
        $idSupplier = (int) ($data['id_supplier'] ?? 0);
        $qty = (float) str_replace(',', '', (string) ($data['qty'] ?? '0'));
        $batchSap = $data['batch_sap'] ?? '';
        $idTail = isset($data['idTail']) ? (int) $data['idTail'] : null;
        $idHead = isset($data['idHead']) ? (int) $data['idHead'] : null;

        /* UPDATE mode: modify committed t_balance_detail + t_trace_detail */
        if ($mode === 'UPDATE' && $idTail !== null) {
            $existing = DB::connection('eudr_ts')->selectOne(
                'SELECT id_supplier, qty FROM t_balance_detail WHERE id_balance_tail = ? AND status = 1',
                [$idTail]
            );

            if ($existing) {
                // Case 1: idTail found — update existing detail row
                $oldSupplier = (int) $existing->id_supplier;
                $oldQty = (float) $existing->qty;

                DB::connection('eudr_ts')->table('log_transactions')->insert([
                    'log_module' => 'T_BALANCE_TAIL',
                    'log_type' => 'UPDATE',
                    'log_description' => 'IDHEAD: '.($idHead ?? 0).' IDTAIL: '.$idTail.
                        ' | ID_SUPPLIER: '.$oldSupplier.' >>> '.$idSupplier.
                        ' / QTY: '.$oldQty.' >>> '.$qty.' | Status: 1',
                    'created_by' => $user,
                ]);

                DB::connection('eudr_ts')->update(
                    'UPDATE t_trace_detail SET id_supplier = ?, in_qty = ?, batch_sap = ?, updated_by = ?
                      WHERE id_balance_tail = ?',
                    [$idSupplier, $qty, $batchSap, $user, $idTail]
                );

                DB::connection('eudr_ts')->update(
                    'UPDATE t_balance_detail SET id_supplier = ?, qty = ?, init_qty = ?, batch_sap = ?, updated_by = ?
                      WHERE id_balance_tail = ?',
                    [$idSupplier, $qty, $qty, $batchSap, $user, $idTail]
                );

                $this->recalcBalanceHeaderQty($idHead, $user);

                return ['response' => 1];
            }

            // Case 2: idTail not found — check if supplier exists for this idHead
            if ($idHead !== null) {
                $existingBySupplier = DB::connection('eudr_ts')->selectOne(
                    'SELECT id_supplier, qty, id_balance_tail, batch_sap
                       FROM t_balance_detail
                      WHERE id_supplier = ? AND id_balance_head = ? AND status = 1',
                    [$idSupplier, $idHead]
                );

                if ($existingBySupplier) {
                    $oldQty = (float) $existingBySupplier->qty;
                    $foundTail = (int) $existingBySupplier->id_balance_tail;
                    $oldBatch = $existingBySupplier->batch_sap ?? '';

                    DB::connection('eudr_ts')->table('log_transactions')->insert([
                        'log_module' => 'T_BALANCE_TAIL',
                        'log_type' => 'UPDATE',
                        'log_description' => 'IDHEAD: '.$idHead.' IDTAIL: '.$foundTail.
                            ' | ID_SUPPLIER: '.$idSupplier.' / QTY: '.$oldQty.' >>> '.$qty.
                            ' / BATCH_SAP: '.$oldBatch.' >>> '.$batchSap.' | Status: 1',
                        'created_by' => $user,
                    ]);

                    DB::connection('eudr_ts')->update(
                        'UPDATE t_trace_detail SET in_qty = ?, batch_sap = ?, updated_by = ?
                          WHERE id_balance_tail = ?',
                        [$qty, $batchSap, $user, $foundTail]
                    );

                    DB::connection('eudr_ts')->update(
                        'UPDATE t_balance_detail SET qty = ?, init_qty = ?, batch_sap = ?, updated_by = ?
                          WHERE id_supplier = ? AND id_balance_head = ?',
                        [$qty, $qty, $batchSap, $user, $idSupplier, $idHead]
                    );

                    $this->recalcBalanceHeaderQty($idHead, $user);

                    return ['response' => 1];
                }

                // Case 3: insert new detail + trace rows
                $newTail = DB::connection('eudr_ts')->table('t_balance_detail')->insertGetId([
                    'id_balance_head' => $idHead,
                    'id_supplier' => $idSupplier,
                    'id_material' => $idMaterial,
                    'qty' => $qty,
                    'init_qty' => $qty,
                    'batch_sap' => $batchSap,
                    'created_by' => $user,
                    'updated_by' => $user,
                ], 'id_balance_tail');

                $traceHead = DB::connection('eudr_ts')->selectOne(
                    'SELECT id_trace_head FROM t_trace_header WHERE id_balance_head = ?',
                    [$idHead]
                );

                if ($traceHead) {
                    DB::connection('eudr_ts')->table('t_trace_detail')->insert([
                        'id_trace_head' => $traceHead->id_trace_head,
                        'id_balance_tail' => $newTail,
                        'id_supplier' => $idSupplier,
                        'id_material' => $idMaterial,
                        'in_qty' => $qty,
                        'batch_sap' => $batchSap,
                        'created_by' => $user,
                        'updated_by' => $user,
                    ]);
                }

                DB::connection('eudr_ts')->table('log_transactions')->insert([
                    'log_module' => 'T_BALANCE_TAIL',
                    'log_type' => 'UPDATE',
                    'log_description' => 'IDHEAD: '.$idHead.' IDTAIL: '.$newTail.
                        ' | ID_SUPPLIER: '.$idSupplier.' / QTY: '.$qty.
                        ' / BATCH_SAP: '.$batchSap.' | Status: 1',
                    'created_by' => $user,
                ]);

                $this->recalcBalanceHeaderQty($idHead, $user);

                return ['response' => 1];
            }

            return ['response' => 0];
        }

        /* ADD mode: insert into t_balance_temporary */
        $requiredKeys = ['entry_no', 'id_material', 'qty'];
        foreach ($requiredKeys as $key) {
            if (! isset($data[$key])) {
                throw new \Exception("Required field '{$key}' is missing");
            }
        }

        $idManufacturer = null;
        if (! empty($data['id_manufacturer'])) {
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
                        $code = substr($baseCode, 0, 35).'-'.$counter;
                        $counter++;
                    }

                    $idManufacturer = DB::connection('eudr_ts')->table('m_manufacturer')->insertGetId([
                        'code' => $code,
                        'description' => $val,
                        'status' => 1,
                        'created_by' => $user,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ], 'id_manufacturer');
                }
            }
        }

        $hasManufacturerCol = $this->columnExists('t_balance_temporary', 'id_manufacturer');

        $insertData = [
            'entry_no' => $data['entry_no'],
            'id_supplier' => $data['id_supplier'] ?? null,
            'id_material' => $data['id_material'],
            'id_plant' => $data['id_plant'],
            'qty' => $data['qty'],
            'batch_sap' => $data['batch_sap'] ?? null,
            'status' => 1,
            'created_by' => $user,
        ];

        if ($hasManufacturerCol) {
            $insertData['id_manufacturer'] = $idManufacturer;
        }

        if (isset($data['tf_number'])) {
            $insertData['tf_number'] = $data['tf_number'];
        }

        $result = DB::connection('eudr_ts')->table('t_balance_temporary')->insertGetId($insertData, 'id_balance_temp');

        return ['id_balance_temp' => $result];
    }

    public function getSupplierList(string $entryNo): array
    {
        $hasManufacturer = $this->columnExists('t_balance_temporary', 'id_manufacturer');

        $sql = 'SELECT a.id_balance_temp, a.id_supplier, a.id_material, a.batch_sap, a.qty,
                       b.code AS supplier_code, b.description AS supplier_name,
                       c.code AS material_code';

        if ($hasManufacturer) {
            $sql .= ', a.id_manufacturer,
                       d.description AS manufacturer_name';
        }

        $sql .= '  FROM t_balance_temporary a
                  LEFT JOIN m_supplier b ON a.id_supplier = b.id_supplier
                  LEFT JOIN m_material c ON a.id_material = c.id_material';

        if ($hasManufacturer) {
            $sql .= ' LEFT JOIN m_manufacturer d ON a.id_manufacturer = d.id_manufacturer';
        }

        $sql .= ' WHERE a.entry_no = ? AND a.status = 1 ORDER BY a.id_balance_temp';

        $results = DB::connection('eudr_ts')->select($sql, [$entryNo]);

        return array_map(function ($item) {
            return [
                'id' => $item->id_balance_temp,
                'supplier' => $item->supplier_code ? ($item->supplier_code.' :: '.$item->supplier_name) : 'N/A',
                'material' => $item->material_code ?? 'N/A',
                'manufacturer' => isset($item->manufacturer_name) && $item->manufacturer_name ? $item->manufacturer_name : 'N/A',
                'batch_sap' => $item->batch_sap ?? 'N/A',
                'qty' => (float) $item->qty,
            ];
        }, $results);
    }

    protected function columnExists(string $table, string $column): bool
    {
        try {
            $driver = DB::connection('eudr_ts')->getDriverName();
            if ($driver === 'pgsql') {
                $result = DB::connection('eudr_ts')->select(
                    'SELECT column_name FROM information_schema.columns WHERE table_name = ? AND column_name = ?',
                    [$table, $column]
                );

                return ! empty($result);
            }
            $result = DB::connection('eudr_ts')->select(
                "SHOW COLUMNS FROM {$table} WHERE Field = ?",
                [$column]
            );

            return ! empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteSupplierTemp(int $id, string $user): bool
    {
        return (bool) DB::connection('eudr_ts')->table('t_balance_temporary')
            ->where('id_balance_temp', $id)
            ->update(['status' => 0, 'updated_by' => $user, 'updated_at' => now()]);
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
            ->update(['status' => 0, 'updated_by' => $user, 'updated_at' => now()]);
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

        $dateFmt = $this->dbDateFormat($this->dbCurDate(), '%y%m%d');

        $datSeq = DB::connection('eudr_ts')->select(
            "SELECT a.seq_no
               FROM (SELECT LPAD(CAST(CAST(SUBSTRING(a.batch_sap,7,2) AS INTEGER) + 1 AS TEXT), 2, '0') AS seq_no
                       FROM t_balance_detail a
                       LEFT JOIN t_balance_header b ON a.id_balance_head = b.id_balance_head
                      WHERE a.status = 1
                        AND SUBSTRING(a.batch_sap,1,6) = {$dateFmt}
                        AND SUBSTRING(b.trace_no::text,1,1) = '1'
                      ORDER BY SUBSTRING(a.batch_sap,1,8) DESC
                      LIMIT 1) a
               UNION ALL SELECT '01' AS seq_no LIMIT 1",
            []
        );
        $seqNo = $datSeq[0]->seq_no ?? '01';

        $result = DB::connection('eudr_ts')->select(
            "SELECT CONCAT({$dateFmt}, CAST(? AS TEXT), '-', UPPER(a.batch_code)) AS batchCode
               FROM m_supplier a
              WHERE a.status = 1 AND a.id_supplier = ?",
            [$seqNo, $supplierId]
        );

        return $result[0]->batchcode ?? $result[0]->batchCode ?? null;
    }

    protected function recalcBalanceHeaderQty(?int $idHead, string $user): void
    {
        if ($idHead === null) {
            return;
        }

        $sum = DB::connection('eudr_ts')->selectOne(
            'SELECT COALESCE(SUM(init_qty), 0) AS qty FROM t_balance_detail WHERE id_balance_head = ? AND status = 1',
            [$idHead]
        );
        $newTotal = (float) ($sum->qty ?? 0);

        DB::connection('eudr_ts')->update(
            'UPDATE t_balance_header SET init_qty = ?, qty = ?, updated_by = ? WHERE id_balance_head = ?',
            [$newTotal, $newTotal, $user, $idHead]
        );

        DB::connection('eudr_ts')->update(
            'UPDATE t_trace_header SET in_qty = ?, updated_by = ? WHERE id_balance_head = ?',
            [$newTotal, $user, $idHead]
        );
    }
}
