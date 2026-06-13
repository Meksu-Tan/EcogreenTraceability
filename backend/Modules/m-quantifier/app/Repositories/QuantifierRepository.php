<?php declare(strict_types=1);

namespace Modules\Quantifier\Repositories;

use Modules\Quantifier\Repositories\Contracts\QuantifierRepositoryInterface;
use Illuminate\Support\Facades\DB;

class QuantifierRepository implements QuantifierRepositoryInterface
{
    protected string $connection = 'eudr_ts';

    public function getQuantifierList(array $filters = []): array
    {
        $sql = 'SELECT a.id_reset, a.reset_date, a.flowmeter, a.remark, a.value,
                       a.status, a.created_by, a.created_at,
                       CASE a.status
                           WHEN 1 THEN "Active"
                           WHEN 0 THEN "Inactive"
                           ELSE "Unknown"
                       END AS status_desc,
                       a.updated_by, a.updated_at
                FROM t_reset_quantifier a';

        $conditions = [];
        $params = [];

        if (isset($filters['status'])) {
            $conditions[] = 'a.status = ?';
            $params[] = $filters['status'];
        }
        if (isset($filters['date_from'])) {
            $conditions[] = 'a.reset_date >= ?';
            $params[] = $filters['date_from'];
        }
        if (isset($filters['date_to'])) {
            $conditions[] = 'a.reset_date <= ?';
            $params[] = $filters['date_to'];
        }
        if (isset($filters['flowmeter'])) {
            $conditions[] = 'a.flowmeter LIKE ?';
            $params[] = '%' . $filters['flowmeter'] . '%';
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY a.reset_date DESC, a.id_reset DESC';

        if (isset($filters['limit'])) {
            $sql .= ' LIMIT ?';
            $params[] = (int) $filters['limit'];
            if (isset($filters['offset'])) {
                $sql .= ' OFFSET ?';
                $params[] = (int) $filters['offset'];
            }
        }

        return DB::connection($this->connection)->select($sql, $params);
    }

    public function getActiveFlowmeters(): array
    {
        $sql = 'SELECT flowmeter FROM (
                    SELECT qtf_feed AS flowmeter FROM m_material WHERE status = 1 AND qtf_feed LIKE "%FT%"
                    UNION
                    SELECT qtf_rundown AS flowmeter FROM m_material WHERE status = 1 AND qtf_rundown LIKE "%FT%"
                ) t GROUP BY flowmeter ORDER BY flowmeter ASC';

        return DB::connection($this->connection)->select($sql);
    }

    public function getQuantifierDetail(int $id): ?array
    {
        $result = DB::connection($this->connection)->selectOne(
            'SELECT a.*, b.description AS flowmeter_desc
               FROM t_reset_quantifier a
               LEFT JOIN m_material b ON (b.qtf_feed = a.flowmeter OR b.qtf_rundown = a.flowmeter)
              WHERE a.id_reset = ?',
            [$id]
        );
        return $result ? (array) $result : null;
    }

    public function createQuantifier(string $resetDate, string $flowmeter, float $value, string $remark, string $user): int
    {
        $id = DB::connection($this->connection)->table('t_reset_quantifier')->insertGetId([
            'reset_date' => $resetDate,
            'flowmeter' => $flowmeter,
            'value' => $value,
            'remark' => $remark,
            'status' => 1,
            'created_by' => $user,
        ]);

        DB::connection($this->connection)->table('log_transactions')->insert([
            'log_module' => 'T_RESET_QTY',
            'log_type' => 'ADD',
            'log_description' => 'ID: ' . $id . ' | DATE: ' . $resetDate
                . ' / FLOWMETER: ' . $flowmeter
                . ' / VALUE: ' . $value
                . ' / REMARK: ' . $remark . ' | Status: 1',
            'created_by' => $user,
        ]);

        return $id;
    }

    public function updateQuantifier(int $id, string $resetDate, string $flowmeter, float $value, string $remark, string $user): array
    {
        $old = DB::connection($this->connection)->selectOne(
            'SELECT reset_date, flowmeter, value, remark FROM t_reset_quantifier WHERE id_reset = ?',
            [$id]
        );
        if (!$old) {
            return ['response' => 0, 'message' => 'Quantifier not found'];
        }

        $affected = DB::connection($this->connection)->table('t_reset_quantifier')
            ->where('id_reset', $id)
            ->update([
                'reset_date' => $resetDate,
                'flowmeter' => $flowmeter,
                'value' => $value,
                'remark' => $remark,
                'updated_by' => $user,
            ]);

        DB::connection($this->connection)->table('log_transactions')->insert([
            'log_module' => 'T_RESET_QTY',
            'log_type' => 'UPDATE',
            'log_description' => 'ID: ' . $id
                . ' | DATE: ' . $old->reset_date . ' >> ' . $resetDate
                . ' / FLOWMETER: ' . $old->flowmeter . ' >> ' . $flowmeter
                . ' / VALUE: ' . $old->value . ' >> ' . $value
                . ' / REMARK: ' . $old->remark . ' >> ' . $remark . ' | Status: 1',
            'created_by' => $user,
        ]);

        return ['response' => $affected > 0 ? 1 : 0];
    }

    public function deactivateQuantifier(int $id, string $user): array
    {
        DB::connection($this->connection)->table('log_transactions')->insert([
            'log_module' => 'T_RESET_QTY',
            'log_type' => 'DE-ACTIVATE',
            'log_description' => 'Id: ' . $id . ' | Status: 1 >> 0',
            'created_by' => $user,
        ]);

        $affected = DB::connection($this->connection)->table('t_reset_quantifier')
            ->where('id_reset', $id)
            ->update(['status' => 0, 'updated_by' => $user]);

        return ['response' => $affected > 0 ? 1 : 0];
    }

    public function activateQuantifier(int $id, string $user): array
    {
        DB::connection($this->connection)->table('log_transactions')->insert([
            'log_module' => 'T_RESET_QTY',
            'log_type' => 'ACTIVATE',
            'log_description' => 'Id: ' . $id . ' | Status: 0 >> 1',
            'created_by' => $user,
        ]);

        $affected = DB::connection($this->connection)->table('t_reset_quantifier')
            ->where('id_reset', $id)
            ->update(['status' => 1, 'updated_by' => $user]);

        return ['response' => $affected > 0 ? 1 : 0];
    }
}
