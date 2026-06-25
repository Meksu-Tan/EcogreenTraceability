<?php
declare(strict_types=1);
namespace Modules\Shared\Services\Contracts;

interface PeriodLockServiceInterface
{
    /**
     * Check if a period is locked.
     *
     * @param string $date Date in Y-m-d format
     * @return bool True if period is locked
     */
    public static function isLocked(string $date): bool;

    /**
     * Lock a period.
     *
     * @param string $date Date in Y-m-d format
     * @param string $user User performing the lock
     * @param string|null $reason Reason for locking
     * @return array ['response' => int, 'message' => string]
     */
    public static function lock(string $date, string $user, ?string $reason = null): array;

    /**
     * Unlock a period.
     *
     * @param string $date Date in Y-m-d format
     * @param string $user User performing the unlock
     * @return array ['response' => int, 'message' => string]
     */
    public static function unlock(string $date, string $user): array;

    /**
     * Get lock status for a date range.
     *
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @return array List of locked periods
     */
    public static function getLockedPeriods(string $startDate, string $endDate): array;
}
