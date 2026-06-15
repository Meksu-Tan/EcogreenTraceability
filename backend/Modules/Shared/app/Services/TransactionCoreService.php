<?php declare(strict_types=1);

namespace Modules\Shared\Services;

use Illuminate\Support\Facades\DB;
use Modules\Shared\Traits\TransactionLoggerTrait;
use Modules\Shared\Constants\TransactionResponseCode;

class TransactionCoreService
{
    use TransactionLoggerTrait;

    protected string $connection = 'eudr_ts';

    /**
     * Create or update a material document.
     */
    public function createMaterialDocument(string $user, int $idTraceHead, ?string $materialDoc, string $mode): array
    {
        if ($mode === 'ADD') {
            DB::connection($this->connection)->insert(
                'INSERT INTO t_material_document (id_trace_head, material_document, created_by)
                 VALUES (?, ?, ?)',
                [$idTraceHead, $materialDoc, $user]
            );

            $id = DB::connection($this->connection)->select(
                'SELECT id_matdoc FROM t_material_document ORDER BY id_matdoc DESC LIMIT 1'
            );

            $this->logTransaction('T_MATERIAL_DOCUMENT', 'ADD',
                'ID: ' . $id[0]->id_matdoc . ' | IDTRACEHEAD: ' . $idTraceHead . ' / DOC_NO: ' . $materialDoc,
                $user);

            return ['response' => TransactionResponseCode::SUCCESS];
        }

        $dat = DB::connection($this->connection)->select(
            'SELECT id_matdoc, material_document FROM t_material_document WHERE id_trace_head = ?',
            [$idTraceHead]
        );

        if (empty($dat)) {
            return ['response' => TransactionResponseCode::GENERIC_FAILURE];
        }

        $id_matdoc = $dat[0]->id_matdoc;
        $old_materialDoc = $dat[0]->material_document;

        DB::connection($this->connection)->update(
            'UPDATE t_material_document SET material_document = ?, updated_by = ? WHERE id_trace_head = ?',
            [$materialDoc, $user, $idTraceHead]
        );

        $this->logTransaction('T_MATERIAL_DOCUMENT', 'UPDATE',
            'ID: ' . $id_matdoc . ' | IDTRACEHEAD: ' . $idTraceHead . ' / DOC_NO: ' . $old_materialDoc . ' >>> ' . $materialDoc,
            $user);

        return ['response' => TransactionResponseCode::SUCCESS];
    }

    /**
     * Update sub-tanks for an entry.
     */
    public function updateEntrySubTank(string $user, int $idHead, array $tails): array
    {
        if (empty($tails)) {
            return ['response' => TransactionResponseCode::GENERIC_FAILURE, 'message' => 'INVALID SUBTANK DATA'];
        }

        $jsonTails = json_encode(array_values(array_unique($tails)));

        $row = DB::connection($this->connection)->selectOne(
            'SELECT trace_no FROM t_balance_header WHERE id_balance_head = ? AND status = 1',
            [$idHead]
        );

        if (!$row) {
            return ['response' => TransactionResponseCode::GENERIC_FAILURE, 'message' => 'BALANCE HEAD NOT FOUND'];
        }

        DB::connection($this->connection)->update(
            'UPDATE t_balance_header SET updated_by = ?, id_sloc_tail = ? WHERE id_balance_head = ?',
            [$user, $jsonTails, $idHead]
        );
        DB::connection($this->connection)->update(
            'UPDATE t_trace_header SET updated_by = ?, id_sloc_tail = ? WHERE id_balance_head = ?',
            [$user, $jsonTails, $idHead]
        );
        DB::connection($this->connection)->update(
            'UPDATE t_balance_detail SET updated_by = ?, id_sloc_tail = ? WHERE id_balance_head = ?',
            [$user, $jsonTails, $idHead]
        );

        $this->logTransaction('T_BALANCE_HEAD', 'UPDATE_SUBTANK',
            'IDHEAD: ' . $idHead . ' | TRACE: ' . $row->trace_no . ' | SUBTANKS: ' . implode(',', $tails),
            $user);

        return ['response' => TransactionResponseCode::SUCCESS];
    }
}
