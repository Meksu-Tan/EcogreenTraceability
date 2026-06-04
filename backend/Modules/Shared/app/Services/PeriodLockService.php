<?php declare(strict_types=1);

namespace Modules\Shared\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PeriodLockService
{
    /**
     * Check if a period is locked.
     * Uses m_period_lock table (with fallback to t_report_pspa_head.lock_status).
     *
     * @param string $date Date in Y-m-d format
     * @return bool True if period is locked
     */
    public static function isLocked(string $date): bool
    {
        $lockDateTime = new \DateTime($date);
        $lockYear = $lockDateTime->format('Y');
        $lockMonth = $lockDateTime->format('m');

        // Try m_period_lock first (primary table)
        try {
            $rows = DB::connection('eudr_ts')->select(
                'SELECT lock_status
                   FROM m_period_lock
                  WHERE status = 1
                    AND YEAR(period) = ?
                    AND MONTH(period) = ?',
                [$lockYear, $lockMonth]
            );

            if (!empty($rows) && ($rows[0]->lock_status ?? '0') === '1') {
                return true;
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // m_period_lock table doesn't exist, fallback to t_report_pspa_head
            Log::debug('PeriodLockService: m_period_lock not found, using fallback');
        }

        // Fallback: check t_report_pspa_head.lock_status
        try {
            $rows = DB::connection('eudr_ts')->select(
                'SELECT lock_status
                   FROM t_report_pspa_head
                  WHERE status = 1
                    AND YEAR(period) = ?
                    AND MONTH(period) = ?',
                [$lockYear, $lockMonth]
            );

            if (!empty($rows) && ($rows[0]->lock_status ?? '0') === '1') {
                return true;
            }
        } catch (\Illuminate\Database\QueryException $e) {
            Log::warning('PeriodLockService: Both tables not available');
        }

        return false;
    }

    /**
     * Lock a period.
     *
     * @param string $date Date in Y-m-d format
     * @param string $user User performing the lock
     * @param string|null $reason Reason for locking
     * @return array ['response' => int, 'message' => string]
     */
    public static function lock(string $date, string $user, ?string $reason = null): array
    {
        $lockDateTime = new \DateTime($date);
        $period = $lockDateTime->format('Y-m-01'); // First day of month

        try {
            $existing = DB::connection('eudr_ts')->select(
                'SELECT id, lock_status FROM m_period_lock
                 WHERE YEAR(period) = YEAR(?) AND MONTH(period) = MONTH(?) AND status = 1',
                [$period, $period]
            );

            if (!empty($existing)) {
                // Update existing
                if ($existing[0]->lock_status === '1') {
                    return ['response' => 2, 'message' => 'Period already locked'];
                }

                DB::connection('eudr_ts')->update(
                    'UPDATE m_period_lock SET lock_status = "1", locked_by = ?, locked_at = NOW(), reason = ?, updated_by = ?
                     WHERE id = ?',
                    [$user, $reason, $user, $existing[0]->id]
                );
            } else {
                // Insert new
                DB::connection('eudr_ts')->insert(
                    'INSERT INTO m_period_lock (period, lock_status, locked_by, locked_at, reason, created_by)
                     VALUES (?, "1", ?, NOW(), ?, ?)',
                    [$period, $user, $reason, $user]
                );
            }

            return ['response' => 1, 'message' => 'Period locked successfully'];
        } catch (\Exception $e) {
            Log::error('PeriodLockService::lock failed', ['error' => $e->getMessage()]);
            return ['response' => 0, 'message' => 'Failed to lock period: ' . $e->getMessage()];
        }
    }

    /**
     * Unlock a period.
     *
     * @param string $date Date in Y-m-d format
     * @param string $user User performing the unlock
     * @return array ['response' => int, 'message' => string]
     */
    public static function unlock(string $date, string $user): array
    {
        $lockDateTime = new \DateTime($date);
        $period = $lockDateTime->format('Y-m-01');

        try {
            $existing = DB::connection('eudr_ts')->select(
                'SELECT id, lock_status FROM m_period_lock
                 WHERE YEAR(period) = YEAR(?) AND MONTH(period) = MONTH(?) AND status = 1',
                [$period, $period]
            );

            if (empty($existing)) {
                return ['response' => 0, 'message' => 'Period not found'];
            }

            if ($existing[0]->lock_status === '0') {
                return ['response' => 2, 'message' => 'Period already unlocked'];
            }

            DB::connection('eudr_ts')->update(
                'UPDATE m_period_lock SET lock_status = "0", unlocked_by = ?, unlocked_at = NOW(), updated_by = ?
                 WHERE id = ?',
                [$user, $user, $existing[0]->id]
            );

            return ['response' => 1, 'message' => 'Period unlocked successfully'];
        } catch (\Exception $e) {
            Log::error('PeriodLockService::unlock failed', ['error' => $e->getMessage()]);
            return ['response' => 0, 'message' => 'Failed to unlock period: ' . $e->getMessage()];
        }
    }

    /**
     * Get lock status for a date range.
     *
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @return array List of locked periods
     */
    public static function getLockedPeriods(string $startDate, string $endDate): array
    {
        try {
            return DB::connection('eudr_ts')->select(
                'SELECT period, lock_status, locked_by, locked_at
                   FROM m_period_lock
                  WHERE status = 1
                    AND lock_status = "1"
                    AND period BETWEEN ? AND ?
                  ORDER BY period ASC',
                [$startDate, $endDate]
            );
        } catch (\Exception $e) {
            Log::warning('PeriodLockService::getLockedPeriods failed', ['error' => $e->getMessage()]);
            return [];
        }
    }
}