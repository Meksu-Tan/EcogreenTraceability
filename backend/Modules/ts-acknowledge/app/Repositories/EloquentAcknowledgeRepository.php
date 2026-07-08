<?php

declare(strict_types=1);

namespace Modules\TsAcknowledge\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\TsAcknowledge\Models\TsAcknowledge;

class EloquentAcknowledgeRepository implements AcknowledgeRepositoryInterface
{
    public function getAcknowledgeData(string $plantCode, string $date = '', string $type = 'WIP'): array
    {
        $query = TsAcknowledge::where('plant_code', $plantCode)->where('type', $type);

        if ($date !== '') {
            $query->where('entry_date', $date);
        }

        return $query->get()->toArray();
    }

    public function getBalanceData(string $plantCode, string $prefix, int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;

        return DB::connection('eudr_ts')
            ->table('t_balance_header')
            ->join('m_material', 't_balance_header.id_material', '=', 'm_material.id_material')
            ->leftJoin('m_sloc', 't_balance_header.id_sloc', '=', 'm_sloc.id_sloc')
            ->where('t_balance_header.id_plant', $plantCode)
            ->where('t_balance_header.trace_no', 'like', $prefix.'%')
            ->where('t_balance_header.status', 1)
            ->select(
                't_balance_header.id_balance_head',
                't_balance_header.entry_date',
                't_balance_header.trace_no',
                't_balance_header.id_material',
                'm_material.description as material_name',
                't_balance_header.in_qty',
                't_balance_header.out_qty',
                'm_sloc.description as sloc_name',
                'm_sloc.id_sloc as sloc_id',
                't_balance_header.created_at',
                't_balance_header.created_by'
            )
            ->orderBy('t_balance_header.created_at', 'desc')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->toArray();
    }

    public function countBalanceData(string $plantCode, string $prefix): int
    {
        return DB::connection('eudr_ts')
            ->table('t_balance_header')
            ->where('t_balance_header.id_plant', $plantCode)
            ->where('t_balance_header.trace_no', 'like', $prefix.'%')
            ->where('t_balance_header.status', 1)
            ->count();
    }

    public function saveAcknowledgeData(array $data): object
    {
        $type = $data['type'] ?? 'WIP';
        $transactionId = $data['transaction_id'] ?? null;

        if ($type !== 'WIP' && $transactionId) {
            $record = TsAcknowledge::updateOrCreate(
                [
                    'plant_code' => $data['plant_code'],
                    'entry_date' => $data['entry_date'],
                    'type' => $type,
                    'transaction_id' => $transactionId,
                ],
                [
                    'trace_no' => $data['trace_no'] ?? null,
                    'material_name' => $data['material_name'] ?? null,
                    'source_name' => $data['source_name'] ?? null,
                    'mode_value' => $data['mode_value'] ?? null,
                    'eo_dls_qty' => $data['eo_dls_qty'] ?? null,
                    'dcs_qty' => $data['dcs_qty'] ?? null,
                    'keterangan' => $data['keterangan'] ?? null,
                    'qty_source' => $data['qty_source'] ?? null,
                    'created_by' => $data['created_by'] ?? null,
                    'updated_by' => $data['updated_by'] ?? null,
                ]
            );
        } else {
            $record = TsAcknowledge::updateOrCreate(
                [
                    'plant_code' => $data['plant_code'],
                    'entry_date' => $data['entry_date'],
                    'type' => $data['type'] ?? 'WIP',
                    'section_id' => $data['section_id'] ?? null,
                    'step_type' => $data['step_type'] ?? null,
                ],
                [
                    'transaction_id' => $data['transaction_id'] ?? null,
                    'trace_no' => $data['trace_no'] ?? null,
                    'material_name' => $data['material_name'] ?? null,
                    'source_name' => $data['source_name'] ?? null,
                    'mode_value' => $data['mode_value'] ?? null,
                    'eo_dls_qty' => $data['eo_dls_qty'] ?? null,
                    'dcs_qty' => $data['dcs_qty'] ?? null,
                    'keterangan' => $data['keterangan'] ?? null,
                    'qty_source' => $data['qty_source'] ?? null,
                    'created_by' => $data['created_by'] ?? null,
                    'updated_by' => $data['updated_by'] ?? null,
                ]
            );
        }

        return $record;
    }
}
