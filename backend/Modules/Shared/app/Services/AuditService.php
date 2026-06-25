<?php
declare(strict_types=1);
namespace Modules\Shared\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Services\Contracts\AuditServiceInterface;

class AuditService implements AuditServiceInterface
{
    protected string $connection = 'eudr_ts';

    /**
     * Log a transaction to log_transactions table.
     *
     * @param string $module Module name (e.g., 'TRANSFER', 'WIP', 'BLENDING')
     * @param string $type Operation type (e.g., 'ADD', 'UPDATE', 'DELETE', 'APPROVE', 'REJECT')
     * @param string $description Human-readable description
     * @param string|null $user User performing the operation
     * @param array $metadata Additional metadata (optional)
     * @return bool
     */
    public static function log(
        string $module,
        string $type,
        string $description,
        ?string $user = null,
        array $metadata = []
    ): bool {
        try {
            $user = $user ?? auth()->user()?->name ?? 'system';

            DB::connection('eudr_ts')->insert(
                'INSERT INTO log_transactions (log_module, log_type, log_description, created_by)
                 VALUES (?, ?, ?, ?)',
                [$module, $type, $description, $user]
            );

            // Log metadata if provided (stored in extended description)
            if (!empty($metadata)) {
                Log::channel('audit')->info('AUDIT', [
                    'module' => $module,
                    'type' => $type,
                    'user' => $user,
                    'metadata' => $metadata,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('AuditService::log failed', [
                'module' => $module,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Log transfer entry operation.
     */
    public static function logTransfer(
        string $action,
        array $data,
        string $user,
        int $response = 1
    ): void {
        $actionLabel = match ($action) {
            'CREATE' => 'Transfer Created',
            'UPDATE' => 'Transfer Updated',
            'DELETE' => 'Transfer Deactivated',
            'APPROVE' => 'Transfer Approved',
            'REJECT' => 'Transfer Rejected',
            'SUBMIT' => 'Transfer Submitted for Approval',
            default => $action,
        };

        $details = [
            'entry_no' => $data['entry_no'] ?? null,
            'material' => $data['id_material'] ?? null,
            'qty' => $data['trf_qty'] ?? null,
            'source' => $data['source_sloc'] ?? null,
            'destination' => $data['trf_sloc'] ?? null,
            'response_code' => $response,
        ];

        $description = sprintf(
            '%s | Entry: %s | Material: %s | Qty: %s | %s >> %s | Response: %d',
            $actionLabel,
            $details['entry_no'] ?? 'N/A',
            $details['material'] ?? 'N/A',
            $details['qty'] ?? 'N/A',
            $details['source'] ?? 'N/A',
            $details['destination'] ?? 'N/A',
            $response
        );

        self::log('TRANSFER', $action, $description, $user, $details);
    }

    /**
     * Log WIP entry operation.
     */
    public static function logWip(string $action, array $data, string $user, int $response = 1): void
    {
        $actionLabel = match ($action) {
            'CREATE' => 'WIP Entry Created',
            'UPDATE' => 'WIP Entry Updated',
            'DELETE' => 'WIP Entry Deleted',
            'FEED' => 'WIP Feed Executed',
            'RUNDOWN' => 'WIP Rundown Executed',
            default => $action,
        };

        $details = [
            'entry_no' => $data['entry_no'] ?? null,
            'section' => $data['section_id'] ?? null,
            'qty' => $data['qty'] ?? null,
            'response_code' => $response,
        ];

        $description = sprintf(
            '%s | Entry: %s | Section: %s | Qty: %s | Response: %d',
            $actionLabel,
            $details['entry_no'] ?? 'N/A',
            $details['section'] ?? 'N/A',
            $details['qty'] ?? 'N/A',
            $response
        );

        self::log('WIP', $action, $description, $user, $details);
    }

    /**
     * Log blending operation.
     */
    public static function logBlending(string $action, array $data, string $user, int $response = 1): void
    {
        $actionLabel = match ($action) {
            'CREATE' => 'Blending Created',
            'UPDATE' => 'Blending Updated',
            'DELETE' => 'Blending Deleted',
            default => $action,
        };

        $details = [
            'entry_no' => $data['entry_no'] ?? null,
            'material' => $data['id_material'] ?? null,
            'qty' => $data['qty'] ?? null,
            'response_code' => $response,
        ];

        $description = sprintf(
            '%s | Entry: %s | Material: %s | Qty: %s | Response: %d',
            $actionLabel,
            $details['entry_no'] ?? 'N/A',
            $details['material'] ?? 'N/A',
            $details['qty'] ?? 'N/A',
            $response
        );

        self::log('BLENDING', $action, $description, $user, $details);
    }

    /**
     * Log adjustment operation.
     */
    public static function logAdjustment(
        string $action,
        array $data,
        string $user,
        int $response = 1
    ): void {
        $actionLabel = match ($action) {
            'CREATE' => 'Adjustment Created',
            'UPDATE' => 'Adjustment Updated',
            'APPROVE' => 'Adjustment Approved',
            'REJECT' => 'Adjustment Rejected',
            default => $action,
        };

        $details = [
            'adj_no' => $data['adj_no'] ?? $data['entry_no'] ?? null,
            'material' => $data['id_material'] ?? null,
            'qty' => $data['qty'] ?? null,
            'before' => $data['before_adjust'] ?? null,
            'after' => $data['after_adjust'] ?? null,
            'response_code' => $response,
        ];

        $description = sprintf(
            '%s | AdjNo: %s | Material: %s | Qty: %s | Before: %s | After: %s | Response: %d',
            $actionLabel,
            $details['adj_no'] ?? 'N/A',
            $details['material'] ?? 'N/A',
            $details['qty'] ?? 'N/A',
            $details['before'] ?? 'N/A',
            $details['after'] ?? 'N/A',
            $response
        );

        self::log('ADJUSTMENT', $action, $description, $user, $details);
    }

    /**
     * Log raw material entry operation.
     */
    public static function logRawMaterial(string $action, array $data, string $user, int $response = 1): void
    {
        $actionLabel = match ($action) {
            'CREATE' => 'Raw Material Entry Created',
            'UPDATE' => 'Raw Material Entry Updated',
            'DELETE' => 'Raw Material Entry Deleted',
            default => $action,
        };

        $details = [
            'entry_no' => $data['entry_no'] ?? null,
            'material' => $data['id_material'] ?? null,
            'supplier' => $data['id_supplier'] ?? null,
            'qty' => $data['qty'] ?? null,
            'response_code' => $response,
        ];

        $description = sprintf(
            '%s | Entry: %s | Material: %s | Supplier: %s | Qty: %s | Response: %d',
            $actionLabel,
            $details['entry_no'] ?? 'N/A',
            $details['material'] ?? 'N/A',
            $details['supplier'] ?? 'N/A',
            $details['qty'] ?? 'N/A',
            $response
        );

        self::log('RAW_MATERIAL', $action, $description, $user, $details);
    }

    /**
     * Log period lock/unlock operation.
     */
    public static function logPeriodLock(string $action, string $date, string $user, int $response = 1): void
    {
        $actionLabel = match ($action) {
            'LOCK' => 'Period Locked',
            'UNLOCK' => 'Period Unlocked',
            default => $action,
        };

        $description = sprintf(
            '%s | Period: %s | User: %s | Response: %d',
            $actionLabel,
            $date,
            $user,
            $response
        );

        self::log('PERIOD_LOCK', $action, $description, $user, [
            'period' => $date,
            'response_code' => $response,
        ]);
    }

    /**
     * Get audit logs with filters.
     */
    public static function getLogs(array $filters = [], int $limit = 100): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['module'])) {
            $where[] = 'log_module = ?';
            $params[] = $filters['module'];
        }

        if (!empty($filters['type'])) {
            $where[] = 'log_type = ?';
            $params[] = $filters['type'];
        }

        if (!empty($filters['user'])) {
            $where[] = 'created_by = ?';
            $params[] = $filters['user'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= ?';
            $params[] = $filters['date_to'];
        }

        $sql = sprintf(
            'SELECT * FROM log_transactions WHERE %s ORDER BY created_at DESC LIMIT ?',
            implode(' AND ', $where)
        );

        $params[] = $limit;

        try {
            return DB::connection('eudr_ts')->select($sql, $params);
        } catch (\Exception $e) {
            Log::error('AuditService::getLogs failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get audit summary by module and type.
     */
    public static function getSummary(string $dateFrom, string $dateTo): array
    {
        try {
            return DB::connection('eudr_ts')->select(
                'SELECT log_module, log_type, COUNT(*) as total
                   FROM log_transactions
                  WHERE created_at BETWEEN ? AND ?
                  GROUP BY log_module, log_type
                  ORDER BY log_module, log_type',
                [$dateFrom, $dateTo]
            );
        } catch (\Exception $e) {
            Log::error('AuditService::getSummary failed', ['error' => $e->getMessage()]);
            return [];
        }
    }
}