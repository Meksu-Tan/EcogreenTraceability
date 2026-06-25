<?php
declare(strict_types=1);
namespace Modules\Shared\Services\Contracts;

interface AuditServiceInterface
{
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
    ): bool;

    /**
     * Log transfer entry operation.
     */
    public static function logTransfer(
        string $action,
        array $data,
        string $user,
        int $response = 1
    ): void;

    /**
     * Log WIP entry operation.
     */
    public static function logWip(string $action, array $data, string $user, int $response = 1): void;

    /**
     * Log blending operation.
     */
    public static function logBlending(string $action, array $data, string $user, int $response = 1): void;

    /**
     * Log adjustment operation.
     */
    public static function logAdjustment(
        string $action,
        array $data,
        string $user,
        int $response = 1
    ): void;

    /**
     * Log raw material entry operation.
     */
    public static function logRawMaterial(string $action, array $data, string $user, int $response = 1): void;

    /**
     * Log period lock/unlock operation.
     */
    public static function logPeriodLock(string $action, string $date, string $user, int $response = 1): void;

    /**
     * Get audit logs with filters.
     */
    public static function getLogs(array $filters = [], int $limit = 100): array;

    /**
     * Get audit summary by module and type.
     */
    public static function getSummary(string $dateFrom, string $dateTo): array;
}
